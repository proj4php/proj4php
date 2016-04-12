<?php

namespace proj4php;

class Wkt {

	private static $wktProjections = array();
	private static $wktEllipsoids = array();
	private static $wktDatums = array();

	/**
	 *
	 * returns an associative array of projection arguments from a wkt string
	 */
	public static function Parse($wktString) {
		return self::ParseRecursive($wktString);

	}
	private static function ParseRecursive($wktString, &$wktParams = null) {

		if (empty(self::$wktProjections)) {

			self::initWKTProjections();
			self::initWKTDatums();
			self::initWKTEllipsoids();

		}

		if (is_null($wktParams)) {
			$wktParams = (object) array(
				'to_rads' => deg2rad(1),
				'to_meter' => 1.0,
			);
		}

		$wktSections = self::ParseWKTIntoSections($wktString);

		if (empty($wktSections)) {
			//print_r(json_encode($wktParams,JSON_PRETTY_PRINT));
			return false;
		}

		$wktObject = $wktSections[0];
		$wktName = $wktSections[1];
		$wktArray = $wktSections[2];

		// Do something based on the type of the wktObject being parsed.
		// Add in variations in the spelling as required.
		switch ($wktObject) {
		case 'LOCAL_CS':
			$wktParams->projName = 'identity';
			$wktParams->localCS = true;
			$wktParams->srsCode = $wktName;
			break;
		case 'GEOGCS':
			$wktParams->projName = 'longlat';
			$wktParams->geocsCode = $wktName;

			if (!isset($wktParams->srsCode)) {
				$wktParams->srsCode = $wktName;
			}
			break;
		case 'PROJCS':
			$wktParams->srsCode = $wktName;
			break;
		case 'GEOCCS':
			break;
		case 'PROJECTION':

			if (key_exists($wktName, self::$wktProjections)) {
				$wktParams->projName = self::$wktProjections[$wktName];
			} else {
				throw new \Exception('Undefined Projection: ' . $wktName);
			}
			break;
		case 'DATUM':
			$wktParams->datumName = $wktName;
			if (key_exists($wktName, self::$wktDatums)) {
				$wktParams->datumCode = self::$wktDatums[$wktName];
			}
			break;
		case 'LOCAL_DATUM':
			$wktParams->datumCode = 'none';
			break;
		case 'SPHEROID':

			if (key_exists($wktName, self::$wktEllipsoids)) {
				$wktParams->ellps = self::$wktEllipsoids[$wktName];
			} else {
				$wktParams->ellps = $wktName;
				$wktParams->a = floatval(array_shift($wktArray));
				$wktParams->rf = floatval(array_shift($wktArray));
			}

			break;
		case 'PRIMEM':
			// to radians?

			$wktParams->from_greenwich = deg2rad(floatval(array_shift($wktArray)));
			break;
		case 'UNIT':
			$wktParams->units = $wktName;
			if (($wktToMeter = self::parseWKTToMeter($wktName, $wktArray)) !== false) {
				$wktParams->to_meter = $wktToMeter;
				if (isset($wktParams->x0)) {
					$wktParams->x0 = $wktParams->to_meter * $wktParams->x0;
				}
				if (isset($wktParams->y0)) {
					$wktParams->y0 = $wktParams->to_meter * $wktParams->y0;
				}
			}

			if (($wktToRads = self::parseWKTToRads($wktName, $wktArray)) !== false) {
				$wktParams->to_rads = $wktToRads;

				if (isset($wktParams->lat_ts)) {
					$wktParams->lat_ts = $wktParams->to_rads * $wktParams->lat_ts;
				}

				if (isset($wktParams->x0)) {
					$wktParams->x0 = $wktParams->to_rads * $wktParams->x0;
				}

				if (isset($wktParams->y0)) {
					$wktParams->y0 = $wktParams->to_rads * $wktParams->y0;
				}

				if (isset($wktParams->longc)) {
					$wktParams->longc = $wktParams->to_rads * $wktParams->longc;
				}

				if (isset($wktParams->long0)) {
					$wktParams->long0 = $wktParams->to_rads * $wktParams->long0;
				}

				if (isset($wktParams->lat0)) {
					$wktParams->lat0 = $wktParams->to_rads * $wktParams->lat0;
				}

				if (isset($wktParams->lat1)) {
					$wktParams->lat1 = $wktParams->to_rads * $wktParams->lat1;
				}

				if (isset($wktParams->lat2)) {
					$wktParams->lat2 = $wktParams->to_rads * $wktParams->lat2;
				}

				if (isset($wktParams->alpha)) {
					$wktParams->alpha = $wktParams->to_rads * $wktParams->alpha;
				}

			}

			break;
		case 'PARAMETER':
			$name = strtolower($wktName);
			$value = floatval(array_shift($wktArray));

			// there may be many variations on the wktName values, add in case
			// statements as required
			switch ($name) {
			case 'false_easting':

				$wktParams->x0 = $value;
				if (isset($wktParams->to_meter)) {
					$wktParams->x0 = $wktParams->to_meter * $wktParams->x0;
				}
				break;
			case 'false_northing':
				$wktParams->y0 = $value;
				if (isset($wktParams->to_meter)) {
					$wktParams->y0 = $wktParams->to_meter * $wktParams->y0;
				}
				break;
			case 'scale_factor':
				$wktParams->k0 = $value;
				break;
			case 'central_meridian':
			case 'longitude_of_center': // SR-ORG:10
				$wktParams->longc = $value * $wktParams->to_rads;
			case 'longitude_of_origin': // SR-ORG:118
				$wktParams->long0 = $value * $wktParams->to_rads;

				break;
			case 'latitude_of_origin':
			case 'latitude_of_center': // SR-ORG:10
				$wktParams->lat0 = $value * $wktParams->to_rads;
				if ($wktParams->projName == 'merc' || $wktParams->projName == 'eqc'
				) {
					$wktParams->lat_ts = $value * $wktParams->to_rads; //EPSG:3752 (merc), EPSG:3786 (eqc), SR-ORG:7710" (stere)
					//this cannot be set here in: SR-ORG:6647 (stere)
				}
				break;
			case 'standard_parallel_1':
				$wktParams->lat1 = $value * $wktParams->to_rads;
				$wktParams->lat_ts = $value * $wktParams->to_rads; //SR-ORG:22
				break;
			case 'standard_parallel_2':
				$wktParams->lat2 = $value * $wktParams->to_rads;
				break;
			case 'rectified_grid_angle':
				if (!isset($wktParams->alpha)) {
					//I'm not sure if this should be set here.
					//EPSG:3167 defineds azimuth and rectified_grid_angle. both are similar (azimuth is closer)
					//SR-ORG:7172 defines both, and both are 90.
					$wktParams->alpha = $value * $wktParams->to_rads;
				}
				break;
			case 'azimuth':
				$wktParams->alpha = $value * $wktParams->to_rads; //EPSG:2057
				break;
			case 'more_here':
				break;
			default:
				break;
			}
			break;
		case 'TOWGS84':
			$wktParams->datum_params = $wktArray;
			break;
		//DGR 2010-11-12: AXIS
		case 'AXIS':
			$name = strtolower($wktName);
			$value = array_shift($wktArray);
			switch ($value) {
			case 'EAST':
				$value = 'e';
				break;
			case 'WEST':
				$value = 'w';
				break;
			case 'NORTH':
				$value = 'n';
				break;
			case 'SOUTH':
				$value = 's';
				break;
			case 'UP':
				$value = 'u';
				break;
			case 'DOWN':
				$value = 'd';
				break;
			case 'OTHER':
			default:
				//throw new Exception("Unknown Axis ".$name." Value:  ".$value);
				$value = ' ';

				break; // FIXME
			}
			if (!isset($wktParams->axis)) {
				$wktParams->axis = "enu";
			}

			switch ($name) {
			case 'e(x)': // EPSG:2140
			case 'x':

				$wktParams->axis = $value . substr($wktParams->axis, 1, 2);
				break;
			case 'n(y)':
			case 'y':

				$wktParams->axis = substr($wktParams->axis, 0, 1) . $value . substr($wktParams->axis, 2, 1);
				break;
			case 'z':$wktParams->axis = substr($wktParams->axis, 0, 2) . $value;
				break;

			// Here is a list of other axis that exist in wkt definitions. are they useful?

			case 'geodetic latitude': //from SR-ORG:29

			case 'latitude':
			case 'lat':
			case 'geodetic longitude':
			case 'longitude':
			case 'long':
			case 'lon':

			case 'e':
			case 'n': //SR-ORG:4705

			case 'gravity-related height':

			case 'geocentric y': //SR-ORG:7910

			case 'east':
			case 'north': //SR-ORG:4705

			case 'ellipsoidal height': //EPSG:3823

			case 'easting':
			case 'northing':
			case 'southing': //SR-ORG:8262
				break;

			default:
				throw new \Exception("Unknown Axis Name: " . $name); //for testing
				break;
			}

		case 'EXTENSION':

			$name = strtolower($wktName);
			$value = array_shift($wktArray);
			switch ($name) {
			case 'proj4':
				// WKT can define a proj4 definition. for example SR-ORG:6
				$wktParams->defData = $value;
				break;
			default:
				break;
			}
			break;
		case 'MORE_HERE':
			break;
		default:
			break;
		}

		foreach ($wktArray as $wktArrayContent) {

			self::ParseRecursive($wktArrayContent, $wktParams);
		}

		//print_r(json_encode($wktParams,JSON_PRETTY_PRINT));
		return $wktParams;

	}

	/**
	 * returns an array with three parts,
	 * 0: the wktObject ie: PROJCS
	 * 1: the wktName ie: NAD_1983_UTM_Zone_17N
	 * 2: the wkt sub sections array, returns nested wkt strings as an array, each can be passed into this
	 * method again later
	 *
	 * @param string $wktStr
	 */
	private static function ParseWKTIntoSections($wktStr) {
		$regex = '/^(\w+)\[(.*)\]$/';

		if (false === ($match = preg_match($regex, $wktStr, $wktMatch))) {
			return;
		}
		if (!isset($wktMatch[1])) {
			return;
		}

		$wktObject = $wktMatch[1];
		$wktContent = $wktMatch[2];
		$wktTemp = explode(",", $wktContent);

		$wktName = (strtoupper($wktObject) == "TOWGS84") ? "TOWGS84" : trim(array_shift($wktTemp), '"');

		$wktArray = array();
		$bkCount = 0;

		$obj = '';
		while (count($wktTemp)) {

			$obj .= array_shift($wktTemp);
			$bkCount = substr_count($obj, '[') - substr_count($obj, ']');

			if ($bkCount === 0) {
				array_push($wktArray, $obj);
				$obj = '';
			} else {
				$obj .= ',';
			}
		}

		return array(
			$wktObject,
			$wktName,
			$wktArray,
		);
	}

	protected static function parseWKTToMeter($wktName, &$wktArray) {
		if ($wktName == 'US survey foot' ||
			$wktName == 'US Survey Foot' ||
			$wktName == 'Foot_US' ||
			$wktName == 'U.S. Foot' ||
			$wktName == "Clarke's link" ||
			$wktName == "Clarke's foot" ||
			$wktName == "link" ||
			$wktName == "Gold Coast foot" ||
			$wktName == "foot" ||
			$wktName == "Foot" ||
			$wktName == "British chain (Sears 1922 truncated)" ||
			$wktName == "Meter" ||
			$wktName == "metre" ||
			$wktName == "foot_survey_us" ||
			$wktName == "Kilometer" ||
			$wktName == "international_feet" ||
			$wktName == "m" ||
			$wktName == "Mile_US" ||
			$wktName == "Coord" ||
			$wktName == "Indian yard" ||
			$wktName == "British yard (Sears 1922)" ||
			$wktName == "British chain (Sears 1922)" ||
			$wktName == "British foot (Sears 1922)"
		) {

			//$wktName=="1/32meter" = 0.03125 SR-ORG:98 ? should we support this?

			//Example projections with non-meter units:
			// R-ORG:27 Foot_US
			// EPSG:2066 Clarke's link http://georepository.com/unit_9039/Clarke-s-link.html
			// EPSG:2136 Gold Coast foot, 0.3047997101815088
			// EPSG:2155 US survey foot
			// SR-ORG:6659 US Survey Foot
			// EPSG:2222 foot
			// EPSG:2314 Clarke's foot
			// EPSG:3140 link
			// EPSG:3167 British chain (Sears 1922 truncated)",20.116756
			// SR-ORG:6635 UNIT["Meter",-1]
			// SR-ORG:6887 U.S. Foot
			// SR-ORG:6982 UNIT[\"metre\",1.048153]]
			// SR-ORG:7008 foot_survey_us
			// SR-ORG:7496 Kilometer
			// SR-ORG:7508 international_feet
			// SR-ORG:7677 Foot
			// SR-ORG:7753 m = 9000.0
			// SR-ORG:7889 Mile_US
			// SR-ORG:8262 Coord = 0.0746379
			// EPSG:24370 Indian yard 0.9143985307444408
			// EPSG:27291 British yard (Sears 1922) 0.9143984146160287
			// EPSG:29871 British chain (Sears 1922) 20.11676512155263,
			// EPSG:29872 British foot (Sears 1922) 0.3047994715386762,
			return floatval(array_shift($wktArray));

		}
		return false;

	}

	/**
	 * convert from tmerc to utm+zone after parseWkt
	 * @return [type] [description]
	 */
	protected static function applyWKTUtmFromTmerc() {
		// 'UTM Zone 15',
		//  WGS_1984_UTM_Zone_17N,
		// 'Lao_1997_UTM_48N'
		// 'UTM Zone 13, Southern Hemisphere'
		// 'Hito XVIII 1963 / UTM zone 19S'
		// 'ETRS89 / ETRS-TM26' EPSG:3038 (UTM 26)
		$srsCode = strtolower(str_replace('_', ' ', $wktParams->srsCode));
		if (strpos($srsCode, "utm zone ") !== false || strpos($srsCode, "lao 1997 utm ") !== false || strpos($srsCode, "etrs-tm") !== false) {

			$srsCode = str_replace('-tm', '-tm ', $srsCode); //'ETRS89 / ETRS-TM26' ie: EPSG:3038 (UTM 26)

			$zoneStr = substr($srsCode, strrpos($srsCode, ' ') + 1);
			$zlen = strlen($zoneStr);
			if ($zoneStr{$zlen - 1} == 'n') {
				$zoneStr = substr($zoneStr, 0, -1);
			} elseif ($zoneStr{$zlen - 1} == 's') {
				// EPSG:2084 has Hito XVIII 1963 / UTM zone 19S
				$zoneStr = substr($zoneStr, 0, -1);
				$wktParams->utmSouth = true;
			}
			$wktParams->zone = intval($zoneStr, 10);
			$wktParams->projName = "utm";
			if (!isset($wktParams->utmSouth)) {
				$wktParams->utmSouth = false;
			}
		}
	}

	protected static function parseWKTToRads($wktName, &$wktArray) {
		if ($wktName == 'Radian' ||
			$wktName == 'Degree' ||
			$wktName == 'degree' ||
			$wktName == 'grad'
		) {

			// SR-ORG:7753 degree=0.081081
			// SR-ORG:8163 grad=0.01570796326794897,

			return floatval(array_shift($wktArray));

		}
		return false;

	}

	protected static function initWKTProjections() {
		self::$wktProjections["Lambert_Conformal_Conic"] = "lcc";
		self::$wktProjections["Lambert Tangential Conformal Conic Projection"] = "lcc";
		self::$wktProjections["Lambert_Conformal_Conic_1SP"] = "lcc"; //SR-ORG:91
		self::$wktProjections["Lambert_Conformal_Conic_2SP"] = "lcc";
		self::$wktProjections["Lambert_Conformal_Conic_2SP_Belgium"] = "lcc"; //SR-ORG:49
		self::$wktProjections["Mercator"] = "merc";
		self::$wktProjections["Mercator_1SP"] = "merc";
		self::$wktProjections["Mercator_2SP"] = "merc";
		self::$wktProjections["Transverse_Mercator"] = "tmerc";
		self::$wktProjections["Transverse Mercator"] = "tmerc";
		self::$wktProjections["Lambert Azimuthal Equal Area"] = "laea";
		self::$wktProjections["Universal Transverse Mercator System"] = "utm";

		self::$wktProjections["Mollweide"] = 'moll'; //SR-ORG:7
		self::$wktProjections["Albers_Conic_Equal_Area"] = 'aea'; //SR-ORG:10
		self::$wktProjections['Albers_conic_equal_area'] = 'aea'; //SR-ORG:6952

		self::$wktProjections["Cylindrical_Equal_Area"] = "cea"; //SR-ORG:22
		self::$wktProjections["Lambert_Azimuthal_Equal_Area"] = "laea"; //SR-ORG:28
		self::$wktProjections["Krovak"] = "krovak"; //SR-ORG:36
		self::$wktProjections["Oblique_Stereographic"] = "sterea"; //SR-ORG:43
		self::$wktProjections["Polyconic"] = "poly"; //SR-ORG:86
		self::$wktProjections["New_Zealand_Map_Grid"] = "nzmg"; //SR-ORG:118
		self::$wktProjections["Hotine_Oblique_Mercator"] = "omerc"; //EPSG:2057
		self::$wktProjections["hotine_oblique_mercator"] = "omerc"; //SR-ORG:7531

		self::$wktProjections["Cassini_Soldner"] = "cass"; //EPSG:2066
		self::$wktProjections["Polar_Stereographic"] = "stere"; //EPSG:3031
		self::$wktProjections['Equirectangular'] = "eqc"; //EPSG:3786
		self::$wktProjections["Sinusoidal"] = "sinu"; //SR-ORG:4741
		self::$wktProjections["Stereographic"] = "stere"; //SR-ORG:6647

		// self::$wktProjections['VanDerGrinten']='vandg';
		self::$wktProjections['Orthographic'] = 'ortho'; //SR-ORG:6980
		self::$wktProjections["Azimuthal_Equidistant"] = "aeqd"; //SR-ORG:7238
		self::$wktProjections["Miller_Cylindrical"] = "mill"; //SR-ORG:8064
		self::$wktProjections["Equidistant_Conic"] = "eqdc"; //SR-ORG:8159

		self::$wktProjections['Hotine_Oblique_Mercator_Two_Point_Natural_Origin'] = 'omerc'; //ESRI:53025
		self::$wktProjections['Hotine_Oblique_Mercator_Azimuth_Center'] = 'omerc';
		self::$wktProjections['VanDerGrinten'] = 'vandg'; //ESRI:53029

	}

	protected static function initWKTEllipsoids() {

		self::$wktEllipsoids["Clarke 1880 (RGS)"] = "clrk80"; //EPSG:2000
		self::$wktEllipsoids["Clarke_1880_RGS"] = "clrk80"; //SR-ORG:7244
		self::$wktEllipsoids["Clarke_1866"] = "clrk66"; //SR-ORG:11
		self::$wktEllipsoids['Clarke 1880'] = "clrk80"; //EPSG:62416405
		//self::$wktEllipsoids["Krasovsky_1940"]="krass"; //SR-ORG:7191
		//self::$wktEllipsoids["WGS 84"]="WGS84"; //SR-ORG:62

	}

	protected static function initWKTDatums() {

		self::$wktDatums["WGS_1984"] = "WGS84"; // SR-ORG:3 and 4, etc
		self::$wktDatums["World Geodetic System 1984"] = "WGS84"; // SR-ORG:29
		self::$wktDatums["D_WGS_1984"] = "WGS84"; //SR-ORG:6917 but breaks SR-ORG:6668
		//self::$wktDatums["World Geodetic System 1984"]="WGS84"; //SR-ORG:29
		self::$wktDatums["North_American_Datum_1983"] = "NAD83"; //SR-ORG:10
		self::$wktDatums["North American Datum 1983"] = "NAD83"; //SR-ORG:7220
		self::$wktDatums["North_American_Datum_1927"] = "NAD27"; //SR-ORG:11
		self::$wktDatums["North American Datum 1927"] = "NAD27";
		self::$wktDatums["Deutsches_Hauptdreiecksnetz"] = "potsdam"; //EPSG:3068
		self::$wktDatums["New_Zealand_Geodetic_Datum_1949"] = "nzgd49"; //EPSG:4272
		self::$wktDatums["OSGB_1936"] = "OSGB36"; // EPSG:4277
		self::$wktDatums["New Zealand Geodetic Datum 1949"] = "nzgd49"; //EPSG:62726405
		self::$wktDatums["OSGB 1936"] = "OSGB36"; // EPSG:62776405
		self::$wktDatums["Deutsches Hauptdreiecksnetz"] = "potsdam"; // EPSG:63146405

	}

}