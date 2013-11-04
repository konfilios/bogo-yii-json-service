<?php
/**
 * Generic grid request json.
 *
 * @since 1.2
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBJsonModelGridQuery extends CBJsonModel
{
	/**
	 * Associates sort aliases to sql names.
	 *
	 * @return string[]
	 * @ignore
	 */
	public function getSortFields()
	{
		return array();
	}

	/**
	 * Apply query filters to counter/finder models.
	 *
	 * @param CActiveRecord $search
	 * @return CActiveRecord
	 * @ignore
	 */
	public function applyFilters($search)
	{
		return $search;
	}

	/**
	 * Column to order results.
	 * @var string[]
	 */
	public $sort;

	/**
	 * Offset of results.
	 *
	 * @var integer
	 */
	public $offset;

	/**
	 * Limit results.
	 * @var integer
	 */
	public $limit;

	/**
	 * Query sequence number.
	 *
	 * @var integer
	 */
	public $sequence;

	/**
	 * Order By sql string generated from $sort.
	 *
	 * @return string
	 * @ignore
	 */
	public function getOrderBy()
	{
		$sqlOrderBy = '';

		if (!empty($this->sort)) {
			$sortFields = $this->getSortFields();

			foreach ($this->sort as $sort) {
				// Seperate sort direction from field alias
				$sortDirection = 'asc';
				$fieldAlias = $sort;

				switch ($sort{0}) {
				case '+':
					$sortDirection = 'asc';
					$fieldAlias = substr($sort, 1);
					break;
				case '-':
					$sortDirection = 'desc';
					$fieldAlias = substr($sort, 1);
					break;
				}

				// Turn sort field alias into an sql name
				if (!empty($sortFields[$fieldAlias])) {
					// There's a manual transformation
					$sqlFieldName = $sortFields[$fieldAlias];
				} else {
					throw new Exception('Unknown sort column "'.$fieldAlias.'"');
				}

				// Append to the ORDER BY string
				$sqlOrderBy .= ($sqlOrderBy ? ', ' : "").$sqlFieldName.' '.$sortDirection;
			}
		}

		return $sqlOrderBy;
	}
}
