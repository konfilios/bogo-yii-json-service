<?php
/**
 * Json-capable CHttpRequest.
 *
 * The following capabilities are added to Yii::app()->request:
 * <ol>
 * <li>Access to request headers: getRequestHeaders() and getRequestHeader()</li>
 * <li>Access to request raw payload: getRequestRawBody()</li>
 * </ol>
 *
 * @since 1.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBHttpRequest extends CHttpRequest
{
	/**
	 * All headers, ucword()ized.
	 * @var array
	 */
	private $headers = null;

	/**
	 * Content type of request's body.
	 * @var string
	 */
	private $requestContentType = null;

	/**
	 * Normalized array of accepted content types.
	 * @var array
	 */
	private $acceptContentTypes = array();

	/**
	 * Initialize content type of request.
	 */
	public function  init()
	{
		parent::init();

		//
		// What's the content type of the request body?
		//
		$this->requestContentType = !isset($_SERVER['CONTENT_TYPE']) ? ''
			: $this->_truncateAfterSemicolon(strtolower($_SERVER['CONTENT_TYPE']));

		//
		// What content types are accepted?
		//
		$acceptContentTypes = $this->getAcceptTypes();
		if (!empty($acceptContentTypes)) {
			foreach (explode(',', $acceptContentTypes) as $contentType) {
				$this->acceptContentTypes[] = $this->_truncateAfterSemicolon($contentType);
			}
		}
	}

	/**
	 * Keep characters up to first semicolon
	 *
	 * @param string $str
	 * @return string
	 */
	private function _truncateAfterSemicolon($str)
	{
		if (($pos = strpos($str, ';')) !== false) {
			return substr($str, 0, $pos);
		} else {
			return $str;
		}
	}

	/**
	 * Returns array of accepted content types.
	 *
	 * @return string[]
	 */
	public function getAcceptContentTypes()
	{
		return $this->acceptContentTypes;
	}

	/**
	 * Returns whether this is a JSON request.
	 *
	 * @return boolean
	 */
	public function getIsJsonRequest()
	{
		return ($this->requestContentType == 'application/json');
	}

	/**
	 * Get raw body of HTTP request.
	 *
	 * @return string
	 */
	public function getRequestRawBody()
	{
		return file_get_contents('php://input');
	}

	/**
	 * Get all request headers.
	 *
	 * @param string $fieldName
	 * @return string Header value or null if header is not set
	 */
	public function getRequestHeaders()
	{
		if ($this->headers === null) {
			// Fetch $this->headers for first time. Normalize and store
			$this->headers = array();

			// Fetch the real headers first.
			foreach($_SERVER as $key => $value)	{
				$key = strtolower($key);

				// Headers are $_SERVER variables starting with HTTP_
				if (substr($key, 0, 5) != 'http_') {
					continue;
				}

				// 1. Replace _ and - with space so ucwords works properly
				// 2. Trim spaces created at step 1
				// 3. Lowercase the first letter
				// Eg. HTTP_CONTENT_TYPE becomes contentType
				$header = lcfirst(str_replace(' ', '',
						ucwords(str_replace(array('_', '-'), ' ', substr($key, 5)))));

				// Add three common forms of header
				$this->headers[$header] = $value;
			}

			if ((defined('YII_DEBUG') && (constant('YII_DEBUG') === true))
					&& isset($_GET['header']) && is_array($_GET['header'])) {
				// We're in debug mode and GET headers have been passed. Append them
				foreach ($_GET['header'] as $header=>$value) {
					$this->headers[$header] = $value;
				}
			}
		}

		return $this->headers;
	}

	/**
	 * Get request header value.
	 *
	 * @param string $fieldName
	 * @return string Header value or null if header is not set
	 */
	public function getRequestHeader($fieldName)
	{
		$headers = $this->getRequestHeaders();

		return isset($headers[$fieldName]) ? $headers[$fieldName] : null;
	}
}
