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



$proj4->addDef("EPSG:27700",'+proj=tmerc +lat_0=49 +lon_0=-2 +k=0.9996012717 +x_0=400000 +y_0=-100000 +ellps=airy +datum=OSGB36 +units=m +no_defs');

$projOSGB36 = new Proj('EPSG:27700',$proj4);
$pointSrc = new Point(671196.3657,1230275.0454,$projOSGB36);
echo "Source : ".$pointSrc->toShortString()." in OSGB36<br>";
$pointDest = $proj4->transform($projWGS84, $pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in WGS84<br><br>";

$pointSrc = $pointDest;
echo "Source : ".$pointSrc->toShortString()." in WGS84<br>";
$pointDest = $proj4->transform($projOSGB36, $pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in OSGB36<br><br>";


$projOSGB36_2 = new Proj('PROJCS["OSGB 1936 / British National Grid",GEOGCS["OSGB 1936",DATUM["OSGB_1936",SPHEROID["Airy 1830",6377563.396,299.3249646,AUTHORITY["EPSG","7001"]],AUTHORITY["EPSG","6277"]],PRIMEM["Greenwich",0,AUTHORITY["EPSG","8901"]],UNIT["degree",0.01745329251994328,AUTHORITY["EPSG","9122"]],AUTHORITY["EPSG","4277"]],UNIT["metre",1,AUTHORITY["EPSG","9001"]],PROJECTION["Transverse_Mercator"],PARAMETER["latitude_of_origin",49],PARAMETER["central_meridian",-2],PARAMETER["scale_factor",0.9996012717],PARAMETER["false_easting",400000],PARAMETER["false_northing",-100000],AUTHORITY["EPSG","27700"],AXIS["Easting",EAST],AXIS["Northing",NORTH]]',$proj4);
$pointSrc = new Point(671196.3657,1230275.0454,$projOSGB36_2);
echo "Source : ".$pointSrc->toShortString()." in OSGB36 from OGC WKT<br>";
$pointDest = $proj4->transform($projWGS84, $pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in WGS84<br><br>";

$pointSrc = $pointDest;
echo "Source : ".$pointSrc->toShortString()." in WGS84<br>";
$pointDest = $proj4->transform($projOSGB36_2, $pointSrc);
echo "Conversion : ".$pointDest->toShortString()." in OSGB36 from OGC WKT<br><br>";

