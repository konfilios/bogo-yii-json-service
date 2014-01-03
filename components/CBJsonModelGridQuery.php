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
	 * Maximum value for `limit` field.
	 *
	 * This is a safety mechanism to avoid accidentally retrieving the whole database. If
	 * $maxLimit is set to an empty value any value can be given to $limit, but it's always
	 * recommended you set a reasonable, non-zero maxLimit.
	 *
	 * @var integer
	 * @ignore
	 */
	protected $maxLimit = 150;

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
	 * @ignore
	 */
	public function applyFilters($search)
	{
	}

	/**
	 * Apply query filters to finder models.
	 *
	 * @param CActiveRecord $search
	 * @ignore
	 */
	public function applyFinderFilters($search)
	{
		$this->applyFilters($search);
	}

	/**
	 * Apply query filters to counter models.
	 *
	 * @param CActiveRecord $search
	 * @ignore
	 */
	public function applyCounterFilters($search)
	{
		$this->applyFilters($search);
	}

	/**
	 * Order By sql string generated from $sort.
	 *
	 * @param CActiveRecord $search
	 * @ignore
	 */
	public function applyOrderBy($search)
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
					throw new Exception('Unknown sort field "'.$fieldAlias.'". Valid sort fields are: '.implode(', ', array_keys($sortFields)));
				}

				// Append to the ORDER BY string
				$sqlOrderBy .= ($sqlOrderBy ? ', ' : "").$sqlFieldName.' '.$sortDirection;
			}
		}

		$search->dbCriteria->order = $sqlOrderBy;
	}

	/**
	 * Apply limit & .
	 *
	 * @param CActiveRecord $search
	 * @throws CHttpException
	 * @ignore
	 */
	public function applyPaging($search)
	{
		if (!empty($this->maxLimit)) {
			if (empty($this->limit) || $this->limit > $this->maxLimit) {
				throw new CHttpException(400, 'You must set a limit value within [1, '.$this->maxLimit.']');
			}
		}

		if (!empty($this->limit)) {
			$search->dbCriteria->limit = $this->limit;
		}

		if (!empty($this->offset)) {
			$search->dbCriteria->offset = $this->offset;
		}
	}
}
