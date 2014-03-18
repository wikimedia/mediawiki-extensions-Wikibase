<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
abstract class EntitySerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $claimsSerializer;

	/**
	 * @param Serializer $claimsSerializer
	 */
	public function __construct( Serializer $claimsSerializer ) {
		$this->claimsSerializer = $claimsSerializer;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return boolean
	 */
	public function isSerializerFor( $object ) {
		return is_object( $object ) && $object instanceof Entity;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param mixed $object
	 *
	 * @return array
	 * @throws SerializationException
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'EntitySerializer can only serialize Entity objects'
			);
		}

		return array_merge(
			$this->getSerialized( $object ),
			$this->getSpecificSerialization( $object )
		);
	}

	/**
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected abstract function getSpecificSerialization( Entity $entity );

	private function getSerialized( Entity $entity ) {
		$serialization = array(
			'type' => $entity->getType()
		);
		$this->addIdToSerialization( $entity, $serialization );
		$this->addLabelsToSerialization( $entity, $serialization );
		$this->addDescriptionsToSerialization( $entity, $serialization );
		$this->addAliasesToSerialization( $entity, $serialization );
		$this->addClaimsToSerialization( $entity, $serialization );

		return $serialization;
	}

	private function addIdToSerialization( Entity $entity, array &$serialization ) {
		$id = $entity->getId();

		if ( $id === null ) {
			return;
		}

		$serialization['id'] = $id->getSerialization();
	}


	private function addLabelsToSerialization( Entity $entity, array &$serialization ) {
		$labels = $entity->getLabels();

		if ( count( $labels ) === 0 ) {
			return;
		}

		$serialization['labels'] = $this->serializeValuePerLanguageArray( $labels );
	}

	private function addDescriptionsToSerialization( Entity $entity, array &$serialization ) {
		$descriptions = $entity->getDescriptions();

		if ( count( $descriptions ) === 0 ) {
			return;
		}

		$serialization['descriptions'] = $this->serializeValuePerLanguageArray( $descriptions );
	}

	private function serializeValuePerLanguageArray( $array ) {
		$serialization = array();

		foreach( $array as $language => $value ) {
			$serialization[$language] = array(
				'language' => $language,
				'value' => $value
			);
		}

		return $serialization;
	}


	private function addAliasesToSerialization( Entity $entity, array &$serialization ) {
		$aliases = $entity->getAllAliases();

		if ( count( $aliases ) === 0 ) {
			return;
		}

		$serialization['aliases'] = $this->serializeValuesPerLanguageArray( $aliases );
	}

	private function serializeValuesPerLanguageArray( $array ) {
		$serialization = array();

		foreach( $array as $language => $values ) {
			foreach( $values as $value ) {
				$serialization[$language][] = array(
					'language' => $language,
					'value' => $value
				);
			}
		}

		return $serialization;
	}


	private function addClaimsToSerialization( Entity $entity, array &$serialization ) {
		$claims = new Claims( $entity->getClaims() );

		if ( $claims->isEmpty() ) {
			return;
		}

		$serialization['claims'] = $this->claimsSerializer->serialize( $claims );
	}
}
