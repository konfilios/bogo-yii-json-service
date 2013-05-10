<?php
/**
 * Json representation of the Product AR model.
 *
 * This is the interface of the Product AR model exposed through the JSON API.
 *
 * @package RestApi.Objects
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class ProductJson extends CBJsonModel
{
	/**
	 * Types of non-scalar members.
	 *
	 * @return string[]
	 */
	public function getAttributeTypes()
	{
		return array(
			'manufacturer' => 'ManufacturerJson',
			'categories' => 'ProductCategoryJson[]',
		);
	}

	/**
	 * Product id.
	 * @var integer
	 */
	public $id;

	/**
	 * Product title.
	 * @var string
	 */
	public $title;

	/**
	 * Product price.
	 * @var double
	 */
	public $price;

	/**
	 * Manufacturer.
	 * @var ManufacturerJson
	 */
	public $manufacturer;

	/**
	 * Relevant categories.
	 * @var ProductCategoryJson[]
	 */
	public $categories = array();
}
