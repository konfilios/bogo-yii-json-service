<?php
/**
 * Nodeset Json.
 *
 * @since 1.2
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBJsonModelTreeNode extends CBJsonModel
{
	/**
	 * Wrapped data.
	 *
	 * @var mixed
	 */
	public $data;

	/**
	 * Child nodes.
	 *
	 * @var CBJsonModelTreeNode[]
	 */
	public $children;

	/**
	 * Create an array of json tree nodes using passed nodeset.
	 *
	 * @param \BogoTree\INodeset $nodeset
	 * @return CBJsonModelTreeNode[]
	 */
	static public function createFromNodeset(\BogoTree\INodeset $nodeset)
	{
		$jsonNode = new static();

		// Retrieve data type
		$attributeTypes = $jsonNode->getAttributeTypes();
		$dataType = empty($attributeTypes['data']) ? '' : $attributeTypes['data'];

		$jsonNodes = array();
		foreach ($nodeset as $node /* @var $node \BogoTree\Node */) {
//			print_r($node);
			$jsonNode = new static();

			$jsonNode->data = $dataType ? $dataType::createFromOne($node->getObject()) : $node->getObject();

			$childNodeset = $node->getChildren();
			if (count($childNodeset) > 0) {
				$jsonNode->children = static::createFromNodeset($childNodeset);
			}

			$jsonNodes[] = $jsonNode;
		}

		return $jsonNodes;
	}
}