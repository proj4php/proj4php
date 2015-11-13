<?php
include(__DIR__ . "/../vendor/autoload.php");

use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;

class Proj4phpTest extends PHPUnit_Framework_TestCase
{

    public function testTransform()
    {

        $proj4     = new Proj4php();
        $projL93   = new Proj('EPSG:2154', $proj4);
        $projWGS84 = new Proj('EPSG:4326', $proj4);
        $projLI    = new Proj('EPSG:27571', $proj4);
        $projLSud  = new Proj('EPSG:27563', $proj4);
        $projL72   = new Proj('EPSG:31370', $proj4);
        $proj25833 = new Proj('EPSG:25833', $proj4);
        $proj31468 = new Proj('EPSG:31468', $proj4);
        $proj5514  = new Proj('EPSG:5514', $proj4);

// GPS
// latitude        longitude
// 48,831938       2,355781
// 48°49'54.977''  2°21'20.812''
//
// L93
// 652709.401   6859290.946
//
// LI
// 601413.709   1125717.730
//

        $pointSrc  = new Point('652709.401', '6859290.946', $projL93);
        $pointDest = $proj4->transform($projWGS84, $pointSrc);

        $pointSrc  = $pointDest;
        $pointDest = $proj4->transform($projLSud, $pointSrc);

        $pointSrc  = $pointDest;
        $pointDest = $proj4->transform($projWGS84, $pointSrc);

        $pointSrc  = $pointDest;
        $pointDest = $proj4->transform($projLI, $pointSrc);

        $pointSrc  = $pointDest;
        $pointDest = $proj4->transform($projL93, $pointSrc);


        $pointSrc  = new Point('177329.253543', '58176.702191');
        $pointDest = $proj4->transform($projL72, $projWGS84, $pointSrc);

        $pointSrc  = $pointDest;
        $pointDest = $proj4->transform($projWGS84, $projL72, $pointSrc);


        $pointSrc  = $pointDest;
        $pointDest = $proj4->transform($projL72, $proj25833, $pointSrc);

        $pointSrc  = $pointDest;
        $pointDest = $proj4->transform($proj25833, $projWGS84, $pointSrc);

        $pointSrc  = $pointDest;
        $pointDest = $proj4->transform($projWGS84, $proj31468, $pointSrc);

        $pointSrc  = new Point('-868208.53', '-1095793.57');
        $pointDest = $proj4->transform($proj5514, $projWGS84, $pointSrc);

        $pointSrc  = $pointDest;
        $pointDest = $proj4->transform($projWGS84, $proj5514, $pointSrc);
    }

    public function testInlineProjectionMethod1()
    {
        $proj4           = new Proj4php();
        $proj4->addDef("EPSG:27700",'+proj=tmerc +lat_0=49 +lon_0=-2 +k=0.9996012717 +x_0=400000 +y_0=-100000 +ellps=airy +datum=OSGB36 +units=m +no_defs');

        $projWGS84       = new Proj('EPSG:4326', $proj4);
        $projOSGB36 = new Proj('EPSG:27700',$proj4);
        $pointSrc = new Point(671196.3657,1230275.0454,$projOSGB36);
        $pointDest = $proj4->transform($projWGS84, $pointSrc);
        $this->assertEquals(2.9964931538756, $pointDest->x, '', 0.1);
        $this->assertEquals(60.863435314163, $pointDest->y, '', 0.1);

        $pointSrc = $pointDest;
        $pointDest = $proj4->transform($projOSGB36, $pointSrc);
        $this->assertEquals(671196.3657, $pointDest->x, '', 20);
        $this->assertEquals(1230275.0454, $pointDest->y, '', 20);

    }

    public function testInlineProjectionMethod2()
    {
        $proj4           = new Proj4php();

        $projWGS84       = new Proj('GEOGCS["WGS 84",DATUM["WGS_1984",SPHEROID["WGS 84",6378137,298.257223563,AUTHORITY["EPSG","7030"]],AUTHORITY["EPSG","6326"]],PRIMEM["Greenwich",0,AUTHORITY["EPSG","8901"]],UNIT["degree",0.01745329251994328,AUTHORITY["EPSG","9122"]],AUTHORITY["EPSG","4326"]]',$proj4);
        $projOSGB36      = new Proj('PROJCS["OSGB 1936 / British National Grid",GEOGCS["OSGB 1936",DATUM["OSGB_1936",SPHEROID["Airy 1830",6377563.396,299.3249646,AUTHORITY["EPSG","7001"]],AUTHORITY["EPSG","6277"]],PRIMEM["Greenwich",0,AUTHORITY["EPSG","8901"]],UNIT["degree",0.01745329251994328,AUTHORITY["EPSG","9122"]],AUTHORITY["EPSG","4277"]],UNIT["metre",1,AUTHORITY["EPSG","9001"]],PROJECTION["Transverse_Mercator"],PARAMETER["latitude_of_origin",49],PARAMETER["central_meridian",-2],PARAMETER["scale_factor",0.9996012717],PARAMETER["false_easting",400000],PARAMETER["false_northing",-100000],AUTHORITY["EPSG","27700"],AXIS["Easting",EAST],AXIS["Northing",NORTH]]',$proj4);
        $pointSrc = new Point(671196.3657,1230275.0454,$projOSGB36);

        $projLCC2SP = new Proj('PROJCS["Belge 1972 / Belgian Lambert 72",GEOGCS["Belge 1972",DATUM["Reseau_National_Belge_1972",SPHEROID["International 1924",6378388,297,AUTHORITY["EPSG","7022"]],TOWGS84[106.869,-52.2978,103.724,-0.33657,0.456955,-1.84218,1],AUTHORITY["EPSG","6313"]],PRIMEM["Greenwich",0,AUTHORITY["EPSG","8901"]],UNIT["degree",0.01745329251994328,AUTHORITY["EPSG","9122"]],AUTHORITY["EPSG","4313"]],UNIT["metre",1,AUTHORITY["EPSG","9001"]],PROJECTION["Lambert_Conformal_Conic_2SP"],PARAMETER["standard_parallel_1",51.16666723333333],PARAMETER["standard_parallel_2",49.8333339],PARAMETER["latitude_of_origin",90],PARAMETER["central_meridian",4.367486666666666],PARAMETER["false_easting",150000.013],PARAMETER["false_northing",5400088.438],AUTHORITY["EPSG","31370"],AXIS["X",EAST],AXIS["Y",NORTH]]',$proj4);
        $pointLCC2SP=$proj4->transform($projLCC2SP, $pointSrc);

        // check known conversion value for WGS84
        $pointWGS84 =$proj4->transform($projWGS84, $pointLCC2SP);
        $this->assertEquals(2.9964931538756, $pointWGS84->x, '', 0.1);
        $this->assertEquals(60.863435314163, $pointWGS84->y, '', 0.1);

        // check known convertion value for OSGB36
        $pointOSGB36 =$proj4->transform($projOSGB36, $pointLCC2SP);
        $this->assertEquals(671196.3657, $pointOSGB36->x, '', 20);
        $this->assertEquals(1230275.0454, $pointOSGB36->y, '', 20);        
    }

    public function testProjFour()
    {
        $proj4           = new Proj4php();
        $projL93         = new Proj('EPSG:2154', $proj4);
        $projWGS84       = new Proj('EPSG:4326', $proj4);
        $projLI          = new Proj('EPSG:27571', $proj4);
        $projLSud        = new Proj('EPSG:27563', $proj4);
        $projLSeventyTwo = new Proj('EPSG:31370', $proj4);


        $pointSrc = new Point('652709.401', '6859290.946');
        $this->assertEquals('652709.401 6859290.946', $pointSrc->toShortString());

        $pointDest = $proj4->transform($projL93, $projWGS84, $pointSrc);
        $this->assertEquals(2.3557811127971, $pointDest->x, '', 0.1);
        $this->assertEquals(48.831938054369, $pointDest->y, '', 0.1);

        $pointDest = $proj4->transform($projWGS84, $projLSeventyTwo, $pointSrc);
        $this->assertEquals(2179.4161950587, $pointDest->x, '', 20);
        $this->assertEquals(-51404.55306690, $pointDest->y, '', 20);
        $this->assertEquals(2354.4969810662, $pointDest->x, '', 300);
        $this->assertEquals(-51359.251012595, $pointDest->y, '', 300);


        $pointDest = $proj4->transform($projLSeventyTwo, $projWGS84, $pointSrc);
        $this->assertEquals(2.3557811002407, $pointDest->x, '', 0.1);
        $this->assertEquals(48.831938050542, $pointDest->y, '', 0.1);
        $this->assertEquals(2.3557811127971, $pointDest->x, '', 0.1);
        $this->assertEquals(48.831938054369, $pointDest->y, '', 0.1);

        $pointDest = $proj4->transform($projWGS84, $projLSud, $pointSrc);
        $this->assertEquals(601419.93654252, $pointDest->x, '', 0.1);
        $this->assertEquals(726554.08650133, $pointDest->y, '', 0.1);
        $this->assertEquals(601419.93647681, $pointDest->x, '', 0.1);
        $this->assertEquals(726554.08650133, $pointDest->y, '', 0.1);

        $pointDest = $proj4->transform($projLSud, $projWGS84, $pointSrc);

        $this->assertEquals(2.3557810993491, $pointDest->x, '', 0.1);
        $this->assertEquals(48.831938051718, $pointDest->y, '', 0.1);
        $this->assertEquals(2.3557811002407, $pointDest->x, '', 0.1);
        $this->assertEquals(48.831938050527, $pointDest->y, '', 0.1);

        $pointDest = $proj4->transform($projWGS84, $projLI, $pointSrc);

        $this->assertEquals(601415.06988072, $pointDest->x, '', 0.1);
        $this->assertEquals(1125718.0309796, $pointDest->y, '', 0.1);
        $this->assertEquals(601415.06994621, $pointDest->x, '', 0.1);
        $this->assertEquals(1125718.0308472, $pointDest->y, '', 0.1);

        $pointDest = $proj4->transform($projLI, $projL93, $pointSrc);

        $this->assertEquals(652709.40007563, $pointDest->x, '', 0.1);
        $this->assertEquals(6859290.9456811, $pointDest->y, '', 0.1);
        $this->assertEquals(652709.40001126, $pointDest->x, '', 0.1);
        $this->assertEquals(6859290.9458141, $pointDest->y, '', 0.1);
    }
}
