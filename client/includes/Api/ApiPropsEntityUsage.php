<?php

declare( strict_types=1 );

namespace Wikibase\Client\Api;

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use ApiResult;
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
class ApiPropsEntityUsage extends ApiQueryBase {

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	public function __construct( ApiQuery $query, string $moduleName, RepoLinker $repoLinker ) {
		parent::__construct( $query, $moduleName, 'wbeu' );

		$this->repoLinker = $repoLinker;
	}

	public function execute(): void {
		$params = $this->extractRequestParams();
		$res = $this->doQuery( $params );
		if ( !$res ) {
			return;
		}

		$prop = array_flip( (array)$params['prop'] );
		$this->formatResult( $res, $params['limit'], $prop );
	}

	private function formatResult( IResultWrapper $res, int $limit, array $prop ): void {
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

	public function doQuery( array $params ): ?IResultWrapper {
		$pages = $this->getPageSet()->getGoodTitles();
		if ( !$pages ) {
			return null;
		}

		$this->addFields( [
			'eu_page_id',
			'eu_entity_id',
			'eu_aspect',
		] );

		$this->addTables( 'wbc_entity_usage' );
		$this->addWhereFld( 'eu_page_id', array_keys( $pages ) );

		if ( isset( $params['entities'] ) ) {
			$this->addWhereFld( 'eu_entity_id', $params['entities'] );
		}

		if ( $params['continue'] !== null ) {
			$db = $this->getDB();
			[ $pageContinue, $entityContinue, $aspectContinue ] = explode( '|', $params['continue'], 3 );
			// Filtering out results that have been shown already and
			// starting the query from where it ended.
			$this->addWhere( $db->buildComparison( '>=', [
				'eu_page_id' => (int)$pageContinue,
				'eu_entity_id' => $entityContinue,
				'eu_aspect' => $aspectContinue,
			] ) );
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
				// These messages are also reused for the same values in the ApiListEntityUsageModule
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'entities' => [
				ParamValidator::PARAM_ISMULTI => true,
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
			'action=query&prop=wbentityusage&titles=Main%20Page'
				=> 'apihelp-query+wbentityusage-example-simple',
		];
	}

	public function getHelpUrls(): string {
		return 'https://www.mediawiki.org/wiki/Special:MyLanguage/Wikibase/API#wbentityusage';
	}

}
