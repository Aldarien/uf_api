<?php
namespace UF\API\Definition;

/**
 * Mold for object that grabs the information from each web page.
 * @author Aldarien
 *
 */
interface Getter
{
	/**
	 * Get all the information for the year.
	 * @param int $year
	 */
	public function get(int $year);
}
?>