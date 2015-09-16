<?php

namespace Wikibase\Repo\Api;

use ApiPageSet;
use ApiQuery;
use ApiQueryGeneratorBase;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module to search for Wikibase entities that can be used as a generator.
 *
 * @licence GNU GPL v2+
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
			$repo->getStore()->getTermIndex(),
			new LanguageFallbackLabelDescriptionLookup(
				$repo->getTermLookup(),
				$repo->getLanguageFallbackChainFactory()
					->newFromLanguageCode( $this->getLanguage()->getCode() )
			)
		);

		$this->setServices(
			$entitySearchHelper,
			$repo->getEntityTitleLookup(),
			$repo->getEntityFactory()->getEntityTypes()
		);
	}

	/**
	 * Override services, for use for testing.
	 *
	 * @param EntitySearchHelper $entitySearchHelper
	 * @param EntityTitleLookup $titleLookup
	 * @param array $entityTypes
	 */
	public function setServices(
		EntitySearchHelper $entitySearchHelper,
		EntityTitleLookup $titleLookup,
		array $entityTypes
	) {
		$this->entitySearchHelper = $entitySearchHelper;
		$this->titleLookup = $titleLookup;
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
		$titles = array();

		foreach ( $searchResults as $match ) {
			$title = $this->titleLookup->getTitleForId( $match->getEntityId() );
			$titles[] = $title;
			$resultPageSet->setGeneratorData( $title, array( 'displaytext' => $match->getMatchedTerm()->getText() ) );
		}

		$resultPageSet->populateFromTitles( $titles );
	}

	private function getSearchResults( array $params ) {
		$searchResults =  $this->entitySearchHelper->getRankedSearchResults(
			$params['search'],
			'',
			$params['type'],
			$params['offset'] + $params['limit'] + 1,
			false
		);

		$hits = count( $searchResults );
		$offset = $params['offset'] + $params['limit'];
		$searchResults = array_slice( $searchResults, $params['offset'], $params['limit'] );

		if ( $hits > $offset && $offset <= self::LIMIT_SML1 ) {
			$this->setContinueEnumParameter( 'offset', $offset );
		}

		return $searchResults;
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
			'offset' => array(
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
			'action=query&list=wbsearch&wbssearch=abc' => 'apihelp-query+wbsearch-example-1',
			'action=query&list=wbsearch&wbssearch=abc&wbslimit=50' => 'apihelp-query+wbsearch-example-2',
			'action=query&list=wbsearch&wbssearch=alphabet&wbstype=property' => 'apihelp-query+wbsearch-example-3',
		);
	}

}
