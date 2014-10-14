<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Deserializers\Exceptions\MissingTypeException;
use Deserializers\Exceptions\UnsupportedTypeException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Statement\Statement;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ClaimDeserializer implements DispatchableDeserializer {

	private $rankIds = array(
		'deprecated' => Statement::RANK_DEPRECATED,
		'normal' => Statement::RANK_NORMAL,
		'preferred' => Statement::RANK_PREFERRED
	);

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

	public function __construct( Deserializer $snakDeserializer, Deserializer $snaksDeserializer, Deserializer $referencesDeserializer ) {
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
		return $this->hasType( $serialization ) && $this->hasCorrectType( $serialization );
	}

	private function hasType( $serialization ) {
		return is_array( $serialization ) && array_key_exists( 'type', $serialization );
	}

	private function hasCorrectType( $serialization ) {
		return in_array( $serialization['type'], array( 'claim', 'statement' ) );
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param mixed $serialization
	 *
	 * @return object
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );
		$this->requireAttribute( $serialization, 'mainsnak' );

		return $this->getDeserialized( $serialization );
	}

	private function getDeserialized( array $serialization ) {
		$mainSnak = $this->snakDeserializer->deserialize( $serialization['mainsnak'] );

		$claim = $serialization['type'] === 'statement' ? new Statement( new Claim( $mainSnak ) ) : new Claim( $mainSnak );

		$this->setGuidFromSerialization( $serialization, $claim );
		$this->setQualifiersFromSerialization( $serialization, $claim );

		if ( $serialization['type'] === 'statement' ) {
			$this->setRankFromSerialization( $serialization, $claim );
			$this->setReferencesFromSerialization( $serialization, $claim );
		}

		return $claim;
	}

	private function setGuidFromSerialization( array &$serialization, Claim $claim ) {
		if ( !array_key_exists( 'id', $serialization ) ) {
			return;
		}

		if ( !is_string( $serialization['id'] ) ) {
			throw new DeserializationException( 'The id ' . $serialization['id'] . ' is not a valid GUID.' );
		}

		$claim->setGuid( $serialization['id'] );
	}

	private function setQualifiersFromSerialization( array &$serialization, Claim $claim ) {
		if ( !array_key_exists( 'qualifiers', $serialization ) ) {
			return;
		}

		$qualifiers = $this->snaksDeserializer->deserialize( $serialization['qualifiers'] );

		if( array_key_exists( 'qualifiers-order', $serialization ) ) {
			$this->assertQualifiersOrderIsArray( $serialization );

			$qualifiers->orderByProperty( $serialization['qualifiers-order'] );
		}

		$claim->setQualifiers( $qualifiers );
	}

	private function setRankFromSerialization( array &$serialization, Statement $statement ) {
		if ( !array_key_exists( 'rank', $serialization ) ) {
			return;
		}

		if ( !array_key_exists( $serialization['rank'], $this->rankIds ) ) {
			throw new DeserializationException( 'The rank ' . $serialization['rank'] . ' is not a valid rank.' );
		}

		$statement->setRank( $this->rankIds[$serialization['rank']] );
	}

	private function setReferencesFromSerialization( array &$serialization, Statement $statement ) {
		if ( !array_key_exists( 'references', $serialization ) ) {
			return;
		}

		$statement->setReferences( $this->referencesDeserializer->deserialize( $serialization['references'] ) );
	}

	private function assertCanDeserialize( $serialization ) {
		if ( !$this->hasType( $serialization ) ) {
			throw new MissingTypeException();
		}

		if ( !$this->hasCorrectType( $serialization ) ) {
			throw new UnsupportedTypeException( $serialization['type'] );
		}
	}

	protected function requireAttribute( array $array, $attributeName ) {
		if ( !array_key_exists( $attributeName, $array ) ) {
			throw new MissingAttributeException(
				$attributeName
			);
		}
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
