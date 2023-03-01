<?php

declare( strict_types=1 );

namespace Wikibase\Client\Api;

use ApiBase;
use ApiPageSet;
use ApiQuery;
use ApiQueryGeneratorBase;
use ApiResult;
use Title;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Usage\EntityUsage;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * API module to get the usage of entities.
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani < ladsgroup@gmail.com >
 */
class ApiListEntityUsage extends ApiQueryGeneratorBase {

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	public function __construct( ApiQuery $query, string $moduleName, RepoLinker $repoLinker ) {
		parent::__construct( $query, $moduleName, 'wbeu' );

		$this->repoLinker = $repoLinker;
	}

	/**
	 * @see ApiQueryGeneratorBase::executeGenerator
	 *
	 * @param ApiPageSet $resultPageSet
	 */
	public function executeGenerator( $resultPageSet ): void {
		$this->run( $resultPageSet );
	}

	public function execute(): void {
		$this->run();
	}

	public function run( ApiPageSet $resultPageSet = null ): void {
		$params = $this->extractRequestParams();
		$res = $this->doQuery( $params, $resultPageSet );
		if ( !$res ) {
			return;
		}

		$prop = array_flip( (array)$params['prop'] );
		$this->formatResult( $res, $params['limit'], $prop, $resultPageSet );
	}

	private function addPageData( object $row ): array {
		$pageData = [];
		$title = Title::makeTitle( $row->page_namespace, $row->page_title );
		self::addTitleInfo( $pageData, $title );
		$pageData['pageid'] = (int)$row->page_id;
		return $pageData;
	}

	private function formatResult(
		IResultWrapper $res,
		int $limit,
		array $prop,
		?ApiPageSet $resultPageSet
	): void {
		$entry = [];
		$count = 0;
		$result = $this->getResult();
		$previousRow = null;

		foreach ( $res as $row ) {
			if ( ++$count > $limit ) {
				// We've reached the one extra which shows that
				// there are additional pages to be had. Stop here...
				$this->setContinueFromRow( $row );
				break;
			}

			if ( $resultPageSet !== null ) {
				$resultPageSet->processDbRow( $row );
				continue;
			}

			if ( $previousRow !== null && $row->eu_page_id !== $previousRow->eu_page_id ) {
				// finish previous entry: Let's add the data and check if it needs continuation
				$fit = $this->formatPageData( $previousRow, intval( $previousRow->eu_page_id ), $entry, $result );
				if ( !$fit ) {
					$this->setContinueFromRow( $row );
					break;
				}
				$entry = [];
			}

			$previousRow = $row;

			if ( array_key_exists( $row->eu_entity_id, $entry ) ) {
				$entry[$row->eu_entity_id]['aspects'][] = $row->eu_aspect;
			} else {
				$this->buildEntry( $entry, $row, isset( $prop['url'] ) );
			}

		}
		if ( $entry ) {
			$this->formatPageData( $previousRow, intval( $previousRow->eu_page_id ), $entry, $result );
		}
	}

	private function buildEntry( array &$entry, object $row, bool $url ): void {
		$entry[$row->eu_entity_id] = [ 'aspects' => [ $row->eu_aspect ] ];
		if ( $url ) {
			$entry[$row->eu_entity_id]['url'] = $this->repoLinker->getPageUrl(
				'Special:EntityPage/' . $row->eu_entity_id );
		}
		ApiResult::setIndexedTagName(
			$entry[$row->eu_entity_id]['aspects'], 'aspect'
		);
		ApiResult::setArrayType( $entry, 'kvp', 'id' );
	}

	private function formatPageData( object $row, int $pageId, array $entry, object $result ): bool {
		$pageData = $this->addPageData( $row );
		$result->addValue( [ 'query', 'pages' ], $pageId, $pageData );
		$fit = $this->addPageSubItems( $pageId, $entry );
		return $fit;
	}

	private function setContinueFromRow( object $row ): void {
		$this->setContinueEnumParameter(
			'continue',
			"{$row->eu_page_id}|{$row->eu_entity_id}|{$row->eu_aspect}"
		);
	}

	/**
	 * @see ApiQueryBase::getCacheMode
	 *
	 * @param array $params
	 */
	public function getCacheMode( $params ): string {
		return 'public';
	}

	public function doQuery( array $params, ApiPageSet $resultPageSet = null ): ?IResultWrapper {
		if ( !$params['entities'] ) {
			return null;
		}

		$this->addFields( [
			'eu_page_id',
			'eu_entity_id',
			'eu_aspect',
		] );

		$this->addTables( 'wbc_entity_usage' );

		if ( $resultPageSet === null ) {
			$this->addFields( [ 'page_id', 'page_title', 'page_namespace' ] );
		} else {
			$this->addFields( $resultPageSet->getPageTableFields() );
		}

		$this->addTables( [ 'page' ] );
		$this->addJoinConds( [ 'wbc_entity_usage' => [ 'LEFT JOIN', 'eu_page_id=page_id' ] ] );

		$this->addWhereFld( 'eu_entity_id', $params['entities'] );

		if ( $params['continue'] !== null ) {
			$this->addContinue( $params['continue'] );
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

	private function addContinue( string $continueParam ): void {
		$db = $this->getDB();
		[ $pageContinue, $entityContinue, $aspectContinue ] = explode( '|', $continueParam, 3 );
		// Filtering out results that have been shown already and
		// starting the query from where it ended.
		$this->addWhere( $db->buildComparison( '>=', [
			'eu_page_id' => (int)$pageContinue,
			'eu_entity_id' => $entityContinue,
			'eu_aspect' => $aspectContinue,
		] ) );
	}

	public function getAllowedParams(): array {
		return [
			'prop' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'url',
				],
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'aspect' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					EntityUsage::SITELINK_USAGE,
					EntityUsage::LABEL_USAGE,
					EntityUsage::DESCRIPTION_USAGE,
					EntityUsage::TITLE_USAGE,
					EntityUsage::STATEMENT_USAGE,
					EntityUsage::ALL_USAGE,
					EntityUsage::OTHER_USAGE,
				],
				// This reuses the message from the ApiPropsEntityUsage module to avoid needless duplication
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [
					EntityUsage::SITELINK_USAGE => 'apihelp-query+wbentityusage-paramvalue-aspect-S',
					EntityUsage::LABEL_USAGE => 'apihelp-query+wbentityusage-paramvalue-aspect-L',
					EntityUsage::DESCRIPTION_USAGE => 'apihelp-query+wbentityusage-paramvalue-aspect-D',
					EntityUsage::TITLE_USAGE => 'apihelp-query+wbentityusage-paramvalue-aspect-T',
					EntityUsage::STATEMENT_USAGE => 'apihelp-query+wbentityusage-paramvalue-aspect-C',
					EntityUsage::ALL_USAGE => 'apihelp-query+wbentityusage-paramvalue-aspect-X',
					EntityUsage::OTHER_USAGE => 'apihelp-query+wbentityusage-paramvalue-aspect-O',
				],
			],
			'entities' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_REQUIRED => true,
			],
			'limit' => [
				ParamValidator::PARAM_DEFAULT => 10,
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => ApiBase::LIMIT_BIG1,
				IntegerDef::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
			],
			'continue' => [
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			],
		];
	}

	protected function getExamplesMessages(): array {
		return [
			'action=query&list=wblistentityusage&wbeuentities=Q2'
				=> 'apihelp-query+wblistentityusage-example-simple',
			'action=query&list=wblistentityusage&wbeuentities=Q2&wbeuprop=url'
				=> 'apihelp-query+wblistentityusage-example-url',
			'action=query&list=wblistentityusage&wbeuentities=Q2&wbeuaspect=S|O'
				=> 'apihelp-query+wblistentityusage-example-aspect',
		];
	}

	public function getHelpUrls(): string {
		return 'https://www.mediawiki.org/wiki/Special:MyLanguage/Wikibase/API';
	}

}
