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
		// This module temporarily supports the "wbeu" and "wbleu" prefixes (T196962)
		parent::__construct( $query, $moduleName, '' );

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
		$rawParams = $this->extractRequestParams();
		$this->validateParams( $rawParams );
		$isLegacyRequest = $this->isLegacyRequest( $rawParams );
		$params = $this->canonicalizeParams( $rawParams, $isLegacyRequest );

		$res = $this->doQuery( $params, $resultPageSet );
		if ( !$res ) {
			return;
		}

		$prop = array_flip( (array)$params['prop'] );
		$this->formatResult( $res, $params['limit'], $prop, $resultPageSet, $isLegacyRequest );
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
		?ApiPageSet $resultPageSet,
		bool $isLegacyRequest
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
				$fit = $this->formatPageData( $previousRow, intval( $previousRow->eu_page_id ), $entry, $result, $isLegacyRequest );
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
			$this->formatPageData( $previousRow, intval( $previousRow->eu_page_id ), $entry, $result, $isLegacyRequest );
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

	/**
	 * @return bool True the result fits into the output, false otherwise
	 */
	private function formatPageData(
		object $row,
		int $pageId,
		array $entry,
		ApiResult $result,
		bool $isLegacyRequest
	): bool {
		if ( $isLegacyRequest ) {
			return $this->formatPageDataLegacy( $row, $pageId, $entry, $result );
		}
		$pageData = $this->addPageData( $row );
		$result->addIndexedTagName( [ 'query', 'entityusage' ], 'page' );

		$value = array_merge( $pageData, [ $this->getModuleName() => $entry ] );
		ApiResult::setIndexedTagName( $value[$this->getModuleName()], 'wbleu' );
		return $result->addValue( [ 'query', 'entityusage' ], null, $value );
	}

	/**
	 * Legacy output version of formatPageData.
	 *
	 * @return bool True the result fits into the output, false otherwise
	 */
	private function formatPageDataLegacy( object $row, int $pageId, array $entry, ApiResult $result ): bool {
		$pageData = $this->addPageData( $row );
		$result->addValue( [ 'query', 'pages' ], $pageId, $pageData );
		$fit = $this->legacyAddPageSubItems( $pageId, $entry );
		return $fit;
	}

	/**
	 * Copy of ApiQueryBase::addPageSubItems with hard coded legacy module prefix.
	 *
	 * Add a sub-element under the page element with the given page ID
	 * @param int $pageId
	 * @param array $data Data array à la ApiResult
	 * @return bool Whether the element fit in the result
	 */
	private function legacyAddPageSubItems( $pageId, $data ) {
		$result = $this->getResult();
		ApiResult::setIndexedTagName( $data, 'wbeu' );

		return $result->addValue( [ 'query', 'pages', (int)$pageId ],
			$this->getModuleName(),
			$data );
	}

	private function setContinueFromRow( object $row ): void {
		$this->setContinueEnumParameter(
			'continue',
			"{$row->eu_page_id}|{$row->eu_entity_id}|{$row->eu_aspect}"
		);
	}

	private function isLegacyRequest( array $params ): bool {
		$params = $this->removeDefaultLimitFromParams( $params );

		$isLegacyRequest = false;
		foreach ( $params as $key => $value ) {
			if ( $value === null ) {
				continue;
			}
			if ( strpos( $key, 'wbeu' ) === 0 ) {
				$isLegacyRequest = true;
			} elseif ( $isLegacyRequest ) {
				$this->dieWithError( 'wikibase-client-wblistentityusage-param-format-mix' );
			}
		}

		return $isLegacyRequest;
	}

	private function validateParams( array $params ): void {
		$this->requireOnlyOneParameter( $params, 'wbeuentities', 'wbleuentities' );
	}

	/**
	 * Removes the wb(l)eulimit parameters for the given input params array, if they aren't user specified (but defaults).
	 */
	private function removeDefaultLimitFromParams( array $params ): array {
		$request = $this->getRequest();
		$defaultLimit = $this->getAllowedParamsUnprefixed()['limit'][ParamValidator::PARAM_DEFAULT];
		if ( $params['wbeulimit'] === $defaultLimit && !$request->getBool( 'wbeulimit' ) ) {
			unset( $params['wbeulimit'] );
		}
		if ( $params['wbleulimit'] === $defaultLimit && !$request->getBool( 'wbleulimit' ) ) {
			unset( $params['wbleulimit'] );
		}

		return $params;
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

	private function canonicalizeParams( array $params, bool $isLegacyRequest ): array {
		$prefixLength = strlen( $isLegacyRequest ? 'wbeu' : 'wbleu' );
		$canonicalParams = [];
		foreach ( $params as $key => $value ) {
			if ( $isLegacyRequest !== ( strpos( $key, 'wbeu' ) === 0 ) ) {
				// Only use legacy params on a legacy request
				continue;
			}
			$canonicalKey = substr( $key, $prefixLength );
			$canonicalParams[$canonicalKey] = $value;
		}

		return $canonicalParams;
	}

	private function getAllowedParamsUnprefixed(): array {
		return [
			'prop' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'url',
				],
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
				ApiBase::PARAM_HELP_MSG => 'apihelp-query+wblistentityusage-param-prop',
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
				ApiBase::PARAM_HELP_MSG => 'apihelp-query+wblistentityusage-param-aspect',
			],
			'entities' => [
				ParamValidator::PARAM_ISMULTI => true,
				// Enforced by self::validateParams
				//ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-query+wblistentityusage-param-entities',
			],
			'limit' => [
				ParamValidator::PARAM_DEFAULT => 10,
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => ApiBase::LIMIT_BIG1,
				IntegerDef::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
				ApiBase::PARAM_HELP_MSG => 'apihelp-query+wblistentityusage-param-limit',
			],
			'continue' => [
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			],
		];
	}

	public function getAllowedParams(): array {
		$prefixedParams = [];
		foreach ( $this->getAllowedParamsUnprefixed() as $name => $param ) {
			$newName = 'wbleu' . $name;
			$prefixedParams['wbeu' . $name] = array_merge(
				[
					ParamValidator::PARAM_DEPRECATED => true,
					ApiBase::PARAM_HELP_MSG_APPEND => [ [ 'apihelp-query+wblistentityusage-format-migration', $newName ] ],
				],
				$param
			);
			$prefixedParams[$newName] = $param;
		}

		return $prefixedParams;
	}

	protected function getExamplesMessages(): array {
		return [
			'action=query&list=wblistentityusage&wbleuentities=Q2'
				=> 'apihelp-query+wblistentityusage-example-simple',
			'action=query&list=wblistentityusage&wbleuentities=Q2&wbleuprop=url'
				=> 'apihelp-query+wblistentityusage-example-url',
			'action=query&list=wblistentityusage&wbleuentities=Q2&wbleuaspect=S|O'
				=> 'apihelp-query+wblistentityusage-example-aspect',
		];
	}

	public function getHelpUrls(): string {
		return 'https://www.mediawiki.org/wiki/Special:MyLanguage/Wikibase/API';
	}

}
