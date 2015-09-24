<?php
namespace proj4php;

/**
 * Author : Julien Moquet
 * 
 * Simple conversion from javascript to PHP of Proj4php by Mike Adair madairATdmsolutions.ca and Richard Greenwood rich@greenwoodmap.com 
 *
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */

use Exception;

class Proj4php
{
    protected $defaultDatum = 'WGS84';

    // FIXME: (https://github.com/proj4php/proj4php/issues/4)
    // Making these static means they are global, effectively shared between
    // all instantiations of this class. These properties can only be accessed
    // statically (i.e. not through an instamtiation) but will only be set once
    // this class has been instantiated once. That's just all wrong. These should
    // not be static.

    public static $ellipsoid = [];
    protected $datums = [];
    protected $defs = [];
    public static $wktProjections = [];
    public static $primeMeridian = [];
    public static $proj = [];

    // Default projection always created on instantiation.
    // Used as a fallback when projections cannot be created.
    public $WGS84 = null;

    public $msg = '';

    /**
     * Property: defsLookupService
     * service to retreive projection definition parameters from
     */
    public static $defsLookupService = 'http://spatialreference.org/ref';

    /**
     * Proj4php.defs is a collection of coordinate system definition objects in the
     * PROJ.4 command line format.
     * Generally a def is added by means of a separate .js file for example:
     *
     * <SCRIPT type="text/javascript" src="defs/EPSG26912.js"></SCRIPT>
     *
     * def is a CS definition in PROJ.4 WKT format, for example:
     * +proj="tmerc"   //longlat, etc.
     * +a=majorRadius
     * +b=minorRadius
     * +lat0=somenumber
     * +long=somenumber
     */
    protected function initDefs()
    {
        // These are so widely used, we'll go ahead and throw them in
        // without loading a separate file.

        $default_defs = [
            'WGS84' => "+title=long/lat:WGS84 +proj=longlat +ellps=WGS84 +datum=WGS84 +units=degrees",
            'EPSG:4326' => "+title=long/lat:WGS84 +proj=longlat +a=6378137.0 +b=6356752.31424518 +ellps=WGS84 +datum=WGS84 +units=degrees",
            'EPSG:4269' => "+title=long/lat:NAD83 +proj=longlat +a=6378137.0 +b=6356752.31414036 +ellps=GRS80 +datum=NAD83 +units=degrees",
            'EPSG:3875' => "+title= Google Mercator +proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m +nadgrids=@null +no_defs",
        ];

        // Some defs are aliases.
        $default_defs['EPSG:3785'] = $default_defs['EPSG:3875'];
        $default_defs['GOOGLE'] = $default_defs['EPSG:3875'];
        $default_defs['EPSG:900913'] = $default_defs['EPSG:3875'];
        $default_defs['EPSG:102113'] = $default_defs['EPSG:3875'];

        // Load them through the API so we have a single point of validation.
        foreach($default_defs as $key => $data) {
            $this->addDef($key, $data);
        }
    }

    /**
     * Tells us if a def has been loaded.
     * @returns bool
     */
    public function hasDef($key)
    {
        return array_key_exists($key, $this->defs);
    }

    /**
     * Returns a def source data.
     * Returns an empty string if a def key is not found.
     * @returns string
     */
    public function getDef($key)
    {
        return $this->hasDef($key) ? $this->defs[$key] : '';
    }

    /**
     * Adda new def, overwriting if the key already exists.
     * @returns void
     */
    public function addDef($key, $data)
    {
        $this->defs[$key] = $data;
    }

    // lookup table to go from the projection name in WKT to the Proj4php projection name
    // build this out as required
    protected function initWKTProjections()
    {
        self::$wktProjections["Lambert Tangential Conformal Conic Projection"] = "lcc";
        self::$wktProjections["Mercator"] = "merc";
        self::$wktProjections["Mercator_1SP"] = "merc";
        self::$wktProjections["Transverse_Mercator"] = "tmerc";
        self::$wktProjections["Transverse Mercator"] = "tmerc";
        self::$wktProjections["Lambert Azimuthal Equal Area"] = "laea";
        self::$wktProjections["Universal Transverse Mercator System"] = "utm";
    }

    protected function initDatum()
    {
        $default_datums = [
            "WGS84" => [
                'towgs84' => "0,0,0",
                'ellipse' => "WGS84",
                'name' => "WGS84"
            ],
            "GGRS87" => [
                'towgs84' => "-199.87,74.79,246.62",
                'ellipse' => "GRS80",
                'name' => "Greek_Geodetic_Reference_System_1987"
            ],
            "NAD83" => [
                'towgs84' => "0,0,0",
                'ellipse' => "GRS80",
                'name' => "North_American_Datum_1983"
            ],
            "NAD27" => [
                'nadgrids' => "@conus,@alaska,@ntv2_0.gsb,@ntv1_can.dat",
                'ellipse' => "clrk66",
                'name' => "North_American_Datum_1927"
            ],
            "potsdam" => [
                'towgs84' => "606.0,23.0,413.0",
                'ellipse' => "bessel",
                'name' => "Potsdam Rauenberg 1950 DHDN"
            ],
            "carthage" => [
                'towgs84' => "-263.0,6.0,431.0",
                'ellipse' => "clark80",
                'name' => "Carthage 1934 Tunisia"
            ],
            "hermannskogel" => [
                'towgs84' => "653.0,-212.0,449.0",
                'ellipse' => "bessel",
                'name' => "Hermannskogel"
            ],
            "ire65" => [
                'towgs84' => "482.530,-130.596,564.557,-1.042,-0.214,-0.631,8.15",
                'ellipse' => "mod_airy",
                'name' => "Ireland 1965"
            ],
            "nzgd49" => [
                'towgs84' => "59.47,-5.04,187.44,0.47,-0.1,1.024,-4.5993",
                'ellipse' => "intl",
                'name' => "New Zealand Geodetic Datum 1949"
            ],
            "OSGB36" => [
                'towgs84' => "446.448,-125.157,542.060,0.1502,0.2470,0.8421,-20.4894",
                'ellipse' => "airy",
                'name' => "Airy 1830"
            ],
        ];

        // Load them through the API so we have a single point of validation.
        foreach($default_datums as $key => $data) {
            $this->addDatum($key, $data);
        }
    }

    /**
     * Tells us if a datum has been loaded.
     * @returns bool
     */
    public function hasDatum($key)
    {
        return array_key_exists($key, $this->datums);
    }

    /**
     * Returns a datum source data.
     * Returns an empty arry if a datum key is not found.
     * @returns array
     */
    public function getDatum($key)
    {
        return $this->hasDatum($key) ? $this->datums[$key] : [];
    }

    /**
     * Adda new datum, overwriting if the key already exists.
     * @returns void
     */
    public function addDatum($key, $data)
    {
        $this->datums[$key] = $data;
    }

    protected function initEllipsoid()
    {
        self::$ellipsoid["MERIT"] = ['a' => 6378137.0, 'rf' => 298.257, 'name' => "MERIT 1983"];
        self::$ellipsoid["SGS85"] = ['a' => 6378136.0, 'rf' => 298.257, 'name' => "Soviet Geodetic System 85"];
        self::$ellipsoid["GRS80"] = ['a' => 6378137.0, 'rf' => 298.257222101, 'name' => "GRS 1980(IUGG, 1980)"];
        self::$ellipsoid["IAU76"] = ['a' => 6378140.0, 'rf' => 298.257, 'name' => "IAU 1976"];
        self::$ellipsoid["airy"] = ['a' => 6377563.396, 'b' => 6356256.910, 'name' => "Airy 1830"];
        self::$ellipsoid["APL4."] = ['a' => 6378137, 'rf' => 298.25, 'name' => "Appl. Physics. 1965"];
        self::$ellipsoid["NWL9D"] = ['a' => 6378145.0, 'rf' => 298.25, 'name' => "Naval Weapons Lab., 1965"];
        self::$ellipsoid["mod_airy"] = ['a' => 6377340.189, 'b' => 6356034.446, 'name' => "Modified Airy"];
        self::$ellipsoid["andrae"] = ['a' => 6377104.43, 'rf' => 300.0, 'name' => "Andrae 1876 (Den., Iclnd.)"];
        self::$ellipsoid["aust_SA"] = ['a' => 6378160.0, 'rf' => 298.25, 'name' => "Australian Natl & S. Amer. 1969"];
        self::$ellipsoid["GRS67"] = ['a' => 6378160.0, 'rf' => 298.2471674270, 'name' => "GRS 67(IUGG 1967)"];
        self::$ellipsoid["bessel"] = ['a' => 6377397.155, 'rf' => 299.1528128, 'name' => "Bessel 1841"];
        self::$ellipsoid["bess_nam"] = ['a' => 6377483.865, 'rf' => 299.1528128, 'name' => "Bessel 1841 (Namibia)"];
        self::$ellipsoid["clrk66"] = ['a' => 6378206.4, 'b' => 6356583.8, 'name' => "Clarke 1866"];
        self::$ellipsoid["clrk80"] = ['a' => 6378249.145, 'rf' => 293.4663, 'name' => "Clarke 1880 mod."];
        self::$ellipsoid["CPM"] = ['a' => 6375738.7, 'rf' => 334.29, 'name' => "Comm. des Poids et Mesures 1799"];
        self::$ellipsoid["delmbr"] = ['a' => 6376428.0, 'rf' => 311.5, 'name' => "Delambre 1810 (Belgium)"];
        self::$ellipsoid["engelis"] = ['a' => 6378136.05, 'rf' => 298.2566, 'name' => "Engelis 1985"];
        self::$ellipsoid["evrst30"] = ['a' => 6377276.345, 'rf' => 300.8017, 'name' => "Everest 1830"];
        self::$ellipsoid["evrst48"] = ['a' => 6377304.063, 'rf' => 300.8017, 'name' => "Everest 1948"];
        self::$ellipsoid["evrst56"] = ['a' => 6377301.243, 'rf' => 300.8017, 'name' => "Everest 1956"];
        self::$ellipsoid["evrst69"] = ['a' => 6377295.664, 'rf' => 300.8017, 'name' => "Everest 1969"];
        self::$ellipsoid["evrstSS"] = ['a' => 6377298.556, 'rf' => 300.8017, 'name' => "Everest (Sabah & Sarawak)"];
        self::$ellipsoid["fschr60"] = ['a' => 6378166.0, 'rf' => 298.3, 'name' => "Fischer (Mercury Datum) 1960"];
        self::$ellipsoid["fschr60m"] = ['a' => 6378155.0, 'rf' => 298.3, 'name' => "Fischer 1960"];
        self::$ellipsoid["fschr68"] = ['a' => 6378150.0, 'rf' => 298.3, 'name' => "Fischer 1968"];
        self::$ellipsoid["helmert"] = ['a' => 6378200.0, 'rf' => 298.3, 'name' => "Helmert 1906"];
        self::$ellipsoid["hough"] = ['a' => 6378270.0, 'rf' => 297.0, 'name' => "Hough"];
        self::$ellipsoid["intl"] = ['a' => 6378388.0, 'rf' => 297.0, 'name' => "International 1909 (Hayford)"];
        self::$ellipsoid["kaula"] = ['a' => 6378163.0, 'rf' => 298.24, 'name' => "Kaula 1961"];
        self::$ellipsoid["lerch"] = ['a' => 6378139.0, 'rf' => 298.257, 'name' => "Lerch 1979"];
        self::$ellipsoid["mprts"] = ['a' => 6397300.0, 'rf' => 191.0, 'name' => "Maupertius 1738"];
        self::$ellipsoid["new_intl"] = ['a' => 6378157.5, 'b' => 6356772.2, 'name' => "New International 1967"];
        self::$ellipsoid["plessis"] = ['a' => 6376523.0, 'rf' => 6355863.0, 'name' => "Plessis 1817 (France)"];
        self::$ellipsoid["krass"] = ['a' => 6378245.0, 'rf' => 298.3, 'name' => "Krassovsky, 1942"];
        self::$ellipsoid["SEasia"] = ['a' => 6378155.0, 'b' => 6356773.3205, 'name' => "Southeast Asia"];
        self::$ellipsoid["walbeck"] = ['a' => 6376896.0, 'b' => 6355834.8467, 'name' => "Walbeck"];
        self::$ellipsoid["WGS60"] = ['a' => 6378165.0, 'rf' => 298.3, 'name' => "WGS 60"];
        self::$ellipsoid["WGS66"] = ['a' => 6378145.0, 'rf' => 298.25, 'name' => "WGS 66"];
        self::$ellipsoid["WGS72"] = ['a' => 6378135.0, 'rf' => 298.26, 'name' => "WGS 72"];
        self::$ellipsoid["WGS84"] = ['a' => 6378137.0, 'rf' => 298.257223563, 'name' => "WGS 84"];
        self::$ellipsoid["sphere"] = ['a' => 6370997.0, 'b' => 6370997.0, 'name' => "Normal Sphere (r=6370997)"];
    }

    protected function initPrimeMeridian()
    {
        self::$primeMeridian["greenwich"] = '0.0';               //"0dE",
        self::$primeMeridian["lisbon"] = -9.131906111111;   //"9d07'54.862\"W",
        self::$primeMeridian["paris"] = 2.337229166667;   //"2d20'14.025\"E",
        self::$primeMeridian["bogota"] = -74.080916666667;  //"74d04'51.3\"W",
        self::$primeMeridian["madrid"] = -3.687938888889;  //"3d41'16.58\"W",
        self::$primeMeridian["rome"] = 12.452333333333;  //"12d27'8.4\"E",
        self::$primeMeridian["bern"] = 7.439583333333;  //"7d26'22.5\"E",
        self::$primeMeridian["jakarta"] = 106.807719444444;  //"106d48'27.79\"E",
        self::$primeMeridian["ferro"] = -17.666666666667;  //"17d40'W",
        self::$primeMeridian["brussels"] = 4.367975;        //"4d22'4.71\"E",
        self::$primeMeridian["stockholm"] = 18.058277777778;  //"18d3'29.8\"E",
        self::$primeMeridian["athens"] = 23.7163375;       //"23d42'58.815\"E",
        self::$primeMeridian["oslo"] = 10.722916666667;  //"10d43'22.5\"E"
    }

    /**
     *
     */
    public function __construct()
    {
        $this->initWKTProjections();
        $this->initDefs();
        $this->initDatum();
        $this->initEllipsoid();
        $this->initPrimeMeridian();

        self::$proj['longlat'] = new LongLat();
        self::$proj['identity'] = new LongLat();

        // Create a default projection. It's not clear why.
        $this->WGS84 = new Proj('WGS84', $this);
    }

    /**
     * Method: transform(source, dest, point)
     * Transform a point coordinate from one map projection to another.  This is
     * really the only public method you should need to use.
     *
     * Parameters:
     * source - {Proj4phpProj} source map projection for the transformation
     * dest - {Proj4phpProj} destination map projection for the transformation
     * point - {Object} point to transform, may be geodetic (long, lat) or
     *     projected Cartesian (x,y), but should always have x,y properties.
     */
    public function transform(Proj $source, Proj $dest, Point $point)
    {
        $this->msg = '';

        if ( ! $source->readyToUse) {
            self::reportError("Proj4php initialization for: " . $source->srsCode . " not yet complete");
            return $point;
        }

        if ( ! $dest->readyToUse) {
            self::reportError("Proj4php initialization for: " . $dest->srsCode . " not yet complete");
            return $point;
        }

        // DGR, 2010/11/12

        if ($source->axis != "enu") {
            $this->adjust_axis($source, false, $point);
        }

        // Transform source points to long/lat, if they aren't already.
        if ($source->projName == "longlat") {
            // convert degrees to radians
            $point->x *= Common::D2R;
            $point->y *= Common::D2R;
        } else {
            if (isset($source->to_meter)) {
                $point->x *= $source->to_meter;
                $point->y *= $source->to_meter;
            }

            // Convert Cartesian to longlat
            $source->inverse($point);
        }

        // Adjust for the prime meridian if necessary
        if (isset($source->from_greenwich)) {
            $point->x += $source->from_greenwich;
        }

        // Convert datums if needed, and if possible.
        $point = $this->datum_transform($source->datum, $dest->datum, $point);

        // Adjust for the prime meridian if necessary
        if (isset($dest->from_greenwich)) {
            $point->x -= $dest->from_greenwich;
        }

        if ($dest->projName == "longlat") {
            // convert radians to decimal degrees
            $point->x *= Common::R2D;
            $point->y *= Common::R2D;
        } else {
            // else project
            $dest->forward($point);
            if (isset($dest->to_meter)) {
                $point->x /= $dest->to_meter;
                $point->y /= $dest->to_meter;
            }
        }

        // DGR, 2010/11/12
        if ($dest->axis != "enu") {
            $this->adjust_axis($dest, true, $point);
        }

        // Nov 2014 - changed Werner Schäffer
        // clone point to avoid a lot of problems
        return (clone $point);
    }

    /** datum_transform()
      source coordinate system definition,
      destination coordinate system definition,
      point to transform in geodetic coordinates (long, lat, height)
     */
    public function datum_transform($source, $dest, $point)
    {
        // Short cut if the datums are identical.
        if ($source->compare_datums($dest)) {
            return $point; // in this case, zero is sucess,
            // whereas cs_compare_datums returns 1 to indicate TRUE
            // confusing, should fix this
        }

        // Explicitly skip datum transform by setting 'datum=none' as parameter for either source or dest
        if ($source->datum_type == Common::PJD_NODATUM
            || $dest->datum_type == Common::PJD_NODATUM
        ) {
            return $point;
        }

        /*
        // If this datum requires grid shifts, then apply it to geodetic coordinates.
        if ($source->datum_type == Common::PJD_GRIDSHIFT ) {
            throw(new Exception( "ERROR: Grid shift transformations are not implemented yet." ));
        }

        if ($dest->datum_type == Common::PJD_GRIDSHIFT ) {
            throw(new Exception( "ERROR: Grid shift transformations are not implemented yet." ));
        }
        */

        // Do we need to go through geocentric coordinates?
        if ($source->es != $dest->es || $source->a != $dest->a
            || $source->datum_type == Common::PJD_3PARAM
            || $source->datum_type == Common::PJD_7PARAM
            || $dest->datum_type == Common::PJD_3PARAM
            || $dest->datum_type == Common::PJD_7PARAM
        ) {
            // Convert to geocentric coordinates.
            $source->geodetic_to_geocentric($point);
            // CHECK_RETURN;
            // Convert between datums
            if ($source->datum_type == Common::PJD_3PARAM || $source->datum_type == Common::PJD_7PARAM) {
                $source->geocentric_to_wgs84( $point );
                // CHECK_RETURN;
            }

            if ($dest->datum_type == Common::PJD_3PARAM || $dest->datum_type == Common::PJD_7PARAM) {
                $dest->geocentric_from_wgs84($point);
                // CHECK_RETURN;
            }

            // Convert back to geodetic coordinates
            $dest->geocentric_to_geodetic($point);
            // CHECK_RETURN;
        }

        // Apply grid shift to destination if required
        /*
        if ($dest->datum_type == Common::PJD_GRIDSHIFT ) {
            throw(new Exception( "ERROR: Grid shift transformations are not implemented yet." ));
            // pj_apply_gridshift( pj_param(dest.params,"snadgrids").s, 1, point);
            // CHECK_RETURN;
        }
        */
        return $point;
    }


    /**
     * Function: adjust_axis
     * Normalize or de-normalized the x/y/z axes.  The normal form is "enu"
     * (easting, northing, up).
     * Parameters:
     * crs {Proj4php.Proj} the coordinate reference system
     * denorm {Boolean} when false, normalize
     * point {Object} the coordinates to adjust
     */
    public function adjust_axis($crs, $denorm, $point)
    {
        $xin = $point->x;
        $yin = $point->y;
        $zin = isset( $point->z ) ? $point->z : 0.0;

        for ($i = 0; $i < 3; $i++) {
            if ($denorm && $i == 2 && !isset($point->z)) {
                continue;
            }

            if ($i == 0) {
                $v = $xin;
                $t = 'x';
            } elseif ($i == 1) {
                $v = $yin;
                $t = 'y';
            } else {
                $v = $zin;
                $t = 'z';
            }

            switch ($crs->axis[$i]) {
                case 'e':
                    $point[$t] = $v;
                    break;
                case 'w':
                    $point[$t] = -$v;
                    break;
                case 'n':
                    $point[$t] = $v;
                    break;
                case 's':
                    $point[$t] = -$v;
                    break;
                case 'u':
                    if (isset( $point[$t])) {
                        $point->z = $v;
                    }
                    break;
                case 'd':
                    if (isset( $point[$t])) {
                        $point->z = -$v;
                    }
                    break;
                default :
                    throw(new Exception("ERROR: unknow axis (" . $crs->axis[$i] . ") - check definition of " . $crs->projName));
                    return null;
            }
        }

        return $point;
    }

    /**
     * Function: reportError
     * An internal method to report errors back to user.
     * Override this in applications to report error messages or throw exceptions.
     */
    public static function reportError( $msg )
    {
        throw(new Exception($msg));
    }

    /**
     * Function : loadScript
     * adapted from original. PHP is simplier.
     */
    public function loadScript($filename)
    {
        if (stripos($filename, 'http://') !== false ) {
            // If fecthing from a URL, just return the body of the response.
            return @file_get_contents($filename);
        } elseif (file_exists($filename)) {
            // Get the definition. An array will be returned.
            $def = require_once($filename);

            // Add any definitions we have imported to the defs array.
            foreach($def as $def_name => $def_details) {
                $this->defs[$def_name] = $def_details;
            }

            return true;
        } else {
            throw new Exception("File $filename could not be found or was not able to be loaded.");
        }
    }

    /**
     * Function: extend
     * Copy all properties of a source object to a destination object.  Modifies
     *     the passed in destination object.  Any properties on the source object
     *     that are set to undefined will not be (re)set on the destination object.
     *
     * Parameters:
     * destination - {Object} The object that will be modified
     * source - {Object} The object with properties to be set on the destination
     *
     * Returns:
     * {Object} The destination object.
     */
    public static function extend($destination, $source)
    {
        if ($source != null) {
            foreach ($source as $key => $value) {
                $destination->$key = $value;
            }
        }

        return $destination;
    }
}
