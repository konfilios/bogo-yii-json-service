<?php
/**
 * Product AR model.
 *
 * @property integer $id
 * @property integer $manufacturerId
 * @property string $title
 * @property double $price
 * @property string $internalCode
 * @property integer $createdByUserId
 *
 * @property Manufacturer $manufacturer Parent manufacturer (belongs-to relation)
 * @property ProductCategory[] $categories Relevant categories (many-to-many relation)
 *
 * @package Internals.AR
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class Product extends CActiveRecord
{
	// Implementation has been skipped. Class level doc-comments are all we need for the demo.
}