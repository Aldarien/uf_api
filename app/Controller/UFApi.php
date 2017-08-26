<?php
namespace App\Controller;

use Carbon\Carbon;
use UF\API\Provider\UFParser;

class UFApi
{
    public static function index()
    {
    	$cmd = strtolower(input('cmd'));
    	switch ($cmd) {
    		case 'value':
    			return self::value();
    		case 'list':
    			return self::ufs();
    		case 'transform':
    			return self::transform();
    		case 'setup':
    			return self::setup();
    		case 'load':
    			return self::load();
    		case 'remove':
    		case 'delete':
    			return self::remove();
    		case 'help':
    			return self::help();
    	}
    }
    public static function value()
    {
    	$tz = new \DateTimeZone(config('app.timezone'));
		$date = Carbon::parse(input('date'), $tz);
		$year = input('year');
		$month = input('month');
		$day = input('day');
		$today = Carbon::today($tz);
		if ($year or $month or $day) {
			if (!$year) {
				$year = $today->year;
			}
			if (!$month) {
				$month = $today->month;
			}
			if (!$day) {
				$day = $today->day;
			}
			$date = Carbon::createFromDate($year, $month, $day, $tz);
		}
		if (!$date) {
			$date = $today->copy();
		}
        $next_9 = $today->copy()->addMonth(1)->day(9);
		if ($date > $next_9) {
            return api(false, 204);
        } else {
            $uf = \Model::factory('\UF\API\Model\UF')->where('fecha', $date->format('Y-m-d'))->findOne();
			if (!$uf) {
				self::load();
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
        	self::load();
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
    public static function transform()
    {
    	$type = input('to');
    	$value = input('value');
    	$tz = new \DateTimeZone(config('app.timezone'));
    	$date = Carbon::parse(input('date'), $tz);
    	$year = input('year');
    	$month = input('month');
    	$day = input('day');
    	
    	$today = Carbon::today($tz);
    	if ($year or $month or $day) {
    		if (!$year) {
    			$year = $today->year;
    		}
    		if (!$month) {
    			$month = $today->month;
    		}
    		if (!$day) {
    			$day = $today->day;
    		}
    		$date = Carbon::createFromDate($year, $month, $day, $tz);
    	}
    	if (!$date) {
    		$date = $today->copy();
    	}
    	$next_9 = $today->copy()->addMonth(1)->day(9);
    	if ($date > $next_9) {
    		return api(false, 204);
    	} else {
    		$uf = \Model::factory('\UF\API\Model\UF')->where('fecha', $date->format('Y-m-d'))->findOne();
    		switch (strtolower($type)) {
    			case 'uf':
    			case 'clf':
    				$result = $value / $uf->valor;
    				break;
    			case 'pesos':
    			case 'clp':
    				$result = $value * $uf->valor;
    				break;
    			default:
    				$result = 'Can not transform to ' . $type;
    		}
    		$output = [
    				'status' => 'ok',
    				'date' => $date->format('Y-m-d'),
    				'from' => $value,
    				'to' => $result
    		];
    		return api($output);
    	}
    }
    public static function help()
    {
    	$output = [];
    	$output['commands'] = [
    			'uf' => [
    					'subcommands' => [
    							'value' => [
    									'options' => [
		    									'date' => ['type' => 'string', 'format' => 'Y-m-d'],
		    									'year' => ['type' => 'int'],
		    									'month' => ['type' => 'int'],
    											'day' => ['type' => 'int']
    									],
    									'description' => 'returns the value for CLF for that date'
    							],
    							'list' => [
    									'options' => [
    											'year' => ['type' => 'int'],
    											'month' => ['type' => 'int']
    									],
    									'description' => 'returns all CLF values for the month or year'
    							],
    							'transform' => [
    									'options' => [
    											'date' => ['type' => 'string', 'format' => 'Y-m-d'],
    											'year' => ['type' => 'int'],
    											'month' => ['type' => 'int'],
    											'day' => ['type' => 'int'],
    											'to' => ['type' => 'string', 'options' => ['clp', 'clf', 'pesos', 'uf']],
    											'value' => ['type' => 'float']
    									],
    									'description' => 'returns the transformation of the value to CLP or CLF'
    							],
    							'setup' => [
    									'description' => 'loads all CLF values that are missing from the database'
    							],
    							'load' => [
    									'options' => [
    											'getter' => ['type' => 'string'],
    											'year' => ['type' => 'int']
    									],
    									'description' => 'loads all CLF for the selected year'
    							],
    							'remove' => [
    									'options' => [
    											'date' => ['type' => 'string', 'format' => 'Y-m-d'],
    											'year' => ['type' => 'int'],
    											'month' => ['type' => 'int'],
    											'day' => ['type' => 'int']],
    									'description' => 'remove all values for date, month, year'
    							],
    							'delete' => [
    									'description' => 'alias of remove'
    							],
    							'help' => [
    									'description' => 'This help'
    							]
    					]
    			]
    	];
    	return api($output);
    }
    
    public static function setup()
    {
    	$start = microtime(true);
    	self::loadUF();
    	return api(['status' => 'ok', 'time' => microtime(true) - $start]);
    }
    protected static function loadUF()
    {
    	$parser = new UFParser();
    	$getters = $parser->listGetters();
    	$parser->getAll($getters);
    }
    public static function load()
    {
    	$year = input('year');
    	$getter = input('getter');
    	$parser = new UFParser();
    	$start = microtime(true);
    	
    	$getters = [];
    	if ($getter == null) {
    		$getters = $parser->listGetters();
    	} else {
    		$getters = $parser->findGetter($getter);
    	}
    	if ($year == null) {
    		foreach ($getters as $getter) {
    			$parser->get($getter);
    		}
    	} else {
    		foreach ($getters as $getter) {
    			$parser->getYear($getter, $year);
    		}
    	}
    	return api(['status' => 'ok', 'getters' => count($getters), 'time' => microtime(true) - $start]);
    }
	public static function remove()
	{
		$start = microtime(true);
		$tz = new \DateTimeZone(config('app.timezone'));
		$date = input('date');
		$year = input('year');
		$month = input('month');
		$day = input('day');
		
		$today = Carbon::today($tz);
		if ($year or $month or $day) {
			if ($year == null) {
				$year = $today->year;
			}
			
			if ($month != null) {
				if ($day != null) {
					$date = Carbon::createFromDate($year, $month, $day, $tz);
					$next_9 = $today->copy()->addMonth(1)->day(9);
					if ($date > $next_9) {
						return api(false, 204);
					} else {
						$ufs = \Model::factory('\UF\API\Model\UF')->where('fecha', $date->format('Y-m-d'))->findMany();
					}
				} else {
					$ufs = \Model::factory('\UF\API\Model\UF')->whereLike('fecha', $year . '-' . $month . '%')->orderByAsc('fecha')->findMany();
				}
			} else {
				$ufs = \Model::factory('\UF\API\Model\UF')->whereLike('fecha', $year . '%')->orderByAsc('fecha')->findMany();
			}
		} else {
			if ($date) {
				$date = Carbon::parse(input('date'), $tz);
				$next_9 = $today->copy()->addMonth(1)->day(9);
				if ($date > $next_9) {
					return api(false, 204);
				} else {
					$ufs = \Model::factory('\UF\API\Model\UF')->where('fecha', $date->format('Y-m-d'))->findMany();
				}
			} else {
				$ufs = \Model::factory('\UF\API\Model\UF')->findMany();
			}
		}
		if (count($ufs) > 100) {
			set_time_limit(count($ufs) * 3);
		}
		foreach ($ufs as $uf) {
			$uf->delete();
		}
		$output = ['status' => 'ok', 'total' => count($ufs), 'time' => microtime(true) - $start];
		return api($output);
	}
}
?>
