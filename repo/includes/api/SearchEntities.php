<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\Interactors\TermIndexSearchInteractor;
use Wikibase\Repo\Interactors\TermSearchResult;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;

/**
 * API module to search for Wikibase entities.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Thiemo Mättig
 * @author Adam Shorland
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
	 * @var TermIndexSearchInteractor
	 */
	private $termIndexSearchInteractor;

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

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
			$repo->getEntityTitleLookup(),
			$repo->getEntityIdParser(),
			$repo->getEntityFactory()->getEntityTypes(),
			$repo->getTermsLanguages(),
			$repo->newTermSearchInteractor( $this->getLanguage()->getCode() ),
			$repo->getStore()->getTermIndex(),
			new LanguageFallbackLabelDescriptionLookup(
				$repo->getTermLookup(),
				$repo->getLanguageFallbackChainFactory()
				->newFromLanguageCode( $this->getLanguage()->getCode() )
			)
		);
	}

	/**
	 * Override services, for use for testing.
	 *
	 * @param EntityTitleLookup $titleLookup
	 * @param EntityIdParser $idParser
	 * @param array $entityTypes
	 * @param ContentLanguages $termLanguages
	 * @param TermIndexSearchInteractor $termIndexSearchInteractor
	 * @param TermIndex $termIndex
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 */
	public function setServices(
		EntityTitleLookup $titleLookup,
		EntityIdParser $idParser,
		array $entityTypes,
		ContentLanguages $termLanguages,
		TermIndexSearchInteractor $termIndexSearchInteractor,
		TermIndex $termIndex,
		LabelDescriptionLookup $labelDescriptionLookup
	) {
		$this->titleLookup = $titleLookup;
		$this->idParser = $idParser;
		$this->entityTypes = $entityTypes;
		$this->termsLanguages = $termLanguages;
		$this->termIndexSearchInteractor = $termIndexSearchInteractor;
		$this->termIndex = $termIndex;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
	}

	/**
	 * Wrapper around TermSearchInteractor::searchForEntities
	 *
	 * @see TermSearchInteractor::searchForEntities
	 *
	 * @param string $text
	 * @param string $entityType
	 * @param string $languageCode
	 * @param int $limit
	 * @param bool $prefixSearch
	 * @param bool $strictLanguage
	 *
	 * @return TermSearchResult[]
	 */
	private function searchEntities( $text, $entityType, $languageCode, $limit, $prefixSearch, $strictLanguage ) {
		$this->termIndexSearchInteractor->setLimit( $limit );
		$this->termIndexSearchInteractor->setIsPrefixSearch( $prefixSearch );
		$this->termIndexSearchInteractor->setIsCaseSensitive( false );
		$this->termIndexSearchInteractor->setUseLanguageFallback( !$strictLanguage );
		return $this->termIndexSearchInteractor->searchForEntities(
			$text,
			$languageCode,
			$entityType,
			array( TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS )
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
		$searchResults = $this->getRankedSearchResults(
			$params['search'],
			$params['type'],
			$params['language'],
			$params['continue'] + $params['limit'] + 1,
			$params['strictlanguage']
		);

		$entries = array();
		foreach ( $searchResults as $match ) {
			//TODO: use EntityInfoBuilder, EntityInfoTermLookup
			$title = $this->titleLookup->getTitleForId( $match->getEntityId() );
			$entry = array();
			$entry['id'] = $match->getEntityId()->getSerialization();
			$entry['url'] = $title->getFullUrl();
			$entry['title'] = $title->getPrefixedText();
			$entry['pageid'] = $title->getArticleID();
			$displayLabel = $match->getDisplayLabel();
			if ( !is_null( $displayLabel ) ) {
				$entry['label'] = $displayLabel->getText();
			}
			$displayDescription = $match->getDisplayDescription();
			if ( !is_null( $displayDescription ) ) {
				$entry['description'] = $displayDescription->getText();
			}
			$entry['match']['type'] = $match->getMatchedTermType();

			//Special handling for 'entityId's as these are not actually Term objects
			if ( $entry['match']['type'] === 'entityId' ) {
				$entry['match']['text'] = $entry['id'];
				$entry['aliases'] = array( $entry['id'] );
			} else {
				$matchedTerm = $match->getMatchedTerm();
				$matchedTermText = $matchedTerm->getText();
				$entry['match']['language'] = $matchedTerm->getLanguageCode();
				$entry['match']['text'] = $matchedTermText;

				/**
				 * Add matched terms to the aliases key in the result to give some context for the matched Term
				 * if the matched term is different to the alias.
				 * XXX: This appears odd but is used in the UI / Entity suggesters
				 */
				if ( !array_key_exists( 'label', $entry ) || $matchedTermText != $entry['label'] ) {
					$entry['aliases'] = array( $matchedTerm->getText() );
				}
			}
			$entries[] = $entry;
		}
		return $entries;
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
	 * @param string $text
	 * @param string $entityType
	 * @param string $languageCode
	 * @param int $limit
	 * @param bool $strictLanguage
	 *
	 * @return TermSearchResult[] Key: string Serialized EntityId
	 */
	private function getRankedSearchResults( $text, $entityType, $languageCode, $limit, $strictLanguage ) {
		$allSearchResults = array();

		// If $text is the ID of an existing item, include it in the result.
		$entityId = $this->getExactMatchForEntityId( $text, $entityType );
		if ( $entityId !== null ) {
			// This is nothing to do with terms, but make it look a normal result so everything is easier
			$displayTerms = $this->getDisplayTerms( $entityId );
			$allSearchResults[$entityId->getSerialization()] = new TermSearchResult(
				new Term( 'qid', $entityId->getSerialization() ),
				'entityId',
				$entityId,
				$displayTerms['label'],
				$displayTerms['description']
			);
		}

		// If not matched enough then search for full term matches
		$missing = $limit - count( $allSearchResults );
		if ( $missing > 0 ) {
			$exactSearchResults = $this->searchEntities(
				$text,
				$entityType,
				$languageCode,
				$missing,
				false,
				$strictLanguage
			);
			$allSearchResults = $this->mergeSearchResults( $allSearchResults, $exactSearchResults, $limit );

			// If still not enough matched then search for prefix matches
			$missing = $limit - count( $allSearchResults );
			if ( $missing > 0 ) {
				$prefixSearchResults = $this->searchEntities(
					$text,
					$entityType,
					$languageCode,
					$missing,
					true,
					$strictLanguage
				);
				$allSearchResults = $this->mergeSearchResults( $allSearchResults, $prefixSearchResults, $limit );
			}
		}

		return $allSearchResults;
	}

	/**
	 * @param TermSearchResult[] $searchResults
	 * @param TermSearchResult[] $newSearchResults
	 * @param int $limit
	 *
	 * @return TermSearchResult[]
	 */
	private function mergeSearchResults( $searchResults, $newSearchResults, $limit ) {
		$searchResultEntityIdSerializations = array_keys( $searchResults );

		foreach ( $newSearchResults as $searchResultToAdd ) {
			$entityIdString = $searchResultToAdd->getEntityId()->getSerialization();

			if ( !in_array( $entityIdString, $searchResultEntityIdSerializations ) ) {
				$searchResults[$entityIdString] = $searchResultToAdd;
				$searchResultEntityIdSerializations[] = $entityIdString;
				$missing = $limit - count( $searchResults );

				if ( $missing <= 0 ) {
					return $searchResults;
				}
			}
		}

		return $searchResults;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Term[] array with keys 'label' and 'description'
	 */
	private function getDisplayTerms( EntityId $entityId ) {
		$displayTerms = array();

		try {
			$displayTerms['label'] = $this->labelDescriptionLookup->getLabel( $entityId );
		} catch ( OutOfBoundsException $e ) {
			$displayTerms['label'] = null;
		}

		try {
			$displayTerms['description'] = $this->labelDescriptionLookup->getDescription( $entityId );
		} catch ( OutOfBoundsException $e ) {
			$displayTerms['description'] = null;
		}

		return $displayTerms;
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$params = $this->extractRequestParams();

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
		if ( $hits > $nextContinuation && $nextContinuation <= self::LIMIT_SML1 ) {
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

		$this->getResult()->addIndexedTagName( array( 'search' ), 'entity' );

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
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			),
			'language' => array(
				self::PARAM_TYPE => $this->termsLanguages->getLanguages(),
				self::PARAM_REQUIRED => true,
			),
			'strictlanguage' => array(
				self::PARAM_TYPE => 'boolean',
				self::PARAM_DFLT => false
			),
			'type' => array(
				self::PARAM_TYPE => $this->entityTypes,
				self::PARAM_DFLT => 'item',
			),
			'limit' => array(
				self::PARAM_TYPE => 'limit',
				self::PARAM_DFLT => 7,
				self::PARAM_MAX => self::LIMIT_SML1,
				self::PARAM_MAX2 => self::LIMIT_SML2,
				self::PARAM_MIN => 0,
				self::PARAM_RANGE_ENFORCE => true,
			),
			'continue' => array(
				self::PARAM_TYPE => 'integer',
				self::PARAM_REQUIRED => false,
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
