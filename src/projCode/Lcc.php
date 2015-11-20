<?php
namespace proj4php\projCode;

/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4JS from Mike Adair madairATdmsolutions.ca
 * and Richard Greenwood rich@greenwoodma$p->com
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html
 */
/*******************************************************************************
  NAME LAMBERT CONFORMAL CONIC

  PURPOSE:	Transforms input longitude and latitude to Easting and
  Northing for the Lambert Conformal Conic projection.  The
  longitude and latitude must be in radians.  The Easting
  and Northing values will be returned in meters.


  ALGORITHM REFERENCES

  1.  Snyder, John P., "Map Projections--A Working Manual", U.S. Geological
  Survey Professional Paper 1395 (Supersedes USGS Bulletin 1532), United
  State Government Printing Office, Washington D.C., 1987.

  2.  Snyder, John P. and Voxland, Philip M., "An Album of Map Projections",
  U.S. Geological Survey Professional Paper 1453 , United State Government
 *******************************************************************************/

//<2104> +proj=lcc +lat_1=10.16666666666667 +lat_0=10.16666666666667 +lon_0=-71.60561777777777 +k_0=1 +x0=-17044 +x0=-23139.97 +ellps=intl +units=m +no_defs  no_defs
// Initialize the Lambert Conformal conic projection
// -----------------------------------------------------------------
//class Proj4phpProjlcc = Class.create();

use proj4php\Proj4php;
use proj4php\Common;
use proj4php\Point;

class Lcc
{
    // All public properties are referenced externally in simple tests.
    // The remaining protected properties may be accessed in other tests.

    public $lat0;
    // first standard parallel
    public $lat1;
    // second standard parallel
    public $lat2;
    public $k0;
    public $a;
    public $b;
    public $e;
    public $title;
    public $long0;
    public $x0;
    public $y0;

    protected $ns;
    protected $f0;
    protected $rh;

    public function init()
    {
        // array of:  r_maj,r_min,lat1,lat2,c_lon,c_lat,false_east,false_north
        //double c_lat;                   /* center latitude                      */
        //double c_lon;                   /* center longitude                     */
        //double lat1;                    /* first standard parallel              */
        //double lat2;                    /* second standard parallel             */
        //double r_maj;                   /* major axis                           */
        //double r_min;                   /* minor axis                           */
        //double false_east;              /* x offset in meters                   */
        //double false_north;             /* y offset in meters                   */

        // SR-ORG:123
        if ( ! isset($this->lat0)) {
            $this->lat0=0.0;
        }

        // If lat2 is not defined
        if ( ! isset($this->lat2)) {
            $this->lat2 = $this->lat0;
        }

        // SR-ORG:113
        if ( ! isset($this->lat1)) {
            $this->lat1 = $this->lat0;
        }

        if ( ! isset($this->k0)) {
            $this->k0 = 1.0;
        }

        // Standard Parallels cannot be equal and on opposite sides of the equator
        if (abs($this->lat1 + $this->lat2) < Common::EPSLN) {
            Proj4php::reportError("lcc:init: Equal Latitudes");
            return;
        }

        $temp = $this->b / $this->a;
        $this->e = sqrt(1.0 - $temp * $temp);

        $sin1 = sin($this->lat1);
        $cos1 = cos($this->lat1);
        $ms1 = Common::msfnz($this->e, $sin1, $cos1);
        $ts1 = Common::tsfnz($this->e, $this->lat1, $sin1);

        $sin2 = sin($this->lat2);
        $cos2 = cos($this->lat2);
        $ms2 = Common::msfnz($this->e, $sin2, $cos2);
        $ts2 = Common::tsfnz($this->e, $this->lat2, $sin2);

        $ts0 = Common::tsfnz($this->e, $this->lat0, sin($this->lat0));

        if (abs($this->lat1 - $this->lat2) > Common::EPSLN) {
            $this->ns = log($ms1 / $ms2) / log($ts1 / $ts2);
        } else {
            $this->ns = $sin1;
        }

        $this->f0 = $ms1 / ($this->ns * pow($ts1, $this->ns));
        $this->rh = $this->a * $this->f0 * pow($ts0, $this->ns);

        if ( ! isset($this->title)) {
            $this->title = 'Lambert Conformal Conic';
        }
    }

    // Lambert Conformal conic forward equations--mapping lat,long to x,y
    // -----------------------------------------------------------------
    // CHECKME: should this be a LongLat object? Why is "log" and "lat" being squeezed into a "point"?
    public function forward(Point $p)
    {
        list($lon, $lat) = $p->toArray();

        // convert to radians
        if ($lat <= 90.0 && $lat >= -90.0 && $lon <= 180.0 && $lon >= -180.0) {
            //lon = lon * Common::D2R;
            //lat = lat * Common::D2R;
        } else {
            Proj4php::reportError('lcc:forward: llInputOutOfRange: ' . $lon . ' : ' . $lat);
            return;
        }

        $con = abs(abs($lat) - Common::HALF_PI);

        if ($con > Common::EPSLN) {
            $ts = Common::tsfnz($this->e, $lat, sin($lat));
            $rh1 = $this->a * $this->f0 * pow($ts, $this->ns);
        } else {
            $con = $lat * $this->ns;

            if ($con <= 0) {
                Proj4php::reportError('lcc:forward: No Projection');
                return;
            }

            $rh1 = 0;
        }

        $theta = $this->ns * Common::adjust_lon($lon - $this->long0);
        $p->x = $this->k0 * ($rh1 * sin($theta)) + $this->x0;
        $p->y = $this->k0 * ($this->rh - $rh1 * cos($theta)) + $this->y0;

        return $p;
    }

    /**
     * Lambert Conformal Conic inverse equations--mapping x,y to lat/long
     * 
     * @param type $p
     * @return null 
     */
    public function inverse($p)
    {
        $x = ($p->x - $this->x0) / $this->k0;
        $y = ($this->rh - ($p->y - $this->y0) / $this->k0);

        if ($this->ns > 0) {
            $rh1 = sqrt($x * $x + $y * $y);
            $con = 1.0;
        } else {
            $rh1 = -sqrt($x * $x + $y * $y);
            $con = -1.0;
        }
        $theta = 0.0;

        if ($rh1 != 0) {
            $theta = atan2(($con * $x ), ($con * $y));
        }

        if (($rh1 != 0) || ($this->ns > 0.0)) {
            $con = 1.0 / $this->ns;
            $ts = pow(($rh1 / ($this->a * $this->f0)), $con);
            $lat = Common::phi2z($this->e, $ts);

            if ($lat == -9999) {
                return null;
            }
        } else {
            $lat = -Common::HALF_PI;
        }
        $lon = Common::adjust_lon($theta / $this->ns + $this->long0);

        $p->x = $lon;
        $p->y = $lat;

        return $p;
    }
}
