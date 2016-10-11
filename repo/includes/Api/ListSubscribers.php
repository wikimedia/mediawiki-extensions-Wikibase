<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiQueryBase;
use ApiQuery;
use ApiResult;
use MediaWiki\MediaWikiServices;
use ResultWrapper;
use SiteLookup;
use stdClass;
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
class ListSubscribers extends ApiQueryBase {

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
		$params = $this->extractRequestParams();

		$idStrings = $params['entities'];

		$entities = [];
		foreach ( $idStrings as $idString ) {
			try {
				$entities[] = $this->idParser->parse( $idString );
			} catch ( EntityIdParsingException $e ) {
				$this->errorReporter->dieException( $e, 'param-invalid' );
			}
		}

		// Normalize entity ids
		$idStrings = array_map( function( $entity ) {
			return $entity->getSerialization();
		}, $entities );

		$res = $this->doQuery( $idStrings, $params );
		$props = isset( $params['prop'] ) ? $params['prop'] : [];
		$this->formatResult( $res, $params['limit'], $props );
	}

	/**
	 * @param string[] $idStrings
	 * @param array $params
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
		return $this->select( __METHOD__ );
	}

	/**
	 * @param string $continueParam
	 */
	private function addContinue( $continueParam ) {
		$db = $this->getDB();
		$continueParams = explode( '|', $continueParam );
		if ( count( $continueParams ) !== 2 ) {
			$this->errorReporter->dieError(
				'Unable to parse continue param',
				'param-invalid'
			);
		}
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

		$result->addIndexedTagName( [ 'query', $this->getModuleName() ], 'entity' );
		$result->addArrayType( [ 'query', $this->getModuleName() ], 'kvp', 'id' );

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
				$entry['subscribers'][] = $this->getSubscriber( $row, isset( $props['url'] ) );
			} else {
				$this->buildNewEntry( $entry, $row, isset( $props['url'] ) );
			}

		}

		if ( $entry ) {
			$fit = $this->formatPageData( $currentEntity, $entry, $result );
			if ( !$fit ) {
				$this->setContinueFromRow( $row );
			}
		}
	}

	/**
	 * @param array &$entry
	 * @param object $row
	 * @param bool $url
	 */
	private function buildNewEntry( array &$entry, $row, $url ) {
		$entry = [ 'subscribers' => [ $this->getSubscriber( $row, $url ) ] ];
		ApiResult::setIndexedTagName(
			$entry['subscribers'], 'subscriber'
		);
	}

	/**
	 * @param string $entityId
	 * @param array $entry
	 * @param ApiResult $result
	 *
	 * @return bool
	 */
	private function formatPageData( $entityId, array $entry, ApiResult $result ) {
		$fit = $result->addValue( [ 'query', $this->getModuleName() ], $entityId, $entry );
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
	 * @param stdClass $row
	 * @param bool $url
	 *
	 * @return string[]
	 */
	private function getSubscriber( stdClass $row, $url ) {
		$entry = [ '*' => $row->cs_subscriber_id ];
		if ( $url ) {
			$urlProp = $this->getEntityUsageUrl(
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
				self::PARAM_REQUIRED => true
			],
			'prop' => [
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
			"action=query&list=wbsubscribers&wblsentities=Q42&wblsprop=url" =>
				"apihelp-query+wbsubscribers-example-2",
		];
	}

	/**
	 * @param string $subscription
	 * @param string $entityIdString
	 * @return null|string
	 */
	private function getEntityUsageUrl( $subscription, $entityIdString ) {
		$site = $this->siteLookup->getSite( $subscription );
		if ( !$site ) {
			return null;
		}

		return $site->getPageUrl( 'Special:EntityUsage/' . $entityIdString );
	}

	/**
	 * @see ApiQueryBase::getCacheMode
	 * @param array $params
	 * @return string
	 */
	public function getCacheMode( $params ) {
		return 'public';
	}

}
