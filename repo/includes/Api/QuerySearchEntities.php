<?php

namespace Wikibase\Repo\Api;

use ApiPageSet;
use ApiQuery;
use ApiQueryGeneratorBase;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module to search for Wikibase entities that can be used as a generator.
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class QuerySearchEntities extends ApiQueryGeneratorBase {

	/**
	 * @var EntitySearchHelper
	 */
	private $entitySearchHelper;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @var string[]
	 */
	private $entityTypes;

	/**
	 * @param ApiQuery $apiQuery
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiQuery $apiQuery, $moduleName, $modulePrefix = 'wbs' ) {
		parent::__construct( $apiQuery, $moduleName, $modulePrefix );

		$repo = WikibaseRepo::getDefaultInstance();
		$entitySearchHelper = new EntitySearchHelper(
			$repo->getEntityTitleLookup(),
			$repo->getEntityIdParser(),
			$repo->newTermSearchInteractor( $this->getLanguage()->getCode() ),
			new LanguageFallbackLabelDescriptionLookup(
				$repo->getTermLookup(),
				$repo->getLanguageFallbackChainFactory()
					->newFromLanguage( $this->getLanguage() )
			)
		);

		$this->setServices(
			$entitySearchHelper,
			$repo->getEntityTitleLookup(),
			$repo->getTermsLanguages(),
			$repo->getEnabledEntityTypes()
		);
	}

	/**
	 * Override services, for use for testing.
	 *
	 * @param EntitySearchHelper $entitySearchHelper
	 * @param EntityTitleLookup $titleLookup
	 * @param ContentLanguages $termsLanguages
	 * @param array $entityTypes
	 */
	public function setServices(
		EntitySearchHelper $entitySearchHelper,
		EntityTitleLookup $titleLookup,
		ContentLanguages $termsLanguages,
		array $entityTypes
	) {
		$this->entitySearchHelper = $entitySearchHelper;
		$this->titleLookup = $titleLookup;
		$this->termsLanguages = $termsLanguages;
		$this->entityTypes = $entityTypes;
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$searchResults = $this->getSearchResults( $params );
		$result = $this->getResult();

		foreach ( $searchResults as $match ) {
			$title = $this->titleLookup->getTitleForId( $match->getEntityId() );

			$values = array(
				'ns' => intval( $title->getNamespace() ),
				'title' => $title->getPrefixedText(),
				'pageid' => intval( $title->getArticleID() ),
				'displaytext' => $match->getMatchedTerm()->getText(),
			);

			$result->addValue( array( 'query', $this->getModuleName() ), null, $values );
		}

		$result->addIndexedTagName(
			array( 'query', $this->getModuleName() ), $this->getModulePrefix()
		);
	}

	/**
	 * @param ApiPageSet $resultPageSet
	 */
	public function executeGenerator( $resultPageSet ) {
		$params = $this->extractRequestParams();
		$searchResults = $this->getSearchResults( $params );
		$titles = [];

		foreach ( $searchResults as $match ) {
			$title = $this->titleLookup->getTitleForId( $match->getEntityId() );
			$titles[] = $title;
			$resultPageSet->setGeneratorData( $title, array( 'displaytext' => $match->getMatchedTerm()->getText() ) );
		}

		$resultPageSet->populateFromTitles( $titles );
	}

	/**
	 * @param array $params
	 *
	 * @return TermSearchResult[]
	 */
	private function getSearchResults( array $params ) {
		return $this->entitySearchHelper->getRankedSearchResults(
			$params['search'],
			$params['language'] ?: $this->getLanguage()->getCode(),
			$params['type'],
			$params['limit'],
			$params['strictlanguage']
		);
	}

	/**
	 * @see ApiBase::isInternal
	 */
	public function isInternal() {
		return true; // mark this api module as internal until we settled on a solution for search
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
			),
			'strictlanguage' => array(
				self::PARAM_TYPE => 'boolean',
				self::PARAM_DFLT => false,
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
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=query&list=wbsearch&wbssearch=abc&wbslanguage=en' => 'apihelp-query+wbsearch-example-1',
			'action=query&list=wbsearch&wbssearch=abc&wbslanguage=en&wbslimit=50' => 'apihelp-query+wbsearch-example-2',
			'action=query&list=wbsearch&wbssearch=alphabet&wbslanguage=en&wbstype=property' => 'apihelp-query+wbsearch-example-3',
		);
	}

}
