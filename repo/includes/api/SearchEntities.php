<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\ContentLanguages;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Term;
use Wikibase\TermIndex;

/**
 * API module to search for Wikibase entities.
 *
 * FIXME: this module is doing to much work. Ranking terms is not its job and should be delegated
 * FIXME: the continuation currently relies on the search order returned by the TermStore
 *
 * Note: Continuation only works for a rather small number of entities. It is assumed that a large
 * number of entities will not be searched through by human editors, and that bots cannot search
 * through them anyway.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Thiemo Mättig
 */
class SearchEntities extends ApiBase {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @var  LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @var string[]
	 */
	private $entityTypes;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$repo = WikibaseRepo::getDefaultInstance();
		$this->setServices(
			$repo->getStore()->getTermIndex(),
			$repo->getEntityTitleLookup(),
			$repo->getEntityIdParser(),
			$repo->getEntityFactory()->getEntityTypes(),
			$repo->getTermsLanguages(),
			$repo->getLanguageFallbackChainFactory()
		);
	}

	/**
	 * Override services, for use for testing.
	 *
	 * @param TermIndex $termIndex
	 * @param EntityTitleLookup $titleLookup
	 * @param EntityIdParser $idParser
	 * @param array $entityTypes
	 * @param ContentLanguages $termLanguages
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 */
	public function setServices(
		TermIndex $termIndex,
		EntityTitleLookup $titleLookup,
		EntityIdParser $idParser,
		array $entityTypes,
		ContentLanguages $termLanguages,
		LanguageFallbackChainFactory $languageFallbackChainFactory
	) {
		$this->termIndex = $termIndex;
		$this->titleLookup = $titleLookup;
		$this->idParser = $idParser;
		$this->entityTypes = $entityTypes;
		$this->termsLanguages = $termLanguages;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
	}

	/**
	 * Get the entities corresponding to the provided languages and term.
	 * Term means it is either a label or an alias.
	 *
	 * @param string $term
	 * @param string|null $entityType
	 * @param string[] $languages
	 * @param int $limit
	 * @param bool $prefixSearch
	 *
	 * @return Term[]
	 */
	private function searchEntities( $term, $entityType, array $languages, $limit, $prefixSearch ) {
		$termTemplates = array();

		foreach ( $languages as $language ) {
			$termTemplates[] = new Term( array(
				'termType' 		=> Term::TYPE_LABEL,
				'termLanguage' 	=> $language,
				'termText' 		=> $term
			) );

			$termTemplates[] = new Term( array(
				'termType' 		=> Term::TYPE_ALIAS,
				'termLanguage' 	=> $language,
				'termText' 		=> $term
			) );
		}

		//TODO: use getMatchingTerms instead
		return $this->termIndex->getMatchingIDs(
			$termTemplates,
			$entityType,
			array(
				'caseSensitive' => false,
				'prefixSearch' => $prefixSearch,
				'LIMIT' => $limit,
			)
		);
	}

	/**
	 * Populates the search result returning the number of requested matches plus one additional
	 * item for being able to determine if there would be any more results.
	 * If there are not enough exact matches, the list of returned entries will be additionally
	 * filled with prefixed matches.
	 *
	 * @param array $params
	 *
	 * @return array[]
	 */
	private function getSearchEntries( array $params ) {
		$ids = array();
		$required = $params['continue'] + $params['limit'] + 1;

		$languages = $this->getLanguages( $params );

		$ids = array_merge(
			$ids,
			$this->getRankedMatches( $params['search'], $params['type'], $languages, $required )
		);
		$ids = array_unique( $ids );

		return $this->getEntries( $ids, $params['search'], $languages );
	}

	private function getLanguages( array $params ) {
		$lang = $params['language'];

		if ( !$params['strictlanguage'] ) {
			$fallbackMode = (
				LanguageFallbackChainFactory::FALLBACK_VARIANTS
				| LanguageFallbackChainFactory::FALLBACK_OTHERS
				| LanguageFallbackChainFactory::FALLBACK_SELF );

			$fallbackChain = $this->languageFallbackChainFactory
				->newFromLanguageCode( $lang, $fallbackMode );

			return $languages = $fallbackChain->getFetchLanguageCodes();
		} else {
			return array( $lang );
		}
	}

	/**
	 * Gets exact match for the search term as an EntityId if it can be found.
	 *
	 * @param string $term
	 * @param string $entityType
	 *
	 * @return EntityId|null
	 */
	private function getExactMatchForEntityId( $term, $entityType ) {
		try {
			$entityId = $this->idParser->parse( $term );
			$title = $this->titleLookup->getTitleForId( $entityId );

			if ( $title && $title->exists() && ( $entityId->getEntityType() === $entityType ) ) {
				return $entityId;
			}
		} catch ( EntityIdParsingException $ex ) {
			// never mind, doesn't look like an ID.
		}

		return null;
	}

	/**
	 * Gets exact matches. If there are not enough exact matches, it gets prefixed matches.
	 *
	 * @param string $term
	 * @param string|null $entityType
	 * @param string[] $languages
	 * @param int $limit
	 *
	 * @return EntityId[]
	 */
	private function getRankedMatches( $term, $entityType, array $languages, $limit ) {
		if ( empty( $languages ) ) {
			return array();
		}

		/**
		 * @var EntityId[] $ids
		 */
		$ids = array();

		// If $term is the ID of an existing item, include it in the result.
		$entityId = $this->getExactMatchForEntityId( $term, $entityType );
		if ( $entityId !== null ) {
			$ids[] = $entityId;
		}

		// If not enough matches yet, search for full term matches (for all languages at once).
		// No preference is applied to any language.
		$missing = $limit - count( $ids );
		if ( $missing > 0 ) {
			$matchedIds = $this->searchEntities( $term, $entityType, $languages, $missing, false );
			$ids = array_unique( array_merge( $ids, $matchedIds ) );
		}

		// If not enough matches yet, search for prefix matches, one language at a time.
		// This causes multiple queries for cases with few or no matches, but only one
		// with a single language if there are many results (e.g. for a short prefix,
		// as is common for type-ahead suggestions). This way, languages are preferred
		// according to the language fallback chain, and database load is hopefully
		// reduced.
		foreach ( $languages as $lang ) {
			$missing = $limit - count( $ids );
			if ( $missing <= 0 ) {
				break;
			}

			$matchedIds = $this->searchEntities( $term, $entityType, array( $lang ), $missing, true );
			$ids = array_unique( array_merge( $ids, $matchedIds ) );
		}

		// Clip overflow, if any
		$ids = array_slice( $ids, 0, $limit );

		return $ids;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string $search
	 * @param string[] $languages
	 *
	 * @return array[]
	 */
	private function getEntries( array $entityIds, $search, $languages ) {
		/**
		 * @var array[] $entries
		 */
		$entries = array();

		//TODO: do not re-implement language fallback here!
		//TODO: use EntityInfoBuilder, EntityInfoTermLookup, and LanguageFallbackLabelDescriptionLookup
		foreach ( $entityIds as $id ) {
			$key = $id->getSerialization();
			$title = $this->titleLookup->getTitleForId( $id );
			$entries[ $key ] = array(
				'id' => $id->getSerialization(),
				'url' => $title->getFullUrl()
			);
		}

		$termTypes = array( Term::TYPE_LABEL, Term::TYPE_DESCRIPTION, Term::TYPE_ALIAS );

		// Find all the remaining terms for the given entities
		$terms = $this->termIndex->getTermsOfEntities(
			$entityIds, $termTypes, $languages );
		// TODO: This needs to be rethought when a different search engine is used
		$termPattern = '/^' . preg_quote( $search, '/' ) . '/i';

		// ranks for fallback
		$languageRanks = array_flip( $languages );
		$languageRanks[''] = PHP_INT_MAX; // no language is worst

		// track "best" language seen for each entity and term type
		$bestLangPerSlot = array();

		foreach ( $terms as $term ) {
			$key = $term->getEntityId()->getSerialization();
			if ( !isset( $entries[$key] ) ) {
				continue;
			}

			$type = $term->getType();
			$bestLang = isset( $bestLangPerSlot[$key][$type] ) ? $bestLangPerSlot[$key][$type] : '';
			$currentLang = $term->getLanguage();

			// we already have a "better" language for this slot
			if ( $languageRanks[$bestLang] < $languageRanks[$currentLang] ) {
				continue;
			}

			$entry = $entries[$key];

			switch ( $type ) {
				case Term::TYPE_LABEL:
					$entry['label'] = $term->getText();
					$bestLangPerSlot[$key][$type] = $currentLang;
					break;
				case Term::TYPE_DESCRIPTION:
					$entry['description'] = $term->getText();
					$bestLangPerSlot[$key][$type] = $currentLang;
					break;
				case Term::TYPE_ALIAS:
					// Only include matching aliases
					if ( preg_match( $termPattern, $term->getText() ) ) {
						if ( !isset( $entry['aliases'] ) ) {
							$entry['aliases'] = array();
							$this->getResult()->setIndexedTagName( $entry['aliases'], 'alias' );
						}
						$entry['aliases'][] = $term->getText();
						$bestLangPerSlot[$key][$type] = $currentLang;
					}
					break;
			}

			$entries[$key] = $entry;
		}

		//TODO: If we show a non-matching label for lang1 but the match was for the label in lang2,
		//      treat the lang2 label like an alias, so there is an indication what term matched.

		$entries = array_values( $entries );

		return $entries;
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		//TODO: factor search logic out into a new class (TermSearchInteractor), re-use in SpecialTermDisambiguation.
		$entries = $this->getSearchEntries( $params );

		$this->getResult()->addValue(
			null,
			'searchinfo',
			array(
				'search' => $params['search']
			)
		);

		$this->getResult()->addValue(
			null,
			'search',
			array()
		);

		// getSearchEntities returns one more item than requested in order to determine if there
		// would be any more results coming up.
		$hits = count( $entries );

		// Actual result set.
		$entries = array_slice( $entries, $params['continue'], $params['limit'] );

		$nextContinuation = $params['continue'] + $params['limit'];

		// Only pass search-continue param if there are more results and the maximum continuation
		// limit is not exceeded.
		if ( $hits > $nextContinuation && $nextContinuation <= ApiBase::LIMIT_SML1 ) {
			$this->getResult()->addValue(
				null,
				'search-continue',
				$nextContinuation
			);
		}

		$this->getResult()->addValue(
			null,
			'search',
			$entries
		);

		$this->getResult()->setIndexedTagName_internal( array( 'search' ), 'entity' );

		// @todo use result builder?
		$this->getResult()->addValue(
			null,
			'success',
			(int)true
		);
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {

		return array(
			'search' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'language' => array(
				ApiBase::PARAM_TYPE => $this->termsLanguages->getLanguages(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'strictlanguage' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false
			),
			'type' => array(
				ApiBase::PARAM_TYPE => $this->entityTypes,
				ApiBase::PARAM_DFLT => 'item',
			),
			'limit' => array(
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_DFLT => 7,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_SML1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_SML2,
				ApiBase::PARAM_MIN => 0,
				ApiBase::PARAM_RANGE_ENFORCE => true,
			),
			'continue' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false,
			),
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbsearchentities&search=abc&language=en' => 'apihelp-wbsearchentities-example-1',
			'action=wbsearchentities&search=abc&language=en&limit=50' => 'apihelp-wbsearchentities-example-2',
			'action=wbsearchentities&search=alphabet&language=en&type=property' => 'apihelp-wbsearchentities-example-3',
		);
	}

}
