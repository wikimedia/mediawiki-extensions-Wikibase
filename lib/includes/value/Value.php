<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daniel
 * Date: 27.07.12
 * Time: 18:00
 * To change this template use File | Settings | File Templates.
 */
interface Value {
	public function getTypeID();

	public function toJSONArray();
}

abstract class ValueBase implements Value {
	var $typeID;
	var $value;

	/**
	 * @param String $typeID
	 */
	public function __construct( $typeID ) {
		$this->typeID = $typeID;
	}

	/**
	 * @return String
	 */
	public function getTypeID() {
		return $this->typeID;
	}
}

abstract class Values {
	public static function fromJSONArray( $data ) {
		$class = $data[ 'type' ];

		$value = new $class( $data );

		return $value;
	}
}
