<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * Utility class for generating cache keys and encoding and decoding cache values and
 * meta-information.
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TermCacheCodec {

	/**
	 * @var array
	 */
	private $types;

	/**
	 * The key prefix to use when caching entities in memory.
	 *
	 * @var $cacheKeyPrefix
	 */
	private $cacheKeyPrefix;

	/**
	 * @param string $cacheKeyPrefix The key prefix to use for constructing cache keys.
	 *         Defaults to "wbterms". There should be no reason to change this.
	 */
	public function __construct(
		$cacheKeyPrefix = 'wbterms'
	) {
		$types = array( 'labels', 'descriptions' );
		$this->types = array_flip( $types );

		$this->cacheKeyPrefix = $cacheKeyPrefix;
	}

	/**
	 * Returns a cache key suitable for the given entity
	 *
	 * @param EntityId $entityId
	 * @param string|null $termType
	 * @param string|null $language
	 *
	 * @return string
	 */
	public function getCacheKey( EntityId $entityId, $termType = null, $language = null ) {
		$cacheKey = $this->cacheKeyPrefix . ':' . $entityId->getSerialization();

		if ( $termType !== null ) {
			$cacheKey .= ',' . $termType;
		}

		if ( $language !== null ) {
			$cacheKey .= ',' . $language;
		}

		return $cacheKey;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string[] $termTypes
	 * @param string[] $languages
	 *
	 * @return string[]
	 */
	public function getCacheKeys( array $entityIds, array $termTypes, array $languages ) {
		$keys = array();

		foreach ( $entityIds as $entityId ) {
			foreach ( $termTypes as $termType ) {
				foreach ( $languages as $language ) {
					$keys[] = $this->getCacheKey( $entityId, $termType, $language );
				}
			}
		}

		return $keys;
	}

	private function getLanguageGroupString( $name, array $languages ) {
		return $name . ':' . implode( '|', $languages );
	}

	public function getInventoryString( Fingerprint $terms ) {
		$info = '';

		if ( isset( $this->types['labels'] )  ) {
			$labelLanguages = array_keys( $terms->getLabels()->toTextArray() );
			$info .= $this->getLanguageGroupString( 'L', $labelLanguages ) . ';';
		}

		if ( isset( $this->types['descriptions'] )  ) {
			$descriptionLanguages = array_keys( $terms->getDescriptions()->toTextArray() );
			$info .= $this->getLanguageGroupString( 'D', $descriptionLanguages ) . ';';
		}

		return $info;
	}

	/**
	 * @param EntityId $entityId
	 * @param $fingerprintInfo
	 *
	 * @return array
	 */
	public function getCacheKeysForInventory( EntityId $entityId, $fingerprintInfo ) {
		$groups = explode( ';', $fingerprintInfo );
		$batch = array();

		foreach ( $groups as $group ) {
			list( $termType, $languages ) = explode( ':', $group, 2 );
			$languages = explode( '|', $languages );

			$batchForTermType = $this->getCacheKeys( array( $entityId ), array( $termType ), $languages );
			$batch = array_merge( $batch, $batchForTermType );
		}

		return $batch;
	}

	private function getTermListEntries( EntityId $entityId, $termType, TermList $terms ) {
		$entries = array();

		/** @var Term $term */
		foreach ( $terms as $term ) {
			$key = $this->getCacheKey( $entityId, $termType, $term->getLanguageCode() );
			$entries[$key] = $term->getText();
		}

		return $entries;
	}

	public function getCacheValues( EntityId $entityId, Fingerprint $terms ) {
		$batch = array();

		if ( isset( $this->types['labels'] )  ) {
			$entries = $this->getTermListEntries( $entityId, 'L', $terms->getLabels() );
			$batch = array_merge( $batch, $entries );
		}

		if ( isset( $this->types['descriptions'] )  ) {
			$entries = $this->getTermListEntries( $entityId, 'D', $terms->getDescriptions() );
			$batch = array_merge( $batch, $entries );
		}

		return $batch;
	}

}
