<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Addshore
 */
class StatementListDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

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
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The StatementList serialization should be an array' );
		}

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
				if ( !is_array( $statementArray ) ) {
					throw new DeserializationException(
						"The statements per property \"$key\" should be an array"
					);
				}

				foreach ( $statementArray as $statementSerialization ) {
					/** @var Statement $statement */
					$statement = $this->statementDeserializer->deserialize( $statementSerialization );
					$statementList->addStatement( $statement );
				}
			} else {
				/** @var Statement $statement */
				$statement = $this->statementDeserializer->deserialize( $statementArray );
				$statementList->addStatement( $statement );
			}
		}

		return $statementList;
	}

}
