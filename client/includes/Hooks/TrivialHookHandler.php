<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use ContentHandler;
use MediaWiki\Content\Hook\SearchDataForIndexHook;
use MediaWiki\Hook\AbortEmailNotificationHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\UnitTestsListHook;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Search\Hook\SearchIndexFieldsHook;
use MediaWiki\SpecialPage\Hook\WgQueryPagesHook;
use OutputPage;
use ParserOutput;
use RecentChange;
use SearchEngine;
use SearchIndexField;
use Skin;
use Title;
use User;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\Specials\SpecialUnconnectedPages;
use WikiPage;

/**
 * Handler for “trivial” hooks:
 * ones that don’t need any services injected,
 * and whose code is fairly brief.
 *
 * @license GPL-2.0-or-later
 */
class TrivialHookHandler implements
	AbortEmailNotificationHook,
	BeforePageDisplayHook,
	SearchDataForIndexHook,
	SearchIndexFieldsHook,
	UnitTestsListHook,
	WgQueryPagesHook
{
	/**
	 * @param User $editor
	 * @param Title $title
	 * @param RecentChange $recentChange
	 *
	 * @return bool
	 */
	public function onAbortEmailNotification( $editor, $title, $recentChange ): bool {
		if ( $recentChange->getAttribute( 'rc_source' ) === RecentChangeFactory::SRC_WIKIBASE ) {
			return false;
		}

		return true;
	}

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

	/**
	 * @param array $fields
	 * @param ParserOutput $parserOutput
	 */
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

}
