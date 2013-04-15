BogoYiiJsonService
==================

JSON Controllers for Yii applications.

Latest version: 1.0 (23 Mar 2012)

## Installation

### Get the code

Download and extract the code into `/protected/extensions/BogoYiiJsonService/` folder.

### Make it visible

Add the following line in the import array of your `/protected/config/main.php` to make the
extensions classes visible to your code:

```php
return array(
	// [..]

	// autoloading model and component classes
	'import'=>array(
		// [..]
		'ext.BogoYiiJsonService.components.*',
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

### The Controller

Now you're ready to write your first JSON-capable controller. All you have to do is extend
`CBJsonController`. Here's a simple example:

```php
class ProductController extends CBJsonController
{
	/**
	 * Find products.
	 *
	 * @param ProductQueryJson $queryJson Query criteria
	 * @return ProductJson[] Matching products
	 */
	public function actionCreateProduct(ProductQueryJson $queryJson)
	{
		$foundProducts = Product::model()->scopeApplyQuery($queryJson)->findAll();

		return ProductJson::createFromMany($foundProducts);
	}
}
```

You'll notice we've used two JSON classes here:
+ `ProductQueryJson`: This holds the query data sent by our client/caller.
+ `ProductJson`: This is a wrapper class which only reveals the properties of `Product` model we wish to reveal.

A sample definition of the two classes follows.

### The Request JSON

The product query extends the `CBJsonModel` class.

```php
class ProductQueryJson extends CBJsonModel
{
	/**
	 * Minimum product price filter.
	 * @var double
	 */
	public $filterMinPrice;

	/**
	 * Limit results.
	 * @var integer
	 */
	public $resultsLimit;

	/**
	 * Column to order results.
	 * @var string
	 */
	public $resultsOrderBy;
}
```

### The Response JSON

Although the `Product` Active Record model might feature hundreds of properties, we may only want to reveal
its name, price, manufacturer and relevant categories, corresponding to, let's say,
`Manufacturer` and `ProductCategory` Active Record models.

Here's a nice demonstration of how you can create a "deep" JSON which links to other object
types or arrays of them.

Notice the "compound" `ProductJson` class which overrides the `getAttributeTypes()` method
which gives information about non-scalar types. This is, unfortunately the only way to go given
PHP does not support type hints in class properties and phpdoc comments may be removed on a
production environment for performance reasons (i.e. this is what APC does).

In the following classes it's assumed that the mentioned properties also exist in the respective
Active Record classes, either as simple properties or "magic" properties.

```php
class ProductJson extends CBJsonModel
{
	/**
	 * Types of non-scalar members.
	 *
	 * @return string[]
	 */
	public function getAttributeTypes()
	{
		return array(
			'manufacturer' => 'ManufacturerJson',
			'categories' => 'ProductCategoryJson[]',
		);
	}

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

	/**
	 * Product price.
	 * @var double
	 */
	public $price;

	/**
	 * Manufacturer.
	 * @var ManufacturerJson[]
	 */
	public $manufacturer;

	/**
	 * Relevant categories.
	 * @var ProductCategoryJson[]
	 */
	public $categories = array();
}

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

```