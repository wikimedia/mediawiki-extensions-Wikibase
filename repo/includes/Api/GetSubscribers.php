<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiQueryBase;
use ApiQuery;
use ApiResult;
use ResultWrapper;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for getting subscribers to given entities.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class GetSubscribers extends ApiQueryBase {

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @param ApiQuery $queryModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiQuery $queryModule, $moduleName, $modulePrefix = 'wbgs' ) {
		parent::__construct( $queryModule, $moduleName, $modulePrefix );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );
		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->idParser = $wikibaseRepo->getEntityIdParser();
	}

	public function execute() {
		$this->getMain()->setCacheMode( 'public' );

		$params = $this->extractRequestParams();

		$idStrings = $params['entities'];

		foreach ( $idStrings as $idString ) {
			try {
				$this->idParser->parse( $idString );
			}
			catch ( EntityIdParsingException $e ) {
				$this->errorReporter->dieException( $e, 'param-invalid' );
			}
		}

		$res = $this->doQuery( $idStrings, $params );
		$props = isset( $params['props'] ) ? $params['props'] : [];
		$this->formatResult( $res, $params['limit'], $props );
	}

	/**
	 * @param string[] $idStrings
	 * @param string[] $params
	 *
	 * @return ResultWrapper|null
	 */
	public function doQuery( array $idStrings, array $params ) {

		$this->addFields( [
			'cs_entity_id',
			'cs_subscriber_id',
		] );

		$this->addTables( 'wb_changes_subscription' );

		$this->addWhereFld( 'cs_entity_id', $idStrings );

		if ( !is_null( $params['continue'] ) ) {
			$this->addContinue( $params['continue'] );
		}

		$orderBy = [ 'cs_entity_id', 'cs_subscriber_id' ];
		$this->addOption( 'ORDER BY', $orderBy );

		$this->addOption( 'LIMIT', $params['limit'] + 1 );
		$res = $this->select( __METHOD__ );
		return $res;
	}

	/**
	 * @param string $continueParam
	 *
	 */
	private function addContinue( $continueParam ) {
		$db = $this->getDB();
		$continueParams = explode( '|', $continueParam );
		$entityContinueSql = $db->addQuotes( $continueParams[0] );
		$wikiContinueSql = $db->addQuotes( $continueParams[1] );
		// Filtering out results that have been shown already and
		// starting the query from where it ended.
		$this->addWhere(
			"eu_page_id > $pageContinueSql OR " .
			"(eu_page_id = $pageContinueSql AND " .
			"(eu_entity_id > $entityContinueSql OR " .
			"(eu_entity_id = $entityContinueSql AND " .
			"eu_aspect >= $aspectContinueSql)))"
		);
	}

	/**
	 * @param ResultWrapper $res
	 * @param int $limit
	 * @param array $props
	 */
	private function formatResult( ResultWrapper $res, $limit, array $props ) {
		$currentEntity = null;
		$entry = [];
		$count = 0;
		$result = $this->getResult();
		$prRow = null;

		foreach ( $res as $row ) {
			if ( ++$count > $limit ) {
				// We've reached the one extra which shows that
				// there are additional pages to be had. Stop here...
				$this->setContinueFromRow( $row );
				break;
			}

			if ( $currentEntity !== null && $row->cs_entity_id !== $currentEntity ) {
				// Let's add the data and check if it needs continuation
				$fit = $this->formatPageData( $prRow, $currentEntity, $entry, $result );
				if ( !$fit ) {
					$this->setContinueFromRow( $row );
					break;
				}
				$entry = [];
			}

			$currentEntity = $row->cs_entity_id;
			$prRow = $row;

			if ( array_key_exists( $row->cs_entity_id, $entry ) ) {
				$entry['subscribers'][] = $row->cs_subscriber_id;
			} else {
				$this->buildEntry( $entry, $row, isset( $props['url'] ) );
			}

		}
		if ( $entry ) {
			$this->formatPageData( $row, $currentEntity, $entry, $result );
		}
	}

	/**
	 * @param array $entry
	 * @param object $row
	 * @param bool $url
	 */
	private function buildEntry( &$entry, $row, $url ) {
		$entry = [ 'subscribers' => [ $row->cs_subscriber_id ] ];
		if ( $url ) {
			$entry['subscribers'][$row->cs_subscriber_id]['url'] = $this->repoLinker->getPageUrl(
				'Special:EntityData/' . $row->cs_entity_id );
		}
		ApiResult::setIndexedTagName(
			$entry['subscribers'], 'subscriber'
		);
		ApiResult::setArrayType( $entry, 'kvp', 'id' );
	}

	/**
	 * @param \stdClass $row
	 * @param string $entityId
	 * @param array $entry
	 * @param ApiResult $result
	 *
	 * @return bool
	 */
	private function formatPageData( \stdClass $row, $entityId, array $entry, ApiResult $result ) {
		$fit = $result->addValue( [ 'query', 'subscribers' ], $entityId, $entry );
		return $fit;
	}

	/**
	 * @param object $row
	 */
	private function setContinueFromRow( $row ) {
		$this->setContinueEnumParameter(
			'continue',
			"{$row->cs_entity_id}|{$row->cs_subscriber_id}"
		);
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return [
			'entities' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_ISMULTI => true,
			],
			'props' => [
				self::PARAM_TYPE => [
					'url',
				],
				self::PARAM_ISMULTI => true,
			],
			'limit' => [
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			]
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return [
			"action=wbgetclaims&entity=Q42" =>
				"apihelp-wbgetclaims-example-1",
			"action=wbgetclaims&entity=Q42&property=P2" =>
				"apihelp-wbgetclaims-example-2",
		];
	}

}
