<?php

namespace Wikibase\Client\Tests\Integration;

use ApiMain;
use ApiQuery;
use IContextSource;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use RequestContext;
use Traversable;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\Hooks\EchoSetupHookHandlers;
use Wikibase\Client\Hooks\EditActionHookHandler;
use Wikibase\Client\Hooks\NoLangLinkHandler;
use Wikibase\Client\Hooks\ShortDescHandler;
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
class GlobalStateFactoryMethodsResourceTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Factory methods should never access the database or do http requests
		// https://phabricator.wikimedia.org/T243729
		$this->disallowDBAccess();
		$this->disallowHttpAccess();
	}

	private function getExtensionJson(): array {
		static $extensionJson = null;
		if ( $extensionJson === null ) {
			$extensionJson = json_decode(
				file_get_contents( __DIR__ . '/../../../../../extension-client.json' ),
				true
			);
		}
		return $extensionJson;
	}

	/** @dataProvider provideHookHandlerNames */
	public function testHookHandler( string $hookHandlerName ): void {
		$specification = $this->getExtensionJson()['HookHandlers'][$hookHandlerName];
		$objectFactory = MediaWikiServices::getInstance()->getObjectFactory();
		$objectFactory->createObject( $specification, [
			'allowClassName' => true,
		] );
		$this->assertTrue( true );
	}

	public function provideHookHandlerNames(): Traversable {
		foreach ( $this->getExtensionJson()['HookHandlers'] as $hookHandlerName => $specification ) {
			yield [ $hookHandlerName ];
		}
	}

	/** @dataProvider provideApiModuleListsAndNames */
	public function testApiModule( string $moduleList, string $moduleName ): void {
		$specification = $this->getExtensionJson()[$moduleList][$moduleName];
		$objectFactory = MediaWikiServices::getInstance()->getObjectFactory();
		$objectFactory->createObject( $specification, [
			'allowClassName' => true,
			'extraArgs' => [ $this->mockApiQuery(), 'query' ],
		] );
		$this->assertTrue( true );
	}

	public function provideApiModuleListsAndNames(): Traversable {
		foreach ( [ 'APIListModules', 'APIMetaModules', 'APIPropModules' ] as $moduleList ) {
			foreach ( $this->getExtensionJson()[$moduleList] as $moduleName => $specification ) {
				yield [ $moduleList, $moduleName ];
			}
		}
	}

	/** @dataProvider provideSpecialPageNames */
	public function testSpecialPage( string $specialPageName ): void {
		$specification = $this->getExtensionJson()['SpecialPages'][$specialPageName];
		$objectFactory = MediaWikiServices::getInstance()->getObjectFactory();
		$objectFactory->createObject( $specification, [
			'allowClassName' => true,
		] );
		$this->assertTrue( true );
	}

	public function provideSpecialPageNames(): Traversable {
		foreach ( $this->getExtensionJson()['SpecialPages'] as $specialPageName => $specification ) {
			yield [ $specialPageName ];
		}
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

	public function testNoLangLinkHandler(): void {
		TestingAccessWrapper::newFromClass( NoLangLinkHandler::class )
			->newFromGlobalState();
		$this->assertTrue( true );
	}

	public function testShortDescHandler(): void {
		TestingAccessWrapper::newFromClass( ShortDescHandler::class )
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

	private function mockApiQuery(): ApiQuery {
		$apiMain = $this->createMock( ApiMain::class );
		$apiMain->method( 'getContext' )
			->willReturn( $this->createMock( IContextSource::class ) );
		$apiQuery = $this->createMock( ApiQuery::class );
		$apiQuery->method( 'getMain' )
			->willReturn( $apiMain );
		return $apiQuery;
	}

}
