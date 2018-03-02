<?php

namespace Wikibase\Client\Api;

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use ApiResult;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Usage\EntityUsage;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * API module to get the usage of entities.
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani < ladsgroup@gmail.com >
 */
class ApiPropsEntityUsage extends ApiQueryBase {

	/**
	 * @var RepoLinker
	 */
	private $repoLinker = null;

	/**
	 * @param ApiQuery $query
	 * @param string $moduleName
	 * @param RepoLinker $repoLinker
	 */
	public function __construct( ApiQuery $query, $moduleName, RepoLinker $repoLinker ) {
		parent::__construct( $query, $moduleName, 'wbeu' );

		$this->repoLinker = $repoLinker;
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$res = $this->doQuery( $params );
		if ( !$res ) {
			return;
		}

		$prop = array_flip( (array)$params['prop'] );
		$this->formatResult( $res, $params['limit'], $prop );
	}

	/**
	 * @param IResultWrapper $res
	 * @param int $limit
	 * @param array $prop
	 */
	private function formatResult( IResultWrapper $res, $limit, array $prop ) {
		$currentPageId = null;
		$entry = [];
		$count = 0;

		foreach ( $res as $row ) {
			if ( ++$count > $limit ) {
				// We've reached the one extra which shows that
				// there are additional pages to be had. Stop here...
				$this->setContinueFromRow( $row );
				break;
			}

			if ( isset( $currentPageId ) && $row->eu_page_id !== $currentPageId ) {
				// Flush out everything we built
				$fit = $this->addPageSubItems( $currentPageId, $entry );
				if ( !$fit ) {
					$this->setContinueFromRow( $row );
					break;
				}
				$entry = [];
			}
			$currentPageId = $row->eu_page_id;
			if ( array_key_exists( $row->eu_entity_id, $entry ) ) {
				$entry[$row->eu_entity_id]['aspects'][] = $row->eu_aspect;
			} else {
				$entry[$row->eu_entity_id] = [ 'aspects' => [ $row->eu_aspect ] ];
				if ( isset( $prop['url'] ) ) {
					$entry[$row->eu_entity_id]['url'] = $this->repoLinker->getPageUrl(
						'Special:EntityPage/' . $row->eu_entity_id );
				}
				ApiResult::setIndexedTagName(
					$entry[$row->eu_entity_id]['aspects'], 'aspect'
				);
				ApiResult::setArrayType( $entry, 'kvp', 'id' );
			}

		}

		if ( $entry ) { // Sanity
			// Flush out remaining ones
			$this->addPageSubItems( $currentPageId, $entry );
		}
	}

	/**
	 * @param object $row
	 */
	private function setContinueFromRow( $row ) {
		$this->setContinueEnumParameter(
			'continue',
			"{$row->eu_page_id}|{$row->eu_entity_id}|{$row->eu_aspect}"
		);
	}

	/**
	 * @see ApiQueryBase::getCacheMode
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	public function getCacheMode( $params ) {
		return 'public';
	}

	/**
	 * @param array $params
	 *
	 * @return IResultWrapper|null
	 */
	public function doQuery( array $params ) {
		$pages = $this->getPageSet()->getGoodTitles();
		if ( !$pages ) {
			return null;
		}

		$this->addFields( [
			'eu_page_id',
			'eu_entity_id',
			'eu_aspect'
		] );

		$this->addTables( 'wbc_entity_usage' );
		$this->addWhereFld( 'eu_page_id', array_keys( $pages ) );

		if ( isset( $params['entities'] ) ) {
			$this->addWhereFld( 'eu_entity_id', $params['entities'] );
		}

		if ( !is_null( $params['continue'] ) ) {
			$db = $this->getDB();
			$continueParams = explode( '|', $params['continue'] );
			$pageContinue = intval( $continueParams[0] );
			$entityContinue = $db->addQuotes( $continueParams[1] );
			$aspectContinue = $db->addQuotes( $continueParams[2] );
			// Filtering out results that have been shown already and
			// starting the query from where it ended.
			$this->addWhere(
				"eu_page_id > $pageContinue OR " .
				"(eu_page_id = $pageContinue AND " .
				"(eu_entity_id > $entityContinue OR " .
				"(eu_entity_id = $entityContinue AND " .
				"eu_aspect >= $aspectContinue)))"
			);
		}

		$orderBy = [ 'eu_page_id' , 'eu_entity_id' ];
		if ( isset( $params['aspect'] ) ) {
			$this->addWhereFld( 'eu_aspect', $params['aspect'] );
		} else {
			$orderBy[] = 'eu_aspect';
		}
		$this->addOption( 'ORDER BY', $orderBy );

		$this->addOption( 'LIMIT', $params['limit'] + 1 );
		$res = $this->select( __METHOD__ );
		return $res;
	}

	public function getAllowedParams() {
		return [
			'prop' => [
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => [
					'url',
				],
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'aspect' => [
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => [
					EntityUsage::SITELINK_USAGE,
					EntityUsage::LABEL_USAGE,
					EntityUsage::DESCRIPTION_USAGE,
					EntityUsage::TITLE_USAGE,
					EntityUsage::STATEMENT_USAGE,
					EntityUsage::ALL_USAGE,
					EntityUsage::OTHER_USAGE,
				]
			],
			'entities' => [
				ApiBase::PARAM_ISMULTI => true,
			],
			'limit' => [
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			],
			'continue' => [
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			],
		];
	}

	protected function getExamplesMessages() {
		return [
			'action=query&prop=wbentityusage&titles=Main%20Page'
				=> 'apihelp-query+wbentityusage-example-simple',
		];
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Wikibase/API#wbentityusage';
	}

}
