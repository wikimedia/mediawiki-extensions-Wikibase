<?php
namespace Wikibase\Test;

use FormOptions;
use User;
use Wikibase\Client\RecentChanges\RecentChangesFilterOptions;
use Wikibase\Client\WikibaseClient;

/**
 * @covers Wikibase\Client\RecentChanges\RecentChangesFilterOptions
 *
 * @group WikibaseClient
 * @group Test
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class RecentChangesFilterOptionsTest extends \MediaWikiTestCase {
	public function setUp() {
		parent::setUp();

		$user = User::newFromName( 'RecentChangesFilterOptionsTest' );
		$this->setMwGlobals( 'wgUser', $user );
	}

	/**
	 * @dataProvider provideShowWikibaseEdits
	 *
	 * @param bool $expected
	 * @param bool $showExternalRecentChanges
	 * @param bool $hideWikibase
	 * @param bool $useNewRc
	 */
	public function testShowWikibaseEdits(
		$expected,
		$showExternalRecentChanges,
		$hideWikibase,
		$useNewRc
	) {
		global $wgUser; // Set by setUp

		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$oldShowExternal = $settings->getSetting( 'showExternalRecentChanges' );
		$wgUser->setOption( 'usenewrc', $useNewRc );

		$settings->setSetting( 'showExternalRecentChanges', $showExternalRecentChanges );
		$opts = new FormOptions();
		$opts->add( 'hidewikidata', $hideWikibase );

		$recentChangesFilterOptions = new RecentChangesFilterOptions( $opts );
		$this->assertSame( $expected, $recentChangesFilterOptions->showWikibaseEdits() );

		$settings->setSetting( 'showExternalRecentChanges', $oldShowExternal );
	}

	public function provideShowWikibaseEdits() {
		return array(
			'Wikibase shown' => array(
				'expected' => true,
				'showExternalRecentChanges' => true,
				'hideWikibase' => false,
				'useNewRc' => false
			),
			'hidewikidata set' => array(
				'expected' => false,
				'showExternalRecentChanges' => true,
				'hideWikibase' => true,
				'useNewRc' => false
			),
			'showExternalRecentChanges is false' => array(
				'expected' => false,
				'showExternalRecentChanges' => false,
				'hideWikibase' => false,
				'useNewRc' => false
			),
			'hidewikidata set and showExternalRecentChanges are false' => array(
				'expected' => false,
				'showExternalRecentChanges' => false,
				'hideWikibase' => true,
				'useNewRc' => false
			),
			'usenewrc user option true' => array(
				'expected' => false,
				'showExternalRecentChanges' => true,
				'hideWikibase' => false,
				'useNewRc' => true
			),
		);
	}
}
