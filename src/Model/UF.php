<?php
namespace UF\API\Model;

use Carbon\Carbon;

/**
 * UF handler, with Paris Model structure.
 * @author Aldarien
 * 
 * @property int id;
 * @property Carbon fecha;
 * @property float valor;
 */
class UF extends \Model
{
	/**
	 * Diferent table name from 'uf' for Paris
	 * @var string
	 */
	public static $_table = 'ufs';

	/**
	 * Transform UF(CLF) to $CLP
	 * @param float $ufs
	 * @return number
	 */
	public function pesos(float $ufs)
	{
		return $ufs * $this->valor;
	}
	/**
	 * Transform $CLP to UF(CLF)
	 * @param int $pesos
	 * @return number
	 */
	public function ufs(int $pesos)
	{
		return $pesos / $this->valor;
	}
}
?>
