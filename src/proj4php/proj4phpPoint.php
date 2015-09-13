<?php
/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4js from Mike Adair madairATdmsolutions.ca
 *                      and Richard Greenwood rich@greenwoodmap.com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */
 
 class proj4phpPoint {
 
	var $x;
	var $y;
	var $z;
 
    /**
     * Constructor: Proj4js.Point
     *
     * Parameters:
     * - x {float} or {Array} either the first coordinates component or
     *     the full coordinates
     * - y {float} the second component
     * - z {float} the third component, optional.
     */
    function proj4phpPoint($x,$y,$z=null) {
      if (is_object($x)) {
        $this->x = $x[0];
        $this->y = $x[1];
        $this->z = (sizeof($x)>=3)?$x[2]:0.0;
      } else if (is_string($x) && !is_numeric($y)) {
        $coords = explode(',',$x);
        $this->x = floatval($coords[0]);
        $this->y = floatval($coords[1]);
        $this->z = (sizeof($coords)>=2)?floatval($coords[2]):0.0;
      } else {
        $this->x = $x;
        $this->y = $y;
        $this->z = ($z!=null)?$z:0.0;
      }
    }

    /**
     * APIMethod: clone
     * Build a copy of a Proj4js.Point object.
     *
	 * renamed because of PHP keyword.
	 * 
     * Return:
     * {Proj4js}.Point the cloned point.
     */
    function _clone() {
      return new Proj4phpPoint($this->x, $this->y, $this->z);
    }

    /**
     * APIMethod: toString
     * Return a readable string version of the point
     *
     * Return:
     * {String} String representation of Proj4js.Point object. 
     *           (ex. <i>"x=5,y=42"</i>)
     */
    function toString() {
        return "x=" . $this->x . ",y=" . $this->y;
    }

    /** 
     * APIMethod: toShortString
     * Return a short string version of the point.
     *
     * Return:
     * {String} Shortened String representation of Proj4js.Point object. 
     *         (ex. <i>"5, 42"</i>)
     */
    function toShortString() {
        return $this->x . ", " . $this->y;
    }
 
 }