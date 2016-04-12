<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;

/**
 * @since 4.2
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DispatchingEntityIdParser implements EntityIdParser {

	/**
	 * @var callable[]
	 */
	private $idBuilders;

	/**
	 * Takes an array in which each key is a preg_match pattern.
	 * The first pattern the id matches against will be picked.
	 * The value this key points to has to be a builder function
	 * that takes as only required argument the id serialization
	 * (string) and returns an EntityId instance.
	 *
	 * @param callable[] $idBuilders
	 */
	public function __construct( array $idBuilders ) {
		$this->idBuilders = $idBuilders;
	}

	/**
	 * @param string $idSerialization
	 *
	 * @throws EntityIdParsingException
	 * @return EntityId
	 */
	public function parse( $idSerialization ) {
		$this->assertIdIsString( $idSerialization );

		if ( empty( $this->idBuilders ) ) {
			throw new EntityIdParsingException( 'No id builders are configured' );
		}

		foreach ( $this->idBuilders as $idPattern => $idBuilder ) {
			if ( preg_match( $idPattern, $idSerialization ) ) {
				return $this->buildId( $idBuilder, $idSerialization );
			}
		}

		throw new EntityIdParsingException(
			"The serialization \"$idSerialization\" is not recognized by the configured id builders"
		);
	}

	/**
	 * @param string $idSerialization
	 *
	 * @throws EntityIdParsingException
	 */
	private function assertIdIsString( $idSerialization ) {
		if ( !is_string( $idSerialization ) ) {
			throw new EntityIdParsingException( '$idSerialization must be a string' );
		}
	}

	/**
	 * @param callable $idBuilder
	 * @param string $idSerialization
	 *
	 * @throws EntityIdParsingException
	 * @return EntityId
	 */
	private function buildId( $idBuilder, $idSerialization ) {
		try {
			return call_user_func( $idBuilder, $idSerialization );
		} catch ( InvalidArgumentException $ex ) {
			// Should not happen, but if it does, re-throw the original message
			throw new EntityIdParsingException( $ex->getMessage() );
		}
	}

}
