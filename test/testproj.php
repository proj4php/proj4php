<?php
include(__DIR__ . "/../vendor/autoload.php");

use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;

$proj4 = new Proj4php();
$projL93 = new Proj('EPSG:2154',$proj4);
$projWGS84 = new Proj('EPSG:4326',$proj4);
$projLI = new Proj('EPSG:27571',$proj4);
$projLSud = new Proj('EPSG:27563',$proj4);
$projL72 = new Proj('EPSG:31370',$proj4);
$proj25833 = new Proj('EPSG:25833',$proj4);
$proj31468 = new Proj('EPSG:31468',$proj4);
$proj5514 = new Proj('EPSG:5514',$proj4);

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

$pointSrc = new Point('652709.401','6859290.946',$projL93);
echo "Source : ".$pointSrc->toShortString()." in L93 <br>";
$pointDest = $proj4->transform($projWGS84,$pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in WGS84<br><br>";

$pointSrc = $pointDest;
echo "Source : ".$pointSrc->toShortString()." in WGS84<br>";
$pointDest = $proj4->transform($projLSud,$pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in Lambert Sud<br><br>";

$pointSrc = $pointDest;
echo "Source : ".$pointSrc->toShortString()." in Lambert Sud<br>";
$pointDest = $proj4->transform($projWGS84,$pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in WGS84<br><br>";

$pointSrc = $pointDest;
echo "Source : ".$pointSrc->toShortString()." in WGS84<br>";
$pointDest = $proj4->transform($projLI,$pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in LI <br><br>";

$pointSrc = $pointDest;
echo "Source : ".$pointSrc->toShortString()." in LI<br>";
$pointDest = $proj4->transform($projL93,$pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in L93<br><br>";



$pointSrc = new Point('177329.253543','58176.702191');
echo "Source : ".$pointSrc->toShortString()." in Lambert 1972<br>";
$pointDest = $proj4->transform($projL72,$projWGS84,$pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in WGS84<br><br>";

$pointSrc = $pointDest;
echo "Source : ".$pointSrc->toShortString()." in WGS84<br>";
$pointDest = $proj4->transform($projWGS84,$projL72,$pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in Lambert 1972<br><br>";


$pointSrc = $pointDest;
echo "Source : ".$pointSrc->toShortString()." in Lambert 1972<br>";
$pointDest = $proj4->transform($projL72,$proj25833,$pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in EPSG:25833<br><br>";

$pointSrc = $pointDest;
echo "Source : ".$pointSrc->toShortString()." in EPSG:25833<br>";
$pointDest = $proj4->transform($proj25833,$projWGS84,$pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in WGS84<br><br>";

$pointSrc = $pointDest;
echo "Source : ".$pointSrc->toShortString()." in WGS84<br>";
$pointDest = $proj4->transform($projWGS84,$proj31468,$pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in EPSG:31468<br><br>";

$pointSrc = new Point('-868208.53', '-1095793.57');
echo "Source : ".$pointSrc->toShortString()." in S-JTSK<br>";
$pointDest = $proj4->transform($proj5514,$projWGS84,$pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in WGS84<br><br>";

$pointSrc = $pointDest;
echo "Source : ".$pointSrc->toShortString()." in WGS84<br>";
$pointDest = $proj4->transform($projWGS84,$proj5514,$pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in S-JTSK<br><br>";
