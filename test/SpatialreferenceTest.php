<?php
include(__DIR__ . "/../vendor/autoload.php");

use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;

class SpatialreferenceTest extends PHPUnit_Framework_TestCase
{

	public function testEveryTransformKnownToMan()
	{

		$this->scrapeEveryCodeKnownToMan();
	}



		function scrapeEveryCodeKnownToMan(){


			$next='http://spatialreference.org/ref/';
			$max=100;
			$pageCodes=array();
			while($next&&$max!==0){


				$page=file_get_contents($next);
				$codes=array_filter(array_map(function($a)use(&$next){ 

					//....>code:num
					$i=strrpos($a, '>');
					$str= substr($a, $i+1);
					if(trim($str)=='Next Page'){
						$url=explode('"', $a);
						$url=$url[count($url)-2];
						$next='http://spatialreference.org/ref/'.$url;
					}
					return $str;

				}, explode('</a>', $page)), function($a){

					if(strpos($a,':')!==false)return true;

				});

				$pageCodes[]=$codes;
				print_r($codes);
				//$next=false;
				$max--;

			}

			file_put_contents(__DIR__.'\\'.'codes.json', json_encode($pageCodes, JSON_PRETTY_PRINT));




		}


	


}
