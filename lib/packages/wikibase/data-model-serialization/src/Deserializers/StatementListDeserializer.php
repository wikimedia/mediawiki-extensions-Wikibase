<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Statement\StatementList;

/**
 * Package private
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Addshore
 */
class StatementListDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

	/**
	 * @param Deserializer $statementDeserializer
	 */
	public function __construct( Deserializer $statementDeserializer ) {
		$this->statementDeserializer = $statementDeserializer;
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param array[] $serialization
	 *
	 * @throws DeserializationException
	 * @return StatementList
	 */
	public function deserialize( $serialization ) {
		$this->assertHasGoodFormat( $serialization );

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param array[] $serialization
	 *
	 * @return StatementList
	 */
	private function getDeserialized( array $serialization ) {
		$statementList = new StatementList();

		foreach ( $serialization as $key => $statementArray ) {
			if ( is_string( $key ) ) {
				foreach ( $statementArray as $statementSerialization ) {
					$statementList->addStatement(
						$this->statementDeserializer->deserialize( $statementSerialization )
					);
				}
			} else {
				$statementList->addStatement( $this->statementDeserializer->deserialize( $statementArray ) );
			}

		}

		return $statementList;
	}

	private function assertHasGoodFormat( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The StatementList serialization should be an array' );
		}

		foreach ( $serialization as $key => $statementArray ) {
			if ( is_string( $key ) && !is_array( $statementArray ) ) {
				throw new DeserializationException( 'The statements per property should be an array' );
			}
		}
	}

}
