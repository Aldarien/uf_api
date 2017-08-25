<?php
namespace App\Controller;

use Carbon\Carbon;
use UF\API\Provider\UFParser;
use UF\API\Provider\SIIGetter;
use UF\API\Provider\ValorUFGetter;

class UFApi
{
    public static function index()
    {
    	if (input('date') != null) {
    		return self::value();
        } else {
            return self::ufs();
        }
    }
    public static function value()
    {
    	$tz = new \DateTimeZone(config('app.timezone'));
		$date = Carbon::parse(input('date'), $tz);
		$today = Carbon::today($tz);
        $next_9 = $today->copy()->addMonth(1)->day(9);
		if ($date > $next_9) {
            return api(false, 204);
        } else {
            $uf = \Model::factory('\UF\API\Model\UF')->where('fecha', $date->format('Y-m-d'))->findOne();
			if (!$uf) {
				self::loadUF();
				$uf = \Model::factory('\UF\API\Model\UF')->where('fecha', $date->format('Y-m-d'))->findOne();
			}
            $output = ['ufs' => ['date' => $date->format('Y-m-d'), 'value' => $uf->valor], 'total' => 1];
            return api($output);
        }
    }
    public static function ufs()
    {
        $year = input('year');
        if ($year == null) {
        	$tz = new \DateTimeZone(config('app.timezone'));
            $today = Carbon::today($tz);
            $year = $today->year;
        }
        $month = input('month');
        if ($month != null) {
        	$ufs = \Model::factory('\UF\API\Model\UF')->whereLike('fecha', $year . '-' . $month . '%')->orderByAsc('fecha')->findMany();
        } else {
            $ufs = \Model::factory('\UF\API\Model\UF')->whereLike('fecha', $year . '%')->orderByAsc('fecha')->findMany();
        }
        if (count($ufs) == 0) {
        	self::loadUF();
        	if ($month != null) {
        		$ufs = \Model::factory('\UF\API\Model\UF')->whereLike('fecha', $year . '-' . $month . '%')->orderByAsc('fecha')->findMany();
        	} else {
        		$ufs = \Model::factory('\UF\API\Model\UF')->whereLike('fecha', $year . '%')->orderByAsc('fecha')->findMany();
        	}
        }
        $output = ['total' => count($ufs)];
        foreach ($ufs as $uf) {
            $output['ufs'] []= ['date' => $uf->fecha, 'value' => $uf->valor];
        }
        return api($output);
    }
    public static function help()
    {
    	$output = [];
    	$output['commands'] = ['uf' => ['value' => ['date' => '(string) Y-m-d'], 'list' => ['year' => '(int) Y', 'month' => '(int) m']]];
    	return api($output);
    }
    
    public static function loadUF()
    {
    	$parser = new UFParser();
    	$getters = [];
    	$getters []= new SIIGetter();
    	$getters []= new ValorUFGetter();
    	$parser->getAll($getters);
    }
}
?>
