<?php
/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4js from Mike Adair madairATdmsolutions.ca
 *                      and Richard Greenwood rich@greenwoodmap.com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */

class Proj4phpProj
{
  var $proj4php = null;
  
  /**
   * Import from Ellipse
   */
  var $a;
  var $b;
  var $rf;
  var $ellipseName;

  /**
   * Property: readyToUse
   * Flag to indicate if initialization is complete for $this Proj object
   */
  var $readyToUse = false;  
  
  /**
   * Property: title
   * The title to describe the projection
   */
  var $title = null;
  
  /**
   * Property: projName
   * The projection class for $this projection, e.g. lcc (lambert conformal conic,
   * or merc for mercator).  These are exactly equivalent to their Proj4 
   * counterparts.
   */
  var $projName= null;
  
  /**
   * Property: projection
   * The projection object for $this projection. */
  public $projection = null;
  
  /**
   * Property: units
   * The units of the projection.  Values include 'm' and 'degrees'
   */
  var $units= null;
  
  /**
   * Property: datum
   * The datum specified for the projection
   */
  var $datum= null;
  /**
   * Property: x0
   * The x coordinate origin
   */
  var $x0= 0;
  /**
   * Property: y0
   * The y coordinate origin
   */
  var $y0= 0;
  /**
   * Property: localCS
   * Flag to indicate if the projection is a local one in which no transforms
   * are required.
   */
  var $localCS= false;

  /**
   * Constructor: initialize
   * Constructor for $this->proj4php->Proj objects
  *
  * Parameters:
  * $srsCode - a code for map projection definition parameters.  These are usually
  * (but not always) EPSG codes.
  */
  function Proj4phpProj($srsCode,$proj4php) {
	  $this->proj4php = &$proj4php;
      $this->srsCodeInput = $srsCode;
  
      if ($srsCode=='') return;
  
      //check to see if $this is a WKT string
      if ((strpos($srsCode,'GEOGCS') !== false) ||
          (strpos($srsCode,'GEOCCS') !== false) ||
          (strpos($srsCode,'PROJCS') !== false) ||
          (strpos($srsCode,'LOCAL_CS') !== false)) {
            $this->parseWKT($srsCode);
            $this->deriveConstants();
            $this->loadProjCode($this->projName);
            return;
      }
      
      // DGR 2008-08-03 : support urn and url
      if (strpos($srsCode,'urn:') === 0) {
          //urn:ORIGINATOR:def:crs:CODESPACE:VERSION:ID
          $urn = explode(':',$srsCode);
          if (($urn[1] == 'ogc' || $urn[1] =='x-ogc') &&
              ($urn[2] =='def') &&
              ($urn[3] =='crs')) {
              $srsCode = $urn[4].':'.$urn[strlen($urn)-1];
          }
      } else if (strpos($srsCode,'http://') === 0) {
          //url#ID
          $url = explode('#',$srsCode);
          if (preg_match("/epsg.org/",$url[0])) {
            // http://www.epsg.org/#
            $srsCode = 'EPSG:'.$url[1];
          } else if (preg_match("/RIG.xml/",$url[0])) {
            //http://librairies.ign.fr/geoportail/resources/RIG.xml#
            //http://interop.ign.fr/registers/ign/RIG.xml#
            $srsCode = 'IGNF:'.$url[1];
          }
      }
      $this->srsCode = strtoupper($srsCode);
      if (strpos($this->srsCode,"EPSG") === 0) {
          $this->srsCode = $this->srsCode;
          $this->srsAuth = 'epsg';
          $this->srsProjNumber = substr($this->srsCode,5);
      // DGR 2007-11-20 : authority IGNF
      } else if (strpos($this->srsCode,"IGNF") === 0) {
          $this->srsCode = $this->srsCode;
          $this->srsAuth = 'IGNF';
          $this->srsProjNumber = substr($this->srsCode,5);
      // DGR 2008-06-19 : pseudo-authority CRS for WMS
      } else if (strpos($this->srsCode,"CRS") === 0) {
          $this->srsCode = $this->srsCode;
          $this->srsAuth = 'CRS';
          $this->srsProjNumber = substr($this->srsCode,4);
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
    function loadProjDefinition() {
      //check in memory
      if (array_key_exists($this->srsCode,$this->proj4php->defs)) {
        $this->defsLoaded();
        return;
      }
      //else check for def on the server
      $filename = dirname(__FILE__). '/defs/' . strtoupper($this->srsAuth) . $this->srsProjNumber . '.php';
      if ($this->proj4php->loadScript($filename))
			$this->defsLoaded(); // succes
	  else
			$this->loadFromService(); // fail
    }

/**
 * Function: loadFromService
 *    Creates the REST URL for loading the definition from a web service and 
 *    loads it.
 *
 *
 * DO IT AGAIN. : SHOULD PHP CODE BE GET BY WEBSERVICES ?
 */
    function loadFromService() {
      //else load from web service
      $url = $this->proj4php->defsLookupService .'/' . $this->srsAuth .'/'. $this->srsProjNumber . '/proj4js/';
	  
	  if (!$this->proj4php->loadScript($url))
		$this->defsFailed();
    }

/**
 * Function: defsLoaded
 * Continues the Proj object initilization once the def file is loaded
 *
 */
    function defsLoaded() {
      $this->parseDefs();
      $this->loadProjCode($this->projName);
    }
    
/**
 * Function: checkDefsLoaded
 *    $this is the loadCheck method to see if the def object exists
 *
 */
    function checkDefsLoaded() {
      if ($this->proj4php->defs[$this->srsCode]) {
        return true;
      } else {
        return false;
      }
    }

 /**
 * Function: defsFailed
 *    Report an error in loading the defs file, but continue on using WGS84
 *
 */
   function defsFailed() {
      $this->proj4php->reportError('failed to load projection definition for: '.$this->srsCode);
      $this->proj4php->defs[$this->srsCode] = $this->proj4php->defs['WGS84'];  //set it to something so it can at least continue
      $this->defsLoaded();
    }

/**
 * Function: loadProjCode
 *    Loads projection class code dynamically if required.
 *     Projection code may be included either through a script tag or in
 *     a built version of proj4php
 *
 * An exception occurs if the projection is not found.
 */
    function loadProjCode($projName) {
      if (array_key_exists($projName,$this->proj4php->proj)) {
        $this->initTransforms();
        return;
      }
      //the filename for the projection code
      $filename = dirname(__FILE__).'/projCode/'.$projName. '.php';
	  if ($this->proj4php->loadScript($filename))
	  {
		$this->loadProjCodeSuccess($projName);
	  }
	  else
	  {
		$this->loadProjCodeFailure($projName);
	  }
    }

 /**
 * Function: loadProjCodeSuccess
 *    Loads any proj dependencies or continue on to final initialization.
 *
 */
    function loadProjCodeSuccess($projName) {
      if ($this->proj4php->proj[$projName]->dependsOn){
        $this->loadProjCode($this->proj4php->proj[$projName]->dependsOn);
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
    function loadProjCodeFailure($projName) {
      $this->proj4php->reportError("failed to find projection file for: " . $projName);
      //TBD initialize with identity transforms so proj will still work?
    }
    
/**
 * Function: checkCodeLoaded
 *    $this is the loadCheck method to see if the projection code is loaded
 *
 */
    function checkCodeLoaded($projName) {
      if ($this->proj4php->proj[$projName]) {
        return true;
      } else {
        return false;
      }
    }

/**
 * Function: initTransforms
 *    Finalize the initialization of the Proj object
 *
 */
    function initTransforms()
	{
	  $this->projection = clone($this->proj4php->proj[$this->projName]);
	  foreach($this as $key=>$value)
	  {
			$this->projection->$key = $value;
	  }
	  
      $this->init();
      $this->readyToUse = true;
	}

	function init()
	{
		$this->projection->init();
	}
	
	function forward($pt)
	{
		return $this->projection->forward($pt);
	}
	
	function inverse($pt)
	{
		return $this->projection->inverse($pt);
	}
  
  
/**
 * Function: parseWKT
 * Parses a WKT string to get initialization parameters
 *
 */
 var $wktRE =  '/^(\w+)\[(.*)\]$/';

 function parseWKT($wkt) {
    $match = preg_match($this->wktRE,$wkt,$wktMatch);
	
    if (!$match) return;
    $wktObject = $wktMatch[1];
    $wktContent = $wktMatch[2];
    $wktTemp = explode(",",$wktContent);
    $wktName = array_shift($wktTemp);
    $wktName = preg_replace('/^\"/',"",$wktName);
    $wktName = preg_replace('/\"$/',"",$wktName);
    
    /*
    $wktContent = implode(",",$wktTemp);
    $wktArray = explode("],",$wktContent);
    for ($i=0; i<sizeof($wktArray)-1; ++$i) {
      $wktArray[$i] .= "]";
    }
    */
    
    $wktArray =array();
    $bkCount = 0;
    $obj = "";
    for ($i=0; i<sizeof($wktTemp); ++$i) {
      $token = $wktTemp[i];
      for ($j=0; $j<strlen($token); ++$j) {
        if ($token[$j] == "[") ++$bkCount;
        if ($token[$j] == "]") --$bkCount;
      }
      $obj .= $token;
      if ($bkCount === 0) {
        array_push($wktArray,$obj);
        $obj = "";
      } else {
        $obj .= ",";
      }
    }
    
    //do something based on the type of the wktObject being parsed
    //add in variations in the spelling as required
    switch ($wktObject) {
      case 'LOCAL_CS':
        $this->projName = 'identity';
        $this->localCS = true;
        $this->srsCode = $wktName;
        break;
      case 'GEOGCS':
        $this->projName = 'longlat';
        $this->geocsCode = $wktName;
        if (!$this->srsCode) $this->srsCode = $wktName;
        break;
      case 'PROJCS':
        $$this->srsCode = $wktName;
        break;
      case 'GEOCCS':
        break;
      case 'PROJECTION':
        $this->projName = $this->proj4php->wktProjections[$wktName];
        break;
      case 'DATUM':
        $this->datumName = $wktName;
        break;
      case 'LOCAL_DATUM':
        $this->datumCode = 'none';
        break;
      case 'SPHEROID':
        $this->ellps = $wktName;
        $this->a = (array_shift($wktArray));//floatval(array_shift($wktArray));
        $this->rf = (array_shift($wktArray));//floatval(array_shift($wktArray));
        break;
      case 'PRIMEM':
        $this->from_greenwich = (array_shift($wktArray));//floatval(array_shift($wktArray)); //to radians?
        break;
      case 'UNIT':
        $this->units = $wktName;
        $this->unitsPerMeter = (array_shift($wktArray));//floatval(array_shift($wktArray));
        break;
      case 'PARAMETER':
        $name = strtolower($wktName);
        $value = (array_shift($wktArray));//floatval(array_shift($wktArray));
        //there may be many variations on the wktName values, add in case
        //statements as required
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
            $this->long0 = $value*$this->proj4php->common->D2R;
            break;
          case 'latitude_of_origin':
            $this->lat0 = $value*$this->proj4php->common->D2R;
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
        $name= strtolower($wktName);
        $value= array_shift($wktArray);
        switch ($value) {
          case 'EAST' : $value= 'e'; break;
          case 'WEST' : $value= 'w'; break;
          case 'NORTH': $value= 'n'; break;
          case 'SOUTH': $value= 's'; break;
          case 'UP'   : $value= 'u'; break;
          case 'DOWN' : $value= 'd'; break;
          case 'OTHER':
          default     : $value= ' '; break;//FIXME
        }
        if (!$this->axis) { $this->axis= "enu"; }
        switch($name) {
          case 'X': $this->axis=                         $value . substr($this->axis,1,2); break;
          case 'Y': $this->axis= substr($this->axis,0,1) . $value . substr($this->axis,2,1); break;
          case 'Z': $this->axis= substr($this->axis,0,2) . $value                        ; break;
          default : break;
        }
      case 'MORE_HERE':
        break;
      default:
        break;
    }
    for($i=0; $i<sizeof($wktArray); ++$i) {
      $this->parseWKT($wktArray[$i]);
    }
 }

/**
 * Function: parseDefs
 * Parses the PROJ.4 initialization string and sets the associated properties.
 *
 */
  function parseDefs() {
      $this->defData = $this->proj4php->defs[$this->srsCode];
      $paramName; $paramVal;
      if (!$this->defData) {
        return;
      }
      $paramArray=explode("+",$this->defData);
      for ($prop=0; $prop<sizeof($paramArray); $prop++)
	  {
		  if (strlen($paramArray[$prop])==0) continue;
          $property = explode("=",$paramArray[$prop]);
		  $paramName = strtolower($property[0]);
		  if (sizeof($property)>=2)
		  {
			$paramVal = $property[1];
		  }
		  switch (trim($paramName)) {  // trim out spaces
              case "": break;   // throw away nameless parameter
              case "title":  $this->title = $paramVal; break;
              case "proj":   $this->projName = trim($paramVal); break;
              case "units":  $this->units = trim($paramVal); break;
              case "datum":  $this->datumCode = trim($paramVal); break;
              case "nadgrids": $this->nagrids = trim($paramVal); break;
              case "ellps":  $this->ellps = trim($paramVal); break;
              case "a":      $this->a =  ($paramVal); break;//floatval($paramVal); break;  // semi-major radius
              case "b":      $this->b =  ($paramVal); break;//floatval($paramVal); break;  // semi-minor radius
              // DGR 2007-11-20
              case "rf":     $this->rf = ($paramVal); break;//floatval(paramVal); break; // inverse flattening rf= a/(a-b)
              case "lat_0":  $this->lat0 = $paramVal*$this->proj4php->common->D2R; break;        // phi0, central latitude
              case "lat_1":  $this->lat1 = $paramVal*$this->proj4php->common->D2R; break;        //standard parallel 1
              case "lat_2":  $this->lat2 = $paramVal*$this->proj4php->common->D2R; break;        //standard parallel 2
              case "lat_ts": $this->lat_ts = $paramVal*$this->proj4php->common->D2R; break;      // used in merc and eqc
              case "lon_0":  $this->long0 = $paramVal*$this->proj4php->common->D2R; break;       // lam0, central longitude
              case "alpha":  $this->alpha =  ($paramVal)*$this->proj4php->common->D2R; break;//floatval($paramVal)*$this->proj4php->common->D2R; break;  //for somerc projection
              case "lonc":   $this->longc = paramVal*$this->proj4php->common->D2R; break;       //for somerc projection
              case "x_0":    $this->x0 = ($paramVal); break;//floatval($paramVal); break;  // false easting
              case "y_0":    $this->y0 = ($paramVal); break;//floatval($paramVal); break;  // false northing
              case "k_0":    $this->k0 = ($paramVal); break;//floatval($paramVal); break;  // projection scale factor
              case "k":      $this->k0 = ($paramVal); break;//floatval($paramVal); break;  // both forms returned
              case "r_a":    $this->R_A = true; break;                 // sphere--area of ellipsoid
              case "zone":   $this->zone = intval($paramVal); break;  // UTM Zone
              case "south":   $this->utmSouth = true; break;  // UTM north/south
              case "towgs84": $this->datum_params = explode(",",$paramVal); break;
              case "to_meter": $this->to_meter = ($paramVal); break;//floatval($paramVal); break; // cartesian scaling
              case "from_greenwich": $this->from_greenwich = $paramVal*$this->proj4php->common->D2R; break;
              // DGR 2008-07-09 : if pm is not a well-known prime meridian take
              // the value instead of 0.0, then convert to radians
              case "pm":     $paramVal = trim($paramVal);
                             $this->from_greenwich = $this->proj4php->primeMeridian[$paramVal] ?
                                $this->proj4php->primeMeridian[$paramVal] : ($paramVal);//floatval($paramVal);
                             $this->from_greenwich *= $this->proj4php->common->D2R; 
                             break;
              // DGR 2010-11-12: axis
              case "axis":   $paramVal = trim($paramVal);
                             $legalAxis= "ewnsud";
                             if (strlen(paramVal)==3 &&
                                 strpos($legalAxis,substr($paramVal,0,1))!==false &&
                                 strpos($legalAxis,substr($paramVal,1,1))!==false &&
                                 strpos($legalAxis,substr($paramVal,2,1))!==false) {
                                $this->axis= $paramVal;
                             } //FIXME: be silent ?
                             break;
              case "no_defs": break; 
              default: //alert("Unrecognized parameter: " . paramName);
          } // switch()
      } // for paramArray
      $this->deriveConstants();
  }

/**
 * Function: deriveConstants
 * Sets several derived constant values and initialization of datum and ellipse
 *     parameters.
 *
 */
  function deriveConstants() {
      if (isset($this->nagrids) && $this->nagrids == '@null') $this->datumCode = 'none';
      if (isset($this->datumCode) && $this->datumCode && $this->datumCode != 'none') {
        $datumDef = $this->proj4php->datum[$this->datumCode];
        if ($datumDef) {
          $this->datum_params = array_key_exists('towgs84',$datumDef) ? explode(',',$datumDef['towgs84']) : null;
          $this->ellps = $datumDef['ellipse'];
          $this->datumName = array_key_exists('datumName',$datumDef) ? $datumDef['datumName'] : $this->datumCode;
        }
      }
      if (!isset($this->a)) {    // do we have an ellipsoid?
		  
	  
		  if (!isset($this->ellps) || strlen($this->ellps)==0 || !array_key_exists($this->ellps,$this->proj4php->ellipsoid))
			$ellipse = $this->proj4php->ellipsoid['WGS84'];
		  else
		  {
			$ellipse =  $this->proj4php->ellipsoid[$this->ellps];
		  }
		  
		  $this->a = $ellipse['a'];
		  $this->b = $ellipse['b'];
		  $this->ellipseName = $ellipse['ellipseName'];
		  $this->rf = $ellipse['rf'];
      }
	  
      if (isset($this->rf) && !isset($this->b)) $this->b = (1.0 - 1.0/$this->rf) * $this->a;
      if (abs($this->a - $this->b)<$this->proj4php->common->EPSLN) {
        $this->sphere = true;
        $this->b= $this->a;
      }
      $this->a2 = $this->a * $this->a;          // used in geocentric
      $this->b2 = $this->b * $this->b;          // used in geocentric
      $this->es = ($this->a2-$this->b2)/$this->a2;  // e ^ 2
      $this->e = sqrt($this->es);        // eccentricity
      if (isset($this->R_A)) {
        $this->a *= 1. - $this->es * ($this->proj4php->common->SIXTH + $this->es * ($this->proj4php->common->RA4 + $this->es * $this->proj4php->common->RA6));
        $this->a2 = $this->a * $this->a;
        $this->b2 = $this->b * $this->b;
        $this->es = 0.;
      }
      $this->ep2=($this->a2-$this->b2)/$this->b2; // used in geocentric
      if (!isset($this->k0)) $this->k0 = 1.0;    //default value
      //DGR 2010-11-12: axis
      if (!isset($this->axis)) { $this->axis= "enu"; }

      $this->datum = new Proj4phpDatum($this,$this->proj4php);
  }
}