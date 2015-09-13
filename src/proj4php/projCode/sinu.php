<?php
/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4php from Mike Adair madairATdmsolutions.ca
 *                      and Richard Greenwood rich@greenwoodma$p->com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */
 
 
 /*******************************************************************************
NAME                  		SINUSOIDAL

PURPOSE:	Transforms input longitude and latitude to Easting and
		Northing for the Sinusoidal projection.  The
		longitude and latitude must be in radians.  The Easting
		and Northing values will be returned in meters.

PROGRAMMER              DATE            
----------              ----           
D. Steinwand, EROS      May, 1991     

This function was adapted from the Sinusoidal projection code (FORTRAN) in the 
General Cartographic Transformation Package software which is available from 
the U.S. Geological Survey National Mapping Division.
 
ALGORITHM REFERENCES

1.  Snyder, John P., "Map Projections--A Working Manual", U.S. Geological
    Survey Professional Paper 1395 (Supersedes USGS Bulletin 1532), United
    State Government Printing Office, Washington D.C., 1987.

2.  "Software Documentation for GCTP General Cartographic Transformation
    Package", U.S. Geological Survey National Mapping Division, May 1982.
*******************************************************************************/

class Proj4phpProjSinu  extends Proj4phpProj  {

	/* Initialize the Sinusoidal projection
	  ------------------------------------*/
	function init() {
		/* Place parameters in storage for common use
		  -------------------------------------------------*/
		$this->R = 6370997.0; //Radius of earth
	}

	/* Sinusoidal forward equations--mapping lat,long to x,y
	-----------------------------------------------------*/
	function forward($p) {
		$x;$y;$delta_lon;	
		$lon=$p->x;
		$lat=$p->y;	
		/* Forward equations
		-----------------*/
		$delta_lon = $this->proj4php->common->adjust_lon($lon - $this->long0);
		$x = $this->R * delta_lon * cos($lat) + $this->x0;
		$y = $this->R * lat + $this->y0;

		$p->x=$x;
		$p->y=$y;	
		return $p;
	}

	function inverse($p) {
		$lat;$temp;$lon;	

		/* Inverse equations
		  -----------------*/
		$p->x -= $this->x0;
		$p->y -= $this->y0;
		$lat = $p->y / $this->R;
		if (abs($lat) > $this->proj4php->common->HALF_PI) {
		    Proj4php::reportError("sinu:Inv:DataError");
		}
		$temp = abs($lat) - $this->proj4php->common->HALF_PI;
		if (abs($temp) > $this->proj4php->common->EPSLN) {
			$temp = $this->long0+ $p->x / ($this->R *cos($lat));
			$lon = $this->proj4php->common->adjust_lon($temp);
		} else {
			$lon = $this->long0;
		}
		  
		$p->x=$lon;
		$p->y=$lat;
		return $p;
	}
};


$this->proj['sinu'] = new Proj4phpProjSinu('',$this);
