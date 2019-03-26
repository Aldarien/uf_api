<?php
namespace App\Controller;

use Carbon\Carbon;
use App\Service\Factory;
use UF\API\Provider\UFParser;
use UF\API\Model\UF;

class UFApi extends Controller
{
  protected $start;

  protected function start() {
    $this->start = microtime(true);
  }

  public function index($request, $response, $arguments) {
  	$cmd = strtolower($arguments['cmd']);
  	switch ($cmd) {
  		case 'value':
  			return $this->value($request, $response, $arguments);
  		case 'list':
  			return $this->ufs($request, $response, $arguments);
  		case 'transform':
  			return $this->transform($request, $response, $arguments);
  		case 'setup':
  			return $this->setup($request, $response, $arguments);
  		case 'load':
  			return $this->load($request, $response, $arguments);
  		case 'remove':
  		case 'delete':
  			return $this->remove($request, $response, $arguments);
  		case 'help':
  			return $this->help($request, $response, $arguments);
  	}
  }
  protected function buildOutput(array $data, int $status = 200) {
    $output = [
      'status' => $status,
      'output' => $data,
      'time' => microtime(true) - $this->start
    ];
    return $output;
  }
  protected function parseDate($arguments) {
    $tz = $this->container->settings['app']['timezone'];
    if (!isset($arguments['month'])) {
      return Carbon::parse($arguments['arg'], $tz);
    }

    $today = Carbon::today($tz);
    if (isset($arguments['arg']) or isset($arguments['month']) or isset($arguments['day'])) {
      $year = $today->year;
      if (isset($arguments['arg'])) {
        $year = $arguments['arg'];
      }
      $month = $today->month;
      if (isset($arguments['month'])) {
        $month = $arguments['month'];
      }
      $day = $today->day;
      if (isset($arguments['day'])) {
        $day = $arguments['day'];
      }
      return Carbon::createFromDate($year, $month, $day, $tz);
    }
    return $today->copy();
  }
  protected function getNext() {
    $tz = $this->container->settings['app']['timezone'];
    $today = Carbon::today($tz);
    return $today->copy()->addMonth(1)->day(9);
  }
  protected function getUF($search, $many = false) {
    $uf = (new Factory(UF::class))->where($search)->find($many);
    if (!$uf) {
      $this->load();
      return $this->getUF($search);
    }
    return $uf;
  }
  public function value($request, $response, $arguments) {
    $this->start();
    $date = $this->parseDate($arguments);
    $next_9 = $this->getNext();
		if ($date > $next_9) {
      return $response->withStatus(204);
      //return api(false, 204);
    } else {
      //$uf = \Model::factory(UF::class)->where('fecha', $date->format('Y-m-d'))->findOne();
      $uf = $this->getUF(['fecha', $date->format('Y-m-d')]);
      if (!isset($uf->valor)) {
        $output = ['total' => 0];
      } else {
        $output = ['uf' => ['date' => $date->format('Y-m-d'), 'value' => $uf->valor], 'total' => 1];
      }
      return $response->withJSON($this->build($output));
      //return api($output);
    }
  }
  public function ufs($request, $response, $arguments) {
    $this->start();
    $tz = $this->container->settings['app']['timezone'];
    $today = Carbon::today($tz);
    $year = $today->year;
    if (isset($arguments['year'])) {
      $year = $arguments['year'];
    }
    if (isset($arguments['month'])) {
      $month = $arguments['month'];
    	//$ufs = \Model::factory(UF::class)->whereLike('fecha', $year . '-' . $month . '%')->orderByAsc('fecha')->findMany();
      $find = $year . '-' . $month . '%';
    } else {
      //$ufs = \Model::factory(UF::class)->whereLike('fecha', $year . '%')->orderByAsc('fecha')->findMany();
      $find = $year . '%';
    }
    $ufs = (new Factory(UF::class))->where(['fecha', $find, 'like'])->order(['fecha'])->find(true);
    $output = ['total' => count($ufs)];
    foreach ($ufs as $uf) {
      $output['ufs'] []= ['date' => $uf->fecha, 'value' => $uf->valor];
    }
    return $response->withJSON($this->buildOutput($output));
    //return api($output);
  }
  public function transform($request, $response, $arguments) {
    $this->start();
    $type = $arguments['to'];
  	$value = $arguments['value'];
  	/*$tz = new \DateTimeZone(config('app.timezone'));
  	$date = Carbon::parse($arguments['date'], $tz);
  	$year = $arguments['year'];
  	$month = $arguments['month'];
  	$day = $arguments['day'];

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
  	}*/
    $date = $this->parseDate($arguments);
  	//$next_9 = $today->copy()->addMonth(1)->day(9);
  	$next_9 = $this->getNext();
  	if ($date > $next_9) {
      return $response->withStatus(204);
  		//return api(false, 204);
  	} else {
  		//$uf = \Model::factory(UF::class)->where('fecha', $date->format('Y-m-d'))->findOne();
      $uf = $this->getUF(['fecha' => $date->format('Y-m-d')]);
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
      return $response->withJSON($this->buildOutput($output));
  		//return api($output);
  	}
  }
  public function help($request, $response, $arguments) {
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
  						'day' => ['type' => 'int']
            ],
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
    return $response->withJSON($output);
  	//return api($output);
  }

  public function setup($request, $response, $arguments) {
  	$start = microtime(true);
  	$this->loadUF();
    return $response->withJSON($this->buildOutput(['status' => 'ok']));
  	//return api(['status' => 'ok', 'time' => microtime(true) - $start]);
  }
  protected function loadUF() {
  	$parser = new UFParser();
  	$getters = $parser->listGetters();
  	$parser->getAll($getters);
  }
  public function load($request, $response, $arguments) {
  	$year = $arguments['year'];
  	$parser = new UFParser();
  	//$start = microtime(true);
  	$this->start();

  	$getters = [];
  	if (!isset($arguments['getter'])) {
  		$getters = $parser->listGetters();
  	} else {
      $getter = $arguments['getter'];
  		$getters = $parser->findGetter($getter);
  	}
    /*if (!isset($arguments['year'])) {
      if (isset($arguments['year'])) {
        $date = $arguments['year'];
        $tz = $this->container->settings['app']['timezone'];
        $date = Carbon::parse($date, $tz);
        $year = $date->year;
      }
    }*/
  	if (!isset($arguments['year'])) {
      foreach ($getters as $getter) {
  			$parser->get($getter);
  		}
  	} else {
      $year = $arguments['year'];
  		foreach ($getters as $getter) {
  			$parser->getYear($getter, $year);
  		}
  	}
    return $response->withJSON($this->buildOutput(['status' => 'ok', 'getters' => count($getters)]));
  	//return api(['status' => 'ok', 'getters' => count($getters), 'time' => microtime(true) - $start]);
  }
  public function remove($request, $response, $arguments) {
		$this->start();
    $date = $this->parseDate($arguments);
    /*$tz = new \DateTimeZone(config('app.timezone'));
		$date = $arguments['date'];
		$year = $arguments['year'];
		$month = $arguments['month'];
		$day = $arguments['day'];

		$today = Carbon::today($tz);*/
    $next_9 = $this->getNext();
    if ($date > $next_9) {
      return $response->withStatus(204);
    }
    $find = $date->year . '%';
    $like = true;
    if (isset($arguments['month'])) {
      $find = $date->year . '-' . $date->month . '%';
    }
    if (isset($day)) {
      $find = $date->format('Y-m-d');
      $like = false;
    }
    $ufs = $this->getUF(['fecha', $find, $like], true);
		/*if ($year or $month or $day) {
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
						$ufs = \Model::factory(UF::class)->where('fecha', $date->format('Y-m-d'));
					}
				} else {
					$ufs = \Model::factory(UF::class)->whereLike('fecha', $year . '-' . $month . '%')->orderByAsc('fecha');
				}
			} else {
				$ufs = \Model::factory(UF::class)->whereLike('fecha', $year . '%')->orderByAsc('fecha');
			}
		} else {
			if ($date) {
				$date = Carbon::parse($arguments['date'], $tz);
				$next_9 = $today->copy()->addMonth(1)->day(9);
				if ($date > $next_9) {
					return api(false, 204);
				} else {
					$ufs = \Model::factory(UF::class)->where('fecha', $date->format('Y-m-d'));
				}
			} else {
				$ufs = \Model::factory(UF::class);
			}
		}*/
		if (count($ufs) > 100) {
			set_time_limit(count($ufs) * 3);
		}
    $cnt = $ufs->count();
		$status = ($ufs->deleteMany()) ? 'ok' : 'error';
		$output = ['status' => $status, 'total' => $cnt];
    return $response->withJSON($this->buildOutput($output));
		//return api($output);
	}
}
?>
