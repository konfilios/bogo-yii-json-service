<?php
/**
 * Json representation of the Product Category AR model.
 *
 * @package RestApi.Objects
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class ProductCategoryJson extends CBJsonModel
{
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
}
