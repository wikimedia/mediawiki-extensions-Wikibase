<?php

declare( strict_types=1 );
namespace Wikibase\Client\DataAccess\Scribunto;

use LogicException;
use MediaWiki\Language\Language;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Languages\LanguageNameUtils;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;

/**
 * This TermLookup allows exposes language based lookups for getLabel and getDescription
 * and is backed by the shared TermFallbackCache which stores TermFallback objects.
 *
 * The lookup tries to find the term in the cache and if not present builds a
 * LanguageFallbackLabelDescriptionLookup based on the TermFallbackChain generated by the requested Language.
 *
 * If the requested language does not match the actual language of the TermFallback this value
 * will still be written to the cache but not returned.
 *
 * If the TermFallback returns null this value will also be written to the cache as this means no term is available
 * in the requested language.
 *
 * @see TermFallbackCacheFacade
 *
 * @license GPL-2.0-or-later
 */
class CachingFallbackBasedTermLookup implements TermLookup {

	/** @var TermFallbackCacheFacade */
	private $termFallbackCache;

	/** @var RedirectResolvingLatestRevisionLookup */
	private $redirectResolvingLatestRevisionLookup;

	/** @var LanguageFallbackChainFactory */
	private $languageFallbackChainFactory;

	/** @var TermLookup */
	private $termLookup;

	/** @var LanguageFactory */
	private $languageFactory;

	/** @var LanguageNameUtils */
	private $langNameUtils;

	/** @var ContentLanguages */
	private $contentLanguages;

	/** @var LanguageFallbackLabelDescriptionLookup[] */
	private $lookups;

	public function __construct(
		TermFallbackCacheFacade $termFallbackCacheFacade,
		RedirectResolvingLatestRevisionLookup $redirectResolvingLatestRevisionLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		TermLookup $termLookup,
		LanguageFactory $languageFactory,
		LanguageNameUtils $langNameUtils,
		ContentLanguages $contentLanguages
	) {
		$this->termFallbackCache = $termFallbackCacheFacade;
		$this->redirectResolvingLatestRevisionLookup = $redirectResolvingLatestRevisionLookup;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->termLookup = $termLookup;
		$this->languageFactory = $languageFactory;
		$this->langNameUtils = $langNameUtils;
		$this->contentLanguages = $contentLanguages;
	}

	/** @inheritDoc */
	public function getLabel( EntityId $entityId, $languageCode ) {
		return $this->getTerm( $entityId, $languageCode, TermTypes::TYPE_LABEL );
	}

	/** @inheritDoc */
	public function getDescription( EntityId $entityId, $languageCode ) {
		return $this->getTerm( $entityId, $languageCode, TermTypes::TYPE_DESCRIPTION );
	}

	private function getTerm( EntityId $entityId, string $languageCode, string $termType ): ?string {
		$resolutionResult = $this->redirectResolvingLatestRevisionLookup->lookupLatestRevisionResolvingRedirect( $entityId );
		if ( $resolutionResult === null ) {
			return null;
		}

		// try getting the requested language first
		// probably less expensive than hitting memcache with an invalid language
		if ( !$this->contentLanguages->hasLanguage( $languageCode ) ) {
			return null;
		}

		if ( $this->langNameUtils->isValidCode( $languageCode ) ) {
			$language = $this->languageFactory->getLanguage( $languageCode );
		} else {
			return null;
		}

		[ $revisionId, $targetEntityId ] = $resolutionResult;

		$termFallback = $this->termFallbackCache->get( $targetEntityId, $revisionId, $languageCode, $termType );

		// We have already cached the fact that there is no value for this term
		if ( $termFallback === null ) {
			return null;
		}

		if ( $termFallback === TermFallbackCacheFacade::NO_VALUE ) {
			// fetch fresh using a term lookup
			$termFallback = $this->lookupWithoutCache( $targetEntityId, $language, $termType );

			// this can be stored in cache, either the term or null
			$this->termFallbackCache->set( $termFallback, $targetEntityId, $revisionId, $languageCode, $termType );

			if ( $termFallback === null ) {
				return null;
			}
		}

		if ( $termFallback->getActualLanguageCode() === $languageCode ) {
			return $termFallback->getText();
		}
		return null;
	}

	/** @inheritDoc */
	public function getLabels( EntityId $entityId, array $languageCodes ) {
		$labels = [];

		foreach ( $languageCodes as $languageCode ) {
			$label = $this->getLabel( $entityId, $languageCode );
			if ( $label !== null ) {
				$labels[$languageCode] = $label;
			}
		}

		return $labels;
	}

	/** @inheritDoc */
	public function getDescriptions( EntityId $entityId, array $languageCodes ) {
		$descriptions = [];

		foreach ( $languageCodes as $languageCode ) {
			$description = $this->getDescription( $entityId, $languageCode );
			if ( $description !== null ) {
				$descriptions[$languageCode] = $description;
			}
		}

		return $descriptions;
	}

	private function getLookup( Language $language ): LanguageFallbackLabelDescriptionLookup {
		if ( !isset( $this->lookups[ $language->getCode() ] ) ) {
			$this->lookups[ $language->getCode() ] = new LanguageFallbackLabelDescriptionLookup(
				$this->termLookup,
				$this->languageFallbackChainFactory->newFromLanguage( $language )
			);
		}
		return $this->lookups[ $language->getCode() ];
	}

	/**
	 * @param EntityId $entityId
	 * @param Language $language
	 * @param string $termType
	 * @return TermFallback|null
	 */
	private function lookupWithoutCache( EntityId $entityId, Language $language, $termType ): ?TermFallback {
		$withoutCacheLookup = $this->getLookup( $language );

		if ( $termType === TermTypes::TYPE_LABEL ) {
			return $withoutCacheLookup->getLabel( $entityId );
		} elseif ( $termType === TermTypes::TYPE_DESCRIPTION ) {
			return $withoutCacheLookup->getDescription( $entityId );
		}

		throw new LogicException( "$termType is not supported." );
	}
}
