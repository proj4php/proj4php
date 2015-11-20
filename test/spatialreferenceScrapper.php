<?php


$next        = 'http://spatialreference.org/ref/';
$max         = 200; //pages
$fileContent = file_get_contents(__DIR__ . '/codes.json');
if (empty($fileContent)) {
    $pageCodes = array();
} else {
    $pageCodes = get_object_vars(json_decode($fileContent));
}
$count=0;
while ($next && $max !== 0) {

    $page  = file_get_contents($next);
    $codes = array_values(array_filter(array_map(function ($a) use (&$next) {

        //....>code:num
        $i   = strrpos($a, '>');
        $str = substr($a, $i + 1);
        if (trim($str) == 'Next Page') {
            $url  = explode('"', $a);
            $url  = $url[count($url) - 2];
            $next = 'http://spatialreference.org/ref/' . $url;
        }
        return $str;

    }, explode('</a>', $page)), function ($a) {

        if (strpos($a, ':') !== false) {
            return true;
        }

    }));
    echo 'page '.($count+1);
    if (!array_key_exists($codes[0], $pageCodes)) {

    	echo ' scrapping'.;

        array_walk($codes, function ($c) use (&$pageCodes) {
            $p             = explode(':', $c);
            $pageCodes[$c] = array(
                'ogcwkt'  => file_get_contents('http://spatialreference.org/ref/' . strtolower($p[0]) . '/' . $p[1] . '/ogcwkt/'),
                'proj4'   => file_get_contents('http://spatialreference.org/ref/' . strtolower($p[0]) . '/' . $p[1] . '/proj4/'),
                'esriwkt' => file_get_contents('http://spatialreference.org/ref/' . strtolower($p[0]) . '/' . $p[1] . '/esriwkt/'),
            );

            echo ' (success)'."\n";

        });
    }else{
		echo ' skipping (exists)'."\n";
    }

    //$next=false;
    $max--;
    $count++;

    file_put_contents(__DIR__ . '/codes.json', json_encode($pageCodes, JSON_PRETTY_PRINT));
}

$c = count($pageCodes);
echo 'Total: '.$c.' definition'.($c==1?'':'s')."\n";
