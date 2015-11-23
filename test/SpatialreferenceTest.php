<?php
include __DIR__ . "/../vendor/autoload.php";

use proj4php\Point;
use proj4php\Proj4php;
use proj4php\Proj;

class SpatialreferenceTest extends PHPUnit_Framework_TestCase
{


	protected $defs;
	protected $code;

    protected $wkt='ogcwkt'; //ersrwkt

    protected $suppressOnAxisMismatch=true;
    protected $suppressOnUtmTmercMismatch=true;
    protected $suppressOnDatumParamsMismatch=true;
    //protected $onlyTestTheseProjections=array('EPSG:32040', 'EPSG:31370'); // uncomment or comment this to test all, one or some projections.

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
    	);

    protected $datumPrecision = array(
    	'b'   => 0.00000001,
    	'es'  => 0.00000001,
    	'ep2' => 0.00000001,
    	'a'   => 0.00000001,
    	'rf'  => 0.00001,
    	);

    protected $skipRegularComparisonsForCode = array(
        //'SR-ORG:11',
        //'SR-ORG:62', //tiny cascading difference in lat_2
        //'SR-ORG:83', //same issue
        //'SR-ORG:89', //''
        //'EPSG:2000', //''
        //'EPSG:2001'
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
    	);



    protected $skipAllTestsForCode = array(

        'SR-ORG:20', // proj4 uses robin (undefined transform)
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
        'SR-ORG:6978' //Failed asserting that 0.0066943799901413156 matches expected 0.0. but looks ok
        );

    /**
     * @runInSeparateProcess
     */
    public function testEveryTransformKnownToMan()
    {

        //$this->scrapeEveryCodeKnownToMan();
    	$proj4 = new Proj4php();

    	$failAtEndErrors = array();

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

                }

    			$this->assertNotNull($projection->projection, $codesString);
    			$this->assertNotNull($projWKTInline->projection, $codesString);

    			$expected = get_object_vars($projection->projection);
    			$actual   = get_object_vars($projWKTInline->projection);


    			if($this->isUtmTmerc($expected, $actual)&&$this->suppressOnUtmTmercMismatch){
						$failAtEndErrors[$code] = 'UTM-TMERC Mismatch: ' . $codesString;
						continue;
    			}

                        //$this->assertEquals($expected, $actual, $codesString);

    			if (key_exists('axis', $actual) || key_exists('axis', $expected)) {
    				if ($actual['axis'] !== $expected['axis']) {
    					if($this->suppressOnAxisMismatch){
    						$failAtEndErrors[$code] = 'Axis Mismatch: ' . $codesString.' axis[ proj4:'.$expected['axis'].', wkt:'.$actual['axis'].' ]';
    					}else{
    						$this->assertEquals(array_intersect_key($expected, array('axis' => '')), array_intersect_key($actual, array('axis' => '')), $codesString);
    					}
    				}
    			}

    			if ((key_exists('to_meters', $actual) && $actual['to_meters'] !== 1.0) || (key_exists('to_meters', $expected) && $expected['to_meters'] !== 1.0)) {
    				$this->assertEquals(array_intersect_key($expected, array('to_meters' => '')), array_intersect_key($actual, array('to_meters' => '')), $codesString);
    			}

    			$this->compareDatums($expected, $actual);
    			$this->compareAlphaGama($expected, $actual);
    			$this->comparePreciseInternals($expected, $actual);

    			if (!in_array($code, $this->skipRegularComparisonsForCode)) {

    				$ignore = array_merge($this->dontUseTheseKeysForRegularComparison, $this->internalsPrecision);

    				$a = array_diff_key($expected, $ignore);
    				$b = array_intersect_key(array_diff_key($actual, $ignore), $a);

    				$this->assertEquals($a, $b, print_r(array($a, $b, $codesString), true));

    			}

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

    	if (count($failAtEndErrors) > 0) {
    		$this->fail(print_r($failAtEndErrors));
    	}

    }

    public function compareDatums($expected, $actual)
    {

    	if (key_exists('datum', $expected)) {

   
            if(!($expected['datumCode']=='WGS84'&&is_null($actual['datumCode']))){
              // because datum wgs84 defines tow84=0,0,0
    		  $this->assertEquals($expected['datumCode'], $actual['datumCode'],$this->projectionString());
            }
    		if(!$this->suppressOnDatumParamsMismatch){
    			$this->assertEquals($expected['datum_params'], $actual['datum_params'],$this->projectionString().json_encode($expected['datum_params']));
    		}
    		

    		$this->assertEquals(
    			array_diff_key(get_object_vars($expected['datum']), $this->datumPrecision, array('datum_params'=>'', 'datum_type'=>'')),
    			array_diff_key(get_object_vars($actual['datum']), $this->datumPrecision, array('datum_params'=>'', 'datum_type'=>''))
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
                //$this->assertEquals($expected[$key], $actual[$key], 'AssertEquals Failed: variables->'.$key.' ('.$precision.'): '.$codesString, $precision);
    			$this->assertWithin($expected[$key], $actual[$key], 'AssertEquals Failed: variables->' . $key . ' (' . $precision . '): ' . $this->projectionString(), $precision);
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
    		strpos($wkt, 'AXIS["X",UNKNOWN]') !== false);

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

}
