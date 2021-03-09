<?php
include_once("vendor/autoload.php");
include_once("src/proj4php/proj4php.php");

class Proj4phpTest extends PHPUnit_Framework_TestCase
{

public function testIssue111()
    {
         $proj4 = new Proj4php();

	 $projWGS84 = new Proj4phpProj('EPSG:4326',$proj4);
	 Proj4php::$defs['EPSG:3004'] = "+proj=tmerc +lat_0=0 +lon_0=15 +k=0.9996 +x_0=2520000 +y_0=0 +ellps=intl +towgs84=-104.1,-49.1,-9.9,0.971,-2.917,0.714,-11.68 +units=m +no_defs";
         $projEPSG3004 = new Proj4phpProj('EPSG:3004',$proj4);

         $pointSrc = new Proj4phpPoint(12.6 , 42.48 , $projWGS84);
         $pointDest = new Proj4phpPoint(2322737.56, 4705874.8, $projEPSG3004);

         $pointTestA = $proj4->transform($projWGS84,$projEPSG3004, $pointSrc);
         $pointTestB = $proj4->transform($projEPSG3004,$projWGS84, $pointDest);

	 assert(abs($pointTestA->x-2322737.56)<0.001);
	 assert(abs($pointTestA->y-4705841.8)<0.001);
	 assert(abs($pointTestB->x-12.6)<0.001);
	 assert(abs($pointTestB->y-42.48)<0.001);
    }

public function testTransform()
{
$proj4 = new Proj4php();
$projL93 = new Proj4phpProj('EPSG:2154',$proj4);
$projWGS84 = new Proj4phpProj('EPSG:4326',$proj4);
$projLI = new Proj4phpProj('EPSG:27571',$proj4);
$projLSud = new Proj4phpProj('EPSG:27563',$proj4);
$projL72 = new Proj4phpProj('EPSG:31370',$proj4);
$proj25833 = new Proj4phpProj('EPSG:25833',$proj4);
$proj31468 = new Proj4phpProj('EPSG:31468',$proj4);
$proj5514 = new Proj4phpProj('EPSG:5514',$proj4);

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

$pointSrc = new proj4phpPoint('652709.401','6859290.946');
$pointDest = $proj4->transform($projL93,$projWGS84,$pointSrc);

$pointSrc = $pointDest;
$pointDest = $proj4->transform($projWGS84,$projLSud,$pointSrc);

$pointSrc = $pointDest;
$pointDest = $proj4->transform($projLSud,$projWGS84,$pointSrc);

$pointSrc = $pointDest;
$pointDest = $proj4->transform($projWGS84,$projLI,$pointSrc);

$pointSrc = $pointDest;
$pointDest = $proj4->transform($projLI,$projL93,$pointSrc);



$pointSrc = new proj4phpPoint('177329.253543','58176.702191');
$pointDest = $proj4->transform($projL72,$projWGS84,$pointSrc);

$pointSrc = $pointDest;
$pointDest = $proj4->transform($projWGS84,$projL72,$pointSrc);


$pointSrc = $pointDest;
$pointDest = $proj4->transform($projL72,$proj25833,$pointSrc);

$pointSrc = $pointDest;
$pointDest = $proj4->transform($proj25833,$projWGS84,$pointSrc);

$pointSrc = $pointDest;
$pointDest = $proj4->transform($projWGS84,$proj31468,$pointSrc);

$pointSrc = new proj4phpPoint('-868208.53', '-1095793.57');
$pointDest = $proj4->transform($proj5514,$projWGS84,$pointSrc);

$pointSrc = $pointDest;
$pointDest = $proj4->transform($projWGS84,$proj5514,$pointSrc);
}
}

$test = new Proj4phpTest();
$test->testIssue111();
