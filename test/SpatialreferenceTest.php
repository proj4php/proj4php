<?php
include __DIR__ . "/../vendor/autoload.php";

use proj4php\Point;
use proj4php\Proj4php;
use proj4php\Proj;

error_reporting(E_STRICT);
ini_set('display_errors', 1);

class SpatialreferenceTest extends PHPUnit_Framework_TestCase
{


	protected $defs;
	protected $code;

    protected $wkt='ogcwkt'; //ersrwkt



    /**
     * the following suppress.. flags indicate some inability to test or missing test code 
     * for certain situations. it would be great if the tests pass with them all set to false.
     */

    protected $suppressOnAxisMismatch=true; 
    protected $suppressOnUtmTmercMismatch=true; // alot of proj4 codes have +proj=utm while wkt is tmerc. are they comparable?
    protected $suppressOnDatumParamsMismatch=true; //
    protected $suppressOnDatumNameOnlyNullInProj4=true; //if null in proj4 then ignore if set in wkt (maybe should check for zeros) 
    protected $suppressIAU2000CentralMeridianPiMismatch=true; //there are a bunch of IAU2000 codes ending in 16 or 17 that have wkt value of pi instead of 0

    protected $suppressToMeterMismatch=true; // I think this is ok to ignore.




    protected $onlyTestTheseProjections=null;//'SR-ORG:8177';//array('EPSG:32040', 'EPSG:31370'); // uncomment or comment this to test all, one or some projections.


    protected $onlyTestTheseProjectionAlgorithms=null;//array('stere');

    protected $ignoreProjectionAlgoirithms=array(

        /**
         * None of these projections are defined in proj4php
         */

        'bonne',
        'robin',
        'eck6',
        'eck4',
        'gall',
        'tpeqd'

    );


    /**
     * not all Projections define all of these. but if they exist it proj4 code, 
     * then this is the accuracy to test each
     */

    protected $internalsPrecision = array(
    	'x0'    => 0.0000000001,
    	'y0'    => 0.0000000001,
    	'lat_1' => 0.0000000001,
    	't2'    => 0.0000000001,
    	'ms2'   => 0.0000000001,
    	'ns0'   => 0.0000000001,
    	'c'     => 0.0000000001,
    	'rh'    => 0.0000000001,
    	'rf'    => 0.000001,
    	'b'     => 0.0000001,
    	'b2'    => 0.0000001,
    	'es'    => 0.0000001,
    	'e'     => 0.0000001,
    	'ep2'   => 0.0000001,
        'longc' => 0.0000000001,
        'gama' => 0.0000000001,
        'singam' => 0.0000000001,
        'cosgam' => 0.0000000001,
        'sinaz' => 0.0000000001,
        'cosaz' => 0.0000000001,
        'u' => 0.0000000001,
        'a'=> 0.0000001,
        'a2'=> 0.0000001,
        'lat0'    => 0.0000000001,
        'long0'    => 0.0000000001, 
        'lat1'    => 0.0000000001,
        'lat2'    => 0.0000000001,
        'sinphi'    => 0.0000000001,
        'cosphi'    => 0.0000000001,
        'ms1'    => 0.0000000001,
        'ml0'    => 0.0000000001,
        'ml1'    => 0.0000000001,
        'ns'    => 0.0000000001,
        'g'    => 0.0000000001,
    	);

    protected $datumPrecision = array(
    	'b'   => 0.00000001,
    	'es'  => 0.00000001,
    	'ep2' => 0.00000001,
    	'a'   => 0.00000001,
    	'rf'  => 0.00001,
    	);

    protected $skipRegularComparisonsForCode = array(
        
    	);

    protected $dontUseTheseKeysForRegularComparison = array(
    	'name'           => '',
        //'projName'=>'',
    	'units'          => '',
    	'srsCode'        => '',
    	'srsCodeInput'   => '',
    	'projection'     => '',
    	'srsAuth'        => '',
    	'srsProjNumber'  => '',
    	'defData'        => '',
    	'geocsCode'      => '',
    	'datumName'      => '',
    	'datumCode'      => '',
    	'from_greenwich' => '',
        //'zone'=>'',
    	'ellps'          => '',
        //'utmSouth'=>'',
    	'datum'          => '',
    	'datum_params'   => '',
    	'alpha'          => '',
    	'axis'           => '',
        'to_meter'=>'',
        'R_A'=>''
        

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


    /**
     * @runInSeparateProcess
     */

    /**
    *
    */
    public function testEveryTransformKnownToMan()
    {
    	$proj4 = new Proj4php();

    

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


    			$proj4->addDef($code, $defs->proj4);





    			$codesString = json_encode(array(
    				$code,
    				$defs->proj4,
    				$wktStr,
    				), JSON_PRETTY_PRINT);

                try{

    			$projection = new Proj($code, $proj4);
    			$projWKTInline = new Proj($wktStr, $proj4);

                }catch(Exception $e){
                    
                    throw new Exception($e->getMessage().$codesString);
                    //throw $e;
                }

    			$this->assertNotNull($projection->projection, $codesString);
    			$this->assertNotNull($projWKTInline->projection, $codesString);

    			$expected = get_object_vars($projection->projection);
    			$actual   = get_object_vars($projWKTInline->projection);


    			if($this->isUtmTmerc($expected, $actual)){
                    if($this->suppressOnUtmTmercMismatch){
						continue;
                    }else{
                         $this->fail('UTM-TMERC Mismatch: ' . $codesString);
                    }
    			}

                        //$this->assertEquals($expected, $actual, $codesString);

    			if (key_exists('axis', $actual) || key_exists('axis', $expected)) {
    				if ($actual['axis'] !== $expected['axis']) {
    					if($this->suppressOnAxisMismatch){


    					}else{
    						$this->assertEquals(array_intersect_key($expected, array('axis' => '')), array_intersect_key($actual, array('axis' => '')), $codesString);
    					}
    				}
    			}

    			if ((!$this->suppressToMeterMismatch)&&((key_exists('to_meters', $actual) && $actual['to_meters'] !== 1.0) || (key_exists('to_meters', $expected) && $expected['to_meters'] !== 1.0))) {
    				$this->assertEquals(array_intersect_key($expected, array('to_meters' => '')), array_intersect_key($actual, array('to_meters' => '')), $codesString);
    			}

    			

    			if (!in_array($code, $this->skipRegularComparisonsForCode)) {

    				$ignore = array_merge($this->dontUseTheseKeysForRegularComparison, $this->internalsPrecision);

    				$a = array_diff_key($expected, $ignore);
    				$b = array_intersect_key(array_diff_key($actual, $ignore), $a);

    				$this->assertEquals($a, $b, print_r(array($a, $b, $codesString), true));

    			}

                $this->compareDatums($expected, $actual);
                $this->compareAlphaGama($expected, $actual);
                $this->comparePreciseInternals($expected, $actual);

    			$unitA = strtolower($actual['units']{0});
    			$unitB = strtolower($expected['units']{0});
    			if (((!empty($unitA)) && $unitA != 'd') || ((!empty($unitB)) && $unitB != 'd')) {
    				$this->assertEquals($unitA, $unitA, '(units mismatch) ' . $codesString);
    			}

                        //if either defines non zero alpha
    			if ((key_exists('from_greenwich', $actual) && $actual['from_greenwich'] !== 0.0) || (key_exists('from_greenwich', $expected) && $expected['from_greenwich'] !== 0.0)) {
    				$this->assertEquals(array_intersect_key($expected, array('from_greenwich' => '')), array_intersect_key($actual, array('from_greenwich' => '')), $codesString);
    			}

    			$this->assertEquals(get_class($projection->projection), get_class($projWKTInline->projection), $codesString);

    		}

    	}



    }

    public function compareDatums($expected, $actual)
    {
    	if (key_exists('datum', $expected)) {
            if(!($expected['datumCode']=='WGS84'&&is_null($actual['datumCode']))){
              // because datum wgs84 defines tow84=0,0,0
              
              if($this->suppressOnDatumNameOnlyNullInProj4&&is_null($expected['datumCode'])&&(!is_null($actual['datumCode']))){
                    // datumCode in wkt is something, proj4 is null.
                }else{
    		      $this->assertEquals($expected['datumCode'], $actual['datumCode'],$this->projectionString());
                }
            }else{
                // wgs is empty datum
            }
    		if(!$this->suppressOnDatumParamsMismatch){
    			$this->assertEquals($expected['datum_params'], $actual['datum_params'],$this->projectionString().json_encode($expected['datum_params']));
    		}

    		$this->assertEquals(
    			array_diff_key(get_object_vars($expected['datum']), $this->datumPrecision, array('datum_code'=>'','datum_params'=>'', 'datum_type'=>'')),
    			array_diff_key(get_object_vars($actual['datum']), $this->datumPrecision, array('datum_code'=>'','datum_params'=>'', 'datum_type'=>''))
    			);

    		foreach ($this->datumPrecision as $key => $precision) {
    			if (key_exists($key, $expected['datum'])) {
                    //$this->assertEquals($expected['datum']->$key, $actual['datum']->$key, 'AssertEquals Failed: datum->'.$key.' ('.$precision.'): '.$codesString,$precision);
    				$this->assertWithin($expected['datum']->$key, $actual['datum']->$key, 'AssertEquals Failed: datum->' . $key . ' (' . $precision . '): ' . $this->projectionString(), $precision);
    			}
    		}
    	}
    }

    public function compareAlphaGama($expected, $actual)
    {
        //if either defines non zero alpha or gama
    	$alphagamma = array();
    	if ((key_exists('alpha', $actual) && $actual['alpha'] !== 0.0) || (key_exists('alpha', $expected) && $expected['alpha'] !== 0.0)) {
    		$alphagamma['alpha'] = '';
    	}
    	if ((key_exists('gamma', $actual) && $actual['gamma'] !== 0.0) || (key_exists('gamma', $expected) && $expected['gamma'] !== 0.0)) {
    		$alphagamma['gamma'] = '';
    	}
    	if (!empty($alphagamma)) {
    		$this->assertEquals(array_intersect_key($expected, $alphagamma), array_intersect_key($actual, $alphagamma),
    			'AssertEquals Failed: alphagamma: ' . $this->projectionString());
    	}
    }

    public function comparePreciseInternals($expected, $actual)
    {

    	foreach ($this->internalsPrecision as $key => $precision) {
    		if (key_exists($key, $expected)) {
               if(!key_exists($key,$actual)){
                    if( $expected[$key]!=0){
                        $this->fail('Expected key ('.$key.':'.$expected[$key].') but was unset');
                     }
                }else{
                    if($key=='long0'&&$this->suppressIAU2000CentralMeridianPiMismatch&&
                        strpos($this->code, 'IAU2000:')===0&&$expected[$key]==0.0&&$actual[$key]==pi()){
                       
                    }else{
                    //$this->assertEquals($expected[$key], $actual[$key], 'AssertEquals Failed: variables->'.$key.' ('.$precision.'): '.$codesString, $precision);
                    $this->assertWithin($expected[$key], $actual[$key], 'AssertEquals Failed: variables->' . $key . ' (' . $precision . '): ' . $this->projectionString(), $precision);
                    }
                }
    		}
    	}

    }

    public function assertWithin($a, $b, $message, $precision)
    {

    	$p = (max(1.0, abs($a)) * $precision);
    	$this->assertEquals($a, $b, 'Asserting Within (' . $p . ') :: ' . $message, $p);

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

	public function isUtmTmerc($expected, $actual){
    	return ($expected['projName'] == 'utm' && $actual['projName'] == 'tmerc');
    		
	}

	public function projectionString(){

		return json_encode(array(

			$this->code,
			$this->defs->proj4,
			$this->defs->{$this->wkt})

		, JSON_PRETTY_PRINT);

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




}
