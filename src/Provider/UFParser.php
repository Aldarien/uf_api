<?php
namespace UF\API\Provider;

use UF\API\Model\UF;
use Carbon\Carbon;
use UF\API\Definition\Getter;

/**
 * Handles the getters' information and interacts with the database
 * @author Aldarien
 *
 */
class UFParser
{
	/**
	 * Get from all getters specified
	 * @param array $getters
	 */
	public function getAll(array $getters)
	{
		foreach ($getters as $getter) {
			if (!is_a($getter, Getter::class)) {
				continue;
			}
			$this->get($getter);
		}
	}
	/**
	 * Get all the data from the getter and saves what is not in the database.
	 * @param Getter $getter
	 */
	public function get(Getter $getter)
	{
        for ($year = (int) date('Y'); $year > 1900; $year --) {
			if ($this->checkYear($year) and $this->getYear($getter, $year)) {
                $this->addTime(60);
				sleep(1 * 60);
			}
		}
	}
	/**
	 * Checks if the year is saved in the database.
	 * @param int $year
	 * @return boolean
	 */
	public function checkYear(int $year)
	{
		$uf1 = \Model::factory('\UF\API\Model\UF')->where('fecha', $year . '-01-01')->findOne();
		$y = date('Y');
		if ($y == $year) {
            return false;
			/*$today = Carbon::today(new \DateTimeZone(config('app.timezone')));
			$uf2 = \Model::factory('\UF\API\Model\UF')->where('fecha', $today->format('Y-m-d'))->findOne();*/
		}
		$uf2 = \Model::factory('\UF\API\Model\UF')->where('fecha', $year . '-12-31')->findOne();
		if ($uf1 and $uf2) {
			return true;
		}
		return false;
	}
	public function findGetter(string $getter_name)
	{
		if ($class = config('getters.' . $getter_name . '.class')) {
			return new $class();
		}
		return null;
	}
	public function listGetters()
	{
		$data = config('getters');
		$getters = [];
		foreach ($data as $get) {
			$getters []= new $get['class']();
		}
		return $getters;
	}
	public function getYear(Getter $getter, int $year) {
        $this->addTime(3*60);
        $ufs = $getter->get($year);
		if (!$ufs) {
			return false;
		}

        $tz = new \DateTimeZone(config('app.timezone'));
		foreach ($ufs as $date => $value) {
			$f = Carbon::parse($date, $tz);
			$uf = \Model::factory('\UF\API\Model\UF')->where('fecha', $f->format('Y-m-d'))->findOne();
			if (!$uf) {
				$uf = \Model::factory('\UF\API\Model\UF')->create();
				$uf->fecha = $f;
				$uf->valor = $value;
				$uf->save();
			}
		}
		return true;
	}
    protected function addTime($seconds)
    {
        $max = ini_get('max_execution_time');
        set_time_limit($max + $seconds);
    }
}
?>
