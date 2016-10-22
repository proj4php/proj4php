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
     * The supplied Spatial Reference System (SRS) code supplied
     * on creation of the projection.
     */
    public $srsCode;


    public $to_meter = 1.0;
    
    public $sphere = false;

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
        //$this->srsCodeInput = $srsCode;
        $this->proj4php = $proj4php;




        // Check to see if $this is a Well Known Text (WKT) string.
        // This is an old, deprecated format, but still used.
        // CHECKME: are these the WKT "objects"? If so, we probably
        // need to check the string *starts* with these names.

        if (preg_match('/(GEOGCS|GEOCCS|PROJCS|LOCAL_CS)/', $srsCode)) {
            $this->to_rads=COMMON::D2R;



            $params=Wkt::Parse($srsCode);

            // TODO: find a better way to apply wkt params to this instance
            foreach($params as $k=>$v){
                $this->$k=$v;
            }


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

        if(strpos($srsCode,'+proj')===0){

            $this->defData=$srsCode;
            $this->parseDefs();
            $this->loadProjCode($this->projName);
            $this->initTransforms();

            return;

        }elseif (strpos($srsCode, 'urn:') === 0) {
            // DGR 2008-08-03 : support urn and url
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
     */
    public function defsLoaded()
    {
        $this->parseDefs();

        $this->loadProjCode($this->projName);
    }

    /**
     * Function: checkDefsLoaded
     * $this is the loadCheck method to see if the def object exists
     */
    public function checkDefsLoaded()
    {
        return $this->proj4php->hasDef($this->srsCode) && $this->proj4php->getDef($this->srsCode) != '';
    }

    /**
     * Function: defsFailed
     * Report an error in loading the defs file, but continue on using WGS84
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
     * Report an error in loading the proj file.  Initialization of the Proj
     * object has failed and the readyToUse flag will never be set.
     */
    public function loadProjCodeFailure($projName)
    {
        Proj4php::reportError("failed to find projection file for: (".gettype($projName).")" . $projName);
        //TBD initialize with identity transforms so proj will still work?
    }

    /**
     * Function: checkCodeLoaded
     * $this is the loadCheck method to see if the projection code is loaded
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
     * @param type $pt
     * @return type
     */
    public function inverse(Point $pt)
    {
        return $this->projection->inverse($pt);
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
                case "geoidgrids":
                    $this->geoidgrids = trim($paramVal);
                    break;
                case "nadgrids": $this->nagrids = trim($paramVal);
                    break;
                case "ellps":
                    $this->ellps = trim($paramVal);
                    if ($this->ellps=='WGS84' && !isset($this->datumCode))
                       $this->datumCode = trim($paramVal);
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
                    $this->rf = floatval($paramVal);
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
                    if (strlen($paramVal) == 3 &&
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

/************************************************************************/
/*                          pj_gridinfo_load()                          */
/*                                                                      */
/*      This function is intended to implement delayed loading of       */
/*      the data contents of a grid file.  The header and related       */
/*      stuff are loaded by pj_gridinfo_init().                         */
/************************************************************************/

    public function gridinfo_load( $name )
    {
        
    }

/************************************************************************/
/*                          pj_gridinfo_init()                          */
/*                                                                      */
/*      Open and parse header details from a datum gridshift file       */
/*      returning a list of PJ_GRIDINFOs for the grids in that          */
/*      file.  This superceeds use of nad_init() for modern             */
/*      applications.                                                   */
/************************************************************************/
    public function gridinfo_init($gridname)
    {
        // open the file
        // look for a header to determine file type
        // determine file type (ntv1 ntv2 gtx ctablev2 ctable)
        // return result
    }

/************************************************************************/
/*                       pj_gridlist_merge_grid()                       */
/*                                                                      */
/*      Find/load the named gridfile and merge it into the              */
/*      last_nadgrids_list.                                             */
/************************************************************************/
    public function gridlist_merge_gridfile($gridfile)
    {
        return gridinfo_init($gridfile);
    }

/************************************************************************/
/*                     pj_gridlist_from_nadgrids()                      */
/*                                                                      */
/*      This functions loads the list of grids corresponding to a       */
/*      particular nadgrids string into a list, and returns it.  The    */
/*      list is kept around till a request is made with a different     */
/*      string in order to cut down on the string parsing cost, and     */
/*      the cost of building the list of tables each time.              */
/************************************************************************/
    public function gridlist_from_nadgrids($nadgrid)
    {
        $list = array();
        $grids = split($nadgrid,",");
        foreach($grids as $grid)
        {
           //$list[] = gridlist_merge_gridfile($grid);
        }
        return $list;
    }

/************************************************************************/
/*                        pj_apply_vgridshift()                         */
/*                                                                      */
/*      This implmentation takes uses the gridlist from a coordinate    */
/*      system definition.  If the gridlist has not yet been            */
/*      populated in the coordinate system definition we set it up      */
/*      now.                                                            */
/* Parameters :                                                         */
/*    listname : nadgrid filename                                       */
/* Return : Point                                                       */
/************************************************************************/
    public function apply_vgridshift($listname,$inverse,Point $point)
    {
        $gridlist = gridlist_from_nadgrids($listname);

        foreach($gridlist as $grid)
        {
          // skip tables that don't match our point at all

          // check if a child apply

          // load the grid shift info if we dont have it

          // interpolation within the grid

          // nodata ?

          // error cases
        }

        return $point;
    }

/************************************************************************/
/*                        pj_apply_gridshift_2()                        */
/*                                                                      */
/*      This implmentation takes uses the gridlist from a coordinate    */
/*      system definition.  If the gridlist has not yet been            */
/*      populated in the coordinate system definition we set it up      */
/*      now.                                                            */
/* Parameters :                                                         */
/*    listname : nadgrid filename                                       */
/* Return : Point or null in case of error                              */
/************************************************************************/    
    public apply_gridshift_2($listname,$inverse,$point)
    {
      if (isset($this->catalog_name))
      {
        throw(new Exception("Catalog Name is not implemented for now"));
        return $this->gc_apply_gridshift($inverse,$point);
      }
      if (!isset($this->gridlist))
      {
        $this->gridlist = $this->gridlist_from_nadgrids($listname);
        if ($this->gridlist == null || sizeof($this->gridlist)==0)
          return null;
      }

      $point = $this->apply_gridshift_3($point);

      return $point;
    }

/************************************************************************/
/*                        pj_apply_gridshift_3()                        */
/*                                                                      */
/*      This is the real workhorse, given a gridlist.                   */
/************************************************************************/
    public apply_gridshift_3($point)
    {
      foreach($this->gridlist as $grid)
      {
        // skip tables that don't match

        // check childs

        // load the grid shift

        // calculate output
        // $point = $this->nad_cvt($point,$inverse,$grid);

        // check errors
      }
      return $point;
    }

    public function nad_intr($point,$grid)
    {
      return $point;
    }

    public function nad_cvt($point,$inverse,$grid)
    {
      // TODO using nad_intr

      return $point;
    }

    public function gc_apply_gridshift($inverse,$point)
    {
      // catalog grid shift to do
      return $point;
    }

    public function gc_apply_gridshift($inverse,$point)
    {
      if (!isset($this->catalog)) {
        $this->catalog = $this->gc_findcatalog($this->catalog_name);
      }
      // make sure we have appropriate "after" shift file available
      // using $this->gc_findgrid($point);
      // using $this->gridinfo_load($name)
      // $this->nad_cvt($point,$inverse,$grid)
      // check errors

      // make sure we have appropriate "before" shift file available
      // using $this->gc_findgrid($point) again
      // $this->gridinfo_load($name)
      // $this->nad_cvt($point,$inverse,$grid)
      // check errors

      // maths
      return $point;
    }

    public function gc_findgrid($point)
    {
      // using $this->gridlist_from_nadgrids($grid_definition);
      // return grid;
    }
}
