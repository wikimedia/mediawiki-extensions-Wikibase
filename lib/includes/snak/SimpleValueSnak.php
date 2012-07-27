<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daniel
 * Date: 27.07.12
 * Time: 18:01
 * To change this template use File | Settings | File Templates.
 */
class SimpleValueSnak extends SnakBase {
	var $value;

	/**
	 * @param String $propertyID
	 * @param Value $value
	 */
	public function __construct( $propertyID, Value $value ) {
		$this->value = $value;
	}

	public function getValue() {
		return $this->value;
	}

}
