<?php
namespace proj4php\projCode;

/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4JS from Mike Adair madairATdmsolutions.ca
 * and Richard Greenwood rich@greenwoodma$p->com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */

use proj4php\Proj4php;
use proj4php\Common;

class Gauss
{
    /**
     * 
     */
    public function init()
    {
        $sphi = sin($this->lat0);
        $cphi = cos($this->lat0);
        $cphi *= $cphi;
        $this->rc = sqrt(1.0 - $this->es) / (1.0 - $this->es * $sphi * $sphi);
        $this->C = sqrt(1.0 + $this->es * $cphi * $cphi / (1.0 - $this->es));
        $this->phic0 = asin($sphi / $this->C);
        $this->ratexp = 0.5 * $this->C * $this->e;
        $this->K = tan(0.5 * $this->phic0 + Common::FORTPI)
            / (
                pow(tan(0.5 * $this->lat0 + Common::FORTPI), $this->C)
                * Common::srat($this->e * $sphi, $this->ratexp)
            );
    }

    /**
     * @param type $p
     * @return type 
     */
    public function forward($p)
    {
        $lon = $p->x;
        $lat = $p->y;

        $p->y = 2.0 * atan($this->K * pow(tan(0.5 * $lat + Common::FORTPI), $this->C) * Common::srat($this->e * sin($lat), $this->ratexp)) - Common::HALF_PI;
        $p->x = $this->C * $lon;

        return $p;
    }

    /**
     * @param type $p
     * @return null 
     */
    public function inverse($p)
    {
        $DEL_TOL = 1e-14;
        $lon = $p->x / $this->C;
        $lat = $p->y;
        $num = pow(tan(0.5 * $lat + Common::FORTPI) / $this->K, 1.0 / $this->C);

        for ($i = Common::MAX_ITER; $i > 0; --$i) {
            $lat = 2.0 * atan( $num * Common::srat($this->e * sin($p->y), -0.5 * $this->e)) - Common::HALF_PI;

            if (abs($lat - $p->y) < $DEL_TOL) {
                break;
            }

            $p->y = $lat;
        }

        // convergence failed
        if ( ! $i) {
            Proj4php::reportError("gauss:inverse:convergence failed");
            return null;
        }

        $p->x = $lon;
        $p->y = $lat;

        return $p;
    }
}
