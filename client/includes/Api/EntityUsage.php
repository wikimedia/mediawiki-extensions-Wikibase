<?php

namespace Wikibase\Client\Api;

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use ApiResult;
use Title;
use Wikibase\Client\WikibaseClient;

/**
 * API module to get the usage of entities.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani < ladsgroup@gmail.com >
 */
class ApiQueryEntityUsage extends ApiQueryBase {

	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'wbeu' );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$res = $this->doQuery();
		$count = 0;
		$prop = array_flip( (array)$params['prop'] );
		foreach ( $res as $row ) {
			if ( ++$count > $params['limit'] ) {
				// We've reached the one extra which shows that
				// there are additional pages to be had. Stop here...
				$this->setContinueEnumParameter(
					'continue',
					"{$row->eu_page_id}|{$row->eu_entity_id}|{$row->eu_aspect}"
				);
				break;
			}
			$entry = [ 'aspect' => $row->eu_aspect ];

			// Page info is not there, explictly putting those
			$result = $this->getResult();
			$data = $result->getResultData( [ 'query', 'pages', intval( $row->eu_page_id ) ] );
			if ( !$data ) {
				$pageData = ['pageid' => $row->eu_page_id ];
				$title = Title::newFromRow( $row );
				$this->addTitleInfo( $pageData, $title );
				$result = $this->getResult();
				$result->addValue( [ 'query', 'pages' ],
					intval( $row->eu_page_id ),
					$pageData
				);
			}

			if ( isset( $prop['url'] ) ) {
				$wikibaseClientSettings = WikibaseClient::getDefaultInstance()->getSettings();
				$repoUrl = $wikibaseClientSettings->getSetting( 'repoUrl' );
				$repoArticlePath = $wikibaseClientSettings->getSetting( 'repoArticlePath' );
				// ewww
				$repoArticlePath = str_replace( "$1", $row->eu_entity_id, $repoArticlePath );
				$entry['url'] = $repoUrl . $repoArticlePath;
			}
			ApiResult::setContentValue( $entry, 'entity', $row->eu_entity_id );
			$fit = $this->addPageSubItem( $row->eu_page_id, $entry );
			if ( !$fit ) {
				$this->setContinueEnumParameter(
					'continue',
					"{$row->eu_page_id}|{$row->eu_entity_id}|{$row->eu_aspect}"
				);
				break;
			}
		}
	}

	public function getCacheMode( $params ) {
		return 'public';
	}

	public function doQuery() {
		$params = $this->extractRequestParams();
		$pages = $this->getPageSet()->getGoodTitles();
		$pagesCount = count( $pages );
		if ( $pagesCount == 0 && !$params['entities'] ) {
			return;
		}

		$entities = explode( '|', $params['entities'] );

		$this->addFields( [
			'eu_page_id',
			'eu_entity_id',
			'eu_aspect'
		] );

		$this->addTables( 'wbc_entity_usage' );
		if ( $pagesCount !== 0 ) {
			$this->addWhereFld( 'eu_page_id', array_keys( $pages ) );
		} else {
			$this->addTables( 'page' );
			$this->addJoinConds( [ 'wbc_entity_usage' => [ 'INNER JOIN', 'eu_page_id=page_id'] ] );
			$this->addFields( [
				'page_title',
				'page_namespace',
			] );
		}

		if ( isset( $params['entities'] ) ) {
			$this->addWhereFld( 'eu_entity_id',  $entities );
		}

		if ( !is_null( $params['continue'] ) ) {
			$op = $params['dir'] == 'descending' ? '<' : '>';
			$db = $this->getDB();
			$continueParams = explode( '|', $params['continue'] );
			$pageContinue = intval( $continueParams[0] );
			$entityContinue = $db->addQuotes( $continueParams[1] );
			$aspectContinue = $db->addQuotes( $continueParams[2] );
			$this->addWhere(
				"eu_page_id $op $pageContinue OR " .
				"(eu_page_id = $pageContinue AND " .
				"(eu_entity_id $op $entityContinue OR " .
				"(eu_entity_id = $entityContinue AND " .
				"eu_aspect $op= $aspectContinue)))"
			);
		}

		$sort = ( $params['dir'] == 'descending' ? ' DESC' : '' );
		$orderBy = [ 'eu_page_id' . $sort, 'eu_entity_id' . $sort ];
		if ( isset( $params['aspect'] ) ) {
			$this->addWhereFld( 'eu_aspect', $params['aspect'] );
		} else {
			$orderBy[] = 'eu_aspect' . $sort;
		}
		$this->addOption( 'ORDER BY', $orderBy );

		$this->addOption( 'LIMIT', $params['limit'] + 1 );
		$res = $this->select( __METHOD__ );
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
			'aspect' => null,
			'entities' => null,
			'dir' => [
				ApiBase::PARAM_DFLT => 'ascending',
				ApiBase::PARAM_TYPE => [
					'ascending',
					'descending'
				]
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
				=> 'apihelp-query+entityusage-example-simple',
		];
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/API:Wikibase/EntityUsage';
	}

}
