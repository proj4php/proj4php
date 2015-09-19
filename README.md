# proj4php
PHP-class for proj4
This is a PHP-Class for geographic coordinates transformation using proj4 definitions, thanks to a translation from Proj4JS. 

## Using

```php
include("proj4php");

use \proj4php\Proj4php;
use \proj4php\Proj4phpProj;
use \proj4php\Proj4phpPoint;

$proj4 = new Proj4php();
$projL93 = new Proj4phpProj('EPSG:2154',$proj4);
$projWGS84 = new Proj4phpProj('EPSG:4326',$proj4);

$pointSrc = new proj4phpPoint('652709.401','6859290.946');
$pointDest = $proj4->transform($projL93,$projWGS84,$pointSrc);

echo "Source : ".$pointSrc->toShortString()." in L93 <br>";
echo "Conversion : ".$pointDest->toShortString()." in WGS84<br><br>";
```

There's is also ways to define inline projections.

## Developping

Feel free to fork us and submit your changes!
