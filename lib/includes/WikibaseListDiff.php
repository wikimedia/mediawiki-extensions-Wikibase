<?php

/**
 * Class representing the diff between to (non-associative) arrays.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseListDiff implements Serializable {

	protected $removals;
	protected $additions;

	protected function __construct( array $additions, array $removals ) {
		$this->removals = $removals;
		$this->additions = $additions;
	}

	public static function newEmpty() {
		return new self( array(), array() );
	}

	public static function newFromArrays( array $firstList, array $secondList ) {
		return new self(
			array_diff( $secondList, $firstList ),
			array_diff( $firstList, $secondList )
		);
	}

	public function unserialize( $serialization ) {
		$this->setAdditions( $serialization['additions'] );
		$this->setRemovals( $serialization['removals'] );
	}

	public function serialize() {
		return array(
			'additions' => $this->additions,
			'removals' => $this->removals,
		);
	}

	public function setAdditions( array $additions ) {
		$this->additions = $additions;
	}

	public function setRemovals( array $removals ) {
		$this->removals = $removals;
	}

	public function addAddition( $addition ) {
		$this->additions[] = $addition;
	}

	public function addRemoval( $removal ) {
		$this->removals[] = $removal;
	}

	public function getAdditions() {
		return $this->additions;
	}

	public function getRemovals() {
		return $this->removals;
	}

	/**
	 * Returns whether the change is empty.
	 * If it's empty, it can be ignored.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->additions === array() && $this->removals === array();
	}

}