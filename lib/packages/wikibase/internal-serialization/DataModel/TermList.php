<?php

namespace Wikibase\DataModel\Term;

/**
 * List of terms. Only one term per language code.
 */
abstract class TermList {

	/**
	 * @var Term[]
	 */
	private $terms;

	/**
	 * @param Term[] $terms
	 */
	public function __construct( array $terms ) {
		$this->terms = $terms;
	}

	public function toArray() {
		$array = array();

		foreach ( $this->terms as $term ) {
			$array[$term->getLanguageCode()] = $term->getText();
		}

		return $array;
	}

}