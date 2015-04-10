<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityInfoTermLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Term;
use Wikibase\TermSearchResult;

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
	 * @var  LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		//TODO: provide a mechanism to override the services
		$this->titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$this->idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		$this->languageFallbackChainFactory = WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory();
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

 		$terms = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex()->getMatchingTerms(
			$termTemplates,
			null,
			$entityType,
			array(
				'caseSensitive' => false,
				'prefixSearch' => $prefixSearch,
				'LIMIT' => $limit,
			)
		);

		return $terms;
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
		$result = new TermSearchResult();
		$required = $params['continue'] + $params['limit'] + 1;

		$languages = $this->getLanguages( $params );

		$entityId = $this->getExactMatchForEntityId( $params['search'], $params['type'] );
		if ( $entityId !== null ) {
			$result->addTerm( new Term( array(
				'termText' => $entityId->getSerialization(),
				'termWeight' => 100,
			) ) );
		}

		// If still space, then merge full length
		$missing = $required - $result->getSize();
		if ( $missing > 0 ) {
			//FIXME: boost score * 10, penalize language fallback *0.5
			$result->addAllTerms(
				 $this->searchEntities( $params['search'], $params['type'], $languages, $missing*2, false )
			);
		}

		// If still space, then merge in prefix matches
		$missing = $required - $result->getSize();
		if ( $missing > 0 ) {
			$result->addAllTerms(
				$this->searchEntities( $params['search'], $params['type'], $languages, $missing*2, true )
			);
		}

		return $this->getEntries( $result, $languages, $required );
	}

	private function getLanguages( array $params ) {
		$lang = $params['language'];

		if ( $params['languagefallback'] ) {
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
	 * @param EntityId[] $entityIds
	 *
	 * @return Title[]
	 */
	private function getEntityPageTitles( array $entityIds ) {
		$titles = array();

		foreach ( $entityIds as $id ) {
			$key = $id->getSerialization();
			$title = $this->titleLookup->getTitleForId( $id );
			$titles[ $key ] = $title;
		}

		return $titles;
	}

	/**
	 * @param TermSearchResult $searchResult
	 * @param string[] $languageCodes
	 * @param int $limit
	 *
	 * @return array[]
	 */
	private function getEntries( TermSearchResult $searchResult, array $languageCodes, $limit ) {
		$hits = $searchResult->getHits( $limit );
		$entityIds = $searchResult->getEntityIds( $limit );

		$infoBuilder = new SqlEntityInfoBuilder( $entityIds );
		$infoBuilder->collectTerms( array( Term::TYPE_LABEL, Term::TYPE_DESCRIPTION ), $languageCodes );

		$info = $infoBuilder->getEntityInfo();

		$labelLookup = new LanguageFallbackLabelDescriptionLookup( new EntityInfoTermLookup( $info ), $languageCodes ); // FIXME: FallbackChain

		//TODO: add collectTitles method to EntityInfoBuilder
		$titles = $this->getEntityPageTitles( $entityIds );

		$entries = array();

		foreach ( $entityIds as $id ) {
			$key = $id->getSerialization();

			//FIXME: catch OutOfBOundsException
			$label = $labelLookup->getLabel( $id );
			$description = $labelLookup->getDescription( $id );
			$url = isset( $titles[$key] ) ? $titles[$key]->getFullUrl() : null;

			$entries[ $key ] = array(
				'id' => $id->getSerialization(),
				'url' => $url,
				'label' => $label->getText(),
				'description' => $description->getText(),
				'label-language' => $label->getLanguageCode(), //FIXME: get original language code
				'description-description' => $description->getLanguageCode(),
			);

			$hitTerms = $hits[$key]->getTerms();
			$term = reset( $hitTerms );

			if ( $term->getText() !== $label ) {
				$entries[ $key ]['matched'] = $term->getText();
				$entries[ $key ]['matched-language'] = $term->getLanguage();
			}
		}

		return $entries;
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
		$entityFactory = WikibaseRepo::getDefaultInstance()->getEntityFactory();

		return array(
			'search' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'language' => array(
				ApiBase::PARAM_TYPE => WikibaseRepo::getDefaultInstance()->getTermsLanguages()->getLanguages(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'languagefallback' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false
			),
			'type' => array(
				ApiBase::PARAM_TYPE => $entityFactory->getEntityTypes(),
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

	private function removeDuplicateMatches( $terms ) {
		//FIXME: remove dupes
		return $terms;
	}
}
