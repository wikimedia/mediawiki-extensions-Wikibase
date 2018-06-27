<?php

namespace  Wikibase\Lib\Store;

use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\LanguageFallbackChain;

/**
 * @license GPL-2.0-or-later
 */
class CachingFallbackLabelDescriptionLookup implements LabelDescriptionLookup {

	// TODO: make private constant when possible (PHP 7.1+)
	/* private */ const LABEL = 'label';
	/* private */ const DESCRIPTION = 'description';

	const FIELD_LANGUAGE = 'language';
	const FIELD_VALUE = 'value';
	const FIELD_REQUEST_LANGUAGE = 'requestLanguage';
	const FIELD_SOURCE_LANGUAGE = 'sourceLanguage';

	const SERIALIZATION_NULL = 'null';

	/**
	 * @var CacheInterface
	 */
	private $cache;

	/**
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var LanguageFallbackChain
	 */
	private $languageFallbackChain;

	/**
	 * @var int
	 */
	private $cacheTtlInSeconds = 3600;

	/**
	 * @param CacheInterface $cache
	 * @param EntityRevisionLookup $revisionLookup
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param int $cacheTtlInSeconds
	 */
	public function __construct(
		CacheInterface $cache,
		EntityRevisionLookup $revisionLookup,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $languageFallbackChain,
		$cacheTtlInSeconds
	) {
		$this->cache = $cache;
		$this->revisionLookup = $revisionLookup;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->languageFallbackChain = $languageFallbackChain;
		$this->cacheTtlInSeconds = $cacheTtlInSeconds;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getDescription( EntityId $entityId ) {
		$languageCodes = $this->languageFallbackChain->getFetchLanguageCodes();

		$languageCode = $languageCodes[0];
		$description = $this->getTerm( $entityId, $languageCode, self::DESCRIPTION );

		if ( $this->shouldCache( $entityId, $languageCode, self::DESCRIPTION ) ) {
			$this->cacheTerm( $entityId, $languageCode, self::DESCRIPTION, $description );
		}

		return $description;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getLabel( EntityId $entityId ) {
		$languageCodes = $this->languageFallbackChain->getFetchLanguageCodes();
		$languageCode = $languageCodes[0];
		$label = $this->getTerm( $entityId, $languageCode, self::LABEL );

		if ( $this->shouldCache( $entityId, $languageCode, self::LABEL ) ) {
			$this->cacheTerm( $entityId, $languageCode, self::LABEL, $label );
		}

		return $label;
	}

	private function getTerm( EntityId $entityId, $languageCode, $termName = self::LABEL ) {
		$cacheKey = $this->getCacheKey( $entityId, $languageCode, $termName );
		$result = $this->cache->get( $cacheKey );
		if ( !$result ) {
			$term = $termName === self::LABEL
				? $this->labelDescriptionLookup->getLabel( $entityId )
				: $this->labelDescriptionLookup->getDescription( $entityId );

			return $term;
		}

		$term = $this->unserialize( $result );

		return $term;
	}

	private function shouldCache( EntityId $entityId, $languageCode, $termName ) {
		$cacheKey = $this->getCacheKey( $entityId, $languageCode, $termName );
		return !$this->cache->has( $cacheKey );
	}

	private function cacheTerm( EntityId $entityId, $languageCode, $termName, TermFallback $term = null ) {
		$cacheKey = $this->getCacheKey( $entityId, $languageCode, $termName );

		$serialization = $this->serialize( $term );

		$this->cache->set( $cacheKey, $serialization, $this->cacheTtlInSeconds );
	}

	/**
	 * @param TermFallback|null $termFallback
	 * @return string
	 */
	private function serialize( $termFallback ) {
		if ( $termFallback === null ) {
			return self::SERIALIZATION_NULL;
		}

		return json_encode( [
			self::FIELD_LANGUAGE => $termFallback->getActualLanguageCode(),
			self::FIELD_VALUE => $termFallback->getText(),
			self::FIELD_REQUEST_LANGUAGE => $termFallback->getLanguageCode(),
			self::FIELD_SOURCE_LANGUAGE => $termFallback->getSourceLanguageCode(),
		] );
	}

	private function unserialize( $serialized ) {
		if ( $serialized === self::SERIALIZATION_NULL ) {
			return null;
		}

		$termData = json_decode( $serialized, true );
		return new TermFallback(
			$termData[self::FIELD_REQUEST_LANGUAGE],
			$termData[self::FIELD_VALUE],
			$termData[self::FIELD_LANGUAGE],
			$termData[self::FIELD_SOURCE_LANGUAGE]
		);
	}

	/**
	 * @param EntityId $entityId
	 * @param string $languageCode
	 * @param string $termName
	 * @return string
	 */
	private function getCacheKey( EntityId $entityId, $languageCode, $termName ) {
		$revisionId = $this->revisionLookup->getLatestRevisionId( $entityId );

		//FIXME prefix?
		return "{$entityId->getSerialization()}_{$revisionId}_{$languageCode}_{$termName}";
	}

}
