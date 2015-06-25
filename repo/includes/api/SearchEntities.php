<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LabelDescriptionLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\Interactors\TermIndexSearchInteractor;
use Wikibase\Repo\Interactors\TermSearchInteractor;
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
	 * @return array[]
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
		$matches = $this->getRankedMatches(
			$params['search'],
			$params['type'],
			$params['language'],
			$params['continue'] + $params['limit'] + 1,
			$params['strictlanguage']
		);

		$entries = array();
		foreach ( $matches as $match ) {
			//TODO: use EntityInfoBuilder, EntityInfoTermLookup
			$title = $this->titleLookup->getTitleForId(
				$match['entityId']
			);
			$entry = array();
			$entry['id'] = $match['entityId'];
			$entry['url'] = $title->getFullUrl();
			$entry = array_merge( $entry, $this->termsToArray( $match['displayTerms'] ) );
			$entry['match']['type'] = $match[TermIndexSearchInteractor::MATCHEDTERMTYPE_KEY];
			//Special handeling for 'entityId's as these are not actually Term objects
			if ( $entry['match']['type'] === 'entityId' ) {
				$entry['match']['text'] = $match['entityId'];
				$entry['aliases'] = array( $match['entityId'] );
			} else {
				/** @var Term $matchedTerm */
				$matchedTerm = $match[TermIndexSearchInteractor::MATCHEDTERM_KEY];
				$entry['match']['language'] = $matchedTerm->getLanguageCode();
				$entry['match']['text'] = $matchedTerm->getText();
				$entry['aliases'] = array( $matchedTerm->getText() );
			}
			$entries[] = $entry;
		}
		return $entries;
	}

	/**
	 * @param Term[]|string[] $terms
	 *
	 * @return array[]
	 */
	private function termsToArray( array $terms ) {
		$termArray = array();
		foreach ( $terms as $key => $term ) {
			if( $term instanceof Term ) {
				$termArray[$key] = $term->getText();
			} else {
				$termArray[$key] = $term;
			}
		}
		return $termArray;
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
	 * @return array[] Key: string Serialized EntityId
	 *           Value: array( entityId => EntityId, displayTerms => Term[], matchedTerm => Term, matchedTermType => string )
	 *           Note: both arrays have possible keys Wikibase\TermIndexEntry::TYPE_*
	 */
	private function getRankedMatches( $text, $entityType, $languageCode, $limit, $strictLanguage ) {
		$allSearchResults = array();

		// If $text is the ID of an existing item, include it in the result.
		$entityId = $this->getExactMatchForEntityId( $text, $entityType );
		if ( $entityId !== null ) {
			// This is nothing to do with terms, but make it look like it is so it is easy to handle
			$allSearchResults[$entityId->getSerialization()] = array(
				TermSearchInteractor::ENTITYID_KEY => $entityId,
				TermSearchInteractor::DISPLAYTERMS_KEY => $this->termsToArray( $this->getDisplayTerms( $entityId ) ),
				// Pretend that the entityId is a type of term so it can be output
				TermSearchInteractor::MATCHEDTERMTYPE_KEY => 'entityId',
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
	 * @param array[] $searchResults
	 * @param array[] $newSearchResults
	 * @param int $limit
	 *
	 * @return array
	 */
	private function mergeSearchResults( $searchResults, $newSearchResults, $limit ) {
		$searchResultEntityIdSerializations = array_keys( $searchResults );
		foreach ( $newSearchResults as $searchResultToAdd ) {
			/** @var EntityId $entityId */
			$entityId = $searchResultToAdd[TermSearchInteractor::ENTITYID_KEY];
			$entityIdString = $entityId->getSerialization();
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
	 * @return Term[] array with possible keys TermIndexEntry::TYPE_*
	 */
	private function getDisplayTerms( EntityId $entityId ) {
		$displayTerms = array();
		try{
			$displayTerms[TermIndexEntry::TYPE_LABEL] = $this->labelDescriptionLookup->getLabel( $entityId );
		} catch ( OutOfBoundsException $e ) {
			// Ignore
		};
		try{
			$displayTerms[TermIndexEntry::TYPE_DESCRIPTION] = $this->labelDescriptionLookup->getDescription( $entityId );
		} catch ( OutOfBoundsException $e ) {
			// Ignore
		};
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
