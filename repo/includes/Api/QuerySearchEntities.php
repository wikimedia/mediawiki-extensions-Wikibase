<?php

namespace Wikibase\Repo\Api;

use ApiPageSet;
use ApiQuery;
use ApiQueryGeneratorBase;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * API module to search for Wikibase entities that can be used as a generator.
 *
 * @license GPL-2.0-or-later
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
	 * @param EntitySearchHelper $entitySearchHelper
	 * @param EntityTitleLookup $titleLookup
	 * @param ContentLanguages $termsLanguages
	 * @param array $entityTypes
	 */
	public function __construct(
		ApiQuery $apiQuery,
		$moduleName,
		EntitySearchHelper $entitySearchHelper,
		EntityTitleLookup $titleLookup,
		ContentLanguages $termsLanguages,
		array $entityTypes
	) {
		parent::__construct( $apiQuery, $moduleName, 'wbs' );

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

			$values = [
				'ns' => intval( $title->getNamespace() ),
				'title' => $title->getPrefixedText(),
				'pageid' => intval( $title->getArticleID() ),
				'displaytext' => $match->getMatchedTerm()->getText(),
			];

			$result->addValue( [ 'query', $this->getModuleName() ], null, $values );
		}

		$result->addIndexedTagName(
			[ 'query', $this->getModuleName() ], $this->getModulePrefix()
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
			$resultPageSet->setGeneratorData( $title, [ 'displaytext' => $match->getMatchedTerm()->getText() ] );
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
	 * @see ApiQueryBase::getCacheMode
	 *
	 * @param array $params
	 * @return string
	 */
	public function getCacheMode( $params ) {
		return 'public';
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
		return [
			'search' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			],
			'language' => [
				self::PARAM_TYPE => $this->termsLanguages->getLanguages(),
			],
			'strictlanguage' => [
				self::PARAM_TYPE => 'boolean',
				self::PARAM_DFLT => false,
			],
			'type' => [
				self::PARAM_TYPE => $this->entityTypes,
				self::PARAM_DFLT => 'item',
			],
			'limit' => [
				self::PARAM_TYPE => 'limit',
				self::PARAM_DFLT => 7,
				self::PARAM_MAX => self::LIMIT_SML1,
				self::PARAM_MAX2 => self::LIMIT_SML2,
				self::PARAM_MIN => 0,
			],
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return [
			'action=query&list=wbsearch&wbssearch=abc&wbslanguage=en' => 'apihelp-query+wbsearch-example-1',
			'action=query&list=wbsearch&wbssearch=abc&wbslanguage=en&wbslimit=50' => 'apihelp-query+wbsearch-example-2',
			'action=query&list=wbsearch&wbssearch=alphabet&wbslanguage=en&wbstype=property' => 'apihelp-query+wbsearch-example-3',
			'action=query&generator=wbsearch&gwbssearch=alphabet&gwbslanguage=en' => 'apihelp-query+wbsearch-example-4',
		];
	}

}
