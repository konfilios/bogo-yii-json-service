<?php
/**
 * Represents a JSON action that is defined as a controller method.
 *
 * This subclass enforces the at-most-one-parameter-with-type-hinting actions.
 *
 * @since 1.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBJsonInlineAction extends CInlineAction
{
	/**
	 * Decode json input.
	 *
	 * @param string $jsonInput
	 * @return mixed
	 * @throws CHttpException
	 */
	protected function decodeJsonParam($jsonInput, $paramName)
	{
		// Object is optional
		if (empty($jsonInput)) {
			// Input string is empty, nothing to json-decode
			return null;
		}

		// Decode the object into an array
		$objectOutput = json_decode($jsonInput, true);

		if (($objectOutput === null) && ($jsonInput !== 'null')) {
			// Json_decode failed
			throw new CHttpException(400, 'Could not JSON-decode parameter "'.$paramName.'"');
		}

		return $objectOutput;
	}

	/**
	 * Executes a method of an object with the supplied named parameters.
	 *
	 * This method is internally used and implements all Restful logic.
	 *
	 * @param mixed $object the object whose method is to be executed
	 * @param ReflectionMethod $method the method reflection
	 * @param array $paramValues the named parameters
	 * @return mixed whether the named parameters are valid
	 */
	protected function runWithParamsInternal($object, $method, $paramValues)
	{
		$methodParams = $method->getParameters();

//		$methodParamsCount = count($methodParams);
//		if ($methodParamsCount != 1) {
//			throw new CHttpException(500, 'Restful action '
//					.$method->class.'::'.$method->name.' expects '.$methodParamsCount
//					.' parameters instead of 1');
//		}

		if (isset($paramValues['jsin'])) {
			$firstMethodParam = $methodParams[0];
			/* @var $firstMethodParam ReflectionParameter */

			$paramValues[$firstMethodParam->name] = $paramValues['jsin'];
			unset($paramValues['jsin']);
		}

		// Extract invocation params
		$invokeParams = array();
		foreach ($methodParams as $methodParam) {
			/* @var $methodParam ReflectionParameter */
			$paramName = $methodParam->name;

			if (isset($paramValues[$paramName])) {
				// A value exists, let's parse it
				try {
					$paramValue = CBJson::decodeAssoc($paramValues[$paramName]);
				} catch (Exception $e) {
					$paramValue = $paramValues[$paramName];
				}
				$methodParamClass = $methodParam->getClass();
				/* @var $methodParamClass ReflectionClass */

				if (!empty($methodParamClass)) {
					//
					// Expecting a value of given class
					//
					$methodParamClassName = $methodParamClass->name;

					if ($paramValue === null) {
						$paramObject = null;
					} else {
						$paramObject = new $methodParamClassName();

						if (!is_a($paramObject, 'CBJsonModel')) {
							throw new CHttpException(500, $method->class.'::'.$method->name
								.': Parameter "'.$paramName.'" is of class '
								.$methodParamClassName.' which is not a subtype of CBJsonModel.');
						}
						$paramObject->copyFrom($paramValue);
					}

					$invokeParams[] = $paramObject;

				} else if ($methodParam->isArray()) {
					//
					// Expecting array
					//
					$invokeParams[] = is_array($paramValue) ? $paramValue : array($paramValue);

				} else if (!is_array($paramValue)) {
					//
					// Expecting scalar
					//
					$invokeParams[] = $paramValue;

				} else {
					//
					// Bad parameter
					//
					throw new CHttpException(400, $method->class.'::'.$method->name
						.': Invalid argument passed for scalar parameter "'.$paramName.'"');
				}
			} else if ($methodParam->isDefaultValueAvailable()) {
				//
				// No value found, but param is optional, so use default
				//
				$invokeParams[] = $methodParam->getDefaultValue();
			} else {
				//
				// No value found and param is mandatory, die
				//
				throw new CHttpException(400, $method->class.'::'.$method->name
					.': No argument passed for mandatory parameter "'.$paramName.'"');
			}
		}

		// Invoke
		return $method->invokeArgs($object, $invokeParams);
	}
}
