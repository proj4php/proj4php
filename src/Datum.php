<?php
namespace proj4php;

/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4js from Mike Adair madairATdmsolutions.ca
 * and Richard Greenwood rich@greenwoodma$p->com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */

class Datum
{
    public $datum_code;
    public $datum_type;
    public $datum_params;

    /**
     *
     * @param type $proj 
     */
    public function __construct(Proj $proj)
    {
        // default setting
        $this->datum_type = Common::PJD_WGS84;

        if (isset($proj->datumCode))
            $this->datum_code = $proj->datumCode;

        if (isset($proj->datumCode) && $proj->datumCode == 'none') {
            $this->datum_type = Common::PJD_NODATUM;
        }

        if (isset($proj->datum_params)) {
            for ($i = 0; $i < sizeof($proj->datum_params); $i++) {
                // So instantiating a Datum object writes properties back to the
                // Proj class. That's a nasty side-effect! Every new Datum you create
                // will overwrite those properties.
                $proj->datum_params[$i] = floatval($proj->datum_params[$i]);
            }

            if ($proj->datum_params[0] != 0 || $proj->datum_params[1] != 0 || $proj->datum_params[2] != 0) {
                $this->datum_type = Common::PJD_3PARAM;
            }

            if (sizeof($proj->datum_params) > 3) {
                if ($proj->datum_params[3] != 0 || $proj->datum_params[4] != 0 ||
                    $proj->datum_params[5] != 0 || $proj->datum_params[6] != 0
                ) {
                    $this->datum_type = Common::PJD_7PARAM;

                    // The Datum messes around with more properties of the Proj directly - smells bad.
                    // What do these anonymous indexes of the datum_params even mean?
                    $proj->datum_params[3] *= Common::SEC_TO_RAD;
                    $proj->datum_params[4] *= Common::SEC_TO_RAD;
                    $proj->datum_params[5] *= Common::SEC_TO_RAD;
                    $proj->datum_params[6] = ($proj->datum_params[6] / 1000000.0) + 1.0;
                }
            }

            // After messing with the Proj datum_params, we copy them back here.
            $this->datum_params = $proj->datum_params;
        }

        if (isset($proj)) {
            // datum object also uses these values
            $this->a = $proj->a;
            $this->b = $proj->b;
            $this->es = $proj->es;
            $this->ep2 = $proj->ep2;
            // $this->datum_params = $proj->datum_params;
        }
    }

    /**
     * Why not call this class "equals()"? Compare functions tend to return more
     * than just a true/false. if ($datum1->equals($datum2)) ...
     *
     * @param type $dest
     * @return boolean Returns TRUE if the two datums match, otherwise FALSE.
     * @throws type
     */
    public function compare_datums(Datum $dest)
    {
        if ($this->datum_type != $dest->datum_type) {
            // Datums are not equal
            return false;
        } elseif ($this->a != $dest->a || abs($this->es - $dest->es) > 0.000000000050) {
            // The tolerence for es is to ensure that GRS80 and WGS84
            // are considered identical.
            return false;
        } elseif ($this->datum_type == Common::PJD_3PARAM) {
            return (
                $this->datum_params[0] == $dest->datum_params[0]
                && $this->datum_params[1] == $dest->datum_params[1]
                && $this->datum_params[2] == $dest->datum_params[2]
            );
        } elseif ($this->datum_type == Common::PJD_7PARAM) {
            return (
                $this->datum_params[0] == $dest->datum_params[0]
                && $this->datum_params[1] == $dest->datum_params[1]
                && $this->datum_params[2] == $dest->datum_params[2]
                && $this->datum_params[3] == $dest->datum_params[3]
                && $this->datum_params[4] == $dest->datum_params[4]
                && $this->datum_params[5] == $dest->datum_params[5]
                && $this->datum_params[6] == $dest->datum_params[6]
            );
        } elseif ($this->datum_type == Common::PJD_GRIDSHIFT ||
            $dest->datum_type == Common::PJD_GRIDSHIFT) {
            throw new Exception("ERROR: Grid shift transformations are not implemented.");
        }

        // Datums are equal.
        return true;
    }

    public function reportDebug()
    {
        if (isset($this->datum_code))
          Proj4php::reportDebug("Datum code=$this->datum_code\n");
        Proj4php::reportDebug('Datum type:'.$this->datum_type."\n");
        if (isset($this->a))
          Proj4php::reportDebug("a=$this->a\n");
        if (isset($this->b))
          Proj4php::reportDebug("b=$this->b\n");
        if (isset($this->es))
          Proj4php::reportDebug("es=$this->es\n");
        if (isset($this->es2))
          Proj4php::reportDebug("es2=$this->es2\n");
        if (isset($this->datum_params))
        {
          foreach($this->datum_params as $key=>$value)
             Proj4php::reportDebug("Param $key=$value\n");
        }
        else
        {
          Proj4php::reportDebug("no params\n");
        }
    }

    /*
     * The function Convert_Geodetic_To_Geocentric converts geodetic coordinates
     * (latitude, longitude, and height) to geocentric coordinates (X, Y, Z),
     * according to the current ellipsoid parameters.
     *
     *    Latitude  : Geodetic latitude in radians                     (input)
     *    Longitude : Geodetic longitude in radians                    (input)
     *    Height    : Geodetic height, in meters                       (input)
     *    X         : Calculated Geocentric X coordinate, in meters    (output)
     *    Y         : Calculated Geocentric Y coordinate, in meters    (output)
     *    Z         : Calculated Geocentric Z coordinate, in meters    (output)
     *
     */
    public function geodetic_to_geocentric($p)
    {
        Proj4php::reportDebug('geodetic_to_geocentric('.$p->x.','.$p->y.")\n");
        $this->reportDebug();

        $Longitude = $p->x;
        $Latitude = $p->y;
        // Z value not always supplied
        $Height = (isset($p->z) ? $p->z : 0);
        // GEOCENT_NO_ERROR;
        $Error_Code = 0;

        /*
         * * Don't blow up if Latitude is just a little out of the value
         * * range as it may just be a rounding issue.  Also removed longitude
         * * test, it should be wrapped by cos() and sin().  NFW for PROJ.4, Sep/2001.
         */

        if ($Latitude < -Common::HALF_PI && $Latitude > -1.001 * Common::HALF_PI) {
            $Latitude = -Common::HALF_PI;
        } elseif ($Latitude > Common::HALF_PI && $Latitude < 1.001 * Common::HALF_PI) {
            $Latitude = Common::HALF_PI;
        } elseif (($Latitude < -Common::HALF_PI) || ($Latitude > Common::HALF_PI)) {
            // Latitude out of range.
            Proj4php::reportError('geocent:lat out of range:' . $Latitude."\n");
            return null;
        }

        if ($Longitude > Common::PI) {
            $Longitude -= (2 * Common::PI);
        }

        // sin(Latitude)
        $Sin_Lat = sin($Latitude);

        // cos(Latitude)
        $Cos_Lat = cos($Latitude);

        // Square of sin(Latitude)
        $Sin2_Lat = $Sin_Lat * $Sin_Lat;

        // Earth radius at location
        $Rn = $this->a / (sqrt(1.0e0 - $this->es * $Sin2_Lat));

        $p->x = ($Rn + $Height) * $Cos_Lat * cos($Longitude);
        $p->y = ($Rn + $Height) * $Cos_Lat * sin($Longitude);
        $p->z = (($Rn * (1 - $this->es)) + $Height) * $Sin_Lat;

        return $Error_Code;
    }

    /**
     * FIXME: what is $p? It is some kind of object.
     * @param object $p
     * @return type 
     */
    public function geocentric_to_geodetic($p)
    {
        Proj4php::reportDebug('geocentric_to_geodetic('.$p->x.','.$p->y.")\n");
        $this->reportDebug();

        // local defintions and variables
        // end-criterium of loop, accuracy of sin(Latitude)

        $genau = 1.E-12;
        $genau2 = ($genau * $genau);
        $maxiter = 30;
        $X = $p->x;
        $Y = $p->y;

        // Z value not always supplied
        $Z = $p->z ? $p->z : 0.0;

        /*
        $P;        // distance between semi-minor axis and location 
        $RR;       // distance between center and location
        $CT;       // sin of geocentric latitude 
        $ST;       // cos of geocentric latitude 
        $RX;
        $RK;
        $RN;       // Earth radius at location 
        $CPHI0;    // cos of start or old geodetic latitude in iterations 
        $SPHI0;    // sin of start or old geodetic latitude in iterations 
        $CPHI;     // cos of searched geodetic latitude
        $SPHI;     // sin of searched geodetic latitude 
        $SDPHI;    // end-criterium: addition-theorem of sin(Latitude(iter)-Latitude(iter-1)) 
        $AtPole;     // indicates location is in polar region 
        $iter;        // of continous iteration, max. 30 is always enough (s.a.) 
        $Longitude;
        $Latitude;
        $Height;
        */

        $AtPole = false;
        $P = sqrt($X * $X + $Y * $Y);
        $RR = sqrt($X * $X + $Y * $Y + $Z * $Z);

        // Special cases for latitude and longitude.
        if ($P / $this->a < $genau) {
            // special case, if P=0. (X=0., Y=0.)
            $AtPole = true;
            $Longitude = 0.0;

            // If (X,Y,Z)=(0.,0.,0.) then Height becomes semi-minor axis
            // of ellipsoid (=center of mass), Latitude becomes PI/2
            if ($RR / $this->a < $genau) {
                $Latitude = Common::HALF_PI;
                $Height = -$this->b;
                return;
            }
        } else {
            // ellipsoidal (geodetic) longitude
            // interval: -PI < Longitude <= +PI
            $Longitude = atan2($Y, $X);
        }

        /* --------------------------------------------------------------
         * Following iterative algorithm was developped by
         * "Institut für Erdmessung", University of Hannover, July 1988.
         * Internet: www.ife.uni-hannover.de
         * Iterative computation of CPHI,SPHI and Height.
         * Iteration of CPHI and SPHI to 10**-12 radian res$p->
         * 2*10**-7 arcsec.
         * --------------------------------------------------------------
         */
        $CT = $Z / $RR;
        $ST = $P / $RR;
        $RX = 1.0 / sqrt(1.0 - $this->es * (2.0 - $this->es) * $ST * $ST);
        $CPHI0 = $ST * (1.0 - $this->es) * $RX;
        $SPHI0 = $CT * $RX;
        $iter = 0;

        // loop to find sin(Latitude) res$p-> Latitude
        // until |sin(Latitude(iter)-Latitude(iter-1))| < genau
        do {
            ++$iter;
            $RN = $this->a / sqrt(1.0 - $this->es * $SPHI0 * $SPHI0);

            /*  ellipsoidal (geodetic) height */
            $Height = $P * $CPHI0 + $Z * $SPHI0 - $RN * (1.0 - $this->es * $SPHI0 * $SPHI0);

            $RK = $this->es * $RN / ($RN + $Height);
            $RX = 1.0 / sqrt( 1.0 - $RK * (2.0 - $RK) * $ST * $ST);
            $CPHI = $ST * (1.0 - $RK) * $RX;
            $SPHI = $CT * $RX;
            $SDPHI = $SPHI * $CPHI0 - $CPHI * $SPHI0;
            $CPHI0 = $CPHI;
            $SPHI0 = $SPHI;
        } while ($SDPHI * $SDPHI > $genau2 && $iter < $maxiter);

        // ellipsoidal (geodetic) latitude
        $Latitude = atan($SPHI / abs($CPHI));

        $p->x = $Longitude;
        $p->y = $Latitude;
        $p->z = $Height;

        return $p;
    }

    /** 
     * Convert_Geocentric_To_Geodetic
     * The method used here is derived from 'An Improved Algorithm for
     * Geocentric to Geodetic Coordinate Conversion', by Ralph Toms, Feb 1996
     * 
     * @param object Point $p
     * @return object Point $p
     */
    public function geocentric_to_geodetic_noniter(Point $p)
    {
        /*
        $Longitude;
        $Latitude;
        $Height;

        $W;        // distance from Z axis 
        $W2;       // square of distance from Z axis 
        $T0;       // initial estimate of vertical component 
        $T1;       // corrected estimate of vertical component 
        $S0;       // initial estimate of horizontal component 
        $S1;       // corrected estimate of horizontal component
        $Sin_B0;   // sin(B0), B0 is estimate of Bowring aux variable 
        $Sin3_B0;  // cube of sin(B0) 
        $Cos_B0;   // cos(B0)
        $Sin_p1;   // sin(phi1), phi1 is estimated latitude 
        $Cos_p1;   // cos(phi1) 
        $Rn;       // Earth radius at location 
        $Sum;      // numerator of cos(phi1) 
        $AtPole;  // indicates location is in polar region 
        */

        // Cast from string to float.
        // Since we are accepting the Point class only, then we can already
        // guarantee we have floats. A simple list($x, $y $Z) = $p->toArray() will
        // give us our values.

        $X = floatval($p->x);
        $Y = floatval($p->y);
        $Z = floatval($p->z ? $p->z : 0);

        $AtPole = false;

        if ($X <> 0.0) {
            $Longitude = atan2($Y, $X);
        } else {
            if ($Y > 0) {
                $Longitude = Common::HALF_PI;
            } elseif (Y < 0) {
                $Longitude = -Common::HALF_PI;
            } else {
                $AtPole = true;
                $Longitude = 0.0;
                if ($Z > 0.0) { /* north pole */
                    $Latitude = Common::HALF_PI;
                } elseif (Z < 0.0) { /* south pole */
                    $Latitude = -Common::HALF_PI;
                } else { /* center of earth */
                    $Latitude = Common::HALF_PI;
                    $Height = -$this->b;
                    return;
                }
            }
        }

        $W2 = $X * $X + $Y * $Y;
        $W = sqrt($W2);
        $T0 = $Z * Common::AD_C;
        $S0 = sqrt($T0 * $T0 + $W2);
        $Sin_B0 = $T0 / $S0;
        $Cos_B0 = $W / $S0;
        $Sin3_B0 = $Sin_B0 * $Sin_B0 * $Sin_B0;
        $T1 = $Z + $this->b * $this->ep2 * $Sin3_B0;
        $Sum = $W - $this->a * $this->es * $Cos_B0 * $Cos_B0 * $Cos_B0;
        $S1 = sqrt($T1 * $T1 + $Sum * $Sum);
        $Sin_p1 = $T1 / $S1;
        $Cos_p1 = $Sum / $S1;
        $Rn = $this->a / sqrt( 1.0 - $this->es * $Sin_p1 * $Sin_p1);

        if ($Cos_p1 >= Common::COS_67P5) {
            $Height = $W / $Cos_p1 - $Rn;
        } elseif ($Cos_p1 <= -Common::COS_67P5) {
            $Height = $W / -$Cos_p1 - $Rn;
        } else {
            $Height = $Z / $Sin_p1 + $Rn * ($this->es - 1.0);
        }

        if ($AtPole == false) {
            $Latitude = atan($Sin_p1 / $Cos_p1);
        }

        $p->x = $Longitude;
        $p->y = $Latitude;
        $p->z = $Height;

        return $p;
    }

    /**
     * p = point to transform in geocentric coordinates (x,y,z)
     * Note: this will change the point by reference.
     */
    public function geocentric_to_wgs84(Point $p)
    {
        Proj4php::reportDebug('geocentric_to_wgs84('.$p->x.','.$p->y.")\n");

        if ($this->datum_type == Common::PJD_3PARAM) {
            Proj4php::reportDebug("+x=".$this->datum_params[0]."\n");
            Proj4php::reportDebug("+y=".$this->datum_params[1]."\n");
            Proj4php::reportDebug("+z=".$this->datum_params[2]."\n");
            $p->x += $this->datum_params[0];
            $p->y += $this->datum_params[1];
            $p->z += $this->datum_params[2];
        } elseif ($this->datum_type == Common::PJD_7PARAM) {
            Proj4php::reportDebug("Dx=".$this->datum_params[0]."\n");
            Proj4php::reportDebug("Dy=".$this->datum_params[1]."\n");
            Proj4php::reportDebug("Dz=".$this->datum_params[2]."\n");
            Proj4php::reportDebug("Rx=".$this->datum_params[3]."\n");
            Proj4php::reportDebug("Ry=".$this->datum_params[4]."\n");
            Proj4php::reportDebug("Rz=".$this->datum_params[5]."\n");
            Proj4php::reportDebug("M=".$this->datum_params[6]."\n"); 
            $Dx_BF = $this->datum_params[0];
            $Dy_BF = $this->datum_params[1];
            $Dz_BF = $this->datum_params[2];
            $Rx_BF = $this->datum_params[3];
            $Ry_BF = $this->datum_params[4];
            $Rz_BF = $this->datum_params[5];
            $M_BF = $this->datum_params[6];

            $p->x = $M_BF * ($p->x - $Rz_BF * $p->y + $Ry_BF * $p->z) + $Dx_BF;
            $p->y = $M_BF * ($Rz_BF * $p->x + $p->y - $Rx_BF * $p->z) + $Dy_BF;
            $p->z = $M_BF * (-$Ry_BF * $p->x + $Rx_BF * $p->y + $p->z) + $Dz_BF;
        }
    }

    /**
     *  coordinate system definition,
     *  point to transform in geocentric coordinates (x,y,z)
     * Note: this will change the point by reference.
     */
    public function geocentric_from_wgs84(Point $p)
    {
        Proj4php::reportDebug('geocentric_from_wgs84('.$p->x.','.$p->y.")\n");

        if ($this->datum_type == Common::PJD_3PARAM) {
            Proj4php::reportDebug("+x=".$this->datum_params[0]."\n");
            Proj4php::reportDebug("+y=".$this->datum_params[1]."\n");
            Proj4php::reportDebug("+z=".$this->datum_params[2]."\n");
            $p->x -= $this->datum_params[0];
            $p->y -= $this->datum_params[1];
            $p->z -= $this->datum_params[2];
        } elseif ($this->datum_type == Common::PJD_7PARAM) {
            Proj4php::reportDebug("Dx=".$this->datum_params[0]."\n");
            Proj4php::reportDebug("Dy=".$this->datum_params[1]."\n");
            Proj4php::reportDebug("Dz=".$this->datum_params[2]."\n");
            Proj4php::reportDebug("Rx=".$this->datum_params[3]."\n");
            Proj4php::reportDebug("Ry=".$this->datum_params[4]."\n");
            Proj4php::reportDebug("Rz=".$this->datum_params[5]."\n");
            Proj4php::reportDebug("M=".$this->datum_params[6]."\n");

            $Dx_BF = $this->datum_params[0];
            $Dy_BF = $this->datum_params[1];
            $Dz_BF = $this->datum_params[2];
            $Rx_BF = $this->datum_params[3];
            $Ry_BF = $this->datum_params[4];
            $Rz_BF = $this->datum_params[5];
            $M_BF = $this->datum_params[6];

            $x_tmp = ($p->x - $Dx_BF) / $M_BF;
            $y_tmp = ($p->y - $Dy_BF) / $M_BF;
            $z_tmp = ($p->z - $Dz_BF) / $M_BF;

            $p->x = $x_tmp + $Rz_BF * $y_tmp - $Ry_BF * $z_tmp;
            $p->y = -$Rz_BF * $x_tmp + $y_tmp + $Rx_BF * $z_tmp;
            $p->z = $Ry_BF * $x_tmp - $Rx_BF * $y_tmp + $z_tmp;
        }
    }
}
