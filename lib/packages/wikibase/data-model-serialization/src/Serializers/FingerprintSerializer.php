<?php

namespace Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupFallback;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
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
	private $useObjectsForMaps;

	/**
	 * @param bool $useObjectsForMaps
	 */
	public function __construct( $useObjectsForMaps ) {
		$this->useObjectsForMaps = $useObjectsForMaps;
	}

	public function addBasicsToSerialization( EntityId $id = null, Fingerprint $fingerprint, array &$serialization ) {
		$this->addIdToSerialization( $id, $serialization );
		$this->addLabelsToSerialization( $fingerprint->getLabels(), $serialization );
		$this->addDescriptionsToSerialization( $fingerprint->getDescriptions(), $serialization );
		$this->addAliasesToSerialization( $fingerprint->getAliasGroups(), $serialization );
	}

	private function addIdToSerialization( EntityId $id = null, array &$serialization ) {
		if ( $id === null ) {
			return;
		}

		$serialization['id'] = $id->getSerialization();
	}

	private function addLabelsToSerialization( TermList $labels, array &$serialization ) {
		$serialization['labels'] = $this->serializeValuePerTermList( $labels );
	}

	public function addDescriptionsToSerialization( TermList $descriptions, array &$serialization ) {
		$serialization['descriptions'] = $this->serializeValuePerTermList( $descriptions );
	}

	private function serializeValuePerTermList( TermList $list ) {
		$serialization = array();

		foreach ( $list as $term ) {
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

	public function addAliasesToSerialization( AliasGroupList $aliases, array &$serialization ) {
		$serialization['aliases'] = $this->serializeAliasGroupList( $aliases );
	}

	private function serializeAliasGroupList( AliasGroupList $aliases ) {
		$serialization = array();

		foreach ( $aliases as $aliasGroup ) {
			$this->serializeAliasGroup( $aliasGroup, $serialization );
		}

		if ( $this->useObjectsForMaps ) {
			$serialization = (object)$serialization;
		}
		return $serialization;
	}

	private function serializeAliasGroup( AliasGroup $aliasGroup, array &$serialization ) {
		$language = $aliasGroup->getLanguageCode();
		foreach ( $aliasGroup->getAliases() as $value ) {
			$result = array(
				'language' => $language,
				'value' => $value
			);
			if ( $aliasGroup instanceof AliasGroupFallback ) {
				$result['language'] = $aliasGroup->getActualLanguageCode();
				$result['source'] = $aliasGroup->getSourceLanguageCode();
			}
			$serialization[$language][] = $result;
		}
	}

}
