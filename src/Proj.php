<?php
namespace proj4php;

/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4js from Mike Adair madairATdmsolutions.ca
 *                      and Richard Greenwood rich@greenwoodmap.com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */

use Exception;

class Proj
{
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
     * The projection class for $this projection, e.g. lcc (lambert conformal conic,
     * or merc for mercator).  These are exactly equivalent to their Proj4
     * counterparts.
     */
    public $projName = null;
    
    /**
     * Property: projection
     * The projection object for $this projection. */
    public $projection = null;

    /**
     * Property: units
     * The units of the projection.  Values include 'm' and 'degrees'
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

    /**
     * RE to split an SRS code in WKT format.
     * Given "Alphanum123[something,or,nothing]" would give:
     * match 1: "Alphanum123"
     * match 2: "something,or,nothing"
     * @var type
     */
    const WKT_RE = '/^(\w+)\[(.*)\]$/';

    /**
     * The supplied Spatial Reference System (SRS) code supplied
     * on creation of the projection.
     */
    public $srsCode;


    public $to_meter = 1.0;

    /**
     * Constructor: initialize
     * Constructor for Proj4php::Proj objects
     *
     * Parameters:
     * $srsCode - a code for map projection definition parameters. These are usually
     * (but not always) EPSG codes.
     */
    public function __construct($srsCode, Proj4php $proj4php)
    {
        $this->srsCodeInput = $srsCode;
        $this->proj4php = $proj4php;

        


        // Check to see if $this is a Well Known Text (WKT) string.
        // This is an old, deprecated format, but still used.
        // CHECKME: are these the WKT "objects"? If so, we probably
        // need to check the string *starts* with these names.

        if (preg_match('/(GEOGCS|GEOCCS|PROJCS|LOCAL_CS)/', $srsCode)) {
            $this->to_rads=COMMON::D2R;
            $this->parseWKT($srsCode);

            // this is useful for a bunch of projections that are using tmerc while the proj4code uses utm+zone
            // however I would like to remove it if I can compare tmerc to utm 
            //$this->applyWKTUtmFromTmerc(); 

            if(isset($this->defData)){
                // wkt codes can contain EXTENSION["PROJ4", "..."]
                // for example SR-ORG:6
                $this->parseDefs();
                $this->initTransforms();
                return;
            }

            $this->deriveConstants();
            $this->loadProjCode($this->projName);
            return;
        }

        // DGR 2008-08-03 : support urn and url
        if (strpos($srsCode, 'urn:') === 0) {
            //urn:ORIGINATOR:def:crs:CODESPACE:VERSION:ID
            $urn = explode(':', $srsCode);

            if (($urn[1] == 'ogc' || $urn[1] == 'x-ogc') &&
                ($urn[2] == 'def') &&
                ($urn[3] == 'crs')
            ) {
                $srsCode = $urn[4] . ':' . $urn[strlen($urn) - 1];
            }
        } elseif (strpos($srsCode, 'http://') === 0) {
            //url#ID
            $url = explode('#', $srsCode);

            if (preg_match("/epsg.org/", $url[0])) {
                // http://www.epsg.org/#
                $srsCode = 'EPSG:' . $url[1];
            } elseif (preg_match("/RIG.xml/", $url[0])) {
                //http://librairies.ign.fr/geoportail/resources/RIG.xml#
                //http://interop.ign.fr/registers/ign/RIG.xml#
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
     *    Loads the coordinate system initialization string if required.
     *    Note that dynamic loading happens asynchronously so an application must
     *    wait for the readyToUse property is set to true.
     *    To prevent dynamic loading, include the defs through a script tag in
     *    your application.
     *
     */
    public function loadProjDefinition()
    {
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
    public function loadFromService()
    {
        // Load from web service
        $url = Proj4php::$defsLookupService . '/' . $this->srsAuth . '/' . $this->srsProjNumber . '/proj4/';

        try {
            $this->proj4php->addDef(
                strtoupper($this->srsAuth) . ':' . $this->srsProjNumber,
                $this->proj4php->loadScript($url)
            );
        } catch (Exception $e) {
            $this->defsFailed();
        }
    }

    /**
     * Function: defsLoaded
     * Continues the Proj object initilization once the def file is loaded
     *
     */
    public function defsLoaded()
    {
        $this->parseDefs();

        $this->loadProjCode($this->projName);
    }

    /**
     * Function: checkDefsLoaded
     *    $this is the loadCheck method to see if the def object exists
     *
     */
    public function checkDefsLoaded()
    {
        return $this->proj4php->hasDef($this->srsCode) && $this->proj4php->getDef($this->srsCode) != '';
    }

    /**
     * Function: defsFailed
     *    Report an error in loading the defs file, but continue on using WGS84
     *
     */
    public function defsFailed()
    {
        Proj4php::reportError('failed to load projection definition for: ' . $this->srsCode);

        // Set it to something so it can at least continue
        $this->proj4php->addDef(
            $this->srsCode,
            $this->proj4php->WGS84
        );
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
    public function loadProjCode($projName)
    {
        if (array_key_exists($projName, Proj4php::$proj)) {
            $this->initTransforms();
            return;
        }

        // The class name for the projection code
        $classname = '\\proj4php\\projCode\\' . ucfirst($projName);

        if (class_exists($classname)) {
            // Instantiate the class then store it in the global static (for now) $prog array.
            Proj4php::$proj[$projName] = new $classname;
            $this->loadProjCodeSuccess($projName);
        } else {
            $this->loadProjCodeFailure($projName);
        }
    }

    /**
     * Function: loadProjCodeSuccess
     * Loads any proj dependencies or continue on to final initialization.
     *
     */
    public function loadProjCodeSuccess($projName)
    {
        if (isset(Proj4php::$proj[$projName]->dependsOn) && !empty(Proj4php::$proj[$projName]->dependsOn)) {
            $this->loadProjCode(Proj4php::$proj[$projName]->dependsOn);
        } else {
            $this->initTransforms();
        }
    }

    /**
     * Function: defsFailed
     *    Report an error in loading the proj file.  Initialization of the Proj
     *    object has failed and the readyToUse flag will never be set.
     *
     */
    public function loadProjCodeFailure($projName)
    {
        Proj4php::reportError("failed to find projection file for: (".gettype($projName).")" . $projName);
        //TBD initialize with identity transforms so proj will still work?
    }

    /**
     * Function: checkCodeLoaded
     * $this is the loadCheck method to see if the projection code is loaded
     *
     */
    public function checkCodeLoaded($projName)
    {
        return isset(Proj4php::$proj[$projName]) && !empty(Proj4php::$proj[$projName]);
    }

    /**
     * Function: initTransforms
     * Finalize the initialization of the Proj object
     */
    public function initTransforms()
    {
        $this->projection = new Proj4php::$proj[$this->projName];
        Proj4php::extend($this->projection, $this);

        // initiate depending class
        if (false !== ($dependsOn = isset($this->projection->dependsOn) && !empty($this->projection->dependsOn) ? $this->projection->dependsOn : false)) {
            Proj4php::extend(Proj4php::$proj[$dependsOn], $this->projection);
            Proj4php::$proj[$dependsOn]->init();
            Proj4php::extend($this->projection, Proj4php::$proj[$dependsOn]);
        }

        $this->init();
        $this->readyToUse = true;
    }

    /**
     *
     */
    public function init()
    {
        $this->projection->init();
    }

    /**
     * @param type $pt
     * @return type 
     */
    public function forward(Point $pt)
    {
        return $this->projection->forward($pt);
    }

    /**
     *
     * @param type $pt
     * @return type 
     */
    public function inverse(Point $pt)
    {
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

        
        if (false === ($match = preg_match($regex, $wktStr, $wktMatch))){
            return;
        }
        if (!isset($wktMatch[1])){
            return;
        }
        
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
    public function parseWKT($wkt)
    {

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

                if ( ! $this->srsCode) {
                    $this->srsCode = $wktName;
                }
                break;
            case 'PROJCS':
                $this->srsCode = $wktName;
                break;
            case 'GEOCCS':
                break;
            case 'PROJECTION':

                if(key_exists($wktName, Proj4php::$wktProjections)){
                    $this->projName = Proj4php::$wktProjections[$wktName];
                }else{
                    Proj4php::reportError('Undefined Projection: '.$wktName);
                }
                break;
            case 'DATUM':
                $this->datumName = $wktName;
                if(key_exists($wktName, Proj4php::$wktDatums)){
                    $this->datumCode = Proj4php::$wktDatums[$wktName];
                }
                break;
            case 'LOCAL_DATUM':
                $this->datumCode = 'none';
                break;
            case 'SPHEROID':
               

                if(key_exists($wktName, Proj4php::$wktEllipsoids)){
                    $this->ellps = Proj4php::$wktEllipsoids[$wktName];
                }else{
                     $this->ellps = $wktName;
                     $this->a = floatval(array_shift($wktArray));
                     $this->rf = floatval(array_shift($wktArray));
                }

                
                break;
            case 'PRIMEM':
                // to radians?

                $this->from_greenwich = floatval(array_shift($wktArray))*Common::D2R;
                break;
            case 'UNIT':
                $this->units = $wktName;
                $this->parseWKTToMeter($wktName, $wktArray);
                $this->parseWKTToRads($wktName, $wktArray);
                break;
            case 'PARAMETER':
                $name = strtolower($wktName);
                $value = floatval(array_shift($wktArray));

                // there may be many variations on the wktName values, add in case
                // statements as required
                switch ($name) {
                    case 'false_easting':

                        $this->x0 =$value;
                        if(isset($this->to_meter)){
                            $this->x0=$this->to_meter*$this->x0;
                        }
                        break;
                    case 'false_northing':
                        $this->y0 =$value;
                         if(isset($this->to_meter)){
                            $this->y0=$this->to_meter*$this->y0;
                        }
                        break;
                    case 'scale_factor':
                        $this->k0 = $value;
                        break;
                    case 'central_meridian':
                    case 'longitude_of_center': // SR-ORG:10
                        $this->longc = $value * $this->to_rads;
                    case 'longitude_of_origin': // SR-ORG:118
                        $this->long0 = $value * $this->to_rads;

                        break;
                    case 'latitude_of_origin':
                    case 'latitude_of_center': // SR-ORG:10
                        $this->lat0 = $value * $this->to_rads;
                        if($this->projName=='merc'||$this->projName=='eqc'
                            ){
                             $this->lat_ts = $value *  $this->to_rads; //EPSG:3752 (merc), EPSG:3786 (eqc), SR-ORG:7710" (stere)
                             //this cannot be set here in: SR-ORG:6647 (stere)
                        }
                        break;
                    case 'standard_parallel_1':
                        $this->lat1 = $value * $this->to_rads;
                        $this->lat_ts = $value * $this->to_rads; //SR-ORG:22
                        break;
                    case 'standard_parallel_2':
                        $this->lat2 = $value * $this->to_rads;
                        break;
                    case 'rectified_grid_angle':
                        if(!isset($this->alpha)){
                            //I'm not sure if this should be set here. 
                            //EPSG:3167 defineds azimuth and rectified_grid_angle. both are similar (azimuth is closer)
                            //SR-ORG:7172 defines both, and both are 90.
                            $this->alpha=$value * $this->to_rads;
                        }
                        break;
                    case 'azimuth':
                        $this->alpha=$value * $this->to_rads;//EPSG:2057
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
            //DGR 2010-11-12: AXIS
            case 'AXIS':
                $name = strtolower($wktName);
                $value = array_shift($wktArray);
                switch ($value) {
                    case 'EAST' : $value = 'e';
                        break;
                    case 'WEST' : $value = 'w';
                        break;
                    case 'NORTH': $value = 'n';
                        break;
                    case 'SOUTH': $value = 's';
                        break;
                    case 'UP' : $value = 'u';
                        break;
                    case 'DOWN' : $value = 'd';
                        break;
                    case 'OTHER':

                    default : 
                    //throw new Exception("Unknown Axis ".$name." Value:  ".$value); 
                    $value = ' ';
                    
                        break; // FIXME
                }
                if (!$this->axis) {
                    $this->axis = "enu";
                }

                switch ($name) {
                    case 'e(x)': // EPSG:2140
                    case 'x': $this->axis = $value . substr($this->axis, 1, 2); 
                        break;
                    case 'n(y)':
                    case 'y': $this->axis = substr($this->axis, 0, 1) . $value . substr($this->axis, 2, 1);
                        break;
                    case 'z': $this->axis = substr($this->axis, 0, 2) . $value;
                        break;

                    // Here is a list of other axis that exist in wkt definitions. are they useful?
                    
                    
                    case 'geodetic latitude': //from SR-ORG:29 

                    case 'latitude':
                    case 'lat':
                    case 'geodetic longitude':
                    case 'longitude':
                    case 'long':
                    case 'lon':

                    case 'e':
                    case 'n': //SR-ORG:4705

                    case 'gravity-related height':

                    case 'geocentric y': //SR-ORG:7910

                    case 'east':
                    case 'north': //SR-ORG:4705

                    case 'ellipsoidal height': //EPSG:3823

                    case 'easting':
                    case 'northing':
                    case 'southing': //SR-ORG:8262
                        break;
                    
                    default : 
                         throw new Exception("Unknown Axis Name: ".$name); //for testing
                    break;
                }

            case 'EXTENSION':

                $name = strtolower($wktName);
                $value = array_shift($wktArray);
                switch ($name) {
                    case 'proj4':
                        // WKT can define a proj4 definition. for example SR-ORG:6
                        $this->defData=$value;
                        
                        break;
                    default:
                        break;
                }
                break;
            case 'MORE_HERE':
                break;
            default:
                break;
        }

        foreach ($wktArray as $wktArrayContent) {
            $this->parseWKT($wktArrayContent);
        }
    }

    protected function parseWKTToMeter($wktName, &$wktArray){
        if($wktName=='US survey foot'||
            $wktName=='US Survey Foot'||
            $wktName=='Foot_US'||
            $wktName=='U.S. Foot'||
            $wktName=="Clarke's link"||
            $wktName=="Clarke's foot"||
            $wktName=="link"||
            $wktName=="Gold Coast foot"||
            $wktName=="foot"||
            $wktName=="Foot" ||
            $wktName=="British chain (Sears 1922 truncated)"||
            $wktName=="Meter"||
            $wktName=="metre" ||
            $wktName=="foot_survey_us" ||
            $wktName=="Kilometer" ||
            $wktName=="international_feet" ||
            $wktName=="m" ||
            $wktName=="Mile_US" ||
            $wktName=="Coord" ||
            $wktName=="Indian yard"||
            $wktName=="British yard (Sears 1922)" ||
            $wktName=="British chain (Sears 1922)" ||
            $wktName=="British foot (Sears 1922)"
            ){

            //$wktName=="1/32meter" = 0.03125 SR-ORG:98 ? should we support this?

            //Example projections with non-meter units:
            // R-ORG:27 Foot_US
            // EPSG:2066 Clarke's link http://georepository.com/unit_9039/Clarke-s-link.html
            // EPSG:2136 Gold Coast foot, 0.3047997101815088
            // EPSG:2155 US survey foot
            // SR-ORG:6659 US Survey Foot
            // EPSG:2222 foot
            // EPSG:2314 Clarke's foot
            // EPSG:3140 link
            // EPSG:3167 British chain (Sears 1922 truncated)",20.116756
            // SR-ORG:6635 UNIT["Meter",-1]
            // SR-ORG:6887 U.S. Foot
            // SR-ORG:6982 UNIT[\"metre\",1.048153]]
            // SR-ORG:7008 foot_survey_us
            // SR-ORG:7496 Kilometer
            // SR-ORG:7508 international_feet
            // SR-ORG:7677 Foot
            // SR-ORG:7753 m = 9000.0
            // SR-ORG:7889 Mile_US
            // SR-ORG:8262 Coord = 0.0746379
            // EPSG:24370 Indian yard 0.9143985307444408
            // EPSG:27291 British yard (Sears 1922) 0.9143984146160287
            // EPSG:29871 British chain (Sears 1922) 20.11676512155263,
            // EPSG:29872 British foot (Sears 1922) 0.3047994715386762,
            $this->to_meter= floatval( array_shift($wktArray));
            if(isset($this->x0)){
                    $this->x0=$this->to_meter*$this->x0;
                }
            if(isset($this->y0)){
                $this->y0=$this->to_meter*$this->y0;
            }
        }

    }

    protected function parseWKTToRads($wktName, &$wktArray){
        if($wktName=='Radian'||
            $wktName=='Degree' ||
            $wktName=='degree' ||
            $wktName=='grad'
            ){

            // SR-ORG:7753 degree=0.081081
            // SR-ORG:8163 grad=0.01570796326794897,

            $this->to_rads= floatval( array_shift($wktArray));
            if(isset($this->lat_ts)){
                $this->lat_ts=$this->to_rads*$this->lat_ts;
            }

            if(isset($this->x0)){
                $this->x0=$this->to_rads*$this->x0;
            }

            if(isset($this->y0)){
                $this->y0=$this->to_rads*$this->y0;
            }

            if(isset($this->longc)){
                $this->longc=$this->to_rads*$this->longc;
            }


            if(isset($this->long0)){
                $this->long0=$this->to_rads*$this->long0;
            }

            if(isset($this->lat0)){
                $this->lat0=$this->to_rads*$this->lat0;
            }

            if(isset($this->lat1)){
                $this->lat1=$this->to_rads*$this->lat1;
            }

            if(isset($this->lat2)){
                $this->lat2=$this->to_rads*$this->lat2;
            }

            if(isset($this->alpha)){
                $this->alpha=$this->to_rads*$this->alpha;
            }
            
            
        }

    }

    /**
     * convert from tmerc to utm+zone after parseWkt
     * @return [type] [description]
     */
    protected function applyWKTUtmFromTmerc(){
            // 'UTM Zone 15', 
            //  WGS_1984_UTM_Zone_17N, 
            // 'Lao_1997_UTM_48N' 
            // 'UTM Zone 13, Southern Hemisphere'
            // 'Hito XVIII 1963 / UTM zone 19S'
            // 'ETRS89 / ETRS-TM26' EPSG:3038 (UTM 26)
            $srsCode=strtolower(str_replace('_', ' ', $this->srsCode));
            if(strpos($srsCode, "utm zone ") !==false||strpos($srsCode, "lao 1997 utm ") !==false||strpos($srsCode, "etrs-tm") !==false){

                $srsCode=str_replace('-tm', '-tm ', $srsCode); //'ETRS89 / ETRS-TM26' ie: EPSG:3038 (UTM 26)

                $zoneStr=substr($srsCode, strrpos($srsCode , ' ')+1);
                $zlen=strlen($zoneStr);
                if($zoneStr{$zlen-1}=='n'){
                    $zoneStr=substr($zoneStr,0,-1);
                }elseif($zoneStr{$zlen-1}=='s'){
                    // EPSG:2084 has Hito XVIII 1963 / UTM zone 19S
                    $zoneStr=substr($zoneStr,0,-1);
                    $this->utmSouth=true;
                }
                $this->zone = intval($zoneStr, 10);
                $this->projName = "utm";
                if(!isset($this->utmSouth)){
                    $this->utmSouth=false;
                }
            }
    }




    /**
     * Function: parseDefs
     * Parses the PROJ.4 initialization string and sets the associated properties.
     *
     */
    public function parseDefs()
    {
        if(!isset($this->defData)){
            // allow wkt to define defData, and not be overwritten here.
            $this->defData = $this->proj4php->getDef($this->srsCode);
        }

        if ( ! $this->defData) {
            return;
        }

        $paramArray = explode("+", $this->defData);

        for ($prop = 0; $prop < sizeof($paramArray); $prop++) {
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
                case "datum": $this->datumCode = trim($paramVal);
                    break;
                case "nadgrids": $this->nagrids = trim($paramVal);
                    break;
                case "ellps": $this->ellps = trim($paramVal);
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
                    $this->rf = floatval( paramVal);
                    break;
                case "lat_0":
                    // phi0, central latitude
                    $this->lat0 = floatval($paramVal) * Common::D2R;
                    break;
                case "lat_1":
                    //standard parallel 1
                    $this->lat1 = floatval($paramVal) * Common::D2R;
                    break;
                case "lat_2":
                    //standard parallel 2
                    $this->lat2 = floatval($paramVal) * Common::D2R;
                    break;
                case "lat_ts":
                    // used in merc and eqc
                    $this->lat_ts = floatval($paramVal) * Common::D2R;
                    break;
                case "lon_0":
                    // lam0, central longitude
                    $this->long0 = floatval($paramVal) * Common::D2R;
                    break;
                case "alpha":
                    $this->alpha = floatval($paramVal) * Common::D2R;
                    //for somerc projection
                    break;
                case "lonc":
                    //for somerc projection
                    $this->longc = floatval($paramVal) * Common::D2R;
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
                    $this->datum_params = explode( ",", $paramVal);
                    break;
                case "to_meter":
                    // cartesian scaling
                    $this->to_meter = floatval($paramVal);
                    break;
                case "from_greenwich":
                    $this->from_greenwich = floatval($paramVal) * Common::D2R;
                    break;
                case "pm":
                    // DGR 2008-07-09 : if pm is not a well-known prime meridian take
                    // the value instead of 0.0, then convert to radians
                    $paramVal = trim($paramVal);

                    $this->from_greenwich = 
                        $this->proj4php->hasPrimeMeridian($paramVal)
                        ? $this->proj4php->getPrimeMeridian($paramVal)
                        : floatval($paramVal);

                    $this->from_greenwich *= Common::D2R; 
                    break;
                case "axis":
                    // DGR 2010-11-12: axis
                    $paramVal = trim($paramVal);
                    $legalAxis = "ewnsud";
                    if (strlen(paramVal) == 3 &&
                        strpos($legalAxis, substr($paramVal, 0, 1)) !== false &&
                        strpos($legalAxis, substr($paramVal, 1, 1)) !== false &&
                        strpos($legalAxis, substr($paramVal, 2, 1)) !== false
                    ) {
                        $this->axis = $paramVal;
                    } //FIXME: be silent ?

                    break;
                case "no_defs":
                    break;
                default:
                    //alert("Unrecognized parameter: " . paramName);
            } // switch()
        } // for paramArray

        $this->deriveConstants();
    }

    /**
     * Function: deriveConstants
     * Sets several derived constant values and initialization of datum and ellipse parameters.
     *
     */
    public function deriveConstants()
    {
        if (isset($this->nagrids) && $this->nagrids == '@null') {
            $this->datumCode = 'none';
        }

        if (isset($this->datumCode) && $this->datumCode != 'none') {
            $datumDef = $this->proj4php->getDatum($this->datumCode);
            if (is_array($datumDef)) {
                $this->datum_params = array_key_exists('towgs84', $datumDef) ? explode(',', $datumDef['towgs84']) : null;
               
               if(!isset($this->ellps)){ 
                    //in the case of SR-ORG:7191, proj for defines  +datum=wgs84, but also +ellps=krass. this would have overwriten that ellipsoid
                    $this->ellps = $datumDef['ellipse'];
                }
                $this->datumName = array_key_exists('name', $datumDef) ? $datumDef['name'] : $this->datumCode;
            }
        }

        // Do we have an ellipsoid?
        if (!isset($this->a)) {
            if ( ! isset($this->ellps) || strlen($this->ellps) == 0 || ! $this->proj4php->hasEllipsoid($this->ellps)) {
                $ellipse = $this->proj4php->getEllipsoid('WGS84');
            } else {
                $ellipse = $this->proj4php->getEllipsoid($this->ellps);
            }

            Proj4php::extend($this, $ellipse);
        }

        if (isset($this->rf) && !isset($this->b)&&$this->rf!=0) { // SR-ORG:28 division by 0

            $this->b = (1.0 - 1.0 / $this->rf) * $this->a;
        }

        // rf is a floatval to ===0 fails // SR-ORG:28
        if ((isset($this->rf) && $this->rf == 0) || abs($this->a - $this->b) < Common::EPSLN) {
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

        if ( ! isset($this->k0)) {
            // default value
            $this->k0 = 1.0;
        }

        // DGR 2010-11-12: axis
        if (!isset($this->axis)) {
            $this->axis = "enu";
        }

        $this->datum = new Datum($this);
    }
}
