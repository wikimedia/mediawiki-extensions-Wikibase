<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Deserializers\Exceptions\MissingTypeException;
use Deserializers\Exceptions\UnsupportedTypeException;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class StatementDeserializer implements DispatchableDeserializer {

	private static $rankIds = [
		'deprecated' => Statement::RANK_DEPRECATED,
		'normal' => Statement::RANK_NORMAL,
		'preferred' => Statement::RANK_PREFERRED,
	];

	/**
	 * @var Deserializer
	 */
	private $snakDeserializer;

	/**
	 * @var Deserializer
	 */
	private $snaksDeserializer;

	/**
	 * @var Deserializer
	 */
	private $referencesDeserializer;

	public function __construct(
		Deserializer $snakDeserializer,
		Deserializer $snaksDeserializer,
		Deserializer $referencesDeserializer
	) {
		$this->snakDeserializer = $snakDeserializer;
		$this->snaksDeserializer = $snaksDeserializer;
		$this->referencesDeserializer = $referencesDeserializer;
	}

	/**
	 * @see DispatchableDeserializer::isDeserializerFor
	 *
	 * @param mixed $serialization
	 *
	 * @return bool
	 */
	public function isDeserializerFor( $serialization ) {
		return is_array( $serialization )
			&& array_key_exists( 'type', $serialization )
			&& $this->isValidStatementType( $serialization['type'] );
	}

	/**
	 * @param string $statementType
	 *
	 * @return bool
	 */
	private function isValidStatementType( $statementType ) {
		return $statementType === 'statement' || $statementType === 'claim';
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param array $serialization
	 *
	 * @throws DeserializationException
	 * @return Statement
	 */
	public function deserialize( $serialization ) {
		// Note: The following functions have been inlined on purpose, see T157013.
		if ( !is_array( $serialization ) || !array_key_exists( 'type', $serialization ) ) {
			throw new MissingTypeException();
		}

		if ( !$this->isValidStatementType( $serialization['type'] ) ) {
			throw new UnsupportedTypeException( $serialization['type'] );
		}

		if ( !array_key_exists( 'mainsnak', $serialization ) ) {
			throw new MissingAttributeException( 'mainsnak' );
		}

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param array $serialization
	 *
	 * @return Statement
	 */
	private function getDeserialized( array $serialization ) {
		/** @var Snak $mainSnak */
		$mainSnak = $this->snakDeserializer->deserialize( $serialization['mainsnak'] );
		$statement = new Statement( $mainSnak );

		// Note: The following ifs are inlined on purpose, see T157013.
		if ( array_key_exists( 'id', $serialization ) ) {
			$this->setGuidFromSerialization( $serialization, $statement );
		}
		if ( array_key_exists( 'qualifiers', $serialization ) ) {
			$this->setQualifiersFromSerialization( $serialization, $statement );
		}
		if ( array_key_exists( 'rank', $serialization ) ) {
			$this->setRankFromSerialization( $serialization, $statement );
		}
		if ( array_key_exists( 'references', $serialization ) ) {
			$this->setReferencesFromSerialization( $serialization, $statement );
		}

		return $statement;
	}

	private function setGuidFromSerialization( array $serialization, Statement $statement ) {
		if ( !is_string( $serialization['id'] ) ) {
			throw new DeserializationException(
				'The id ' . $serialization['id'] . ' is not a valid GUID.'
			);
		}

		$statement->setGuid( $serialization['id'] );
	}

	private function setQualifiersFromSerialization( array $serialization, Statement $statement ) {
		/** @var SnakList $qualifiers */
		$qualifiers = $this->snaksDeserializer->deserialize( $serialization['qualifiers'] );

		if ( array_key_exists( 'qualifiers-order', $serialization ) ) {
			$this->assertQualifiersOrderIsArray( $serialization );

			$qualifiers->orderByProperty( $serialization['qualifiers-order'] );
		}

		$statement->setQualifiers( $qualifiers );
	}

	private function setRankFromSerialization( array $serialization, Statement $statement ) {
		if ( !array_key_exists( $serialization['rank'], self::$rankIds ) ) {
			throw new DeserializationException(
				'The rank ' . $serialization['rank'] . ' is not a valid rank.'
			);
		}

		$statement->setRank( self::$rankIds[$serialization['rank']] );
	}

	private function setReferencesFromSerialization( array $serialization, Statement $statement ) {
		/** @var ReferenceList $references */
		$references = $this->referencesDeserializer->deserialize( $serialization['references'] );
		$statement->setReferences( $references );
	}

	private function assertQualifiersOrderIsArray( array $serialization ) {
		if ( !is_array( $serialization['qualifiers-order'] ) ) {
			throw new InvalidAttributeException(
				'qualifiers-order',
				$serialization['qualifiers-order'],
				'qualifiers-order attribute is not a valid array'
			);
		}
	}

}
