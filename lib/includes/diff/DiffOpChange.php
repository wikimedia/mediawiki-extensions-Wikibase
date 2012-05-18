<?php

namespace Wikibase;

class DiffOpChange extends DiffOp {

	protected $newValue;
	protected $oldValue;

	public function getType() {
		return 'change';
	}

	public function __construct( $oldValue, $newValue ) {
		$this->oldValue = $oldValue;
		$this->newValue = $newValue;
	}

	public function getOldValue() {
		return $this->oldValue;
	}

	public function getNewValue() {
		return $this->newValue;
	}

	public function toArray() {
		return array(
			$this->getType(),
			$this->newValue,
			$this->oldValue,
		);
	}

}