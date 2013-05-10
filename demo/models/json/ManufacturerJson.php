<?php
/**
 * Json representation of the Manufacturer AR model.
 *
 * @package RestApi.Objects
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class ManufacturerJson extends CBJsonModel
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
