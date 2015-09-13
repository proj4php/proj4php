<?php
/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4js from Mike Adair madairATdmsolutions.ca
 *                      and Richard Greenwood rich@greenwoodmap.com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */

class Proj4phpCommon
{
	var $PI = 3.141592653589793238; //Math.PI,
    var $HALF_PI = 1.570796326794896619; //Math.PI*0.5,
    var $TWO_PI = 6.283185307179586477; //Math.PI*2,
    var $FORTPI = 0.78539816339744833;
    var $R2D = 57.29577951308232088;
    var $D2R = 0.01745329251994329577;
    var $SEC_TO_RAD = 4.84813681109535993589914102357e-6; /* SEC_TO_RAD = Pi/180/3600 */
    var $EPSLN = 1.0e-10;
    var $MAX_ITER = 20;
    // following constants from geocent.c
    var $COS_67P5 = 0.38268343236508977;  /* cosine of 67.5 degrees */
    var $AD_C = 1.0026000;                /* Toms region 1 constant */

  /* datum_type values */
    var $PJD_UNKNOWN  = 0;
    var $PJD_3PARAM   = 1;
    var $PJD_7PARAM   = 2;
    var $PJD_GRIDSHIFT= 3;
    var $PJD_WGS84    = 4;   // WGS84 or equivalent
    var $PJD_NODATUM  = 5;   // WGS84 or equivalent
    var $SRS_WGS84_SEMIMAJOR = 6378137.0;  // only used in grid shift transforms

  // ellipoid pj_set_ell.c
    var $SIXTH = .1666666666666666667; /* 1/6 */
    var $RA4   = .04722222222222222222; /* 17/360 */
    var $RA6   = .02215608465608465608; /* 67/3024 */
    var $RV4   = .06944444444444444444; /* 5/72 */
    var $RV6   = .04243827160493827160; /* 55/1296 */

// Function to compute the constant small m which is the radius of
//   a parallel of latitude, phi, divided by the semimajor axis.
// -----------------------------------------------------------------
  function msfnz($eccent, $sinphi, $cosphi) {
      $con = $eccent * $sinphi;
      return $cosphi/(sqrt(1.0 - $con * $con));
  }

// Function to compute the constant small t for use in the forward
//   computations in the Lambert Conformal Conic and the Polar
//   Stereographic projections.
// -----------------------------------------------------------------
  function tsfnz($eccent, $phi, $sinphi) {
    $con = $eccent * $sinphi;
    $com = 0.5 * $eccent;
    $con = pow(((1.0 - $con) / (1.0 + $con)), $com);
    return (tan(.5 * ($this->HALF_PI - $phi))/$con);
  }

/** Function to compute the latitude angle, phi2, for the inverse of the
//   Lambert Conformal Conic and Polar Stereographic projections.
//
// rise up an assertion if there is no convergence.
// ----------------------------------------------------------------
*/
  function phi2z($eccent, $ts) {
    $eccnth = .5 * $eccent;
    $phi = $this->HALF_PI - 2 * atan($ts);
    for ($i = 0; $i <= 15; $i++) {
      $con = $eccent * sin($phi);
      $dphi = $this->HALF_PI - 2 * atan($ts *(pow(((1.0 - $con)/(1.0 + $con)),$eccnth))) - $phi;
      $phi += $dphi;
      if (abs($dphi) <= .0000000001) return $phi;
    }
	assert("false; /* phi2z has NoConvergence */");
    return (-9999);
  }

/* Function to compute constant small q which is the radius of a 
   parallel of latitude, phi, divided by the semimajor axis. 
------------------------------------------------------------*/
  function qsfnz($eccent,$sinphi) {
    if ($eccent > 1.0e-7) {
      $con = $eccent * $sinphi;
      return (( 1.0- $eccent * $eccent) * ($sinphi /(1.0 - $con * $con) - (.5/$eccent)*log((1.0 - $con)/(1.0 + $con))));
    } else {
      return(2.0 * $sinphi);
    }
  }

/* Function to eliminate roundoff errors in asin
----------------------------------------------*/
  function asinz($x) {
    if (abs($x)>1.0) {
      $x=($x>1.0)?1.0:-1.0;
    }
    return asin($x);
  }

// following functions from gctpc cproj.c for transverse mercator projections
  function e0fn($x) {return(1.0-0.25*$x*(1.0+$x/16.0*(3.0+1.25*$x)));}
  function e1fn($x) {return(0.375*$x*(1.0+0.25*$x*(1.0+0.46875*$x)));}
  function e2fn($x) {return(0.05859375*$x*$x*(1.0+0.75*$x));}
  function e3fn($x) {return($x*$x*$x*(35.0/3072.0));}
  function mlfn($e0,$e1,$e2,$e3,$phi) {return($e0*$phi-$e1*sin(2.0*$phi)+$e2*sin(4.0*$phi)-$e3*sin(6.0*$phi));}

  function srat($esinp, $exp) {
    return(pow((1.0-$esinp)/(1.0+$esinp), $exp));
  }

// Function to return the sign of an argument
  function sign($x) { if ($x < 0.0) return(-1); else return(1);}

// Function to adjust longitude to -180 to 180; input in radians
  function adjust_lon($x) {
    $x = (abs($x) < $this->PI) ? $x: ($x - ($this->sign($x)*$this->TWO_PI) );
    return $x;
  }
  
// IGNF - DGR : algorithms used by IGN France

// Function to adjust latitude to -90 to 90; input in radians
  function adjust_lat($x) {
    $x= (abs($x) < $this->HALF_PI) ? $x: ($x - ($this->sign($x)*$this->PI) );
    return $x;
  }

// Latitude Isometrique - close to tsfnz ...
  function latiso($eccent, $phi, $sinphi) {
    if ($abs($phi) > $this->HALF_PI) return +NaN;
    if ($phi==$this->HALF_PI) return INF;
    if ($phi==-1.0*$this->HALF_PI) return -1.0*INF;

    $con= $eccent*$sinphi;
    return log(tan(($this->HALF_PI+$phi)/2.0))+$eccent*log((1.0-$con)/(1.0+$con))/2.0;
  }

  function fL($x,$L) {
    return 2.0*atan($x*exp($L)) - $this->HALF_PI;
  }

// Inverse Latitude Isometrique - close to ph2z
  function invlatiso($eccent, $ts) {
    $phi= $this->fL(1.0,$ts);
    $Iphi= 0.0;
    $con= 0.0;
    do {
      $Iphi= $phi;
      $con= $eccent*sin($Iphi);
      $phi= $this->fL(exp($eccent*log((1.0+$con)/(1.0-$con))/2.0),$ts);
    } while (abs($phi-$Iphi)>1.0e-12);
    return $phi;
  }

// Grande Normale
  function gN($a,$e,$sinphi)
  {
    $temp= $e*$sinphi;
    return $a/sqrt(1.0 - $temp*$temp);
  }
}