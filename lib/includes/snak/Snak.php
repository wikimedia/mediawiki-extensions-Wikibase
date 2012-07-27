<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daniel
 * Date: 27.07.12
 * Time: 17:58
 * To change this template use File | Settings | File Templates.
 */
interface Snak {
	public function getPropertyID();
}

/**
 * @property string propertyID
 */
abstract class SnakBase implements Snak {
	var $propertyID;

	/**
	 * @param String $propertyID
	 */
	public function __construct( $propertyID ) {
		$this->propertyID = $propertyID;
	}

	/**
	 * @return String
	 */
	public function getPropertyID() {
		return $this->propertyID;
	}
}