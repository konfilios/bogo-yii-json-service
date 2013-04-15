<?php
/**
 * Json helper functions.
 *
 * Adds the following functionality:
 * <ul>
 * <li>JSON string validation: CBJson::isValid()</li>
 * <li>JSON formatting/indentation: CBJson::indent()</li>
 * <li>JSON soft decoding: CBJson::softDecode*() (<i>does not fail if json is invalid</i>)</li>
 * </ul>
 *
 * @since 1.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBJson
{

	/**
	 * Check if passed string is valid json.
	 *
	 * @param string $json Json string.
	 *
	 * @return boolean True if passed $json is valid
	 */
	static public function isValid($json)
	{
		return is_string($json) && (json_decode($json) != null);
	}

	/**
	 * Indent passed $json string to increase human-readability.
	 *
	 * @see http://recursive-design.com/blog/2008/03/11/format-json-with-php/
	 *
	 * @param string $json      Unformatted json.
	 * @param string $indentStr Indentation string (tabulator).
	 * @param string $newLine   Line terminator.
	 *
	 * @return string
	 */
	static public function indent($json, $indentStr = '   ', $newLine = "\n")
	{

		$result = '';
		$pos = 0;
		$strLen = strlen($json);
		$prevChar = '';
		$outOfQuotes = true;

		for ($i = 0; $i <= $strLen; $i++) {

			// Grab the next character in the string.
			$char = substr($json, $i, 1);

			// Are we inside a quoted string?
			if ($char == '"' && $prevChar != '\\') {
				$outOfQuotes = !$outOfQuotes;

				// If this character is the end of an element,
				// output a new line and indent the next line.
			} else if (($char == '}' || $char == ']') && $outOfQuotes) {
				$result .= $newLine;
				$pos--;
				for ($j = 0; $j < $pos; $j++) {
					$result .= $indentStr;
				}
			}

			// Add the character to the result string.
			$result .= $char;

			// If the last character was the beginning of an element,
			// output a new line and indent the next line.
			if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
				$result .= $newLine;
				if ($char == '{' || $char == '[') {
					$pos++;
				}

				for ($j = 0; $j < $pos; $j++) {
					$result .= $indentStr;
				}
			}

			$prevChar = $char;
		}

		return $result;
	}

	/**
	 * Returns string/text describing the last error occured while json encoding/decoding.
	 *
	 * This is supported only for php 5.3 or later.
	 *
	 * @see http://www.php.net/manual/en/function.json-last-error.php
	 *
	 * @return string
	 */
	static public function getLastErrorString()
	{
		if (function_exists('json_last_error')) {
			$errorCode = json_last_error();

			switch ($errorCode) {
				case JSON_ERROR_NONE:
					return false;
				case JSON_ERROR_DEPTH:
					return 'Maximum stack depth exceeded';
				case JSON_ERROR_STATE_MISMATCH:
					return 'Invalid or malformed JSON';
				case JSON_ERROR_CTRL_CHAR:
					return 'Control character error, possibly incorrectly encoded';
				case JSON_ERROR_SYNTAX:
					return 'Syntax error';
				case JSON_ERROR_UTF8:
					return 'Malformed UTF-8 characters, possibly incorrectly encoded';
				default:
					return 'Unsupported error code '.$errorCode;
			}
		} else {
			return false;
			//		$error = error_get_last();
			//		if (empty($error) || empty($error['message'])) {
			//			return false;
			//		}
			//		return $error['message'];
		}
	}

	/**
	 * Try to json-decode input.
	 *
	 * @param string $jsonInput
	 * @return mixed
	 */
	static public function softDecode($jsonInput)
	{
		try {
			$jsonOutput = self::decodeAssoc($jsonInput);
		} catch (Exception $e) {
			// Could not json decode, retain value
			$jsonOutput = $jsonInput;
		}

		return $jsonOutput;
	}

	/**
	 * Try to json-decode array elements.
	 *
	 * If elements are not json-decoded their original values are retained.
	 *
	 * @param array $jsonInputs
	 * @return array
	 */
	static public function softDecodeArray(array $jsonInputs)
	{
		$jsonOutputs = array();
		foreach ($jsonInputs as $key=>$jsonInput) {
			$jsonOutputs[$key] = (is_array($jsonInput) || is_object($jsonInput))
				? self::softDecodeArray($jsonInput)
				: self::softDecode($jsonInput);
		}
		return $jsonOutputs;
	}

	/**
	 * Decode json input.
	 *
	 * @param string $jsonInput
	 * @return mixed
	 * @throws CException
	 */
	static public function decodeAssoc($jsonInput)
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
			throw new CException('Could not JSON-decode input object');
		}

		return $objectOutput;
	}
}
