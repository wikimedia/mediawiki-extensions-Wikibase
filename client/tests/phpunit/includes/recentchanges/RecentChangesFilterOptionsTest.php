<?php
namespace Wikibase\Test;

use Wikibase\RecentChangesFilterOptions;
use Wikibase\Client\WikibaseClient;
use FormOptions;

/**
 * @covers Wikibase\RecentChangesFilterOptions
 *
 * @since 0.5
 *
 * @group WikibaseClient
 * @group Test
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class RecentChangesFilterOptionsTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider provideShowWikibaseEdits
	 *
	 * @param bool $expected
	 * @param bool $showExternalRecentChanges
	 * @param bool $hideWikibase
	 */
	public function testShowWikibaseEdits(
		$expected,
		$showExternalRecentChanges,
		$hideWikibase
	) {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$oldShowExternal = $settings->getSetting( 'showExternalRecentChanges' );

		$settings->setSetting( 'showExternalRecentChanges', $showExternalRecentChanges );
		$opts = new FormOptions();
		$opts->add( 'hidewikidata', $hideWikibase );

		$recentChangesFilterOptions = new RecentChangesFilterOptions( $opts );
		$this->assertSame( $expected, $recentChangesFilterOptions->showWikibaseEdits() );

		$settings->setSetting( 'showExternalRecentChanges', $oldShowExternal );
	}

	public function provideShowWikibaseEdits() {
		return array(
			// hidewikibase is false, but showExternalRecentChanges is true
			array( true, true, false ),
			// hidewikibase set to true
			array( false, true, true ),
			// showExternalRecentChanges is false
			array( false, false, false ),
			// hidewikidata set and showExternalRecentChanges is false
			array( false, false, true ),
		);
	}
}
