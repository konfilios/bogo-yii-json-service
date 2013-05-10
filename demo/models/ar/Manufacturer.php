<?php
/**
 * Manufacturer AR model.
 *
 * @property integer $id
 * @property string $title
 * @property string $internalCode
 * @property integer $createdByUserId
 *
 * @property Product[] $products Products belonging to manufacturer (has-many relation)
 *
 * @package Internals.AR
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class Manufacturer extends CActiveRecord
{
	// Implementation has been skipped. Class level doc-comments are all we need for the demo.
}