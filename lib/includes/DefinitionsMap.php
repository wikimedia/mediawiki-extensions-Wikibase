<?php

namespace Wikibase\Lib;

use InvalidArgumentException;

/**
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Daniel Kinzler
 */
class DefinitionsMap {

	/**
	 * @var array[]
	 */
	private $definitions;

	/**
	 * @param array[] $definitions
	 */
	public function __construct( array $definitions ) {
		foreach ( $definitions as $type => $def ) {
			if ( !is_string( $type ) || !is_array( $def ) ) {
				throw new InvalidArgumentException( '$definitions must be a map from string to arrays' );
			}
		}

		$this->definitions = $definitions;
	}

	/**
	 * @param string $field
	 *
	 * @return array An associative array mapping type IDs to the value of $field given in the
	 * original definition provided to the constructor.
	 */
	public function getMapForDefinitionField( $field ) {
		$fieldValues = array();

		foreach ( $this->definitions as $type => $def ) {
			if ( isset( $def[$field] ) ) {
				$fieldValues[$type] = $def[$field];
			}
		}

		return $fieldValues;
	}

	/**
	 * @return string[]
	 */
	public function getKeys() {
		return array_keys( $this->definitions );
	}

	/**
	 * @return array[]
	 */
	public function toArray() {
		return $this->definitions;
	}

}
