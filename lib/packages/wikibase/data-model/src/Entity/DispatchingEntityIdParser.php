<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;
use LogicException;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DispatchingEntityIdParser implements EntityIdParser {

	/**
	 * @var callback[]
	 */
	protected $idBuilders;

	/**
	 * Takes an array in which each key is a preg_match pattern.
	 * The first pattern the id matches against will be picked.
	 * The value this key points to has to be a builder function
	 * that takes as only required argument the id serialization
	 * (string) and returns an EntityId instance.
	 *
	 * @param callback[] $idBuilders
	 */
	public function __construct( array $idBuilders ) {
		$this->idBuilders = $idBuilders;
	}

	/**
	 * @param string $idSerialization
	 *
	 * @throws EntityIdParsingException
	 * @throws LogicException
	 * @return EntityId
	 */
	public function parse( $idSerialization ) {
		$this->assertIdIsString( $idSerialization );

		foreach ( $this->idBuilders as $idPattern => $idBuilder ) {
			if ( preg_match( $idPattern, $idSerialization ) ) {
				return $this->buildId( $idBuilder, $idSerialization );
			}
		}

		$this->throwInvalidId( $idSerialization );

		throw new LogicException(
			'DispatchingEntityIdParser::throwInvalidId did not throw an EntityIdParsingException'
		);
	}

	/**
	 * @param string $idSerialization
	 *
	 * @throws EntityIdParsingException
	 */
	protected function assertIdIsString( $idSerialization ) {
		if ( !is_string( $idSerialization ) ) {
			throw new EntityIdParsingException( 'Entity id serializations need to be strings' );
		}
	}

	/**
	 * @param callback $idBuilder
	 * @param string $idSerialization
	 *
	 * @throws EntityIdParsingException
	 * @throws LogicException
	 * @return EntityId
	 */
	protected function buildId( $idBuilder, $idSerialization ) {
		try {
			return call_user_func( $idBuilder, $idSerialization );
		}
		catch ( InvalidArgumentException $ex ) {
			$this->throwInvalidId( $idSerialization );
		}

		throw new LogicException(
			'DispatchingEntityIdParser::throwInvalidId did not throw an EntityIdParsingException'
		);
	}

	/**
	 * @param string $idSerialization
	 *
	 * @throws EntityIdParsingException
	 */
	protected function throwInvalidId( $idSerialization ) {
		throw new EntityIdParsingException(
			'The provided id serialization "' . $idSerialization . '" is not valid'
		);
	}

}
