<?php
use PHPUnit\Framework\TestCase;
use Goutte\Client;

class UFAPITest extends TestCase
{
	protected $client;
	
	public function setUp()
	{
		$this->client = new Client();
		$this->client->setHeader('Accept', 'application/json');
	}
	
	protected function callApi($input)
	{
		$url = 'http://localhost/uf/?' . http_build_query($input);
		$this->client->request('GET', $url);
	}
	protected function assertIfStatusOK()
	{
		$status = $this->client->getResponse()->getStatus();
		$this->assertEquals($status, 200, 'HTTP status not OK.');
	}
	protected function getData()
	{
		$data = json_decode($this->client->getResponse()->getContent());
		return $data;
	}
	
	public function testGetUfForDate()
	{
		$input = ['p' => 'uf', 'cmd' => 'value', 'date' => '2017-08-01'];
		$this->callApi($input);
		
		$this->assertIfStatusOk();
		
		$data = $this->getData();
		
		$output = (object) ['total' => 1, 'ufs' => (object) ['value' => 26593.89]];
		
		$this->assertEquals($output->total, $data->total, 'Different amount of values found.');
		$this->assertEquals(round($output->ufs->value, 1), round($data->ufs->value, 1), 'UF value is incorrect.');
	}
	public function testGetUfsForYear()
	{
		$input = ['p' => 'uf', 'cmd' => 'list', 'year' => 2016];
		$this->callApi($input);
		
		$this->assertIfStatusOk();
		
		$data = $this->getData();
		
		$output = (object) ['total' => 366, 'ufs' => [59 => (object) ['value' => 25717.4]]];
		$i = 59;
		
		$this->assertEquals($output->total, $data->total, 'Different amount of values found.');
		$this->assertEquals($output->ufs[$i]->value, $data->ufs[$i]->value);
	}
	public function testHelp()
	{
		$input = ['p' => 'uf', 'cmd' => 'help'];
		$this->callApi($input);
		
		$this->assertIfStatusOk();
		
		$data = $this->getData();
		
		$this->assertObjectHasAttribute('commands', $data);
	}
	public function testTransformToCLP()
	{
		$uf = 20;
		$input = ['p' => 'uf', 'cmd' => 'transform', 'value' => $uf, 'date' => '2017-08-01', 'to' => 'clp'];
		$this->callApi($input);
		
		$this->assertIfStatusOK();
		
		$data = $this->getData();
		
		$this->assertEquals($input['date'], $data->date);
		$result = $uf * 26593.89;
		$this->assertEquals($result, $data->to);
	}
}
?>