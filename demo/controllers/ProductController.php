<?php
/**
 * Demo product service.
 *
 * <p>Here you can add a short overview or tutorial of how provided methods can be utilized
 * by the consumer.</p>
 *
 * You can even use <b>HTML markup</b>, at least whatever <a href="http://apigen.org/">apigen</a>
 * accepts.
 *
 * @package RestApi.Services
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
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

