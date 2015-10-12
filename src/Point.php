<?php
namespace proj4php;

/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4js from Mike Adair madairATdmsolutions.ca
 * and Richard Greenwood rich@greenwoodmap.com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */

/** 
 * Point object, nothing fancy, just allows values to be
 * passed back and forth by reference rather than by value.
 * Other point classes may be used as long as they have
 * x and y properties, which will get modified in the transform method.
*/

use Proj4php\Proj;
use InvalidArgumentException;

class Point
{
    protected $x;
    protected $y;
    protected $z;

    protected $projection = null;

    public function getProjection() {
        return $this->projection;
    }

    public function setProjection($projection) {
        $this->projection = $projection;
    }

    /**
     * Constructor: Proj4js.Point
     *
     * Parameters:
     * - x {float} or {Array} either the first coordinates component or
     *     the full coordinates
     * - y {float} the second component
     * - z {float} the third component, optional.
     * - projection {Proj} the point projection, optional.
     *
     * Notice z can be ommitted when projection still present.
     */
    public function __construct($x = null, $y = null, $z = null, Proj $projection = null)
    {
        if ($projection===null and $z instanceof Proj)
        {
          $projection = $z;
          $z = null;
        }
        $this->projection = $projection;
        if (is_array($x)) {
            // [x, y] or [x, y, z]
            $this->__set('x', $x[0]);
            $this->__set('y', $x[1]);
            $this->__set('z', isset($x[2]) ? $x[2] : null);
        } elseif (is_string($x) && is_null($y)) {
            // "x y" or "x y z"
            $coord = explode(' ', $x);
            $this->__set('x', $coord[0]);
            $this->__set('y', $coord[1]);
            $this->__set('z', isset($coord[2]) ? $coord[2] : null);
        } else {
            // Separate x, y, z
            $this->__set('x', $x);
            $this->__set('y', $y);
            $this->__set('z', $z);
        }
    }

    /**
     * APIMethod: clone
     * Build a copy of a Point object.
     *
     * renamed because of PHP keyword.
     * 
     * Return:
     * proj4php\Point the cloned point.
     */
    public function __clone()
    {
        return new static($this->x, $this->y, $this->z);
    }

    /**
     * APIMethod: toString
     * Return a readable string version of the point
     *
     * Return:
     * {String} String representation of Proj4js.Point object. 
     * (ex. "x=5,y=42")
     */
    public function toString()
    {
        return 'x=' . $this->x . ',y=' . $this->y;
    }

    /**
     * APIMethod: toShortString
     * Return a short string version of the point.
     *
     * Return:
     * {String} Shortened String representation of Proj4js.Point object. 
     * (ex. "5, 42")
     * FIXME: actually "4 42" - a single space as separator, not commas.
     */
    public function toShortString()
    {
        return $this->x . ' ' . $this->y;
    }

    /**
     * Getter for x, y and z.
     */
    public function __get($name)
    {
        $name = strtolower($name);

        if ($name != 'x' && $name != 'y' && $name != 'z') {
            // Invalid property exception.
            throw new InvalidArgumentException(sprintf('Invalid property "%s"; expects x, y or z.', $name));
        }

        return $this->$name;
    }

    /**
     * Setter for x, y and z.
     */
    public function __set($name, $value)
    {
        $name = strtolower($name);

        if ($name != 'x' && $name != 'y' && $name != 'z') {
            // Invalid property exception.
            throw new InvalidArgumentException(sprintf('Invalid property "%s"; expects x, y or z.', $name));
        }

        $this->$name = (isset($value) ? (float)$value : 0.0);
    }

    /**
     * Setter for x, y and z.
     */
    public function __isset($name)
    {
        $name = strtolower($name);

        if ($name != 'x' && $name != 'y' && $name != 'z') {
            return false;
        }

        return isset($this->$name);
    }

    /**
     * Return as an [x, y, z] array.
     */
    public function toArray()
    {
        return [$this->x, $this->y, $this->z];
    }
}
