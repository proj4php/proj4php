<?php

namespace proj4php\projCode;

/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4JS from Mike Adair madairATdmsolutions.ca
 *                      and Richard Greenwood rich@greenwoodma$p->com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */
/*******************************************************************************
  NAME                            MOLLWEIDE

  PURPOSE:	Transforms input longitude and latitude to Easting and
  Northing for the MOllweide projection.  The
  longitude and latitude must be in radians.  The Easting
  and Northing values will be returned in meters.

  PROGRAMMER              DATE
  ----------              ----
  D. Steinwand, EROS      May, 1991;  Updated Sept, 1992; Updated Feb, 1993
  S. Nelson, EDC		Jun, 2993;	Made corrections in precision and
  number of iterations.

  ALGORITHM REFERENCES

  1.  Snyder, John P. and Voxland, Philip M., "An Album of Map Projections",
  U.S. Geological Survey Professional Paper 1453 , United State Government
  Printing Office, Washington D.C., 1989.

  2.  Snyder, John P., "Map Projections--A Working Manual", U.S. Geological
  Survey Professional Paper 1395 (Supersedes USGS Bulletin 1532), United
  State Government Printing Office, Washington D.C., 1987.
 ****************************************************************************** */

use proj4php\Proj4php;
use proj4php\Common;
use proj4php\Point;

#[\AllowDynamicProperties]
class Moll
{
    public $a;
    public $long0;
    public $x0;
    public $y0;

    /**
     * Initialize the Mollweide projection
     */
    public function init()
    {
        //no-op
    }

    /**
     * Forward equations
     * Mollweide forward equations--mapping lat,long to x,y
     */
    /**
     * s - sin(s), accurate also for small |s|, where the subtraction would lose most of the digits
     */
    private static function sMinusSinS($s)
    {
        if (abs($s) < 0.25) {
            $s2 = $s * $s;
            return $s * $s2 * (1 / 6 - $s2 * (1 / 120 - $s2 * (1 / 5040 - $s2 * (1 / 362880
                - $s2 * (1 / 39916800 - $s2 / 6227020800)))));
        }
        return $s - sin($s);
    }

    /**
     * Close to the poles. With theta = (PI - s) / 2 and chi the colatitude,
     * 2 * theta + sin(2 * theta) = PI * sin(lat) becomes s - sin(s) = 2 * PI * sin(chi / 2)^2,
     * which can be solved accurately where the Newton iteration on theta converges only linearly.
     */
    private static function polarS($chi)
    {
        $sh = sin(0.5 * $chi);
        $w = 2 * Common::PI * $sh * $sh;
        $s = pow(6 * $w, 1 / 3); // <= the root, as s - sin(s) <= s^3 / 6
        for ($i = 0; $i < 20; $i++) {
            $sh2 = sin(0.5 * $s);
            $d = 2 * $sh2 * $sh2; // 1 - cos(s)
            if ($d == 0) {
                break;
            }
            $ds = (self::sMinusSinS($s) - $w) / $d;
            $s -= $ds;
            if (abs($ds) <= 1e-12 * $s) { // quadratic convergence
                break;
            }
        }
        return $s;
    }

    public function forward($p)
    {
        $lon = $p->x;
        $lat = $p->y;

        $delta_lon = Common::adjust_lon($lon - $this->long0);
        if (abs($lat) > Common::PI / 2 - 0.5) {
            $s = self::polarS(Common::PI / 2 - abs($lat));
            // cos(theta) = sin(s / 2), sin(theta) = cos(s / 2)
            $p->x = M_SQRT2 * 2 / Common::PI * $this->a * $delta_lon * sin(0.5 * $s) + $this->x0;
            $p->y = ($lat < 0 ? -1 : 1) * M_SQRT2 * $this->a * cos(0.5 * $s) + $this->y0;
            return $p;
        }
        $theta = $lat;
        $con = Common::PI * sin($lat);

        // Iterate using the Newton-Raphson method to find theta

        for ($i = 0; true; ++$i) {
            $delta_theta = -($theta + sin($theta) - $con) / (1.0 + cos($theta));
            $theta += $delta_theta;

            if (abs($delta_theta) < Common::EPSLN) {
                break;
            }

            if ($i >= 50) {
                Proj4php::reportError("moll:Fwd:IterationError");
                //return(241);
            }
        }

        $theta /= 2.0;

        // If the latitude is 90 deg, force the x coordinate to be "0 . false easting"
        // this is done here because of precision problems with "cos(theta)"

        if (Common::PI / 2 - abs($lat) < Common::EPSLN) {
            $delta_lon = 0;
        }

        $x = M_SQRT2 * 2 / Common::PI * $this->a * $delta_lon * cos($theta) + $this->x0;
        $y = M_SQRT2 * $this->a * sin($theta) + $this->y0;

        $p->x = $x;
        $p->y = $y;

        return $p;
    }

    /**
     * Inverse equations
     * @param Point $p
     * @return Point 
     */
    public function inverse($p)
    {
        //$theta;
        //$arg;

        $p->x -= $this->x0;
        $p->y -= $this->y0;
        $arg = $p->y / (M_SQRT2 * $this->a);
        if (abs($arg) > 0.5 && abs($arg) <= 1) {
            // Close to the poles: cos(s / 2) = |arg|, with s as in polarS(), and the
            // colatitude chi from 2 * sin(chi / 2)^2 = (s - sin(s)) / PI
            $halfS = 2 * asin(sqrt(0.5 * (1 - abs($arg))));
            $den = M_SQRT2 * 2 / Common::PI * $this->a * sin($halfS);
            $dlon = $den == 0 ? 0 : $p->x / $den; // at the pole x is 0
            $chi = 2 * asin(sqrt(self::sMinusSinS(2 * $halfS) / (2 * Common::PI)));
            $p->x = Common::adjust_lon($this->long0 + max(-Common::PI, min(Common::PI, $dlon)));
            $p->y = ($arg < 0 ? -1 : 1) * (Common::PI / 2 - $chi);
            return $p;
        }

        // Because of division by zero problems, 'arg' can not be 1.0.  Therefore
        // a number very close to one is used instead.
        if (abs($arg) > 0.999999999999) {
            $arg = 0.999999999999;
        }

        $theta = asin($arg);
        $lon = Common::adjust_lon($this->long0 + ($p->x / (M_SQRT2 * 2 / Common::PI * $this->a * cos($theta))));

        if ($lon < (-Common::PI)) {
            $lon = -Common::PI;
        }

        if ($lon > Common::PI) {
            $lon = Common::PI;
        }

        $arg = (2.0 * $theta + sin(2.0 * $theta)) / Common::PI;

        if (abs($arg) > 1.0) {
            $arg = 1.0;
        }

        $lat = asin($arg);
        //return(OK);

        $p->x = $lon;
        $p->y = $lat;

        return $p;
    }
}
