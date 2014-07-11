<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\TermList;

/**
 * EntityInfoFingerprintLookup based on an entity info array as build
 * by EntityInfoBuilder.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityInfoFingerprintLookup implements EntityFingerprintLookup {

	/**
	 * Entity info structure, as build by EntityInfoBuilder
	 *
	 * @var array id-string -> entity-record
	 */
	private $entityInfo;

	/**
	 * @param array $entityInfo An entity info data structure as returned
	 * by EntityInfoBuilder::getEntityInfo()
	 */
	public function __construct( array $entityInfo ) {

		$this->entityInfo = $entityInfo;
	}

	/**
	 * Returns the Fingerprint associated with the given entity.
	 *
	 * Implementations may use the $languages and $termTypes parameters for optimization.
	 * It is however not guaranteed that other languages and types are absent from the
	 * resulting Fingerprint.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 * @param string[]|null $languages A list of language codes we are interested in. Null means any.
	 * @param string[]|null $termTypes A list of term types (like "label", "description", or "alias")
	 *        we are interested in. Null means any.
	 *
	 * @throw StorageException
	 * @return Fingerprint|null
	 */
	public function getFingerprint( EntityId $entityId, array $languages = null, array $termTypes = null ) {
		$key = $entityId->getSerialization();

		if ( !isset( $this->entityInfo[$key] ) ) {
			return null;
		}

		return $this->buildFingerprint( $this->entityInfo[$key], $languages, $termTypes );
	}

	private function buildFingerprint( $record, array $languages = null, array $termTypes = null ) {
		$labels = new TermList();
		$descriptions = new TermList();
		$aliases = new AliasGroupList();

		if ( $termTypes === null || in_array( 'label', $termTypes ) ) {

		}

		$fingerprint = new Fingerprint( $labels, $descriptions, $aliases );
		return $fingerprint;
	}
}
