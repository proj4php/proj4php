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

    /**
     * @runInSeparateProcess
     */
    public function testParseInlineWKTCode(){

        $proj4 = new Proj4php();

        //for lcc these are the public variables that should completley define the projection.
        $compare=array( 'lat0'=>'', 'lat1'=>'', 'lat2'=>'', 'k0'=>'', 'a'=>'',  'b'=>'', 'e'=>'', 'title'=>'', 'long0'=>'', 'x0'=>'', 'y0'=>'');


        $proj4->addDef('EPSG:32040', '+proj=lcc +lat_1=28.38333333333333 +lat_2=30.28333333333333 +lat_0=27.83333333333333 +lon_0=-99 +x_0=609601.2192024384 +y_0=0 +ellps=clrk66 +datum=NAD27 +to_meter=0.3048006096012192 +no_defs');

        $projNAD27Inline = new Proj('PROJCS["NAD27 / Texas South Central",GEOGCS["NAD27",DATUM["North_American_Datum_1927",SPHEROID["Clarke 1866",6378206.4,294.9786982138982,AUTHORITY["EPSG","7008"]],AUTHORITY["EPSG","6267"]],PRIMEM["Greenwich",0,AUTHORITY["EPSG","8901"]],UNIT["degree",0.01745329251994328,AUTHORITY["EPSG","9122"]],AUTHORITY["EPSG","4267"]],UNIT["US survey foot",0.3048006096012192,AUTHORITY["EPSG","9003"]],PROJECTION["Lambert_Conformal_Conic_2SP"],PARAMETER["standard_parallel_1",28.38333333333333],PARAMETER["standard_parallel_2",30.28333333333333],PARAMETER["latitude_of_origin",27.83333333333333],PARAMETER["central_meridian",-99],PARAMETER["false_easting",2000000],PARAMETER["false_northing",0],AUTHORITY["EPSG","32040"],AXIS["X",EAST],AXIS["Y",NORTH]]',$proj4);
        $projNAD27=new Proj('EPSG:32040', $proj4);

        $this->assertEquals(array_intersect_key(get_object_vars($projNAD27), $compare), array_intersect_key(get_object_vars($projNAD27Inline), $compare));


        //$proj4->addDef("EPSG:31370","+proj=lcc +lat_1=51.16666723333333 +lat_2=49.8333339 +lat_0=90 +lon_0=4.367486666666666 +x_0=150000.013 +y_0=5400088.438 +ellps=intl +towgs84=106.869,-52.2978,103.724,-0.33657,0.456955,-1.84218,1 +units=m +no_defs");
        $projBelge72Inline = new Proj('PROJCS["Belge 1972 / Belgian Lambert 72",GEOGCS["Belge 1972",DATUM["Reseau_National_Belge_1972",SPHEROID["International 1924",6378388,297,AUTHORITY["EPSG","7022"]],TOWGS84[106.869,-52.2978,103.724,-0.33657,0.456955,-1.84218,1],AUTHORITY["EPSG","6313"]],PRIMEM["Greenwich",0,AUTHORITY["EPSG","8901"]],UNIT["degree",0.01745329251994328,AUTHORITY["EPSG","9122"]],AUTHORITY["EPSG","4313"]],UNIT["metre",1,AUTHORITY["EPSG","9001"]],PROJECTION["Lambert_Conformal_Conic_2SP"],PARAMETER["standard_parallel_1",51.16666723333333],PARAMETER["standard_parallel_2",49.8333339],PARAMETER["latitude_of_origin",90],PARAMETER["central_meridian",4.367486666666666],PARAMETER["false_easting",150000.013],PARAMETER["false_northing",5400088.438],AUTHORITY["EPSG","31370"],AXIS["X",EAST],AXIS["Y",NORTH]]',$proj4);
        $projBelge72 = new Proj('EPSG:31370',$proj4);

        $this->assertEquals(array_intersect_key(get_object_vars($projBelge72), $compare), array_intersect_key(get_object_vars($projBelge72Inline), $compare));



        $proj4::$wktProjections["Lambert_Conformal_Conic"] = "lcc";
        $projL93Inline         = new Proj('PROJCS["RGF93 / Lambert-93",GEOGCS["RGF93",DATUM["D_RGF_1993",SPHEROID["GRS_1980",6378137,298.257222101]],PRIMEM["Greenwich",0],UNIT["Degree",0.017453292519943295]],PROJECTION["Lambert_Conformal_Conic"],PARAMETER["standard_parallel_1",49],PARAMETER["standard_parallel_2",44],PARAMETER["latitude_of_origin",46.5],PARAMETER["central_meridian",3],PARAMETER["false_easting",700000],PARAMETER["false_northing",6600000],UNIT["Meter",1]]', $proj4);
        $projL93         = new Proj('EPSG:2154', $proj4);

        $this->assertEquals(array_intersect_key(get_object_vars($projL93), $compare), array_intersect_key(get_object_vars($projL93Inline), $compare));
   
        // for wgs84, points are lat/lng, so both functions return the input (identity transform)
        $projWGS84Inline      = new Proj('GEOGCS["GCS_WGS_1984",DATUM["D_WGS_1984",SPHEROID["WGS_1984",6378137,298.257223563]],PRIMEM["Greenwich",0],UNIT["Degree",0.017453292519943295]]', $proj4);
        $projWGS84       = new Proj('EPSG:4326', $proj4);

        $this->assertEquals('proj4php\LongLat', (get_class($projWGS84Inline->projection)));
        $this->assertEquals('proj4php\LongLat', (get_class($projWGS84->projection)));



        $compare=array('e0'=>'', 'e1'=>'', 'e2'=>'', 'e3'=>'', 'ml0'=>'');

        $proj4->addDef("EPSG:27700",'+proj=tmerc +lat_0=49 +lon_0=-2 +k=0.9996012717 +x_0=400000 +y_0=-100000 +ellps=airy +datum=OSGB36 +units=m +no_defs');
        $projOSGB36Inline = new Proj('PROJCS["OSGB 1936 / British National Grid",GEOGCS["OSGB 1936",DATUM["D_OSGB_1936",SPHEROID["Airy_1830",6377563.396,299.3249646]],PRIMEM["Greenwich",0],UNIT["Degree",0.017453292519943295]],PROJECTION["Transverse_Mercator"],PARAMETER["latitude_of_origin",49],PARAMETER["central_meridian",-2],PARAMETER["scale_factor",0.9996012717],PARAMETER["false_easting",400000],PARAMETER["false_northing",-100000],UNIT["Meter",1]]',$proj4);
        $projOSGB36 = new Proj('EPSG:27700',$proj4);
        $this->assertEquals(array_intersect_key(get_object_vars($projOSGB36), $compare), array_intersect_key(get_object_vars($projOSGB36Inline), $compare));



        //$projLI          = new Proj('EPSG:27571', $proj4);
        //$projLSud        = new Proj('EPSG:27563', $proj4);
        



    }

     /**
     * @runInSeparateProcess
     * TODO is this valuable?
     */
     public function testParseInlineProj4Code(){
        $proj4 = new Proj4php();
        $proj4->addDef("EPSG:27700",'+proj=tmerc +lat_0=49 +lon_0=-2 +k=0.9996012717 +x_0=400000 +y_0=-100000 +ellps=airy +datum=OSGB36 +units=m +no_defs');

        $proj27700Inline=new Proj('+proj=tmerc +lat_0=49 +lon_0=-2 +k=0.9996012717 +x_0=400000 +y_0=-100000 +ellps=airy +datum=OSGB36 +units=m +no_defs', $proj4);
        $proj27700=new Proj('EPSG:27700', $proj4);

        //$this->assertEquals(array($proj27700),get_object_vars($proj27700Inline));
    }

    public function testInlineProjectionMethod1()
    {
        $proj4           = new Proj4php();
        $proj4->addDef("EPSG:27700",'+proj=tmerc +lat_0=49 +lon_0=-2 +k=0.9996012717 +x_0=400000 +y_0=-100000 +ellps=airy +datum=OSGB36 +units=m +no_defs');
        $proj4->addDef("EPSG:31370","+proj=lcc +lat_1=51.16666723333333 +lat_2=49.8333339 +lat_0=90 +lon_0=4.367486666666666 +x_0=150000.013 +y_0=5400088.438 +ellps=intl +towgs84=106.869,-52.2978,103.724,-0.33657,0.456955,-1.84218,1 +units=m +no_defs");
        $proj4->addDef("EPSG:32040",'+proj=lcc +lat_1=28.38333333333333 +lat_2=30.28333333333333 +lat_0=27.83333333333333 +lon_0=-99 +x_0=609601.2192024384 +y_0=0 +ellps=clrk66 +datum=NAD27 +to_meter=0.3048006096012192 +no_defs ');

        $projWGS84       = new Proj('EPSG:4326', $proj4);
        $projOSGB36 = new Proj('EPSG:27700',$proj4);
        $projLCC2SP = new Proj('EPSG:31370',$proj4);
        $projNAD27  = new Proj('EPSG:32040',$proj4);

        $pointWGS84 = new Point(-96,28.5, $projWGS84);
        $pointNAD27 = $proj4->transform($projNAD27,$pointWGS84);
        $this->assertEquals($pointNAD27->x,2963487.15,'',0.1);
        $this->assertEquals($pointNAD27->y,255412.99,'', 0.1 );

        $pointWGS84 = $proj4->transform($projWGS84,$pointNAD27);
        $this->assertEquals($pointWGS84->x,-96,'',0.1);
        $this->assertEquals($pointWGS84->y,28.5,'',0.1);

        $pointSrc = new Point(671196.3657,1230275.0454,$projOSGB36);
        $pointDest = $proj4->transform($projWGS84, $pointSrc);
        $this->assertEquals(2.9964931538756, $pointDest->x, '', 0.1);
        $this->assertEquals(60.863435314163, $pointDest->y, '', 0.1);

        $pointSrc = $pointDest;
        $pointDest = $proj4->transform($projOSGB36, $pointSrc);
        $this->assertEquals(671196.3657, $pointDest->x, '', 0.1);
        $this->assertEquals(1230275.0454, $pointDest->y, '', 0.1);

//from @coreation
        $pointLCC2SP=new Point(78367.044643634, 166486.56503096, $projLCC2SP);
        $pointWGS84=new Point(3.3500208637038, 50.803896326566, $projWGS84);

        //Proj4php::setDebug(true);
        $pointWGS84Actual =$proj4->transform($projWGS84, $pointLCC2SP);
        $this->assertEquals($pointWGS84->x, $pointWGS84Actual->x, '', 0.1);
        $this->assertEquals($pointWGS84->y, $pointWGS84Actual->y, '', 0.1);
        //Proj4php::setDebug(false);


        $pointWGS84=new Point(3.3500208637038, 50.803896326566, $projWGS84);
        $pointLCC2SP=new Point(78367.044643634, 166486.56503096, $projLCC2SP);

        //Proj4php::setDebug(true);
        $pointLCC2SPActual=$proj4->transform($projLCC2SP, $pointWGS84);
        $this->assertEquals($pointLCC2SP->x, $pointLCC2SPActual->x, '', 0.1);
        $this->assertEquals($pointLCC2SP->y, $pointLCC2SPActual->y, '', 0.1);
        //Proj4php::setDebug(false);

// from spatialreference.org (EPSG:31370 page)
        $pointLCC2SP=new Point(157361.845373, 132751.380618, $projLCC2SP);
        $pointWGS84=new Point(4.47, 50.505, $projWGS84);

        //Proj4php::setDebug(true);
        $pointWGS84Actual =$proj4->transform($projWGS84, $pointLCC2SP);
        $this->assertEquals($pointWGS84->x, $pointWGS84Actual->x, '', 0.0001);
        $this->assertEquals($pointWGS84->y, $pointWGS84Actual->y, '', 0.0001);
        //Proj4php::setDebug(false);


        $pointWGS84=new Point(4.47, 50.505, $projWGS84);
        $pointLCC2SP=new Point(157361.845373, 132751.380618, $projLCC2SP);

        //Proj4php::setDebug(true);
        $pointLCC2SPActual=$proj4->transform($projLCC2SP, $pointWGS84);
        $this->assertEquals($pointLCC2SP->x, $pointLCC2SPActual->x, '', 0.1);
        $this->assertEquals($pointLCC2SP->y, $pointLCC2SPActual->y, '', 0.1);
        //Proj4php::setDebug(false);

    }

    public function testInlineProjectionMethod2(){
        Proj4php::setDebug(false);

        $proj4           = new Proj4php();
        $projWGS84       = new Proj('EPSG:4326', $proj4);

        $projED50  = new Proj('GEOGCS["ED50",DATUM["European_Datum_1950",SPHEROID["International 1924",6378388,297,AUTHORITY["EPSG","7022"]],AUTHORITY["EPSG","6230"]],PRIMEM["Greenwich",0,AUTHORITY["EPSG","8901"]],UNIT["degree",0.01745329251994328,AUTHORITY["EPSG","9122"]],AUTHORITY["EPSG","4230"]]',$proj4);
        $projNAD27 = new Proj('PROJCS["NAD27 / Texas South Central",GEOGCS["NAD27",DATUM["North_American_Datum_1927",SPHEROID["Clarke 1866",6378206.4,294.9786982138982,AUTHORITY["EPSG","7008"]],AUTHORITY["EPSG","6267"]],PRIMEM["Greenwich",0,AUTHORITY["EPSG","8901"]],UNIT["degree",0.01745329251994328,AUTHORITY["EPSG","9122"]],AUTHORITY["EPSG","4267"]],UNIT["US survey foot",0.3048006096012192,AUTHORITY["EPSG","9003"]],PROJECTION["Lambert_Conformal_Conic_2SP"],PARAMETER["standard_parallel_1",28.38333333333333],PARAMETER["standard_parallel_2",30.28333333333333],PARAMETER["latitude_of_origin",27.83333333333333],PARAMETER["central_meridian",-99],PARAMETER["false_easting",2000000],PARAMETER["false_northing",0],AUTHORITY["EPSG","32040"],AXIS["X",EAST],AXIS["Y",NORTH]]',$proj4);
        $projLCC2SP = new Proj('PROJCS["Belge 1972 / Belgian Lambert 72",GEOGCS["Belge 1972",DATUM["Reseau_National_Belge_1972",SPHEROID["International 1924",6378388,297,AUTHORITY["EPSG","7022"]],TOWGS84[106.869,-52.2978,103.724,-0.33657,0.456955,-1.84218,1],AUTHORITY["EPSG","6313"]],PRIMEM["Greenwich",0,AUTHORITY["EPSG","8901"]],UNIT["degree",0.01745329251994328,AUTHORITY["EPSG","9122"]],AUTHORITY["EPSG","4313"]],UNIT["metre",1,AUTHORITY["EPSG","9001"]],PROJECTION["Lambert_Conformal_Conic_2SP"],PARAMETER["standard_parallel_1",51.16666723333333],PARAMETER["standard_parallel_2",49.8333339],PARAMETER["latitude_of_origin",90],PARAMETER["central_meridian",4.367486666666666],PARAMETER["false_easting",150000.013],PARAMETER["false_northing",5400088.438],AUTHORITY["EPSG","31370"],AXIS["X",EAST],AXIS["Y",NORTH]]',$proj4);

//        Proj4php::setDebug(true);
        $pointWGS84 = new Point(-96,28.5,  $projWGS84);
        $pointNAD27 = $proj4->transform($projNAD27,$pointWGS84);
 
        $this->assertEquals($pointNAD27->x,2963487.15,'', 0.1);
        $this->assertEquals($pointNAD27->y,255412.99,'', 0.1);
//        Proj4php::setDebug(false);

        $pointWGS84 = $proj4->transform($projWGS84,$pointNAD27);
        $this->assertEquals($pointWGS84->x,-96,'',0.1);
        $this->assertEquals($pointWGS84->y,28.5,'',0.1);


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
        $pointWGS84=new Point(3.3500208637038, 50.803896326566, $projWGS84);

        $pointWGS84Actual =$proj4->transform($projWGS84, $pointLCC2SP);
        $this->assertEquals($pointWGS84->x, $pointWGS84Actual->x, '', 0.1);
        $this->assertEquals($pointWGS84->y, $pointWGS84Actual->y, '', 0.1);

        // reverse transform.
        // I have to redefine the input/output expected points because above they 
        // are altered. (is that really the desired behavior?)
        $pointWGS84=new Point(3.3500208637038, 50.803896326566, $projWGS84);
        $pointLCC2SP=new Point(78367.044643634, 166486.56503096, $projLCC2SP);

        $pointLCC2SPActual=$proj4->transform($projLCC2SP, $pointWGS84);
        $this->assertEquals($pointLCC2SP->x, $pointLCC2SPActual->x, '', 0.1);
        $this->assertEquals($pointLCC2SP->y, $pointLCC2SPActual->y, '', 0.1);
    }

    public function testDatum()
    {
        Proj4php::setDebug(false);

        $proj4           = new Proj4php();
        $projWGS84       = new Proj('EPSG:4326', $proj4);

        $projED50  = new Proj('GEOGCS["ED50",DATUM["European_Datum_1950",SPHEROID["International 1924",6378388,297,AUTHORITY["EPSG","7022"]],AUTHORITY["EPSG","6230"]],PRIMEM["Greenwich",0,AUTHORITY["EPSG","8901"]],UNIT["degree",0.01745329251994328,AUTHORITY["EPSG","9122"]],AUTHORITY["EPSG","4230"]]',$proj4);

        // from http://www.ihsenergy.com/epsg/guid7.pdf
        // Chapter 2.3.2
        // 53°48'33.82"N
        // 2°07'46.38"E
        $pointWGS84 = new Point(deg2rad(53.809189444),deg2rad(2.129455), $projWGS84);

        $proj4->datum_transform($projWGS84->datum,$projED50->datum,$pointWGS84);

        $this->assertEquals(deg2rad(53.809189444),$pointWGS84->x,'',0.1);
        $this->assertEquals(deg2rad(2.129455),$pointWGS84->y,'',0.1);
    }

    public function testProjFour()
    {
        Proj4php::setDebug(false);

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

    public function testMonteMarioItaly() {

   
        $proj4 = new Proj4php();

        $projTO = new Proj('+proj=tmerc +lat_0=0 +lon_0=9 +k=0.9996 +x_0=1500000 +y_0=0 +ellps=intl +towgs84=-104.1, -49.1, -9.9, 0.971, -2.917, 0.714, -11.68 +units=m +no_defs', $proj4);
        //$this->fail(print_r($projTO, true));

        $projFROM = new Proj('GOOGLE', $proj4);

        $pointMin = new Point(1013714.5417662, 5692462.5159013);
        $pointMinTr = $proj4->transform($projFROM, $projTO, $pointMin);


        $this->assertEquals(array(1508344.3777571, 5032839.2985009), array($pointMinTr->x, $pointMinTr->y), '', 0.0001);

    }
}
