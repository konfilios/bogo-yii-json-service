bogo-yii-json-service demo application
======================================

This is a demo of a project using bogo-yii-json-service for a JSON-capable API.

The major directories presented are `controllers` and `models`, part of every standard Yii
project.

## Highlights

### Controller

1. **Inherit**: The controller `extend`s the `CBJsonController`
2. **Input**: The controller methods don't access `$_GET` or `$_POST`, but use **object parameters**
3. **Output**: The controller methods `return` objects instead of `render`ing.
4. **Document**: All controller methods, as well as the controller class itself, should come with **phpdoc comments**
   which will become the API-consumer's documentation. That is, comments in your controllers are
   practically **public**.

### Models

1. **Separate internal AR from public JSON**: The `models/` folder is further structured to seperate **internal ActiveRecord
models** (`models/ar/` subfolder) from **publicly exposed JSON models** (`models/json/`).
This is very important, due to the following reasons:
  * Chances are you'll have JSON model's with **similar names as the AR models**. For example, notice
    you have a `Product` model for active record and a `ProductJson` model which is its representation
    in the public world. A flat `models/` folder will make your life really difficult while looking up
    files through your IDE.
  * Your apigen/phpdocumentor configuration will be simpler if you only tell it to look into the
   `models/json` folder while generating the public documentation

  From the above comments it's clear that the `Json` postfix in class names is necessary to avoid name
  conflicts, since most of the times you'll be combining JSON and AR models in the same controller.

2. **Document**: This documentation will be exposed to your consumers.

### Documentation

You'll get your public by doing the following:

1. Document your controllers and their methods using phpdoc
2. Document your JSON models using phpdoc
3. Customize a [apigen.neon configuration file](apigen.neon)
4. Run [apigen](http://apigen.org/) using this command
```
~/code/bogo-yii-json-service/demo$ apigen -c ./apigen.neon
```
5. Check the [output](http://htmlpreview.github.com/?https://github.com/drcypher/bogo-yii-json-service/blob/master/demo/docs/index.html)



## In detail

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
	public function actionFindProduct(ProductQueryJson $queryJson)
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
	 * @var ManufacturerJson
	 */
	public $manufacturer;

	/**
	 * Relevant categories.
	 * @var ProductCategoryJson[]
	 */
	public $categories = array();
}
```

```php
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
```

```php
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