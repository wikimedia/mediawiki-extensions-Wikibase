<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
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
	 * @var EntityIdParser
	 */
	private $idParser;


	/**
	 * @param EntityIdParser $idParser
	 * @param string $cacheKeyPrefix The key prefix to use for constructing cache keys.
	 *         Defaults to "wbterms". There should be no reason to change this.
	 */
	public function __construct(
		EntityIdParser $idParser,
		$cacheKeyPrefix = 'wbterms'
	) {
		$types = array( 'labels', 'descriptions' );
		$this->types = array_flip( $types );

		$this->idParser = $idParser;
		$this->cacheKeyPrefix = $cacheKeyPrefix;
	}

	/**
	 * Returns a cache key suitable for the given entity
	 *
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string $language
	 *
	 * @return string
	 */
	public function getCacheKey( EntityId $entityId, $termType, $language ) {
		$cacheKey = $this->cacheKeyPrefix . ':' . $entityId->getSerialization();

		$cacheKey .= ",$termType,$language";

		return $cacheKey;
	}

	/**
	 * Returns a cache key suitable for the inventory corresponding to the given entity id.
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function getInventoryKey( EntityId $entityId ) {
		$cacheKey = $this->cacheKeyPrefix . ':' . $entityId->getSerialization() . '*';

		return $cacheKey;
	}

	public function parseCacheKey( $key ) {
		if ( !preg_match( '/:(.+),(.*),(.*)$/', $key, $m ) ) {
			throw new InvalidArgumentException( 'Bad cache key: ' . $key );
		}

		return array(
			$this->idParser->parse( $m[1] ),
			$m[2] === '' ? null : $m[2],
			$m[3] === '' ? null : $m[3],
		);
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return string[]
	 */
	public function getInventoryKeys( array $entityIds ) {
		$keys = array();

		foreach ( $entityIds as $entityId ) {
			$keys[] = $this->getInventoryKey( $entityId );
		}

		return $keys;
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

	/**
	 * Convert the indexes of the given array from cache keys to language codes.
	 * If any two keys refer to the same language code, only one will be present in the result.
	 *
	 * @param array $values indexed by cache keys
	 *
	 * @return array[] A list of triples list( $entityId, $types, $languages )
	 */
	public function convertKeysToLanguageCodes( array $values ) {
		$valuesByLanguage = array();

		foreach ( $values as $key => $value ) {
			list( , , $language ) = $this->cacheCodec->parseCacheKey( $key );
			$valuesByLanguage[$language] = $value;
		}

		return $valuesByLanguage;
	}

}
