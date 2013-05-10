bogo-yii-json-service
=====================

JSON Controllers for Yii applications.

Latest version: 1.0 (23 Mar 2012)

## Installation

### Get the code

Download and extract the code into `/protected/extensions/bogo-yii-json-service/` folder.

### Make it visible

Add the following line in the import array of your `/protected/config/main.php` to make the
extensions classes visible to your code:

```php
return array(
	// [..]

	// autoloading model and component classes
	'import'=>array(
		// [..]
		'ext.bogo-yii-json-service.components.*',
		// [..]
	),

	// [..]
);
```

### Configure Yii Request Application Component

Add the following lines in your `/protected/config/main.php` to enable CBHttpRequest functionality
in place of Yii's standard CHttpRequest class:

```php
return array(
	// [..]

	// application components
	'components'=>array(
		// [..]

		// Use json-capable http request
		'request'=>array(
			'class'=>'CBHttpRequest',
		),

		// [..]
	),
	// [..]
);
```

## Usage

### Quick sample

Below a sample code which highlights the usage of JSON capabilities. Things you should keep are:
1. The controller `extends` the `CBJsonController` component
2. The method accepts a `ProductQueryJson` object instead of looking into `$_GET`/`$_POST`
3. The method `return`s an array of `ProductJson` objects instead of calling `$this->render()`

```php
class ProductController extends CBJsonController
{
	/**
	 * Find products.
	 *
	 * @param ProductQueryJson $queryJson Query criteria
	 * @return ProductJson[] Matching products
	 */
	public function actionFindProduct(ProductQueryJson $queryJson)
	{
		$foundProducts = Product::model()->scopeApplyQuery($queryJson)->findAll();

		return ProductJson::createFromMany($foundProducts);
	}
}
```

### More detailed Usage

For more details and examples, see the [the demo page](demo/)

### Your API's public documentation

Here's the [demo controller documentation](http://htmlpreview.github.io/?https://raw.github.com/drcypher/bogo-yii-json-service/master/demo/docs/class-ProductController.html)
you get by using this extension in conjunction with proper phpdoc comments and [apigen](http://apigen.org/).