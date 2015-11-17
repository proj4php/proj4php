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
        $proj4->addDef("EPSG:31370","+proj=lcc +lat_1=51.16666723333333 +lat_2=49.8333339 +lat_0=90 +lon_0=4.367486666666666 +x_0=150000.013 +y_0=5400088.438 +ellps=intl +towgs84=106.869,-52.2978,103.724,-0.33657,0.456955,-1.84218,1 +units=m +no_defs");

        $projWGS84       = new Proj('EPSG:4326', $proj4);
        $projOSGB36 = new Proj('EPSG:27700',$proj4);
        $projLCC2SP = new Proj('EPSG:31370',$proj4);

        $pointSrc = new Point(671196.3657,1230275.0454,$projOSGB36);
        $pointDest = $proj4->transform($projWGS84, $pointSrc);
        $this->assertEquals(2.9964931538756, $pointDest->x, '', 0.1);
        $this->assertEquals(60.863435314163, $pointDest->y, '', 0.1);

        $pointSrc = $pointDest;
        $pointDest = $proj4->transform($projOSGB36, $pointSrc);
        $this->assertEquals(671196.3657, $pointDest->x, '', 20);
        $this->assertEquals(1230275.0454, $pointDest->y, '', 20);

//from @coreation
        $pointLCC2SP=new Point(78367.044643634, 166486.56503096, $projLCC2SP);
        $pointWGS84=new Point(3.35249345076, 50.8044261264, $projWGS84);

        $pointWGS84Actual =$proj4->transform($projWGS84, $pointLCC2SP);
        $this->assertEquals($pointWGS84->x, $pointWGS84Actual->x, '', 0.1);
        $this->assertEquals($pointWGS84->y, $pointWGS84Actual->y, '', 0.1);



        $pointWGS84=new Point(3.35249345076, 50.8044261264, $projWGS84);
        $pointLCC2SP=new Point(78367.044643634, 166486.56503096, $projLCC2SP);

        $pointLCC2SPActual=$proj4->transform($projLCC2SP, $pointWGS84);
        $this->assertEquals($pointLCC2SP->x, $pointLCC2SPActual->x, '', 0.1);
        $this->assertEquals($pointLCC2SP->y, $pointLCC2SPActual->y, '', 0.1);
    }

    public function testInlineProjectionMethod2(){



        $proj4           = new Proj4php();
        $projWGS84       = new Proj('EPSG:4326', $proj4);

        $projNAD27 = new Proj('PROJCS["NAD27 / Texas South Central",GEOGCS["NAD27",DATUM["North_American_Datum_1927",SPHEROID["Clarke 1866",6378206.4,294.9786982138982,AUTHORITY["EPSG","7008"]],AUTHORITY["EPSG","6267"]],PRIMEM["Greenwich",0,AUTHORITY["EPSG","8901"]],UNIT["degree",0.01745329251994328,AUTHORITY["EPSG","9122"]],AUTHORITY["EPSG","4267"]],UNIT["US survey foot",0.3048006096012192,AUTHORITY["EPSG","9003"]],PROJECTION["Lambert_Conformal_Conic_2SP"],PARAMETER["standard_parallel_1",28.38333333333333],PARAMETER["standard_parallel_2",30.28333333333333],PARAMETER["latitude_of_origin",27.83333333333333],PARAMETER["central_meridian",-99],PARAMETER["false_easting",2000000],PARAMETER["false_northing",0],AUTHORITY["EPSG","32040"],AXIS["X",EAST],AXIS["Y",NORTH]]',$proj4);
        $projLCC2SP = new Proj('PROJCS["Belge 1972 / Belgian Lambert 72",GEOGCS["Belge 1972",DATUM["Reseau_National_Belge_1972",SPHEROID["International 1924",6378388,297,AUTHORITY["EPSG","7022"]],TOWGS84[106.869,-52.2978,103.724,-0.33657,0.456955,-1.84218,1],AUTHORITY["EPSG","6313"]],PRIMEM["Greenwich",0,AUTHORITY["EPSG","8901"]],UNIT["degree",0.01745329251994328,AUTHORITY["EPSG","9122"]],AUTHORITY["EPSG","4313"]],UNIT["metre",1,AUTHORITY["EPSG","9001"]],PROJECTION["Lambert_Conformal_Conic_2SP"],PARAMETER["standard_parallel_1",51.16666723333333],PARAMETER["standard_parallel_2",49.8333339],PARAMETER["latitude_of_origin",90],PARAMETER["central_meridian",4.367486666666666],PARAMETER["false_easting",150000.013],PARAMETER["false_northing",5400088.438],AUTHORITY["EPSG","31370"],AXIS["X",EAST],AXIS["Y",NORTH]]',$proj4);

        $pointWGS84 = new Point(0.49741884,-1.67551608, $projWGS84);
        $pointNAD27 = $proj4->transform($projNAD27,$pointWGS84);
        $this->assertEquals($pointNAD27->x,2963503.91,'', 0.1);
        $this->assertEquals($pointNAD27->y,254759.80,'', 0.1);

        $pointWGS84 = $proj4->transform($projWGS84,$pointNAD27);
        $this->assertEquals($pointWGS84->x,0.49741884,'',0.1);
        $this->assertEquals($pointWGS84->y,-1.67551608,'',0.1);

        //from @coreation
        $pointLCC2SP=new Point(78367.044643634, 166486.56503096, $projLCC2SP);

        // from http://cs2cs.mygeodata.eu/
        // using:
        // input projection: Belge 1972 / Belgian Lambert 72 (SRID=31370)
        // +proj=lcc +lat_1=51.16666723333333 +lat_2=49.8333339 +lat_0=90 +lon_0=4.367486666666666 
        // +x_0=150000.013 +y_0=5400088.438 +ellps=intl +towgs84=-106.868628,52.297783,-103.723893,0.336570,-0.456955,1.842183,-1.2747 +units=m +no_defs 
        // 
        // output projection:
        // WWGS 84 (SRID=4326)
        // +proj=longlat +datum=WGS84 +no_defs 
        $pointWGS84=new Point(3.35249345076, 50.8044261264, $projWGS84);

        $pointWGS84Actual =$proj4->transform($projWGS84, $pointLCC2SP);
        $this->assertEquals($pointWGS84->x, $pointWGS84Actual->x, '', 0.1);
        $this->assertEquals($pointWGS84->y, $pointWGS84Actual->y, '', 0.1);

        // reverse transform.
        // I have to redefine the input/output expected points because above they 
        // are altered. (is that really the desired behavior?)
        $pointWGS84=new Point(3.35249345076, 50.8044261264, $projWGS84);
        $pointLCC2SP=new Point(78367.044643634, 166486.56503096, $projLCC2SP);

        $pointLCC2SPActual=$proj4->transform($projLCC2SP, $pointWGS84);
        $this->assertEquals($pointLCC2SP->x, $pointLCC2SPActual->x, '', 0.1);
        $this->assertEquals($pointLCC2SP->y, $pointLCC2SPActual->y, '', 0.1);
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
