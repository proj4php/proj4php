<?php
/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4php from Mike Adair madairATdmsolutions.ca
 *                      and Richard Greenwood rich@greenwoodma$p->com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */
 
 /*******************************************************************************
NAME                             EQUIRECTANGULAR 

PURPOSE:	Transforms input longitude and latitude to Easting and
		Northing for the Equirectangular projection.  The
		longitude and latitude must be in radians.  The Easting
		and Northing values will be returned in meters.

PROGRAMMER              DATE
----------              ----
T. Mittan		Mar, 1993

ALGORITHM REFERENCES

1.  Snyder, John P., "Map Projections--A Working Manual", U.S. Geological
    Survey Professional Paper 1395 (Supersedes USGS Bulletin 1532), United
    State Government Printing Office, Washington D.C., 1987.

2.  Snyder, John P. and Voxland, Philip M., "An Album of Map Projections",
    U.S. Geological Survey Professional Paper 1453 , United State Government
    Printing Office, Washington D.C., 1989.
*******************************************************************************/
class Proj4phpProjEqui extends Proj4phpProj 
{
  function init() {
    if(!$this->x0) $this->x0=0;
    if(!$this->y0) $this->y0=0;
    if(!$this->lat0) $this->lat0=0;
    if(!$this->long0) $this->long0=0;
    ///$this->t2;
  }



/* Equirectangular forward equations--mapping lat,long to x,y
  ---------------------------------------------------------*/
  function forward($p) {

    $lon=$p->x;				
    $lat=$p->y;			

    $dlon = $this->proj4php->common->adjust_lon($lon - $this->long0);
    $x = $this->x0 +$this-> a * $dlon *cos($this->lat0);
    $y = $this->y0 + $this->a * $lat;

    $this->t1=$x;
    $this->t2=cos($this->lat0);
    $p->x=$x;
    $p->y=$y;
    return $p;
  }  //equiFwd()



/* Equirectangular inverse equations--mapping x,y to lat/long
  ---------------------------------------------------------*/
  function inverse($p) {

    $p->x -= $this->x0;
    $p->y -= $this->y0;
    $lat = $p->y /$this-> a;

    if ( abs($lat) > $this->proj4php->common->HALF_PI) {
        Proj4php::reportError("equi:Inv:DataError");
    }
    $lon = $this->proj4php->common->adjust_lon($this->long0 + $p->x / ($this->a * cos($this->lat0)));
    $p->x=$lon;
    $p->y=$lat;
  }//equiInv()
}

$this->proj['equi'] = new Proj4phpProjEqui('',$this);
