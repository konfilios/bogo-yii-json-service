<?php
/**
 * Description of GridResultsJson
 *
 * @since 1.2
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBJsonModelGridResults extends CBJsonModel
{
	/**
	 * Offset of results.
	 *
	 * @var array
	 */
	public $items;

	/**
	 * Total number of items matching the query.
	 *
	 * Used for client-side pagination.
	 *
	 * @var integer
	 */
	public $totalCount;

	/**
	 * Query sequence number.
	 *
	 * @var integer
	 */
	public $sequence;

	/**
	 * Get type of a single item based on the type of `items`.
	 *
	 * @return string
	 * @throws Exception
	 * @ignore
	 */
	private function getItemType()
	{
		$attributeTypes = $this->getAttributeTypes();

		if (empty($attributeTypes['items'])) {
			return array(null, null);
		}

		$itemsType = $attributeTypes['items'];

		if ($itemsType == 'array') {
			return array(null, null);
		}

		if (substr($itemsType, -2) == '[]') {
			return array(substr($itemsType, 0, -2), true);
		} else {
			return array($itemsType, false);
		}
	}

	/**
	 * Create a grid results object.
	 *
	 * @param CActiveRecord $itemFinder
	 * @param CActiveRecord $itemCounter
	 * @param CBJsonModelGridQuery $query
	 * @return static
	 * @ignore
	 */
	static public function createPaginated(CActiveRecord $itemFinder, CActiveRecord $itemCounter, CBJsonModelGridQuery $query)
	{
		$query->validate();

		$results = new static();

		//
		// Apply all search parameters first
		//

		// Apply filters both to the finder and counter
		$query->applyFinderFilters($itemFinder);
		$query->applyCounterFilters($itemCounter);

		// Apply pagination to item finder
		$query->applyPaging($itemFinder);

		// Apply ordering to finder
		$query->applyOrderBy($itemFinder);

		//
		// Echo back sequence number
		//
		$results->sequence = $query->sequence;

		//
		// Get total count
		//
		$results->totalCount = intval($itemCounter->count());

		//
		// Get items
		//
		$foundItems = $itemFinder->findAll();
		list($itemType, $isArrayType) = $results->getItemType();

		if (empty($itemType)) {
			$results->items = $foundItems;
		} else {
			if ($isArrayType) {
				$results->items = $itemType::createFromMany($foundItems);
			} else {
				$results->items = $itemType::createFromOne($foundItems);
			}
		}

		return $results;
	}
}
