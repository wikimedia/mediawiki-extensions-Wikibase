<?php

namespace Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupFallback;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermList;

/**
 * Package private
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 */
class FingerprintSerializer {

	/**
	 * @var bool
	 */
	protected $useObjectsForMaps;

	/**
	 * @param bool $useObjectsForMaps
	 */
	public function __construct( $useObjectsForMaps ) {
		$this->useObjectsForMaps = $useObjectsForMaps;
	}

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
		$labels = $entity->getFingerprint()->getLabels();

		$serialization['labels'] = $this->serializeValuePerTermList( $labels );
	}

	public function addDescriptionsToSerialization( Entity $entity, array &$serialization ) {
		$descriptions = $entity->getFingerprint()->getDescriptions();

		$serialization['descriptions'] = $this->serializeValuePerTermList( $descriptions );
	}

	private function serializeValuePerTermList( TermList $list ) {
		$serialization = array();

		foreach( $list as $term ) {
			$this->serializeTerm( $term, $serialization );
		}

		if ( $this->useObjectsForMaps ) {
			$serialization = (object)$serialization;
		}
		return $serialization;
	}

	private function serializeTerm( Term $term, array &$serialization ) {
		$language = $term->getLanguageCode();
		$result = array(
			'language' => $language,
			'value' => $term->getText(),
		);
		if ( $term instanceof TermFallback ) {
			$result['language'] = $term->getActualLanguageCode();
			$result['source'] = $term->getSourceLanguageCode();
		}
		$serialization[$language] = $result;
	}

	public function addAliasesToSerialization( Entity $entity, array &$serialization ) {
		$aliases = $entity->getFingerprint()->getAliasGroups();

		$serialization['aliases'] = $this->serializeAliasGroupList( $aliases );
	}

	private function serializeAliasGroupList( AliasGroupList $aliases ) {
		$serialization = array();

		foreach( $aliases as $aliasGroup ) {
			$this->serializeAliasGroup( $aliasGroup, $serialization );
		}

		if ( $this->useObjectsForMaps ) {
			$serialization = (object)$serialization;
		}
		return $serialization;
	}

	private function serializeAliasGroup( AliasGroup $aliasGroup, array &$serialization ) {
		$language = $aliasGroup->getLanguageCode();
		foreach( $aliasGroup->getAliases() as $value ) {
			$result = array(
				'language' => $language,
				'value' => $value
			);
			if ($aliasGroup instanceof AliasGroupFallback) {
				$result['language'] = $aliasGroup->getActualLanguageCode();
				$result['source'] = $aliasGroup->getSourceLanguageCode();
			}
			$serialization[$language][] = $result;
		}
	}

}
