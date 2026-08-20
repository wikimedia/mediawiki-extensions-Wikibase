<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * @license GPL-2.0-or-later
 */
class TermFallbackCacheEntityStoreWatcher implements EntityStoreWatcher {

	private const string CACHE_KEY = 'wikibase:termfallbackcache:entitystorewatcher:fallbacklanguages';

	public function __construct(
		private readonly EntityRevisionLookup $entityRevisionLookup,
		private readonly TermFallbackCacheFacade $termFallbackCache,
		private readonly WikibaseContentLanguages $contentLanguages,
		private readonly BagOStuff $localServerObjectCache,
		private readonly LanguageFallbackChainFactory $languageFallbackChainFactory,
	) {
	}

	/** @inheritDoc */
	public function entityUpdated( EntityRevision $entityRevision, int $parentId ): void {
		$updatedEntity = $entityRevision->getEntity();
		if ( $parentId && $updatedEntity instanceof Property ) {
			$parentRevision = $this->entityRevisionLookup->getEntityRevision( $entityRevision->getEntity()->getId(), $parentId );
			$parentEntity = $parentRevision->getEntity();
			if ( $parentEntity instanceof Property ) {
				$oldFingerprint = $parentEntity->getFingerprint();
				$newFingerprint = $updatedEntity->getFingerprint();
				if ( !$oldFingerprint->getDescriptions()->equals( $newFingerprint->getDescriptions() ) ) {
					$this->updateCache(
						$entityRevision->getEntity()->getId(),
						$newFingerprint->getDescriptions(),
						$oldFingerprint->getDescriptions(),
						TermTypes::TYPE_DESCRIPTION
					);
				}
				if ( !$oldFingerprint->getLabels()->equals( $newFingerprint->getLabels() ) ) {
					$this->updateCache(
						$entityRevision->getEntity()->getId(),
						$newFingerprint->getLabels(),
						$oldFingerprint->getLabels(),
						TermTypes::TYPE_LABEL
					);
				}
			}
		}
	}

	/**
	 * The term list for this term type is different in the new revision. We need to:
	 * 1. update the cache lines for any languages that have been updated
	 * 2. update the cache lines for any languages that fall back to the updated terms, where those languages
	 *    do not have their own explicit term
	 * 3. delete the cache lines for languages that are no longer in the new term list, and for which none of the
	 *    languages in the new term list are a fallback (i.e. have been set in 1. or 2.)
	 */
	private function updateCache( EntityId $entityId, TermList $newTerms, TermList $oldTerms, string $termType ): void {
		$oldTermTextsByLanguage = $oldTerms->toTextArray();
		$newTermTextsByLanguage = $newTerms->toTextArray();

		$affectedLanguagesAsKeys = [];
		foreach ( $newTermTextsByLanguage as $language => $newText ) {
			if ( ( $oldTermTextsByLanguage[$language] ?? '' ) !== $newText ) {
				$affectedLanguagesAsKeys[$language] = true;
				foreach ( $this->getLanguagesFallingBackTo( $language ) as $affectedLanguage ) {
					$affectedLanguagesAsKeys[$affectedLanguage] = true;
				}
			}
		}
		foreach ( $oldTermTextsByLanguage as $language => $oldText ) {
			if ( !array_key_exists( $language, $newTermTextsByLanguage ) ) {
				$affectedLanguagesAsKeys[$language] = true;
				foreach ( $this->getLanguagesFallingBackTo( $language ) as $affectedLanguage ) {
					$affectedLanguagesAsKeys[$affectedLanguage] = true;
				}
			}
		}

		$termFallbacks = [];
		foreach ( $affectedLanguagesAsKeys as $language => $_ ) {
			$fallbackChain = $this->languageFallbackChainFactory->newFromLanguageCode( $language );
			$extractedData = $fallbackChain->extractPreferredValue( $newTermTextsByLanguage );
			$termFallbacks[$language] = $extractedData === null ? null : new TermFallback(
				$language, $extractedData['value'], $extractedData['language'], $extractedData['source']
			);
		}

		$this->termFallbackCache->setMultiple( $termFallbacks, $entityId, 0, $termType );
	}

	/**
	 * Generate and return the map from languages to the languages for which they are a fallback
	 * This is intensive to calculate, so we store this in the process cache.
	 *
	 * @return array<string,string[]>
	 */
	private function getLanguagesFallingBackToTargetCache(): array {
		return $this->localServerObjectCache->getWithSetCallback(
			self::CACHE_KEY,
			BagOStuff::TTL_HOUR,
			function () {
				$languagesFallingBackToTarget = [];
				$contentLanguages = $this->contentLanguages->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM );
				foreach ( $contentLanguages->getLanguages() as $language ) {
					$fallbackChain = $this->languageFallbackChainFactory->newFromLanguageCode( $language );
					foreach ( $fallbackChain->getFetchLanguageCodes() as $languageCode ) {
						if ( $languageCode === $language ) {
							continue;
						}
						$languagesFallingBackToTarget[$languageCode][] = $language;
					}
				}

				return $languagesFallingBackToTarget;
			}
		);
	}

	/**
	 * @param string $languageCode
	 * @return string[]
	 */
	private function getLanguagesFallingBackTo( string $languageCode ): array {
		return $this->getLanguagesFallingBackToTargetCache()[ $languageCode ] ?? [];
	}

	/** @inheritDoc */
	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		// We only care about properties here (T434204), so we do not need to worry about redirects
	}

	/** @inheritDoc */
	public function entityDeleted( EntityId $entityId ) {
		// No explicit action is required for deleted properties. Their cache entries can remain as
		// they may continue to be rendered after deletion.
	}
}
