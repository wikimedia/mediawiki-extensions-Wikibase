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
class WikibaseListDiff extends WikibaseDiffOpList implements IWikibaseDiff {

	public function getType() {
		return 'list';
	}

	protected $typePointers = array(
		'add' => array(),
		'remove' => array(),
	);

	protected function addOperations( array $operations ) {
		parent::addOperations( $operations );
		$this->addTypedOperations( $operations );
	}

	public static function newEmpty() {
		return new static( array() );
	}

	public static function newFromArrays( array $firstList, array $secondList ) {
		$instance = new static( array() );

		$instance->addAdditions( array_diff( $secondList, $firstList ) );
		$instance->addRemovals( array_diff( $firstList, $secondList ) );

		return $instance;
	}

	public function serialize() {
		return serialize( array(
			'additions' => $this->additions,
			'removals' => $this->removals,
		) );
	}

}