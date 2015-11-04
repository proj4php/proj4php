<?php
namespace proj4php;

/**
 * Author : Julien Moquet
 *
 * Inspired by Proj4js from Mike Adair madairATdmsolutions.ca
 * and Richard Greenwood rich@greenwoodmap.com
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html
 */
use Exception;

class Proj {
    /**
     * Property: readyToUse
     * Flag to indicate if initialization is complete for $this Proj object
     */
    public $readyToUse = false;
    
    /**
     * Property: title
     * The title to describe the projection
     */
    public $title = null;
    
    /**
     * Property: projName
     * The projection class for $this projection, e.g.
     * lcc (lambert conformal conic,
     * or merc for mercator). These are exactly equivalent to their Proj4
     * counterparts.
     */
    public $projName = null;
    
    /**
     * Property: projection
     * The projection object for $this projection.
     */
    public $projection = null;
    
    /**
     * Property: units
     * The units of the projection.
     * Values include 'm' and 'degrees'
     */
    public $units = null;
    
    /**
     * Property: datum
     * The datum specified for the projection
     */
    public $datum = null;
    
    // The Datum class sets these directly.
    public $datum_params;
    public $datumCode;
    
    /**
     * Property: axis
     * The axis.
     */
    public $axis;
    
    /**
     * Property: x0
     * The x coordinate origin
     */
    public $x0 = 0;
    
    /**
     * Property: y0
     * The y coordinate origin
     */
    public $y0 = 0;
    
    /**
     * Property: localCS
     * Flag to indicate if the projection is a local one in which no transforms
     * are required.
     */
    public $localCS = false;
    
    // Proj4php injected object.
    protected $proj4php;
    protected $srsCode;

    /**
     * RE to split an SRS code in WKT format.
     * Given "Alphanum123[something,or,nothing]" would give:
     * match 1: "Alphanum123"
     * match 2: "something,or,nothing"
     * 
     * @var type
     */
    const WKT_RE = '/^(\w+)\[(.*)\]$/';
    
    /**
     * The supplied Spatial Reference System (SRS) code supplied
     * on creation of the projection.
     */
    protected $srsCode;

    /**
     * Constructor: initialize
     * Constructor for Proj4php::Proj objects
     *
     * Parameters:
     * $srsCode - a code for map projection definition parameters. These are usually
     * (but not always) EPSG codes.
     */
    public function __construct($srsCode, Proj4php $proj4php) {
        $this->srsCode = $srsCode;
        $this->proj4php = $proj4php;
        
        // Check to see if $this is a Well Known Text (WKT) string.
        // This is an old, deprecated format, but still used.
        // CHECKME: are these the WKT "objects"? If so, we probably
        // need to check the string *starts* with these names.
        
        if (preg_match('/(GEOGCS|GEOCCS|PROJCS|LOCAL_CS)/', $srsCode)) {
            $this->parseWKT($srsCode);
            $this->deriveConstants();
            $this->loadProjCode($this->projName);
            return;
        }
        
        // DGR 2008-08-03 : support urn and url
        if (strpos($srsCode, 'urn:') === 0) {
            // urn:ORIGINATOR:def:crs:CODESPACE:VERSION:ID
            $urn = explode(':', $srsCode);
            
            if (($urn[1] == 'ogc' || $urn[1] == 'x-ogc') && ($urn[2] == 'def') && ($urn[3] == 'crs')) {
                $srsCode = $urn[4] . ':' . $urn[strlen($urn) - 1];
            }
        } elseif (strpos($srsCode, 'http://') === 0) {
            // url#ID
            $url = explode('#', $srsCode);
            
            if (preg_match("/epsg.org/", $url[0])) {
                // http://www.epsg.org/#
                $srsCode = 'EPSG:' . $url[1];
            } elseif (preg_match("/RIG.xml/", $url[0])) {
                // http://librairies.ign.fr/geoportail/resources/RIG.xml#
                // http://interop.ign.fr/registers/ign/RIG.xml#
                $srsCode = 'IGNF:' . $url[1];
            }
        }
        
        $this->srsCode = strtoupper($srsCode);
        
        if (strpos($this->srsCode, "EPSG") === 0) {
            $this->srsCode = $this->srsCode;
            $this->srsAuth = 'epsg';
            $this->srsProjNumber = substr($this->srsCode, 5);
            // DGR 2007-11-20 : authority IGNF
        } elseif (strpos($this->srsCode, "IGNF") === 0) {
            $this->srsCode = $this->srsCode;
            $this->srsAuth = 'IGNF';
            $this->srsProjNumber = substr($this->srsCode, 5);
            // DGR 2008-06-19 : pseudo-authority CRS for WMS
        } elseif (strpos($this->srsCode, "CRS") === 0) {
            $this->srsCode = $this->srsCode;
            $this->srsAuth = 'CRS';
            $this->srsProjNumber = substr($this->srsCode, 4);
        } else {
            $this->srsAuth = '';
            $this->srsProjNumber = $this->srsCode;
        }
        
        $this->loadProjDefinition();
    }

    /**
     * Function: loadProjDefinition
     * Loads the coordinate system initialization string if required.
     * Note that dynamic loading happens asynchronously so an application must
     * wait for the readyToUse property is set to true.
     * To prevent dynamic loading, include the defs through a script tag in
     * your application.
     */
    public function loadProjDefinition() {
        // Check in memory
        if ($this->proj4php->hasDef($this->srsCode)) {
            $this->defsLoaded();
            return;
        }
        
        // Check for def on the server
        $filename = __DIR__ . '/defs/' . strtoupper($this->srsAuth) . $this->srsProjNumber . '.php';
        
        try {
            // Load the def data script.
            $this->proj4php->loadScript($filename);
            $this->defsLoaded();
        } catch (Exception $e) {
            $this->loadFromService();
        }
    }

    /**
     * Function: loadFromService
     * Creates the REST URL for loading the definition from a web service and
     * loads it.
     */
    public function loadFromService() {
        // Load from web service
        $url = Proj4php::$defsLookupService . '/' . $this->srsAuth . '/' . $this->srsProjNumber . '/proj4/';
        
        try {
            $this->proj4php->addDef(strtoupper($this->srsAuth) . ':' . $this->srsProjNumber, 
                $this->proj4php->loadScript($url));
        } catch (Exception $e) {
            $this->defsFailed();
        }
    }

    /**
     * Function: defsLoaded
     * Continues the Proj object initilization once the def file is loaded
     */
    public function defsLoaded() {
        $this->parseDefs();
        
        $this->loadProjCode($this->projName);
    }

    /**
     * Function: checkDefsLoaded
     * $this is the loadCheck method to see if the def object exists
     */
    public function checkDefsLoaded() {
        return $this->proj4php->hasDef($this->srsCode) && $this->proj4php->getDef($this->srsCode) != '';
    }

    /**
     * Function: defsFailed
     * Report an error in loading the defs file, but continue on using WGS84
     */
    public function defsFailed() {
        Proj4php::reportError('failed to load projection definition for: ' . $this->srsCode);
        
        // Set it to something so it can at least continue
        $this->proj4php->addDef($this->srsCode, $this->proj4php->WGS84);
        $this->defsLoaded();
    }

    /**
     * Function: loadProjCode
     * Loads projection class code dynamically if required.
     * Projection code may be included either through a script tag or in
     * a built version of proj4php
     *
     * An exception occurs if the projection is not found.
     */
    public function loadProjCode($projName) {
        if (array_key_exists($projName, Proj4php::$proj)) {
            $this->initTransforms();
            return;
        }
        
        // The class name for the projection code
        $classname = '\\proj4php\\projCode\\' . ucfirst($projName);
        
        if (class_exists($classname)) {
            // Instantiate the class then store it in the global static (for now) $prog array.
            Proj4php::$proj[$projName] = new $classname();
            $this->loadProjCodeSuccess($projName);
        } else {
            $this->loadProjCodeFailure($projName);
        }
    }

    /**
     * Function: loadProjCodeSuccess
     * Loads any proj dependencies or continue on to final initialization.
     */
    public function loadProjCodeSuccess($projName) {
        if (isset(Proj4php::$proj[$projName]->dependsOn) && ! empty(Proj4php::$proj[$projName]->dependsOn)) {
            $this->loadProjCode(Proj4php::$proj[$projName]->dependsOn);
        } else {
            $this->initTransforms();
        }
    }

    /**
     * Function: defsFailed
     * Report an error in loading the proj file.
     * Initialization of the Proj
     * object has failed and the readyToUse flag will never be set.
     */
    public function loadProjCodeFailure($projName) {
        Proj4php::reportError("failed to find projection file for: " . $projName);
        // TBD initialize with identity transforms so proj will still work?
    }

    /**
     * Function: checkCodeLoaded
     * $this is the loadCheck method to see if the projection code is loaded
     */
    public function checkCodeLoaded($projName) {
        return isset(Proj4php::$proj[$projName]) && ! empty(Proj4php::$proj[$projName]);
    }

    /**
     * Function: initTransforms
     * Finalize the initialization of the Proj object
     */
    public function initTransforms() {
        $this->projection = new Proj4php::$proj[$this->projName]();
        Proj4php::extend($this->projection, $this);
        
        // initiate depending class
        if (false !==
             ($dependsOn = isset($this->projection->dependsOn) && ! empty($this->projection->dependsOn) ? $this->projection->dependsOn : false)) {
            Proj4php::extend(Proj4php::$proj[$dependsOn], $this->projection);
            Proj4php::$proj[$dependsOn]->init();
            Proj4php::extend($this->projection, Proj4php::$proj[$dependsOn]);
        }
        
        $this->init();
        $this->readyToUse = true;
    }

    /**
     */
    public function init() {
        $this->projection->init();
    }

    /**
     *
     * @param type $pt            
     * @return type
     */
    public function forward(Point $pt) {
        return $this->projection->forward($pt);
    }

    /**
     *
     * @param type $pt            
     * @return type
     */
    public function inverse(Point $pt) {
        return $this->projection->inverse($pt);
    }

    /**
     * returns an array with three parts,
     * 0: the wktObject ie: PROJCS
     * 1: the wktName ie: NAD_1983_UTM_Zone_17N
     * 2: the wkt sub sections array, returns nested wkt strings as an array, each can be passed into this
     * method again later
     *
     * @param string $wktStr            
     */
    private static function ParseWKTIntoSections($wktStr) {
        $regex = '/^(\w+)\[(.*)\]$/';
        
        if (false === ($match = preg_match($regex, $wktStr, $wktMatch)))
            return;
        if (! isset($wktMatch[1]))
            return;
        
        $wktObject = $wktMatch[1];
        $wktContent = $wktMatch[2];
        $wktTemp = explode(",", $wktContent);
        
        $wktName = (strtoupper($wktObject) == "TOWGS84") ? "TOWGS84" : trim(array_shift($wktTemp), '"');
        
        $wktArray = array();
        $bkCount = 0;
        
        $obj = '';
        while (count($wktTemp)) {
            
            $obj .= array_shift($wktTemp);
            $bkCount = substr_count($obj, '[') - substr_count($obj, ']');
            
            if ($bkCount === 0) {
                array_push($wktArray, $obj);
                $obj = '';
            } else {
                $obj .= ',';
            }
        }
        
        return array(
            $wktObject,
            $wktName,
            $wktArray
        );
    }

    /**
     * Function: parseWKT
     * Parses a WKT string to get initialization parameters.
     */
    public function parseWKT($wkt) {
        $wktSections = self::ParseWKTIntoSections($wkt);
        
        if (empty($wktSections)) {
            return;
        }
        
        $wktObject = $wktSections[0];
        $wktName = $wktSections[1];
        $wktArray = $wktSections[2];
        
        // Do something based on the type of the wktObject being parsed.
        // Add in variations in the spelling as required.
        
        switch ($wktObject) {
            case 'LOCAL_CS':
                $this->projName = 'identity';
                $this->localCS = true;
                $this->srsCode = $wktName;
                break;
            case 'GEOGCS':
                $this->projName = 'longlat';
                $this->geocsCode = $wktName;
                if (! $this->srsCode) {
                    $this->srsCode = $wktName;
                }
                break;
            case 'PROJCS':
                $this->srsCode = $wktName;
                break;
            case 'GEOCCS':
                break;
            case 'PROJECTION':
                $this->projName = Proj4php::$wktProjections[$wktName];
                break;
            case 'DATUM':
                $this->datumName = $wktName;
                break;
            case 'LOCAL_DATUM':
                $this->datumCode = 'none';
                break;
            case 'SPHEROID':
                $this->ellps = $wktName;
                $this->a = floatval(array_shift($wktArray));
                $this->rf = floatval(array_shift($wktArray));
                break;
            case 'PRIMEM':
                // to radians?
                $this->from_greenwich = floatval(array_shift($wktArray));
                break;
            case 'UNIT':
                $this->units = $wktName;
                $this->unitsPerMeter = floatval(array_shift($wktArray));
                break;
            case 'PARAMETER':
                $name = strtolower($wktName);
                $value = floatval(array_shift($wktArray));
                
                // there may be many variations on the wktName values, add in case
                // statements as required
                switch ($name) {
                    case 'false_easting':
                        $this->x0 = $value;
                        break;
                    case 'false_northing':
                        $this->y0 = $value;
                        break;
                    case 'scale_factor':
                        $this->k0 = $value;
                        break;
                    case 'central_meridian':
                        $this->long0 = $value * Common::D2R;
                        break;
                    case 'latitude_of_origin':
                        $this->lat0 = $value * Common::D2R;
                        break;
                    case 'more_here':
                        break;
                    default:
                        break;
                }
                break;
            case 'TOWGS84':
                $this->datum_params = $wktArray;
                break;
            // DGR 2010-11-12: AXIS
            case 'AXIS':
                $name = strtolower($wktName);
                $value = array_shift($wktArray);
                switch ($value) {
                    case 'EAST':
                        $value = 'e';
                        break;
                    case 'WEST':
                        $value = 'w';
                        break;
                    case 'NORTH':
                        $value = 'n';
                        break;
                    case 'SOUTH':
                        $value = 's';
                        break;
                    case 'UP':
                        $value = 'u';
                        break;
                    case 'DOWN':
                        $value = 'd';
                        break;
                    case 'OTHER':
                    default:
                        $value = ' ';
                        break; // FIXME
                }
                if (! $this->axis) {
                    $this->axis = "enu";
                }
                switch ($name) {
                    case 'X':
                        $this->axis = $value . substr($this->axis, 1, 2);
                        break;
                    case 'Y':
                        $this->axis = substr($this->axis, 0, 1) . $value . substr($this->axis, 2, 1);
                        break;
                    case 'Z':
                        $this->axis = substr($this->axis, 0, 2) . $value;
                        break;
                    default:
                        break;
                }
            case 'MORE_HERE':
                break;
            default:
                break;
        }
        
        foreach ($wktArray as $wktArrayContent) {
            $this->parseWKT($wktArrayContent);
        }
    }

    /**
     * Function: parseDefs
     * Parses the PROJ.4 initialization string and sets the associated properties.
     */
    public function parseDefs() {
        $this->defData = $this->proj4php->getDef($this->srsCode);
        
        if (! $this->defData) {
            return;
        }
        
        $paramArray = explode("+", $this->defData);
        
        for ($prop = 0; $prop < sizeof($paramArray); $prop ++) {
            if (strlen($paramArray[$prop]) == 0) {
                continue;
            }
            
            $property = explode("=", $paramArray[$prop]);
            $paramName = strtolower($property[0]);
            
            if (sizeof($property) >= 2) {
                $paramVal = $property[1];
            }
            
            switch (trim($paramName)) {
                // throw away nameless parameter
                case "":
                    break;
                case "title":
                    $this->title = $paramVal;
                    break;
                case "proj":
                    $this->projName = trim($paramVal);
                    break;
                case "units":
                    $this->units = trim($paramVal);
                    break;
                case "datum":
                    $this->datumCode = trim($paramVal);
                    break;
                case "nadgrids":
                    $this->nagrids = trim($paramVal);
                    break;
                case "ellps":
                    $this->ellps = trim($paramVal);
                    break;
                case "a":
                    // semi-major radius
                    $this->a = floatval($paramVal);
                    break;
                case "b":
                    // semi-minor radius
                    $this->b = floatval($paramVal);
                    break;
                case "rf":
                    // DGR 2007-11-20
                    // inverse flattening rf= a/(a-b)
                    $this->rf = floatval(paramVal);
                    break;
                case "lat_0":
                    // phi0, central latitude
                    $this->lat0 = $paramVal * Common::D2R;
                    break;
                case "lat_1":
                    // standard parallel 1
                    $this->lat1 = $paramVal * Common::D2R;
                    break;
                case "lat_2":
                    // standard parallel 2
                    $this->lat2 = $paramVal * Common::D2R;
                    break;
                case "lat_ts":
                    // used in merc and eqc
                    $this->lat_ts = $paramVal * Common::D2R;
                    break;
                case "lon_0":
                    // lam0, central longitude
                    $this->long0 = $paramVal * Common::D2R;
                    break;
                case "alpha":
                    $this->alpha = floatval($paramVal) * Common::D2R;
                    // for somerc projection
                    break;
                case "lonc":
                    // for somerc projection
                    $this->longc = paramVal * Common::D2R;
                    break;
                case "x_0":
                    // false easting
                    $this->x0 = floatval($paramVal);
                    break;
                case "y_0":
                    // false northing
                    $this->y0 = floatval($paramVal);
                    break;
                case "k_0":
                    // projection scale factor
                    $this->k0 = floatval($paramVal);
                    break;
                case "k":
                    // both forms returned
                    $this->k0 = floatval($paramVal);
                    break;
                case "r_a":
                    // sphere--area of ellipsoid
                    $this->R_A = true;
                    break;
                case "zone":
                    // UTM Zone
                    $this->zone = intval($paramVal, 10);
                    break;
                case "south":
                    // UTM north/south
                    $this->utmSouth = true;
                    break;
                case "towgs84":
                    $this->datum_params = explode(",", $paramVal);
                    break;
                case "to_meter":
                    // cartesian scaling
                    $this->to_meter = floatval($paramVal);
                    break;
                case "from_greenwich":
                    $this->from_greenwich = $paramVal * Common::D2R;
                    break;
                case "pm":
                    // DGR 2008-07-09 : if pm is not a well-known prime meridian take
                    // the value instead of 0.0, then convert to radians
                    $paramVal = trim($paramVal);
                    
                    $this->from_greenwich = $this->proj4php->hasPrimeMeridian($paramVal) ? $this->proj4php->getPrimeMeridian(
                        $paramVal) : floatval($paramVal);
                    
                    $this->from_greenwich *= Common::D2R;
                    break;
                case "axis":
                    // DGR 2010-11-12: axis
                    $paramVal = trim($paramVal);
                    $legalAxis = "ewnsud";
                    if (strlen(paramVal) == 3 && strpos($legalAxis, substr($paramVal, 0, 1)) !== false &&
                         strpos($legalAxis, substr($paramVal, 1, 1)) !== false &&
                         strpos($legalAxis, substr($paramVal, 2, 1)) !== false) {
                        $this->axis = $paramVal;
                    } // FIXME: be silent ?
                    
                    break;
                case "no_defs":
                    break;
                default:
                // alert("Unrecognized parameter: " . paramName);
            } // switch()
        } // for paramArray
        
        $this->deriveConstants();
    }

    /**
     * Function: deriveConstants
     * Sets several derived constant values and initialization of datum and ellipse parameters.
     */
    public function deriveConstants() {
        if (isset($this->nagrids) && $this->nagrids == '@null') {
            $this->datumCode = 'none';
        }
        
        if (isset($this->datumCode) && $this->datumCode != 'none') {
            $datumDef = $this->proj4php->getDatum($this->datumCode);
            
            if (is_array($datumDef)) {
                $this->datum_params = array_key_exists('towgs84', $datumDef) ? explode(',', $datumDef['towgs84']) : null;
                $this->ellps = $datumDef['ellipse'];
                $this->datumName = array_key_exists('name', $datumDef) ? $datumDef['name'] : $this->datumCode;
            }
        }
        
        // Do we have an ellipsoid?
        if (! isset($this->a)) {
            if (! isset($this->ellps) || strlen($this->ellps) == 0 || ! $this->proj4php->hasEllipsoid($this->ellps)) {
                $ellipse = $this->proj4php->getEllipsoid('WGS84');
            } else {
                $ellipse = $this->proj4php->getEllipsoid($this->ellps);
            }
            
            Proj4php::extend($this, $ellipse);
        }
        
        if (isset($this->rf) && ! isset($this->b)) {
            $this->b = (1.0 - 1.0 / $this->rf) * $this->a;
        }
        
        if ((isset($this->rf) && $this->rf === 0) || abs($this->a - $this->b) < Common::EPSLN) {
            $this->sphere = true;
            $this->b = $this->a;
        }
        
        // used in geocentric
        $this->a2 = $this->a * $this->a;
        // used in geocentric
        $this->b2 = $this->b * $this->b;
        // e ^ 2
        $this->es = ($this->a2 - $this->b2) / $this->a2;
        // eccentricity
        $this->e = sqrt($this->es);
        
        if (isset($this->R_A)) {
            $this->a *= 1. - $this->es * (Common::SIXTH + $this->es * (Common::RA4 + $this->es * Common::RA6));
            $this->a2 = $this->a * $this->a;
            $this->b2 = $this->b * $this->b;
            $this->es = 0.0;
        }
        
        // used in geocentric
        $this->ep2 = ($this->a2 - $this->b2) / $this->b2;
        
        if (! isset($this->k0)) {
            // default value
            $this->k0 = 1.0;
        }
        
        // DGR 2010-11-12: axis
        if (! isset($this->axis)) {
            $this->axis = "enu";
        }
        
        $this->datum = new Datum($this);
    }
}
