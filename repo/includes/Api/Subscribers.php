<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiQueryBase;
use ApiQuery;
use ApiResult;
use MediaWiki\MediaWikiServices;
use ResultWrapper;
use SiteLookup;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for getting wikis subscribed to changes to given entities.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class Subscribers extends ApiQueryBase {

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @param ApiQuery $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiQuery $mainModule, $moduleName, $modulePrefix = 'wbls' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );
		$mediaWikiServices = MediaWikiServices::getInstance();
		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->idParser = $wikibaseRepo->getEntityIdParser();
		$this->siteLookup = $mediaWikiServices->getSiteLookup();
	}

	public function execute() {
		$this->getMain()->setCacheMode( 'public' );

		$params = $this->extractRequestParams();

		$idStrings = $params['entities'];

		foreach ( $idStrings as $idString ) {
			try {
				$this->idParser->parse( $idString );
			} catch ( EntityIdParsingException $e ) {
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
	 * @return ResultWrapper
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
			"cs_entity_id > $entityContinueSql OR " .
			"(cs_entity_id = $entityContinueSql AND " .
			"cs_subscriber_id >= $wikiContinueSql)"
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
		$props = array_flip( $props );

		foreach ( $res as $row ) {
			if ( ++$count > $limit ) {
				// We've reached the one extra which shows that
				// there are additional pages to be had. Stop here...
				$this->setContinueFromRow( $row );
				break;
			}

			if ( $currentEntity !== null && $row->cs_entity_id !== $currentEntity ) {
				// Let's add the data and check if it needs continuation
				$fit = $this->formatPageData( $currentEntity, $entry, $result );
				if ( !$fit ) {
					$this->setContinueFromRow( $row );
					break;
				}
				$entry = [];
			}

			$currentEntity = $row->cs_entity_id;
			if ( $entry ) {
				$entry['subscribers'][] = $this->addSubscriber( $row, isset( $props['url'] ) );
			} else {
				$this->buildEntry( $entry, $row, isset( $props['url'] ) );
			}

		}
		if ( $entry ) {
			$this->formatPageData( $currentEntity, $entry, $result );
		}
	}

	/**
	 * @param array $entry
	 * @param object $row
	 * @param bool $url
	 */
	private function buildEntry( &$entry, $row, $url ) {
		$entry = [ 'subscribers' => [ $this->addSubscriber( $row, $url ) ] ];
		ApiResult::setIndexedTagName(
			$entry['subscribers'], 'subscriber'
		);
		ApiResult::setArrayType( $entry, 'kvp', 'id' );
	}

	/**
	 * @param string $entityId
	 * @param array $entry
	 * @param ApiResult $result
	 *
	 * @return bool
	 */
	private function formatPageData( $entityId, array $entry, ApiResult $result ) {
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
	 * @param \stdClass $row
	 * @param bool $url
	 *
	 * @return string[]
	 */
	private function addSubscriber( \stdClass $row, $url ) {
		$entry = [ '*' => $row->cs_subscriber_id ];
		if ( $url ) {
			$urlProp = $this->getSubscriberUrl(
				$row->cs_subscriber_id,
				$row->cs_entity_id
			);
			if ( $urlProp ) {
				$entry['url'] = $urlProp;
			}
		}
		return $entry;
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
			],
			'continue' => [
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			],
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return [
			"action=query&list=wbsubscribers&wblsentities=Q42" =>
				"apihelp-query+wbsubscribers-example-1",
			"action=query&list=wbsubscribers&wblsentities=Q42&wblsprops=url" =>
				"apihelp-query+wbsubscribers-example-2",
		];
	}

	/**
	 * @param string $subscription
	 * @param string $entity
	 * @return null|string
	 */
	private function getSubscriberUrl( $subscription, $entity ) {
		$site = $this->siteLookup->getSite( $subscription );
		if ( !$site ) {
			return null;
		}

		return $site->getPageUrl( 'Special:EntityUsage/' . $entity );
	}

}
