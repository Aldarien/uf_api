<?php
namespace UF\API\Provider;

use UF\API\Definition\Getter;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Goutte\Client as GClient;

/**
 * Getter from http://www.valoruf.cl
 * @author Aldarien
 *
 */
class ValorUFGetter implements Getter
{
	/**
	 * Getter name for identifying in configuration
	 * @var string
	 */
	protected $getter;
	/**
	 * Client connection
	 * @var GuzzleHttp\Client
	 */
	protected $client;

	public function __construct()
	{
		$this->getter = 'valoruf';
		$this->client = new Client(['base_uri' => config('getters.uf.' . $this->getter . '.url')]);
	}
	/**
	 * Gets the crawler for the web page according to the configuration file
	 * @param int $year
	 * @return boolean|\Symfony\Component\DomCrawler\Crawler
	 */
	protected function getCrawler(int $year)
	{
		$getter = 'getters.uf.' . $this->getter;
		$url = str_replace('<year>', $year, config($getter . '.part'));
		try {
			$request = $this->client->request('GET', $url);
		} catch (ClientException $e) {
			return false;
		}

		if ($request->getStatusCode() != 200) {
			return false;
		}
		$client = new GClient();
		$uri = config($getter . '.url') . $url;
		$crawler = $client->request('GET', $uri);
		return $crawler;
	}
	/**
	 *
	 * {@inheritDoc}
	 * @see \Money\Definition\Getter::get()
	 */
	public function get(int $year)
	{
		$crawler = $this->getCrawler($year);
		if (!$crawler) {
			return false;
		}
		$nodes = $crawler->filter('.container .jumbotron .col-lg-12 tr>td.text-right:nth-child(2)');

		$ufs = [];
		$tz = new \DateTimeZone(config('app.timezone'));
		$today = Carbon::today($tz);
		$next_9 = $today->copy()->addMonth(1)->day(9);
		$n = -1;
		for ($m = 1; $m <= 12; $m ++) {
			for ($d = 1; $d <= 31; $d ++) {
				$fecha = Carbon::createFromDate($year, $m, $d, $tz);
				if ($fecha->month != $m or $fecha > $next_9) {
					continue;
				}
				$n ++;

				try {
					$node = $nodes->eq($n);
					$puf = $node->text();
					if (ord(mb_convert_encoding($puf, 'UTF-8', 'ISO-8859-1')) == 195) {
						continue;
					}
					$uf = (float) trim(str_replace('$', '', str_replace(',', '.', str_replace('.', '', $puf))));

					$ufs[$fecha->format('Y-m-d')] = $uf;
				} catch (\InvalidArgumentException $e) {
				}
			}
		}

		return $ufs;
	}
}
?>
