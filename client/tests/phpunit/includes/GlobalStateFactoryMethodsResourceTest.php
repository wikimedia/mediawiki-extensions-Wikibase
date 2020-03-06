<?php

namespace Wikibase\Client\Tests;

use ChangesList;
use MediaWiki\Http\HttpRequestFactory;
use MediaWikiTestCase;
use RequestContext;
use Wikibase\Client\Hooks\ChangesListLinesHandler;
use Wikibase\Client\Hooks\ChangesListSpecialPageHookHandlers;
use Wikibase\Client\Hooks\DataUpdateHookHandlers;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\Hooks\EchoSetupHookHandlers;
use Wikibase\Client\Hooks\EditActionHookHandler;
use Wikibase\Client\Hooks\MagicWordHookHandlers;
use Wikibase\Client\Hooks\MovePageNotice;
use Wikibase\Client\Hooks\NoLangLinkHandler;
use Wikibase\Client\Hooks\ParserLimitReportPrepareHookHandler;
use Wikibase\Client\Hooks\ParserOutputUpdateHookHandlers;
use Wikibase\Client\Hooks\ShortDescHandler;
use Wikibase\Client\Hooks\SidebarHookHandlers;
use Wikibase\Client\Hooks\SkinTemplateOutputPageBeforeExecHandler;
use Wikibase\Client\Hooks\UpdateRepoHookHandlers;
use Wikimedia\Rdbms\DBConnRef;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\TestingAccessWrapper;

/**
 * Test to assert that factory methods of hook service classes (and similar services)
 * don't access the database or do http requests (which would be a performance issue).
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class GlobalStateFactoryMethodsResourceTest extends MediaWikiTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Factory methods should never access the database or do http requests
		// https://phabricator.wikimedia.org/T243729
		$this->disallowDBAccess();
		$this->disallowHttpAccess();
	}

	public function testChangesListLinesHandler(): void {
		TestingAccessWrapper::newFromClass( ChangesListLinesHandler::class )
			->newFromGlobalState( new ChangesList( RequestContext::getMain() ) );
	}

	public function testChangesListSpecialPageHookHandlers(): void {
		TestingAccessWrapper::newFromClass( ChangesListSpecialPageHookHandlers::class )
			->newFromGlobalState( RequestContext::getMain(), 'dummy' );
	}

	public function testDataUpdateHookHandlers() {
		DataUpdateHookHandlers::newFromGlobalState();
	}

	public function testEchoNotificationsHandlers() {
		EchoNotificationsHandlers::newFromGlobalState();
		$this->assertTrue( true );
	}

	public function testEchoSetupHookHandlers() {
		EchoSetupHookHandlers::newFromGlobalState();
		$this->assertTrue( true );
	}

	public function testEditActionHookHandler() {
		EditActionHookHandler::newFromGlobalState( RequestContext::getMain() );
		$this->assertTrue( true );
	}

	public function testMagicWordHookHandlers(): void {
		MagicWordHookHandlers::newFromGlobalState();
		$this->assertTrue( true );
	}

	public function testMovePageNotice(): void {
		TestingAccessWrapper::newFromClass( MovePageNotice::class )
			->newFromGlobalState();
	}

	public function testNoLangLinkHandler(): void {
		TestingAccessWrapper::newFromClass( NoLangLinkHandler::class )
			->newFromGlobalState();
		$this->assertTrue( true );
	}

	public function testParserLimitReportPrepareHookHandler(): void {
		ParserLimitReportPrepareHookHandler::newFromGlobalState();
		$this->assertTrue( true );
	}

	public function testParserOutputUpdateHookHandlers(): void {
		ParserOutputUpdateHookHandlers::newFromGlobalState();
		$this->assertTrue( true );
	}

	public function testShortDescHandler(): void {
		TestingAccessWrapper::newFromClass( ShortDescHandler::class )
			->newFromGlobalState();
		$this->assertTrue( true );
	}

	public function testSidebarHookHandlers(): void {
		TestingAccessWrapper::newFromClass( SidebarHookHandlers::class )
			->getInstance();
		$this->assertTrue( true );
	}

	public function testSkinTemplateOutputPageBeforeExecHandler(): void {
		TestingAccessWrapper::newFromClass( SkinTemplateOutputPageBeforeExecHandler::class )
			->newFromGlobalState();
		$this->assertTrue( true );
	}

	public function testUpdateRepoHookHandlers(): void {
		TestingAccessWrapper::newFromClass( UpdateRepoHookHandlers::class )
			->newFromGlobalState();
		$this->assertTrue( true );
	}

	private function disallowDBAccess() {
		$this->setService(
			'DBLoadBalancerFactory',
			function() {
				$lb = $this->createMock( ILoadBalancer::class );
				$lb->expects( $this->never() )
					->method( 'getConnection' );
				$lb->expects( $this->never() )
					->method( 'getConnectionRef' );
				$lb->expects( $this->never() )
					->method( 'getMaintenanceConnectionRef' );
				$lb->expects( $this->any() )
					->method( 'getLocalDomainID' )
					->willReturn( 'banana' );

				// This LazyConnectionRef will use our mocked LoadBalancer when actually
				// trying to connect, thus using it for DB queries will fail.
				$lazyDb = new DBConnRef(
					$lb,
					[ 'dummy', 'dummy', 'dummy', 'dummy' ],
					DB_REPLICA
				);
				$lb->expects( $this->any() )
					->method( 'getLazyConnectionRef' )
					->willReturn( $lazyDb );

				$lbFactory = $this->createMock( LBFactory::class );
				$lbFactory->expects( $this->any() )
					->method( 'getMainLB' )
					->willReturn( $lb );

				return $lbFactory;
			}
		);
	}

	private function disallowHttpAccess() {
		$this->setService(
			'HttpRequestFactory',
			function() {
				$factory = $this->createMock( HttpRequestFactory::class );
				$factory->expects( $this->never() )
					->method( 'create' );
				$factory->expects( $this->never() )
					->method( 'request' );
				$factory->expects( $this->never() )
					->method( 'get' );
				$factory->expects( $this->never() )
					->method( 'post' );
				return $factory;
			}
		);
	}

}
