<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Content\ContentHandler;
use MediaWiki\Content\Hook\SearchDataForIndexHook;
use MediaWiki\Hook\MaintenanceShellStartHook;
use MediaWiki\Hook\UnitTestsListHook;
use MediaWiki\Output\Hook\BeforePageDisplayHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\WikiPage;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Search\Hook\SearchIndexFieldsHook;
use MediaWiki\Skin\Skin;
use MediaWiki\SpecialPage\Hook\WgQueryPagesHook;
use MediaWiki\SpecialPage\SpecialPage;
use SearchEngine;
use SearchIndexField;
use Wikibase\Client\Specials\SpecialUnconnectedPages;

/**
 * Handler for “trivial” hooks:
 * ones that don’t need any services injected,
 * and whose code is fairly brief.
 *
 * @license GPL-2.0-or-later
 */
class TrivialHookHandler implements
	BeforePageDisplayHook,
	SearchDataForIndexHook,
	SearchIndexFieldsHook,
	UnitTestsListHook,
	WgQueryPagesHook,
	MaintenanceShellStartHook
{

	/**
	 * Add the connected item prefixed id as a JS config variable, for gadgets etc.
	 * @param OutputPage $outputPage
	 * @param Skin $skin
	 */
	public function onBeforePageDisplay( $outputPage, $skin ): void {
		$prefixedId = $outputPage->getProperty( 'wikibase_item' );

		if ( $prefixedId !== null ) {
			$outputPage->addJsConfigVars( 'wgWikibaseItemId', $prefixedId );
		}
	}

	/**
	 * Add tracking to the summary Item and Property links
	 * and the changelog Item links in class mw-changeslist.
	 * We would only be measuring it on Recentchanges and
	 * Watchlist special pages.
	 *
	 * Temporary: We will remove it by Sept. 2025
	 * Bug: T392469
	 *
	 * @param SpecialPage $special
	 * @param string|null $subPage
	 */
	public function onSpecialPageBeforeExecute( $special, $subPage ): void {
		if ( $special->getName() !== 'Recentchanges' && $special->getName() !== 'Watchlist' ) {
			return;
		}

		$outputPage = $special->getOutput();
		$outputPage->addModules( 'wikibase.summary.tracking' );
	}

	/**
	 * Put wikibase_item into the data.
	 * @param array &$fields
	 * @param ContentHandler $handler
	 * @param WikiPage $page
	 * @param ParserOutput $output
	 * @param SearchEngine $engine
	 */
	public function onSearchDataForIndex( &$fields, $handler, $page, $output, $engine ): void {
		$this->doSearchDataForIndex( $fields, $output );
	}

	/**
	 * Put wikibase_item into the data.
	 * @param array &$fields
	 * @param ContentHandler $handler
	 * @param WikiPage $page
	 * @param ParserOutput $output
	 * @param SearchEngine $engine
	 */
	public function onSearchDataForIndex2(
		array &$fields,
		ContentHandler $handler,
		WikiPage $page,
		ParserOutput $output,
		SearchEngine $engine,
		RevisionRecord $revision
	): void {
		$this->doSearchDataForIndex( $fields, $output );
	}

	private function doSearchDataForIndex( array &$fields, ParserOutput $parserOutput ): void {
		$wikibaseItem = $parserOutput->getPageProperty( 'wikibase_item' );
		if ( $wikibaseItem ) {
			$fields['wikibase_item'] = $wikibaseItem;
		}
	}

	/**
	 * Register wikibase_item field.
	 * @param array &$fields
	 * @param SearchEngine $engine
	 */
	public function onSearchIndexFields( &$fields, $engine ): void {
		$fields['wikibase_item'] = $engine->makeSearchFieldMapping( 'wikibase_item',
			SearchIndexField::INDEX_TYPE_KEYWORD );
	}

	/**
	 * @param string[] &$paths
	 */
	public function onUnitTestsList( &$paths ): void {
		$paths[] = __DIR__ . '/../../tests/phpunit/';
	}

	/**
	 * @param array[] &$queryPages
	 */
	public function onWgQueryPages( &$queryPages ): void {
		$queryPages[] = [ SpecialUnconnectedPages::class, 'UnconnectedPages' ];
		// SpecialPagesWithBadges and SpecialEntityUsage also extend QueryPage,
		// but are not useful in the list of query pages,
		// since they require a parameter (badge, entity id) to operate
	}

	public function onMaintenanceShellStart(): void {
		require_once __DIR__ . '/../MaintenanceShellStart.php';
	}
}
