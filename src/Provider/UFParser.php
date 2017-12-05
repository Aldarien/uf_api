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
		$y = date('Y');
		if ($y == $year) {
            return false;
		}
		$d1 = \Model::factory('\UF\API\Model\UF')->whereLike('fecha', $year . '%')->count('id');
		$d0 = Carbon::parse($year . '-01-01');
		if ($d1 = (365 + $d0->format('L'))) {
			return true;
		}
		return false;
	}
	/**
	 * Find the getter in the configuration
	 * @param string $getter_name
	 * @return Getter|NULL
	 */
	public function findGetter(string $getter_name)
	{
		if ($class = config('getters.' . $getter_name . '.class')) {
			return new $class();
		}
		return null;
	}
	/**
	 * Get an array of getters in the configuration
	 * @return Getter[]
	 */
	public function listGetters()
	{
		$data = config('getters');
		$getters = [];
		foreach ($data as $get) {
			$getters []= new $get['class']();
		}
		return $getters;
	}
	/**
	 * Get all values for $year from Getter $getter
	 * @param Getter $getter
	 * @param int $year
	 * @return boolean
	 */
	public function getYear(Getter $getter, int $year) {
        $this->addTime(3*60);
        $ufs = $getter->get($year);
		if (!$ufs) {
			return false;
		}

        foreach ($ufs as $date => $value) {
			$f = Carbon::parse($date, config('app.timezone'));
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
	/**
	 * Add to execution time
	 * @param int $seconds
	 */
    protected function addTime($seconds)
    {
        $max = ini_get('max_execution_time');
        set_time_limit($max + $seconds);
    }
}
?>
