<?php
/**
 * Product query object.
 *
 * @package RestApi.Objects
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class ProductQueryJson extends CBJsonModel
{
	/**
	 * Minimum product price filter.
	 * @var double
	 */
	public $filterMinPrice;

	/**
	 * Limit results.
	 * @var integer
	 */
	public $resultsLimit;

	/**
	 * Column to order results.
	 * @var string
	 */
	public $resultsOrderBy;
}
