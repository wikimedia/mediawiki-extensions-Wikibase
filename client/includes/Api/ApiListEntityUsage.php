<?php

declare( strict_types=1 );

namespace Wikibase\Client\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiPageSet;
use MediaWiki\Api\ApiQuery;
use MediaWiki\Api\ApiQueryGeneratorBase;
use MediaWiki\Api\ApiResult;
use MediaWiki\Title\Title;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\Sql\EntityUsageDomainDb;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * API module to get the usage of entities.
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani < ladsgroup@gmail.com >
 */
class ApiListEntityUsage extends ApiQueryGeneratorBase {

	use ApiQueryWithContinueTrait;

	private RepoLinker $repoLinker;

	public function __construct(
		ApiQuery $query,
		string $moduleName,
		RepoLinker $repoLinker
	) {
		parent::__construct( $query, $moduleName, 'wbleu' );

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

	public function run( ?ApiPageSet $resultPageSet = null ): void {
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
				$fit = $this->formatPageData( $previousRow, $entry, $result );
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
			$this->formatPageData( $previousRow, $entry, $result );
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
		array $entry,
		ApiResult $result
	): bool {
		$pageData = $this->addPageData( $row );
		$result->addIndexedTagName( [ 'query', 'entityusage' ], 'page' );

		$value = array_merge( $pageData, [ $this->getModuleName() => $entry ] );
		ApiResult::setIndexedTagName( $value[$this->getModuleName()], 'wbleu' );
		return $result->addValue( [ 'query', 'entityusage' ], null, $value );
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

	public function doQuery( array $params, ?ApiPageSet $resultPageSet ): ?IResultWrapper {
		if ( !$params['entities'] ) {
			return null;
		}

		$this->addFields( [
			'eu_page_id',
			'eu_entity_id',
			'eu_aspect',
		] );

		$this->setVirtualDomain( EntityUsageDomainDb::VIRTUAL_DOMAIN_ID );
		$this->addTables( 'wbc_entity_usage' );
		$this->addWhereFld( 'eu_entity_id', $params['entities'] );

		if ( $params['continue'] !== null ) {
			$this->addContinue( $params['continue'], $this->getDB() );
		}

		$orderBy = [ 'eu_page_id', 'eu_entity_id' ];

		if ( isset( $params['aspect'] ) ) {
			$this->addWhereFld( 'eu_aspect', $params['aspect'] );
		} else {
			$orderBy[] = 'eu_aspect';
		}

		$this->addOption( 'ORDER BY', $orderBy );
		$this->addOption( 'LIMIT', $params['limit'] + 1 );

		$euRes = $this->select( __METHOD__ );
		$this->resetVirtualDomain();

		$euResArr = iterator_to_array( $euRes ); # we convert to be able to loop over more than once. a db cursor wouldn't allow that
		$pageIds = [];
		foreach ( $euResArr as $eu_row ) {
			if ( isset( $eu_row->eu_page_id ) ) {
				$pageIds[] = $eu_row->eu_page_id;
			}
		}

		if ( !$pageIds ) {
			return new FakeResultWrapper( $euResArr );
		}

		$fields = $resultPageSet === null ? [ 'page_id', 'page_title', 'page_namespace' ] :
			$resultPageSet->getPageTableFields();

		$pageRes = $this->getDB()->newSelectQueryBuilder()
			->select( $fields )
			->from( 'page' )
			->where( [ 'page_id' => array_unique( $pageIds ) ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$joinedRes = $this->joinedResults( $euResArr, $pageRes, $fields );
		return new FakeResultWrapper( $joinedRes );
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
					// Please do not add new usages of "all'("X") aspects. This places too much pressure
					// on client recentchanges table and generates unnecessary reparses.
					// The method only remains to monitor redirect pages
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

	/**
	 * Merge page table fields into entity usage rows.
	 *
	 * @param \stdClass[] $euData
	 * @param IResultWrapper $pageData
	 * @param string[] $fieldsToCopy
	 */
	private function joinedResults( array $euData, IResultWrapper $pageData, array $fieldsToCopy ): array {
		$pagesGroupedByPageId = [];
		$joinedQueryRes = array_map( fn( object $row ): object => clone $row, $euData );

		foreach ( $pageData as $row ) {
			$pagesGroupedByPageId[(int)$row->page_id] = $row;
		}

		foreach ( $joinedQueryRes as $euRow ) {
			$pageId = (int)$euRow->eu_page_id;
			foreach ( $fieldsToCopy as $field ) {
				$euRow->$field = null;
			}

			if ( isset( $pagesGroupedByPageId[$pageId] ) ) {
				$pageRow = $pagesGroupedByPageId[$pageId];
				foreach ( $fieldsToCopy as $field ) {
					$euRow->$field = $pageRow->$field;
				}
			}
		}
		return $joinedQueryRes;
	}

}
