<?php

namespace Wikibase\DataModel\Serializers;

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
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 */
class FingerprintSerializer {

	public function addBasicsToSerialization( Entity $entity, array &$serialization ) {
		$this->addIdToSerialization( $entity, $serialization );
		$this->addLabelsToSerialization( $entity, $serialization );
		$this->addDescriptionsToSerialization( $entity, $serialization );
		$this->addAliasesToSerialization( $entity, $serialization );
	}

	public function addIdToSerialization( Entity $entity, array &$serialization ) {
		$id = $entity->getId();

		if ( $id === null ) {
			return;
		}

		$serialization['id'] = $id->getSerialization();
	}

	public function addLabelsToSerialization( Entity $entity, array &$serialization ) {
		$labels = $entity->getLabels();

		$serialization['labels'] = $this->serializeValuePerLanguageArray( $labels );
	}

	public function addDescriptionsToSerialization( Entity $entity, array &$serialization ) {
		$descriptions = $entity->getDescriptions();

		$serialization['descriptions'] = $this->serializeValuePerLanguageArray( $descriptions );
	}

	private function serializeValuePerLanguageArray( array $array ) {
		$serialization = array();

		foreach( $array as $language => $value ) {
			$serialization[$language] = array(
				'language' => $language,
				'value' => $value
			);
		}

		return $serialization;
	}

	public function addAliasesToSerialization( Entity $entity, array &$serialization ) {
		$aliases = $entity->getAllAliases();

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

}
