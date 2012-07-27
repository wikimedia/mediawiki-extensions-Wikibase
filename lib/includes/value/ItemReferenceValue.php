<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daniel
 * Date: 27.07.12
 * Time: 18:17
 * To change this template use File | Settings | File Templates.
 */
class ItemReferenceValue extends ValueBase {

	var $itemID;

	/**
	 * @param array $data
	 */
	public function __construct( $data ) {
		$this->itemID = $data[ 'item' ];
	}

	public function toJSONArray() {
		return array(
			'type' => $this->getTypeID(),
			'item' => $this->getItemID(),
		);
	}

	private function getItemID() {
		return $this->itemID;
	}
}
