<?php
/**
 * Base class for JSON models.
 *
 * @since 1.1
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBJsonModel extends CFormModel
{
	/**
	 * Throw exception if validation fails.
	 *
	 * @param array $attributes
	 * @param boolean $clearErrors
	 * @return boolean
	 * @throws CException
	 */
	public function validate($attributes = null, $clearErrors = true)
	{
		if (parent::validate($attributes, $clearErrors)) {
			return true;
		}

		$allErrors = array();
		foreach ($this->getErrors() as $attrName=>$attrErrors) {
			$allErrors = array_merge($allErrors, $attrErrors);
		}

		throw new CException(implode("\n", $allErrors));
	}

	/**
	 * Types of non-scalar members.
	 *
	 * @return string[]
	 */
	public function getAttributeTypes()
	{
		return array();
	}

	/**
	 * Initialize from source object/array.
	 *
	 * @param array|object $source
	 * @return CBJsonModel
	 */
	public function copyFrom($source)
	{
		if (is_array($source)) {
			$isSourceArray = true;
		} else if (is_object($source)) {
			$isSourceArray = false;
		} else {
			throw new CException(get_class($this).' cannot be initialized from a '
					.gettype($source).'. An array or object is required');
		}

		// Our attribute types
		$attrTypes = $this->getAttributeTypes();

		foreach($this->attributeNames() as $attrName) {
			// Loop through our attributes

			if ($isSourceArray) {
				// Array key
				$attrValue = isset($source[$attrName]) ? $source[$attrName] : null;
			} else {
				// Object property
				$attrValue = isset($source->$attrName) ? $source->$attrName : null;
			}

			if ($attrValue === null) {
				// Attribute not in source
				continue;
			}

			if (!isset($attrTypes[$attrName])) {
				// Scalar type
				$this->$attrName = $attrValue;
			} else {
				// Object or array of objects
				$attrType = $attrTypes[$attrName];

				if (substr($attrType, -2) === '[]') {
					// Array type
					$isAttrTypeArray = true;
					// Remove "[]"
					$attrType = substr($attrType, 0, -2);
				} else {
					// Non-array type
					$isAttrTypeArray = false;
				}

				// Object or array of objects
				if ($isAttrTypeArray) {
					// Array of objects

					if (!is_array($attrValue)) {
						// Make sure value is an array of objects
						throw new CException('Attribute '.$attrName.' of '.get_class($this)
								.' must be an array of '.$attrType.' instances');
					}

					$arrayJsonModels = array();
					foreach ($attrValue as $fromJsonAttributeElement) {
						// Loop through array of objects and create corresponding json models
						$arrayJsonModels[] = $this->castFrom($attrType, $fromJsonAttributeElement);
					}
					$this->$attrName = $arrayJsonModels;

				} else {
					// Simple object
					$this->$attrName = $this->castFrom($attrType, $attrValue);
				}
			}
		}

		return $this;
	}

	/**
	 * UTC datetime object.
	 *
	 * @var Datetime
	 */
	private $utcDatetime;

	/**
	 * @return Datetime
	 */
	private function getUtcDatetime()
	{
		if ($this->utcDatetime === null) {
			$this->utcDatetime = new DateTime();
			$this->utcDatetime->setTimezone(new DateTimeZone('UTC'));
		}

		return $this->utcDatetime;
	}

	/**
	 * Cast $sourceData into $targetType.
	 *
	 * @param string $targetType
	 * @param mixed $sourceData
	 * @return mixed
	 */
	private function castFrom($targetType, &$sourceData)
	{
		switch ($targetType) {
		case 'integer':
			return intval($sourceData);

		case 'double':
		case 'float':
			return floatval($sourceData);

		case 'boolean':
			return boolval($sourceData);

		case 'utc8601datetime':
			return $this->getUtcDatetime()->modify($sourceData)->format("Y-m-d\TH:i:s\Z");

		case 'utctimestamp':
			return $this->getUtcDatetime()->modify($sourceData)->format("U");

		default:
			// Non-standard object type
			$elementJsonModel = new $targetType();

			// Recurse into sub-object
			$elementJsonModel->copyFrom($sourceData);

			return $elementJsonModel;
		}
	}

	/**
	 * Model attributes as array.
	 *
	 * @return mixed
	 */
	public function toArray()
	{
		return $this->getAttributes();
	}

	/**
	 * Clones a source into a json model.
	 *
	 * @param array|object $source Source to clone attributes from.
	 * @return CBJsonModel Cloned CBJsonModel subtype.
	 */
	static public function createFromOne($source)
	{
		if ($source === null) {
			return null;
		}

		// Instantiate new subtype of CBJsonModel using Late Static Binding.
		$jsonModelClassName = get_called_class();
		$jsonModel = new $jsonModelClassName();
		/* @var $jsonModel CBJsonModel */

		// Copy attributes (include fromModel's dynamic properties)
		return $jsonModel->copyFrom($source);
	}

	/**
	 * Clones an array of sources into an array of json models.
	 *
	 * Original model array keys are preserved in final array.
	 *
	 * @param array $sources Sources to clone attributes from.
	 * @return CBJsonModel[] Cloned CBJsonModel subtypes.
	 */
	static public function createFromMany(array $sources)
	{
		// Get new subtype of CBJsonModel using Late Static Binding.
		$jsonModelClassName = get_called_class();

		$jsonModels = array();
		foreach ($sources as $key=>$fromModel) {
			/* @var $fromModel CModel */

			$jsonModel = new $jsonModelClassName();
			/* @var $jsonModel CBJsonModel */

			// Copy attributes (include fromModel's dynamic properties)and add to final array
			$jsonModels[$key] = $jsonModel->copyFrom($fromModel);
		}

		return $jsonModels;
	}

	/**
	 * Resolve an object to its proper JSON representation.
	 *
	 * @param mixed $inputObject
	 * @param boolean $doSuppressNulls If true, null properties are suppressed from result.
	 *
	 * @return mixed
	 */
	static public function resolveObjectRecursively($inputObject, $doSuppressNulls = false)
	{
		if (is_array($inputObject)) {
			//
			// Input object is an array, resolve its items
			//
			$objectArray = array();
			foreach ($inputObject as $key=>$responseObjectItem) {
				$objectArray[$key] = self::resolveObjectRecursively($responseObjectItem, $doSuppressNulls);
			}
			return $objectArray;

		} else if (is_object($inputObject)) {
			//
			// Input object is a class instance, handle specially
			//
			if (is_a($inputObject, 'CBJsonModel')) {
				//
				// CBJsonModel subtype, handle attributes specially
				//
				$attributes = array();
				foreach ($inputObject->toArray() as $attrName=>$attrValue) {
					if (is_array($attrValue) || is_object($attrValue)) {
						// Recurse
						$attributes[$attrName] = self::resolveObjectRecursively($attrValue, $doSuppressNulls);

					} else if (!$doSuppressNulls || $attrValue !== null) {
						// Check suppression
						$attributes[$attrName] = $attrValue;
					}
				}
				return $attributes;

			} else {
				//
				// Unknown object type, let json-encoder do its thing
				//
				return $inputObject;
			}
		} else {
			//
			// Input object is a scalar
			//
			return $inputObject;
		}
	}
}
