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
		$input = ['cmd' => 'value', 'date' => '2017-08-01'];
		$this->callApi($input);

		$this->assertIfStatusOk();

		$data = $this->getData();

		$output = (object) ['total' => 1, 'uf' => (object) ['value' => 26593.89]];

		$this->assertEquals($output->total, $data->total, 'Different amount of values found.');
		$this->assertEquals(round($output->uf->value, 1), round($data->uf->value, 1), 'UF value is incorrect.');
	}
	public function testGetUfsForYear()
	{
		$input = ['cmd' => 'list', 'year' => 2016];
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
		$input = ['cmd' => 'help'];
		$this->callApi($input);

		$this->assertIfStatusOk();

		$data = $this->getData();

		$this->assertObjectHasAttribute('commands', $data);
	}
	public function testTransformToCLP()
	{
		$uf = 20;
		$input = ['cmd' => 'transform', 'value' => $uf, 'date' => '2017-08-01', 'to' => 'clp'];
		$this->callApi($input);

		$this->assertIfStatusOK();

		$data = $this->getData();

		$this->assertEquals($input['date'], $data->date);
		$result = round($uf * 26593.89, 0);
		$this->assertEquals($result, round($data->to));
	}
    public function testDeleteDay()
    {
        $date = date('Y-m-d', strtotime('yesterday'));
        $input = ['cmd' => 'delete', 'date' => $date];
        $this->callApi($input);

        $this->assertIfStatusOK();

        $data = $this->getData();
        $this->assertEquals('ok', $data->status);
        $this->assertEquals(1, $data->total);
    }
    public function testGetForDeletedDay()
    {
        $date = date('Y-m-d', strtotime('yesterday'));
        $input = ['cmd' => 'value', 'date' => $date];
        $this->callApi($input);

        $this->assertIfStatusOK();

        $data = $this->getData();
        $this->assertEquals(1, $data->total);
        $this->assertEquals($date, $data->uf->date);
    }
}
?>
