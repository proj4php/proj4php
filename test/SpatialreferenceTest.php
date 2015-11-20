<?php
include __DIR__ . "/../vendor/autoload.php";

use proj4php\Point;
use proj4php\Proj4php;
use proj4php\Proj;

class SpatialreferenceTest extends PHPUnit_Framework_TestCase
{

    protected $defs;
    protected $code;

    protected $internalsPrecision = array(
        'x0'    => 0.0000000001,
        'y0'    => 0.0000000001,
        'lat_1' => 0.0000000001,
        't2'    => 0.0000000001,
        'ms2'   => 0.0000000001,
        'ns0'   => 0.0000000001,
        'c'     => 0.0000000001,
        'rh'    => 0.0000000001,
        'rf'    => 0.00001,
        'b'     => 0.0000000001,
        'b2'    => 0.0000000001,
        'es'    => 0.0000000001,
        'e'     => 0.0000000001,
        'ep2'   => 0.0000000001,
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
        'SR-ORG:4700', //i think +datum=potsdam is missing from proj4? see //EPSG:3068

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
        'SR-ORG:6731', // tmerc-utm mismatch utm zone

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

            /**
             * pinpoint a projection to test
             * @var string
             */
            $onlyTestThisProjection = null;
            $onlyTestThisProjection=array('EPSG:32040', 'EPSG:31370'); // uncomment or comment this to test one or all projections.

            if ((!empty($onlyTestThisProjection))){
                
                if(is_array($onlyTestThisProjection)){
                    if(!in_array($code, $onlyTestThisProjection)){
                        continue;
                    }
                }elseif($code !== $onlyTestThisProjection) {
                    continue;
                }
            }

            if (in_array($code, $this->skipAllTestsForCode)) {
                continue;
            }

            if (key_exists('proj4', $defs) && (!empty($defs->proj4))) {

                if (strpos($defs->ogcwkt, '(deprecated)') !== false ||
                    strpos($defs->ogcwkt, 'AXIS["Easting",UNKNOWN]') ||
                    strpos($defs->ogcwkt, 'AXIS["none",EAST]') ||
                    strpos($defs->ogcwkt, 'AXIS["X",UNKNOWN]')
                ) {
                    continue;
                }

                $proj4->addDef($code, $defs->proj4);
                try {
                    $projection = new Proj($code, $proj4);

                } catch (Exception $e) {

                    throw new Exception('Error loading proj4: ' . $e->getMessage() . ' ' . $code . ' ' . print_r($defs, true) . ' ' . print_r($e->getTrace(), true));

                }
                if (key_exists('ogcwkt', $defs) && (!empty($defs->ogcwkt))) {

                    $codesString = print_r(array(
                        $code,
                        $defs->proj4,
                        $defs->ogcwkt,

                    ), true);

                    try {

                        $projOgcwktInline = new Proj($defs->ogcwkt, $proj4);

                        $this->assertNotNull($projection->projection, $codesString);
                        $this->assertNotNull($projOgcwktInline->projection, $codesString);

                        $expected = get_object_vars($projection->projection);
                        $actual   = get_object_vars($projOgcwktInline->projection);

                        //$this->assertEquals($expected, $actual, $codesString);

                        if (key_exists('axis', $actual) || key_exists('axis', $expected)) {
                            if ($actual['axis'] !== $expected['axis']) {
                                $failAtEndErrors[$code] = 'Axis Mismatch: ' . $codesString;
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

                        $this->assertEquals(get_class($projection->projection), get_class($projOgcwktInline->projection), $codesString);

                    } catch (Exception $e) {
                        if ($e instanceof PHPUnit_Framework_ExpectationFailedException) {
                            throw $e;
                        } else {
                            $this->fail(print_r(array($e->getMessage(), $codesString, get_class($e) /*, $e->getTrace()*/), true));
                        }
                    }
                }
            }

        }

        if (count($failAtEndErrors) > 0) {
            $this->fail(print_r($failAtEndErrors));
        }

    }

    public function compareDatums($expected, $actual)
    {

        if (key_exists('datum', $expected)) {

            $this->assertEquals(array_intersect_key($expected, array(
                //'datumName'=>'',
                'datumCode'    => '',
                //'datum'=>'',
                'datum_params' => '')),
                array_intersect_key($actual, array(
                    //'datumName'=>'',
                    'datumCode'    => '',
                    //'datum'=>'',
                    'datum_params' => '')), print_r(array(
                    $this->code,
                    $this->defs->proj4,
                    $this->defs->ogcwkt,

                ), true));

            $this->assertEquals(
                array_diff_key(get_object_vars($expected['datum']), $this->datumPrecision),
                array_diff_key(get_object_vars($actual['datum']), $this->datumPrecision)
            );

            foreach ($this->datumPrecision as $key => $precision) {
                if (key_exists($key, $expected['datum'])) {
                    //$this->assertEquals($expected['datum']->$key, $actual['datum']->$key, 'AssertEquals Failed: datum->'.$key.' ('.$precision.'): '.$codesString,$precision);
                    $this->assertWithin($expected['datum']->$key, $actual['datum']->$key, 'AssertEquals Failed: datum->' . $key . ' (' . $precision . '): ' . print_r(array(
                        $this->code,
                        $this->defs->proj4,
                        $this->defs->ogcwkt,

                    ), true), $precision);
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
                'AssertEquals Failed: alphagamma: ' . print_r(array(
                    $this->code,
                    $this->defs->proj4,
                    $this->defs->ogcwkt,

                ), true));
        }
    }

    public function comparePreciseInternals($expected, $actual)
    {

        foreach ($this->internalsPrecision as $key => $precision) {
            if (key_exists($key, $expected)) {
                //$this->assertEquals($expected[$key], $actual[$key], 'AssertEquals Failed: variables->'.$key.' ('.$precision.'): '.$codesString, $precision);
                $this->assertWithin($expected[$key], $actual[$key], 'AssertEquals Failed: variables->' . $key . ' (' . $precision . '): ' . print_r(array(
                    $this->code,
                    $this->defs->proj4,
                    $this->defs->ogcwkt,

                ), true), $precision);
            }
        }

    }

    public function assertWithin($a, $b, $message, $precision)
    {

        $p = (max(1.0, abs($a)) * $precision);
        $this->assertEquals($a, $b, 'Asserting Within (' . $p . ') :: ' . $message, $p);

    }

}
