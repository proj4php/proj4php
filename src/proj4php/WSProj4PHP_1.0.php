<?php

$error = false;

if ($_GET['x'])
{
   $x = $_GET['x'];
}
else
	$error = true;
if ($_GET['y'])
{
   $y = $_GET['y'];
}
else
	$error = true;
if ($_GET['projectionxy'])
{
   $projectionxy = $_GET['projectionxy'];
   $projectionxy = str_replace('::',':',$projectionxy);
}
else
	$projectionxy = 'EPSG:2154';
if ($_GET['projection'])
{
   $projection = $_GET['projection'];
   $projection = str_replace('::',':',$projection);
}
else
	$projection = 'EPSG:4326';
if ($_GET['format'])
{
   $format = $_GET['format'];
   if (!($format=='xml' || $format=='json'))
	 $error = true;
}
else
	$format = 'xml';

include_once("proj4php.php");

$proj4 = new Proj4php();
$projsource = new Proj4phpProj($projectionxy,&$proj4);
$projdest = new Proj4phpProj($projection,&$proj4);

// check the projections
if ($proj4->defs[$projectionxy]==$proj4->defs['WGS84'] && $projectionxy!='EPSG:4326')
  $error = true;
if ($proj4->defs[$projection]==$proj4->defs['WGS84'] && $projection!='EPSG:4326')
  $error = true;

if ($error)
{
	if ($format=='json')
	{
		echo "{\"status\":\"error\", \"erreur\": {\"code\": 2, \"message\": \"Wrong parameters.\"} }";
		exit;
	}
	else
	{
		echo "<reponse>";
		echo "  <erreur>";
		echo "    <code>2</code>";
		echo "    <message>Wrong parameters</message>";
		echo "  </erreur>";
		echo "</reponse>";
		exit;
	}
}

$pointSrc = new proj4phpPoint($x,$y);
$pointDest = $proj4->transform($projsource,$projdest,$pointSrc);

$projection = str_replace(':','::',$projection);

if ($format=='json')
{
	echo "{\"status\" :\"success\", \"point\" : {\"x\":".$pointDest->x.", \"y\":".$pointDest->y.",\"projection\" :\"".$projection."\"}}";
	exit;
}
else
{
	echo "<reponse>";
    echo "<point>";
    echo "<x>".$pointDest->x."</x>";
    echo "<y>".$pointDest->y."</y>";
    echo "<projection>".$projection."</projection>";
    echo "</point>";
	echo "</reponse>";
}