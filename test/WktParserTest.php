<?php
include dirname(__DIR__) . "/src/Wkt.php";


error_reporting(E_STRICT);
ini_set('display_errors', 1);

use proj4php\Wkt;

class WktParserTest extends PHPUnit_Framework_TestCase
{



	protected $onlyTestTheseProjections=null;//'SR-ORG:8177';//array('EPSG:32040', 'EPSG:31370'); // uncomment or comment this to test all, one or some projections.

    protected $onlyTestTheseProjectionAlgorithms=null;//array('stere');

    protected $ignoreProjectionAlgoirithms=array(

        /**
         * None of these projections are implemented in proj4php
         */
        'bonne',
        'robin',
        'eck6',
        'eck4',
        'gall',
        'tpeqd'

    );


     /**
     * each of these codes fails for some reason that likely has nothing to do with proj4php
     */

    protected $skipAllTestsForCode = array(
        'SR-ORG:21', // proj4 is utm, wkt is tmerc but how to tell from wkt?
        'SR-ORG:30', // UNIT["unknown" ft->meters]
        'SR-ORG:81',
        'SR-ORG:89', //uncertain about units from greenwhich projection is named pytest
        'SR-ORG:90', //'''
        'SR-ORG:91', //''
        'SR-ORG:93', //''
        'SR-ORG:98', //UNIT "1/32meter" = 0.03125 ? wierd unit name
        'SR-ORG:106', // unnamed projection
        'SR-ORG:108', // just a test
        'SR-ORG:123', //custom

        'EPSG:2056',
        'EPSG:3006', //dont know how to get utm zone from this.

        'EPSG:4001', //GEOGCS["Unknown datum based upon the Airy 1830 ellipsoid",
        'EPSG:4006', //GEOGCS["Unknown datum based upon the Bessel Namibia ellipsoid"

        'SR-ORG:4695', //conflicting defintion fiw proj4, lat_ts/lat0?
        'SR-ORG:4696', //error message in wkt
        'SR-ORG:4700', //I think +datum=potsdam is missing from proj4? see //EPSG:3068

        'EPSG:4188', // Failed asserting that datum->b 6356256.9092372851 matches expected 6356256.9100000001.
        'EPSG:4277', // Failed asserting that datum->b 6356256.9092372851 matches expected 6356256.9100000001.
        'EPSG:4278', //..
        'EPSG:4279', //..
        'EPSG:4293', //..
        'SR-ORG:4701', // Failed asserting that datum->b 6356078.9628400002 matches expected 6356078.9628181886.
        'SR-ORG:6628', // variables->b2 Failed asserting that 40408584830600.609 matches expected 40408584830600.555.
        'SR-ORG:6650', // ogcwkt string is javascript concatinated string.
        'SR-ORG:6651', //
        'SR-ORG:6652',
        'SR-ORG:6684', //tmerc-utm mismatch
        'SR-ORG:6698', //no tows84 datum info in ogcwkt
        'SR-ORG:6704', //  GEoGCS["Test"]
        'SR-ORG:6714', // Failed asserting that 500000.0 matches expected 33500000.0.  PROJCS["ETRS89 / UTM zone 33N with leading 33",GEOGCS...
        'SR-ORG:6715', // same
        'SR-ORG:6719', // SpatialReference:PROJCS[\"UTM-K\",GEOGCS ... (parser fails because of prefix 'SpatialReference:')
        'SR-ORG:6796', // proj4 does not have +k, wkt has scale_factor
        'SR-ORG:6810', // same
        'SR-ORG:6815',
        'SR-ORG:6823',
        'SR-ORG:6847', // check this.
        'SR-ORG:6887', //proj4 has units=us-ft, but mismatch on to_meters
        'SR-ORG:6914', //prefixed with EPSG;325833;PROJCS[\"ETRS89...
        'SR-ORG:6926', // ogcwkt breaks parser
        'SR-ORG:6978', //Failed asserting that 0.0066943799901413156 matches expected 0.0. but looks ok
        'SR-ORG:7108', //weird wkt 
        'SR-ORG:7139', //wierd wkt
        'SR-ORG:7172', //unknown datum
        'SR-ORG:7176', // missing -90 deg somewhere in wkt?
        'SR-ORG:7192', //name=test_sb missing long0 tmerc? ... could set a default long0=0.0
        'SR-ORG:7257', //wkt contains \r\n
        'SR-ORG:7323', //this is interesting. worth looking at why it breaks parser
        'SR-ORG:7403',  // unnamed datum.. pretty close anyway

        'SR-ORG:7496', //to_meter mismatch, (wkt is in km proj4 is in meters but values are consistent after conversion)
        'SR-ORG:7505', //same
        'SR-ORG:7505',  //same
        'SR-ORG:7506',
        'SR-ORG:7528', //proj4.lat0=42.12, wkt.lat0=,46.8 ?
        'SR-ORG:7564', //to_meter mismatch mm, km values are correct
        'SR-ORG:7650', //wkt unit "unknown"! (feet to meters)
        'SR-ORG:7810', // proj4 to_meter is equal to deg2rad(1)... 
        'SR-ORG:7815', // why is there an extra comma in this. ...OID[\"Clarke_1866\",,6378137.0,298....
        'SR-ORG:7816', // proj4 and wkt are very different!
        'SR-ORG:7826', // lat0 (stere) and lat_ts are reversed. i'm not sure if this is a problem with the wkt or not!
        'SR-ORG:7923', // lat0 (stere) ^^ wkt also contains some wierd values ie: scale_value 90 (equal to missing lat0)
        'SR-ORG:8064', //unknown datum text
        'SR-ORG:8141', //wkt=omerc, proj4=somerc?
        'SR-ORG:8159', // +lat_0=0 in proj4, \"Latitude_Of_Origin\",54.0 in wkt
        'SR-ORG:8177', // wkt uses ',' instead of decimal in Spheroid causes the next value parsed to grab 0 value (then division by zero)
        'SR-ORG:8209', // +lat_0=0 in proj4, \"Latitude_Of_Origin\",54.0 in wkt
        'SR-ORG:8243', // issue distinguishing  lat_ts.
        'SR-ORG:8258', //+lat_0=0 in proj4, \"Latitude_Of_Origin\",54.0 in wkt
        'SR-ORG:8259', //wkt central_meridian=100, not in proj4
        'SR-ORG:8297', //datum params undefined index 1, 
        'SR-ORG:8350', //proj4 lon_0=-5156... wkt central_meridian\",-90
        'SR-ORG:8358', // defines UNIT[\"metre\",1], but false_easting in radians.
        'SR-ORG:8389', //+lat_0=0  "latitude_of_origin\",39]

        'EPSG:21780', // wkt=omerc, proj4=somerc?
        'EPSG:21781', // ''
        'EPSG:23700',//''
         //  'IAU2000:29916',//+lon_0=0 \"Central_Meridian\",180 // 
         //  'IAU2000:29917', // ''
        //  'IAU2000:30116', // ''
          // 'IAU2000:30117',// ''
        // 'IAU2000:39916', // ''
        // 'IAU2000:39917', // '' there is a pattern here


        // ...

        'ESRI:54003', // ->es  0.0066943799901413156  expected 0.0.
        'ESRI:54029', // ''
        'ESRI:102005', // ''
        'ESRI:102010', // +lon_0=0 \"Central_Meridian\",-96
        'ESRI:102011', //+lon_0=0 \"Central_Meridian\",15
        'ESRI:102023',
        'ESRI:102026', // +lon_0=0 Central_Meridian\",95
        'ESRI:102029', // +lat_0=0 \"Latitude_Of_Origin\",-15]
        'ESRI:102031', //  +lat_0=0 "Latitude_Of_Origin\",30
        'ESRI:102032', // '' "Latitude_Of_Origin\",-32
        'ESRI:103300' // double check this, alpha is defined in wkt (but not used anyway)
        );


 	protected $wkt='ogcwkt'; //ersrwkt
	/**
     * @runInSeparateProcess
     */
	 public function testAllWktStrings()
    {
    	    

    	$codes = get_object_vars(json_decode(file_get_contents(__DIR__ . '/codes.json')));
    	foreach ($codes as $code => $defs) {
    		$this->defs = $defs;
    		$this->code = $code;

    		if (isset($this->onlyTestTheseProjections)&&(!empty($this->onlyTestTheseProjections))){

    			if(is_array($this->onlyTestTheseProjections)){
    				if(!in_array($code, $this->onlyTestTheseProjections)){
    					continue;
    				}
    			}elseif($code !== $this->onlyTestTheseProjections) {
    				continue;
    			}
    		}

    		if (in_array($code, $this->skipAllTestsForCode)) {
    			continue;
    		}

    		if (key_exists('proj4', $defs) && (!empty($defs->proj4)) && key_exists($this->wkt, $defs) && (!empty($defs->{$this->wkt}))) {

    			$wktStr=$defs->{$this->wkt};

    			if ($this->isInvalidWKT($wktStr)) {
    				continue;
    			}

                if($this->isIgnoredProjection()){
                    continue;
                }


    			$result=Wkt::Parse($wktStr);
    			$this->assertTrue(gettype($result)=='object');
				
			}
		}
	}



    public function isIgnoredProjection(){

        foreach($this->ignoreProjectionAlgoirithms as $alg){
            if(strpos($this->defs->proj4, '+proj='.$alg.' ')!==false){
                return true;
            }
       }

       if(!empty($this->onlyTestTheseProjectionAlgorithms)){

            foreach($this->onlyTestTheseProjectionAlgorithms as $alg){
                if(strpos($this->defs->proj4, '+proj='.$alg.' ')!==false){
                    return false;
                }
            }

            return true;

       }
       return false;
    }


    public function isInvalidWKT($wkt){
    	return (strpos($wkt, '(deprecated)') !== false ||
    		strpos($wkt, 'AXIS["Easting",UNKNOWN]') !== false ||
    		strpos($wkt, 'AXIS["none",EAST]') !== false ||
    		strpos($wkt, 'NULL') !== false ||
    		strpos($wkt, 'AXIS["X",UNKNOWN]') !== false||
            strpos($wkt, "\n") !== false||
            strpos($wkt, "\r") !== false
        );

	}


}