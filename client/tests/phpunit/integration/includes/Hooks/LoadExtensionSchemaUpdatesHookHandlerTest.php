<?php

declare( strict_types=1 );

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\Maintenance\Maintenance;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use Wikibase\Client\Hooks\LoadExtensionSchemaUpdatesHookHandler;
use Wikibase\Client\NamespaceChecker;
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

	public function addDBDataOnce() {
		$this->getDb()->newDeleteQueryBuilder()
			->deleteFrom( 'page' )
			->where( IDatabase::ALL_ROWS )
			->caller( __METHOD__ )
			->execute();

		$titles = [];
		$titles[10] = Title::makeTitle( $this->getDefaultWikitextNS(), 'LoadExtensionSchemaUpdatesHookHandlerTest-0' );
		$titles[20] = Title::makeTitle( NS_TALK, 'LoadExtensionSchemaUpdatesHookHandlerTest-Non-Wikibase-NS' );
		$titles[30] = Title::makeTitle( $this->getDefaultWikitextNS(), 'LoadExtensionSchemaUpdatesHookHandlerTest-1' );

		foreach ( $titles as $pageId => $title ) {
			$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
			$page->insertOn( $this->getDb(), $pageId );
		}
	}

	public function testOnLoadExtensionSchemaUpdates_skipAlreadyUpdated() {
		$dbUpdater = TestingAccessWrapper::newFromObject( DatabaseUpdater::newForDB( $this->getDb() ) );
		$dbUpdater->insertUpdateRow( LoadExtensionSchemaUpdatesHookHandler::UPDATE_KEY_UNEXPECTED_UNCONNECTED_PAGE );

		$handler = new LoadExtensionSchemaUpdatesHookHandler();
		$handler->onLoadExtensionSchemaUpdates( $dbUpdater );

		$this->assertSame( [], $dbUpdater->getExtensionUpdates() );
	}

	public function testOnLoadExtensionSchemaUpdates() {
		$namespaceInt = -$this->getDefaultWikitextNS();
		$namespaceString = strval( $namespaceInt );
		$namespaceFloat = $namespaceInt + 0.0;

		$maintenance = $this->createMock( Maintenance::class );
		$maintenance->expects( $this->any() )
			->method( 'isQuiet' )
			->willReturn( true );

		$dbUpdater = TestingAccessWrapper::newFromObject( DatabaseUpdater::newForDB( $this->getDb(), false, $maintenance ) );

		$this->overrideMwServices( null, [
			'WikibaseClient.NamespaceChecker' => function() {
				return new NamespaceChecker( [], [ $this->getDefaultWikitextNS() ] );
			},
		] );

		$handler = new LoadExtensionSchemaUpdatesHookHandler();
		$handler->onLoadExtensionSchemaUpdates( $dbUpdater );

		$this->assertCount( 1, $dbUpdater->getExtensionUpdates() );

		$dbUpdater->runUpdates( $dbUpdater->getExtensionUpdates(), true );

		$this->newSelectQueryBuilder()
			->select( [ 'pp_page', 'pp_propname', 'pp_value', 'pp_sortkey' ] )
			->from( 'page_props' )
			->assertResultSet( [
				[ 10, 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
				// 20 is not in a Wikibase NS
				[ 30, 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
			] );
		$this->assertTrue(
			$dbUpdater->updateRowExists( LoadExtensionSchemaUpdatesHookHandler::UPDATE_KEY_UNEXPECTED_UNCONNECTED_PAGE )
		);
	}

}
