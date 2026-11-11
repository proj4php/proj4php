<?php

use PHPUnit\Framework\TestCase;
use proj4php\Point;
use proj4php\Proj;
use proj4php\Proj4php;

/**
 * Points where stere, ortho, vandg, gnom, laea and cass gave wrong results or threw,
 * checked against PROJ (cct -d 10).
 */
class ProjectionFixesTest extends TestCase
{
    private const SPHERE = '+a=6378137 +b=6378137';

    private function geo(Proj4php $proj4, string $shape): Proj
    {
        return new Proj("+proj=longlat $shape +no_defs", $proj4);
    }

    private function forward(string $def, string $shape, float $lon, float $lat, float $x, float $y, float $tol = 1e-3): void
    {
        $proj4 = new Proj4php();
        $prj = new Proj("$def $shape +units=m +no_defs", $proj4);
        $r = $proj4->transform($prj, new Point($lon, $lat, $this->geo($proj4, $shape)));
        $this->assertEqualsWithDelta($x, $r->x, $tol, "$def x");
        $this->assertEqualsWithDelta($y, $r->y, $tol, "$def y");
    }

    private function inverse(string $def, string $shape, float $x, float $y, float $lon, float $lat, float $tol = 1e-8, float $lonTol = 1e-8): void
    {
        $proj4 = new Proj4php();
        $prj = new Proj("$def $shape +units=m +no_defs", $proj4);
        $r = $proj4->transform($this->geo($proj4, $shape), new Point($x, $y, $prj));
        $this->assertEqualsWithDelta($lon, $r->x, $lonTol, "$def lon");
        $this->assertEqualsWithDelta($lat, $r->y, $tol, "$def lat");
    }

    public function testStereographic()
    {
        // oblique and equatorial centres were set up as polar ones
        $this->forward('+proj=stere +lat_0=0', self::SPHERE, -48.1, -78.1, -1720856.0629926636, -10971275.7844495904);
        $this->forward('+proj=stere +lat_0=45', '+ellps=WGS84', 40, 50, 2796342.6578293229, 1305145.8208192468);
        $this->forward('+proj=stere +lat_0=0', '+ellps=WGS84', 40, 50, 3540909.4312021420, 6521065.9714589724);
        // the ellipsoidal inverse threw (undefined constants phi_l and lat)
        $this->inverse('+proj=stere +lat_0=-30 +lon_0=20', '+ellps=WGS84', 1000000, -2000000, 32.9088339533, -47.2366086596);
        $this->inverse('+proj=stere +lat_0=0', '+ellps=WGS84', 1000000, -2000000, 9.1883439773, -17.8232647209);
        // the centre itself came back as latitude 0
        $this->inverse('+proj=stere +lat_0=90', self::SPHERE, 0, 0, 0, 90);
    }

    public function testOrthographic()
    {
        // the inverse threw (undefined constant con), and had no longitude for oblique centres
        $this->inverse('+proj=ortho +lat_0=45 +lon_0=10', self::SPHERE, 1000000, 2000000, 29.5920085548, 62.1233359901);
    }

    public function testVanDerGrinten()
    {
        // the forward used an undefined $sq and a fixed radius of 6370997 m
        $this->forward('+proj=vandg', self::SPHERE, 74.5, 10, 8270884.5431942781, 1127256.4268194742);
        // the inverse threw ($p->$y, m1, $this->$long0)
        $this->inverse('+proj=vandg', self::SPHERE, 8000000, 1000000, 72.0198915116, 8.8909979859);
    }

    public function testCentres()
    {
        // the centre itself came back as (0, 0) (gnom used $this->phic0, laea $this->phi0)
        $this->inverse('+proj=gnom +lat_0=45 +lon_0=10', self::SPHERE, 0, 0, 10, 45);
        $this->inverse('+proj=laea +lat_0=45 +lon_0=10', self::SPHERE, 0, 0, 10, 45);
        // polar laea rejected points on the equator (it tested phi + phi0, with phi0 unset)
        $this->forward('+proj=laea +lat_0=90', self::SPHERE, 30, 0, 4510023.9240368214, -7811590.5797829349);
    }

    /**
     * Precision close to the poles, the equator and the origin; expected values from 50-digit
     * computations on a sphere of radius 6400000 m.
     */
    public function testPrecision()
    {
        $sphere = '+a=6400000 +b=6400000';
        // the Newton iteration converges only linearly close to the poles
        $this->forward('+proj=moll', $sphere, 170, 89.9, 261739.81548119086, 9049906.014497606, 1e-6);
        // two large terms were subtracted close to the origin
        $this->forward('+proj=vandg', $sphere, 1e-6, 2e-6, 0.11170107212763708, 0.22340214425527422, 1e-6);
        // asin() of a value close to 1 (latitude to 1e-11 degrees; this close to the pole a
        // longitude error of 1e-8 degrees is only 2e-7 m)
        $this->inverse('+proj=stere +lat_0=45', $sphere, 447.5574930584751, 5300703.896968693, 19.99999999999799, 89.99, 1e-11);
    }

    public function testCassini()
    {
        // the spherical forward ignored +lat_0 ($this->phi0 was never set)
        $this->forward('+proj=cass +lat_0=40', self::SPHERE, 2, 45, 157413.5449679516, 558540.7401694322);
    }
}
