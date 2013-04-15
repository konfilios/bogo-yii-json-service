<?php
/**
 * Yii controllers exchanging JSON objects.
 *
 * This base controller class allows developers to write controller actions which expect
 * object parameters and return objects instead of printing/rendering them. It also has a
 * uniform error handling mechanism which converts exceptions and PHP errors to standard-form
 * jsons. The client application can be tuned to properly handle such exceptions.
 *
 * This approach, apart from making writing actions as if they were naturally called by the
 * client/consumer, allows for proper out-of-the-box documentation (i.e. through apigen).
 *
 * <p>
 * <h2>Action signatures</h2>
 * All restful action <b>signatures</b>:
 * <ul>
 * <li>Accept at most one <b>input parameter</b> object. The input parameter name can be
 * anything since there's at most one parameter.</li>
 * <li>Return exactly one <b>output</b> object.</li>
 * </ul>
 * Here is an example of a restful action expecting an object and returning another:
 * <pre>
 * 	public function actionGetAll(NotificationQueryJson $notificationQuery)
 * 	{
 * 		return new NotificationJson();
 * 	}
 * </pre>
 *
 * <p>
 * <h2>Request/Input Objects</h2>
 * <p>
 * <b>Input</b> parameters are fetched in two different ways:
 * <ul>
 * <li><b>Production mode</b>: From the POST body as raw json object.</li>
 * <li><b>Development mode</b>: From the 'jsin' GET parameter as raw json object.</li>
 * </ul>
 *
 * <p>
 * <b>Input</b> object types of a restful action can be:
 * <ul>
 * <li>Scalars</li>
 * <li>Objects of a CBJsonModel subtype</li>
 * </ul>
 *
 *
 * <p>
 * <h2>Request/Input Headers</h2>
 * <p>
 * Further input can be passed to a restful action through <b>request headers</b>:
 * <ul>
 * <li><b>Production mode</b>: Headers of the form 'this-is-some-header' are accessed as
 * 'thisIsSomeHeader':
 * <pre>
 * this-is-some-header: someValue\r\n
 * this-is-another-header: anotherValue\r\n
 * </pre>
 * </li>
 * <li><b>Development mode</b>: From the 'header' GET parameter as a hash:
 * <pre>
 * http://.../jsin=...&header[thisIsSomeHeader]=someValue&header[thisIsAnotherHeader]=anotherValue
 * </pre>
 * </li>
 * </ul>
 *
 * <p>
 * <h2>Response/Output objects</h2>
 * <p>
 * <b>Output</b> object types of a restful action can be:
 * <ul>
 * <li>Scalars</li>
 * <li>Objects of a CBJsonModel subtype</li>
 * <li>Arrays of the above two element types</li>
 * </ul>
 * Output objects are always automatically converted to json. Appropriate content-type headers
 * are also automatically sent.
 *
 * <p>
 * <h2>Response/Output errors</h2>
 * <p>
 * <b>Errors</b> are uniformly output using the createErrorObject method.
 *
 * <p>
 * <h2>Examples</h2>
 * <p>
 * Here is an example GET request (for development mode) with both header and json:
 * <pre>
 * http://.../index-test.php?r=controller/action&header[applicationId]=1&jsin={"id":34034,"name":"John Doe"}
 * </pre>
 *
 * @since 1.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBRestController extends CController
{
	/**
	 * Action params.
	 *
	 * Stored in case debugging is on.
	 * @var array
	 */
	private $_actionParams;

	/**
	 * Install error and exception handlers.
	 */
	public function init()
	{
		parent::init();

		// Install uncaught PHP error handler
		Yii::app()->attachEventHandler('onError', array($this, 'onError'));
		// Install uncaught exception handler
		Yii::app()->attachEventHandler('onException', array($this, 'onException'));
	}

	/**
	 * Get request header.
	 *
	 * @param string $fieldName
	 * @return string
	 */
	protected function getHeader($fieldName)
	{
		return Yii::app()->request->getRequestHeader($fieldName);
	}

	/**
	 * Print json and headers.
	 *
	 * If we in debug mode, output json is 'prettyfied' for human-readability which
	 * eases debugging.
	 *
	 * @param string $responseObject
	 */
	protected function renderJson($responseObject)
	{
		$responseObject = CBJsonModel::resolveObjectRecursively($responseObject, true);

		// Fix response content type
		header('Content-Type: application/json; charset=utf-8;');

		if ((defined('YII_DEBUG') && (constant('YII_DEBUG') === true))) {
			// Beautify
			$responseJson = CBJson::indent(json_encode($responseObject));
			echo($responseJson);
			Yii::log($_GET['r']."\n"
					."Request: ".print_r($this->_actionParams, true)."\n"
					."Response: ".$responseJson, CLogger::LEVEL_TRACE, 'application.RestController');
		} else {
			// Simple, compact result
			echo(json_encode($responseObject));
		}
	}

	/**
	 * Gather mysql logs.
	 * @return string
	 */
	private function getLogs()
	{
		$origLogs = $this->displaySummary(Yii::getLogger()->getLogs('profile', 'system.db.CDbCommand.*'));
		$finalLogs = array();
		foreach ($origLogs as &$log) {
			$finalLogs[] = array(
				'sql' => substr($log[0], strpos($log[0], '(') + 1, -1),
				'count' => $log[1],
				'totalMilli' => sprintf('%.1f', $log[4] * 1000.0),
			);
		}
		return $finalLogs;
	}

	public $groupByToken=true;

	/**
	 * Displays the summary report of the profiling result.
	 * @param array $logs list of logs
	 */
	protected function displaySummary($logs)
	{
		$stack=array();
		foreach($logs as $log)
		{
			if($log[1]!==CLogger::LEVEL_PROFILE)
				continue;
			$message=$log[0];
			if(!strncasecmp($message,'begin:',6))
			{
				$log[0]=substr($message,6);
				$stack[]=$log;
			}
			else if(!strncasecmp($message,'end:',4))
			{
				$token=substr($message,4);
				if(($last=array_pop($stack))!==null && $last[0]===$token)
				{
					$delta=$log[3]-$last[3];
					if(!$this->groupByToken)
						$token=$log[2];
					if(isset($results[$token]))
						$results[$token]=$this->aggregateResult($results[$token],$delta);
					else
						$results[$token]=array($token,1,$delta,$delta,$delta);
				}
				else
					throw new CException(Yii::t('yii','CProfileLogRoute found a mismatching code block "{token}". Make sure the calls to Yii::beginProfile() and Yii::endProfile() be properly nested.',
						array('{token}'=>$token)));
			}
		}

		$now=microtime(true);
		while(($last=array_pop($stack))!==null)
		{
			$delta=$now-$last[3];
			$token=$this->groupByToken ? $last[0] : $last[2];
			if(isset($results[$token]))
				$results[$token]=$this->aggregateResult($results[$token],$delta);
			else
				$results[$token]=array($token,1,$delta,$delta,$delta);
		}

		$entries=array_values($results);
		$func=create_function('$a,$b','return $a[4]<$b[4]?1:0;');
		usort($entries,$func);

		return $entries;
	}

	/**
	 * Aggregates the report result.
	 * @param array $result log result for this code block
	 * @param float $delta time spent for this code block
	 * @return array
	 */
	protected function aggregateResult($result,$delta)
	{
		list($token,$calls,$min,$max,$total)=$result;
		if($delta<$min)
			$min=$delta;
		else if($delta>$max)
			$max=$delta;
		$calls++;
		$total+=$delta;
		return array($token,$calls,$min,$max,$total);
	}

	/**
	 * Runs the action after passing through all filters.
	 *
	 * This method is invoked by {@link runActionWithFilters} after all possible filters have been
	 * executed and the action starts to run.
	 *
	 * The major difference from the parent method is that it does the rendering
	 * instead of the actions themselves which just return objects.
	 *
	 * Also catches exceptions and prints them accordingly.
	 *
	 * @param CAction $action action to run
	 */
	public function runAction($action)
	{
		// Retrieve action parameters
		$this->_actionParams = $this->getActionParams();

		if (!$this->beforeAction($action)) {
			// Validate request
			throw new CHttpException(403, 'Restful action execution forbidden.');
		}

		// Run action and get response
		$responseObject = $action->runWithParams($this->_actionParams);

		// Run post-action code
		$this->afterAction($action);

		// Render action response object
		$this->renderJson($responseObject);
	}

	/**
	 * Creates the action instance based on the action name.
	 *
	 * The method differs from the parent in that it uses CBJsonInlineAction for inline actions.
	 *
	 * @param string $actionId ID of the action. If empty, the {@link defaultAction default action} will be used.
	 * @return CAction the action instance, null if the action does not exist.
	 * @see actions
	 * @todo Implement External Actions as well.
	 */
	public function createAction($actionId)
	{
		if ($actionId === '') {
			$actionId = $this->defaultAction;
		}

		if (method_exists($this, 'action'.$actionId) && strcasecmp($actionId, 's')) { // we have actions method
			return new CBJsonInlineAction($this, $actionId);
		} else {
			$action = $this->createActionFromMap($this->actions(), $actionId, $actionId);
			if ($action !== null && !method_exists($action, 'run'))
				throw new CException(Yii::t('yii',
						'Action class {class} must implement the "run" method.',
						array('{class}' => get_class($action))));
			return $action;
		}
	}

	/**
	 * Extract json input object.
	 *
	 * Jsin is short for "json input object". The jsin can be extracted in two ways:
	 * <ol>
	 * <li>From raw PUT/POST request body, if it's a PUT/POST request</li>
	 * <li>From 'jsin' GET parameter, if it's a GET request and we're in test mode</li>
	 * </ol>
	 *
	 * @return array
	 */
	public function getActionParams()
	{
		// Get handly pointer
		$request = Yii::app()->request;

		switch ($request->getRequestType()) {
		case 'PUT':
		case 'POST':

			if (!empty($_POST)) {
				$params = $_REQUEST;
			} else if ($request->getIsJsonRequest()) {
				// Read js input object as a string first
				$params = array(
					'jsin' => $request->getRequestRawBody()
				);
			} else {
				$params = array();
			}
			break;

		default:
			$params = $_GET;
			break;
		}

		return $params;
	}

	/**
	 * Handle uncaught exception.
	 *
	 * @param CExceptionEvent $event
	 */
	public function onException($event)
	{
		$e = $event->exception;

		// Directly return an exception
		$this->renderJson($this->createErrorObject($e->getCode(), $e->getMessage(), $e->getTraceAsString(), get_class($e)));

		// Don't bubble up
		$event->handled = true;
	}

	/**
	 * Handle uncaught PHP notice/warning/error.
	 *
	 * @param CErrorEvent $event
	 */
	public function onError($event)
	{
		//
		// Extract backtrace
		//
		$trace=debug_backtrace();
		// skip the first 4 stacks as they do not tell the error position
		if(count($trace)>4)
			$trace=array_slice($trace,4);

		$traceString = "#0 ".$event->file."(".$event->line."): ";
		foreach($trace as $i=>$t)
		{
			if ($i !== 0) {
				if(!isset($t['file']))
					$trace[$i]['file']='unknown';

				if(!isset($t['line']))
					$trace[$i]['line']=0;

				if(!isset($t['function']))
					$trace[$i]['function']='unknown';

				$traceString.="\n#$i {$trace[$i]['file']}({$trace[$i]['line']}): ";
			}
			if(isset($t['object']) && is_object($t['object']))
				$traceString.=get_class($t['object']).'->';
			$traceString.="{$trace[$i]['function']}()";

			unset($trace[$i]['object']);
		}

		//
		// Directly return an exception
		//
		$this->renderJson($this->createErrorObject($event->code, $event->message, $traceString, 'PHP Error'));

		// Don't bubble up
		$event->handled = true;
	}

	/**
	 * Output total millitime header.
	 */
	protected function outputTotalMillitimeHeader()
	{
		// Total execution time in milliseconds
		header('Total-Millitime: '.sprintf('%.1f', 1000.0 * Yii::getLogger()->executionTime));
	}

	/**
	 * Create a standard-form error object from passed details.
	 *
	 * This allows for all kinds of errors (exceptions, php errors, etc.) to be returned to
	 * the service user in a standard form.
	 *
	 * If you wish to add further notification mechanisms you can override this method.
	 *
	 * @param integer $code
	 * @param string $message
	 * @param string $traceString
	 * @param string $type
	 * @return array
	 */
	protected function createErrorObject($code, $message, $traceString, $type)
	{
		$errorObject = array(
			'message' => $message,
			'code' => $code,
			'type' => $type,
		);

		if ((defined('YII_DEBUG') && (constant('YII_DEBUG') === true))) {
			$errorObject['trace'] = explode("\n", $traceString);
		}

		return $errorObject;
	}
}
