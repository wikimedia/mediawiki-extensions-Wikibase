<?php

declare( strict_types=1 );

namespace Wikibase\Client\Tests\Integration\Hooks;

use DatabaseUpdater;
use Maintenance;
use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\Client\Hooks\LoadExtensionSchemaUpdatesHookHandler;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\SettingsArray;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Client\Hooks\LoadExtensionSchemaUpdatesHookHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch <mail@mariushoch.de>
 */
class LoadExtensionSchemaUpdatesHookHandlerTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->tablesUsed[] = 'page_props';
		$this->tablesUsed[] = 'updatelog';
	}

	public function addDBDataOnce() {
		$this->db->delete( 'page', IDatabase::ALL_ROWS, __METHOD__ );

		$titles = [];
		$titles[10] = Title::newFromTextThrow( "LoadExtensionSchemaUpdatesHookHandlerTest-0", $this->getDefaultWikitextNS() );
		$titles[20] = Title::newFromTextThrow( "LoadExtensionSchemaUpdatesHookHandlerTest-Non-Wikibase-NS", NS_TALK );
		$titles[30] = Title::newFromTextThrow( "LoadExtensionSchemaUpdatesHookHandlerTest-1", $this->getDefaultWikitextNS() );

		foreach ( $titles as $pageId => $title ) {
			$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
			$page->insertOn( $this->db, $pageId );
		}
	}

	public function testOnLoadExtensionSchemaUpdates_skipSetting() {
		$dbUpdater = TestingAccessWrapper::newFromObject( DatabaseUpdater::newForDB( $this->db ) );

		$this->overrideMwServices( null, [
			'WikibaseClient.Settings' => function () {
				return new SettingsArray( [ 'tmpUnconnectedPagePagePropMigrationStage' => MIGRATION_OLD ] );
			},
		] );

		$handler = new LoadExtensionSchemaUpdatesHookHandler();
		$handler->onLoadExtensionSchemaUpdates( $dbUpdater );

		$this->assertSame( [], $dbUpdater->getExtensionUpdates() );
	}

	public function testOnLoadExtensionSchemaUpdates_skipAlreadyUpdated() {
		$dbUpdater = TestingAccessWrapper::newFromObject( DatabaseUpdater::newForDB( $this->db ) );
		$dbUpdater->insertUpdateRow( LoadExtensionSchemaUpdatesHookHandler::UPDATE_KEY_UNEXPECTED_UNCONNECTED_PAGE );

		$this->overrideMwServices( null, [
			'WikibaseClient.Settings' => function () {
				return new SettingsArray( [ 'tmpUnconnectedPagePagePropMigrationStage' => MIGRATION_WRITE_BOTH ] );
			},
		] );

		$handler = new LoadExtensionSchemaUpdatesHookHandler();
		$handler->onLoadExtensionSchemaUpdates( $dbUpdater );

		$this->assertSame( [], $dbUpdater->getExtensionUpdates() );
	}

	public function testOnLoadExtensionSchemaUpdates() {
		$namespaceInt = -$this->getDefaultWikitextNS();
		$namespaceString = strval( $namespaceInt );
		$namespaceFloat = $namespaceInt + 0.0;

		$settings = WikibaseClient::getSettings()->getArrayCopy();
		$maintenance = $this->createMock( Maintenance::class );
		$maintenance->expects( $this->any() )
			->method( 'isQuiet' )
			->willReturn( true );

		$dbUpdater = TestingAccessWrapper::newFromObject( DatabaseUpdater::newForDB( $this->db, false, $maintenance ) );

		$this->overrideMwServices( null, [
			'WikibaseClient.Settings' => function () use ( $settings ) {
				return new SettingsArray(
					[
						'tmpUnconnectedPagePagePropMigrationStage' => MIGRATION_WRITE_BOTH,
						'tmpUnconnectedPagePagePropMigrationLegacyFormat' => false,
					] + $settings
				);
			},
			'WikibaseClient.NamespaceChecker' => function() {
				return new NamespaceChecker( [], [ $this->getDefaultWikitextNS() ] );
			},
		] );

		$handler = new LoadExtensionSchemaUpdatesHookHandler();
		$handler->onLoadExtensionSchemaUpdates( $dbUpdater );

		$this->assertCount( 1, $dbUpdater->getExtensionUpdates() );

		$dbUpdater->runUpdates( $dbUpdater->getExtensionUpdates(), true );

		$this->assertSelect(
			'page_props',
			[ 'pp_page', 'pp_propname', 'pp_value', 'pp_sortkey' ],
			IDatabase::ALL_ROWS,
			[
				[ 10, 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
				// 20 is not in a Wikibase NS
				[ 30, 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
			]
		);
		$this->assertTrue(
			$dbUpdater->updateRowExists( LoadExtensionSchemaUpdatesHookHandler::UPDATE_KEY_UNEXPECTED_UNCONNECTED_PAGE )
		);
	}

}
