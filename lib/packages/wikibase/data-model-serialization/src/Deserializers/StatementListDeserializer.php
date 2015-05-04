<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Statement\StatementList;

/**
 * Package private
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
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
	 * @param array $serialization
	 *
	 * @return StatementList
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertHasGoodFormat( $serialization );

		return $this->getDeserialized( $serialization );
	}

	private function getDeserialized( array $serialization ) {
		$statementList = new StatementList();

		foreach( $serialization as $statementArray ) {
			foreach( $statementArray as $statementSerialization ) {
				$statementList->addStatement( $this->statementDeserializer->deserialize( $statementSerialization ) );
			}
		}

		return $statementList;
	}

	private function assertHasGoodFormat( $serialization ) {
		if( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The StatementList serialization should be an array' );
		}

		foreach( $serialization as $statementArray ) {
			if( !is_array( $statementArray ) ) {
				throw new DeserializationException( 'The statements per property should be an array' );
			}
		}
	}

}
