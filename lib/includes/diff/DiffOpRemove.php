<?php

namespace Wikibase;

class DiffOpRemove extends DiffOp {

	protected $oldValue;

	public function getType() {
		return 'remove';
	}

	public function __construct( $oldValue ) {
		$this->oldValue = $oldValue;
	}

	public function getOldValue() {
		return $this->oldValue;
	}

	public function toArray() {
		return array(
			$this->getType(),
			$this->oldValue,
		);
	}

}