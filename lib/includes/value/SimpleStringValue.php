<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daniel
 * Date: 27.07.12
 * Time: 18:17
 * To change this template use File | Settings | File Templates.
 */
class SimpleStringValue extends ValueBase {

	var $string;

	/**
	 * @param array $data
	 */
	public function __construct( $data ) {
		$this->string = $data['*'];
	}

	public function toJSONArray() {
		return array(
			'type' => $this->getTypeID(),
			'*' => $this->getString(),
		);
	}

	private function getString() {
		return $this->string;
	}
}
