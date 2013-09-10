<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;

/**
 * @since 0.5
 *
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com
 */
class DispatchingEntityIdParser implements EntityIdParser {

	protected $idBuilders;

	/**
	 * Takes an array in which each key is a preg_match pattern.
	 * The first pattern the id matches against will be picked.
	 * The value this key points to has to be a builder function
	 * that takes as only required argument the id serialization
	 * (string) and returns an EntityId instance.
	 *
	 * @param array $idBuilders
	 */
	public function __construct( array $idBuilders ) {
		$this->idBuilders = $idBuilders;
	}

	/**
	 * @param string $idSerialization
	 *
	 * @return EntityId
	 * @throws EntityIdParsingException
	 */
	public function parse( $idSerialization ) {
		$this->assertIdIsString( $idSerialization );

		foreach ( $this->idBuilders as $idPattern => $idBuilder ) {
			if ( preg_match( $idPattern, $idSerialization ) ) {
				return $this->buildId( $idBuilder, $idSerialization );
			}
		}

		$this->throwInvalidId( $idSerialization );
	}

	protected function assertIdIsString( $idSerialization ) {
		if ( !is_string( $idSerialization ) ) {
			throw new EntityIdParsingException( 'Entity id serializations need to be strings' );
		}
	}

	protected function buildId( $idBuilder, $idSerialization ) {
		try {
			return call_user_func( $idBuilder, $idSerialization );
		}
		catch ( InvalidArgumentException $ex ) {
			$this->throwInvalidId( $idSerialization );
		}
	}

	protected function throwInvalidId( $idSerialization ) {
		throw new EntityIdParsingException(
			'The provided id serialization "' . $idSerialization . '" is not valid'
		);
	}

}
