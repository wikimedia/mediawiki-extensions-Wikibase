<?php

namespace Wikibase\RDF;

use InvalidArgumentException;

/**
 * Helper class for generating labels for blank nodes.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class BNodeLabeler {

	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * @var int
	 */
	private $counter;

	public function __construct( $prefix = 'n', $start = 1 ) {
		if ( !is_string( $prefix ) ) {
			throw new InvalidArgumentException( '$prefix must be a string' );
		}

		if ( !is_int( $start ) || $start < 1 ) {
			throw new InvalidArgumentException( '$start must be an int >= 1' );
		}

		$this->prefix = $prefix;
		$this->counter = $start;
	}

	/**
	 * @param string|null $label node label, will be generated if not given.
	 *
	 * @return string
	 */
	public function getLabel( $label = null ) {
		if ( $label === null ) {
			$label = $this->prefix . $this->counter;
			$this->counter ++;
		}

		return '_:' . $label;
	}

}
