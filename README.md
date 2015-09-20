# proj4php
PHP-class for proj4
This is a PHP-Class for geographic coordinates transformation using proj4 definitions, thanks to a translation from Proj4JS. 

## This "adding-composer" Branch

In this branch we are aiming to bring the package into line with most curent PHP practices. These include:

* Namespacing.
* PHP5.4+ syntax
* [PSR-2 styling](http://www.php-fig.org/psr/psr-2/)
* [PSR-4 autoloader](http://www.php-fig.org/psr/psr-4/)
* Tests. This is difficult until the other changes are made.
* [semver](http://semver.org/) relase numbers to packagist.org
* Full compatibility with [composer](https://getcomposer.org/)

A legacy branch will be maintained for older applications.

## Using

```php
include("vendor/autoload.php");

use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;

// Initialise Proj4
$proj4 = new Proj4php();

// Create two different projections.
$projL93    = new Proj('EPSG:2154', $proj4);
$projWGS84  = new Proj('EPSG:4326', $proj4);

// Create a point.
$pointSrc = new Point('652709.401', '6859290.946');

// Transform the point between datums.
$pointDest = $proj4->transform($projL93, $projWGS84, $pointSrc);

// Display the result.
echo "Source : " . $pointSrc->toShortString() . " in L93 <br>";
echo "Conversion : " . $pointDest->toShortString() . " in WGS84<br><br>";
```

There are also ways to define inline projections.

A PSR-4 autoloader will be introduced shortly,
and that will change many of the paths and classnames above.

## Developing

Feel free to fork us and submit your changes!
