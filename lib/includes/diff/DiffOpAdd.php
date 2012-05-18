<?php

namespace Wikibase;

class DiffOpAdd extends DiffOp {

	protected $newValue;

	public function getType() {
		return 'add';
	}

	public function __construct( $newValue ) {
		$this->newValue = $newValue;
	}

	public function getNewValue() {
		return $this->newValue;
	}

	public function toArray() {
		return array(
			$this->getType(),
			$this->newValue,
		);
	}

}