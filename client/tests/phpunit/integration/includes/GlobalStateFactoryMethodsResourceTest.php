<?php

namespace Wikibase\Client\Tests\Integration;

use ApiMain;
use ApiQuery;
use ApiTestContext;
use FauxRequest;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use MultiHttpClient;
use ReflectionClass;
use ReflectionMethod;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\Hooks\NoLangLinkHandler;
use Wikibase\Client\Hooks\ShortDescHandler;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\WikibaseServices;
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
 * @coversNothing
 */
class GlobalStateFactoryMethodsResourceTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Factory methods should never access the database or do http requests
		// https://phabricator.wikimedia.org/T243729
		$this->disallowDBAccess();
		$this->disallowHttpAccess();
		// Configure the site group so that it doesn’t need to fall back to the DB site store
		$this->configureSiteGroup();
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

	public function provideHookHandlerNames(): iterable {
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

	public function provideApiModuleListsAndNames(): iterable {
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

	public function provideSpecialPageNames(): iterable {
		foreach ( $this->getExtensionJson()['SpecialPages'] as $specialPageName => $specification ) {
			yield [ $specialPageName ];
		}
	}

	/** @dataProvider provideServicesLists */
	public function testServicesSorted( array $services ): void {
		$sortedServices = $services;
		usort( $sortedServices, function ( $serviceA, $serviceB ) {
			$isClientServiceA = strpos( $serviceA, 'WikibaseClient.' ) === 0;
			$isClientServiceB = strpos( $serviceB, 'WikibaseClient.' ) === 0;
			if ( $isClientServiceA !== $isClientServiceB ) {
				return $isClientServiceA ? 1 : -1;
			}
			return strcmp( $serviceA, $serviceB );
		} );

		$this->assertSame( $sortedServices, $services,
			'Services should be sorted: first all MediaWiki services, then all Wikibase ones.' );
	}

	public function provideServicesLists(): iterable {
		foreach ( $this->provideSpecifications() as $name => $specification ) {
			if (
				is_array( $specification ) &&
				array_key_exists( 'services', $specification )
			) {
				yield $name => [ $specification['services'] ];
			}
		}
	}

	public function provideSpecifications(): iterable {
		foreach ( $this->provideHookHandlerNames() as [ $hookHandlerName ] ) {
			yield "HookHandlers/$hookHandlerName" => $this->getExtensionJson()['HookHandlers'][$hookHandlerName];
		}

		foreach ( $this->provideApiModuleListsAndNames() as [ $moduleList, $moduleName ] ) {
			yield "$moduleList/$moduleName" => $this->getExtensionJson()[$moduleList][$moduleName];
		}

		foreach ( $this->provideSpecialPageNames() as [ $specialPageName ] ) {
			yield "SpecialPages/$specialPageName" => $this->getExtensionJson()['SpecialPages'][$specialPageName];
		}
	}

	/** @dataProvider provideWikibaseServicesMethods */
	public function testWikibaseServicesMethod( string $methodName ) {
		$wikibaseServices = WikibaseClient::getWikibaseServices();

		$wikibaseServices->$methodName();
		$this->addToAssertionCount( 1 );
	}

	public function provideWikibaseServicesMethods(): iterable {
		$reflectionClass = new ReflectionClass( WikibaseServices::class );
		foreach ( $reflectionClass->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ) {
			yield $method->getName() => [ $method->getName() ];
		}
	}

	public function testEchoNotificationsHandlers() {
		EchoNotificationsHandlers::factory();
		$this->assertTrue( true );
	}

	public function testNoLangLinkHandler(): void {
		TestingAccessWrapper::newFromClass( NoLangLinkHandler::class )
			->factory();
		$this->assertTrue( true );
	}

	public function testShortDescHandler(): void {
		TestingAccessWrapper::newFromClass( ShortDescHandler::class )
			->factory();
		$this->assertTrue( true );
	}

	private function disallowDBAccess() {
		$this->setService(
			'DBLoadBalancerFactory',
			function() {
				$lb = $this->createMock( ILoadBalancer::class );
				$lb->expects( $this->never() )
					->method( 'getMaintenanceConnectionRef' );
				$lb->method( 'getLocalDomainID' )
					->willReturn( 'banana' );

				// This LazyConnectionRef will use our mocked LoadBalancer when actually
				// trying to connect, thus using it for DB queries will fail.
				$lazyDb = new DBConnRef(
					$lb,
					[ 'dummy', 'dummy', 'dummy', 'dummy' ],
					DB_REPLICA
				);
				$lb->method( 'getConnectionRef' )
					->willReturn( $lazyDb );
				$lb->method( 'getConnection' )
					->willReturn( $lazyDb );

				$lbFactory = $this->createMock( LBFactory::class );
				$lbFactory->method( 'getMainLB' )
					->willReturn( $lb );
				$lbFactory->method( 'getLocalDomainID' )
					->willReturn( 'banana' );

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
				$factory->method( 'createMultiClient' )
					->willReturn( $this->createMock( MultiHttpClient::class ) );
				return $factory;
			}
		);
	}

	private function configureSiteGroup(): void {
		$settings = clone WikibaseClient::getSettings( $this->getServiceContainer() );
		$settings->setSetting( 'siteGroup', 'testgroup' );
		$this->setService( 'WikibaseClient.Settings', $settings );
	}

	private function mockApiQuery(): ApiQuery {
		$request = new FauxRequest();
		$ctx = new ApiTestContext();
		$ctx = $ctx->newTestContext( $request );
		$apiMain = new ApiMain( $ctx );
		return $apiMain->getModuleManager()->getModule( 'query' );
	}

}
