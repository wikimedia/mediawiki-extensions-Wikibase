<?php

namespace Wikibase\Repo\Tests;

use ApiMain;
use ApiQuery;
use ApiTestContext;
use FauxRequest;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use RequestContext;
use Traversable;
use Wikibase\Repo\Hooks\HtmlPageLinkRendererEndHookHandler;
use Wikibase\Repo\Hooks\OutputPageJsConfigHookHandler;
use Wikibase\Repo\Hooks\ShowSearchHitHandler;
use Wikibase\Repo\ParserOutput\TermboxFlag;
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

	private function getExtensionJson(): array {
		static $extensionJson = null;
		if ( $extensionJson === null ) {
			$extensionJson = json_decode(
				file_get_contents( __DIR__ . '/../../../../extension-repo.json' ),
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

	/** @dataProvider provideApiModuleNames */
	public function testApiModule( string $moduleName ): void {
		$specification = $this->getExtensionJson()['APIModules'][$moduleName];
		$objectFactory = MediaWikiServices::getInstance()->getObjectFactory();
		$objectFactory->createObject( $specification, [
			'allowClassName' => true,
			'extraArgs' => [ $this->mockApiMain(), 'modulename' ],
		] );
		$this->assertTrue( true );
	}

	public function provideApiModuleNames(): Traversable {
		foreach ( $this->getExtensionJson()['APIModules'] as $moduleName => $specification ) {
			yield [ $moduleName ];
		}
	}

	/** @dataProvider provideApiQueryModuleListsAndNames */
	public function testApiQueryModule( string $moduleList, string $moduleName ): void {
		$specification = $this->getExtensionJson()[$moduleList][$moduleName];
		$objectFactory = MediaWikiServices::getInstance()->getObjectFactory();
		$objectFactory->createObject( $specification, [
			'allowClassName' => true,
			'extraArgs' => [ $this->mockApiQuery(), 'query' ],
		] );
		$this->assertTrue( true );
	}

	public function provideApiQueryModuleListsAndNames(): Traversable {
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

	public function testHtmlPageLinkRendererBeginHookHandler(): void {
		TestingAccessWrapper::newFromClass( HtmlPageLinkRendererEndHookHandler::class )
			->factory();
	}

	public function testOutputPageJsConfigHookHandler(): void {
		TestingAccessWrapper::newFromClass( OutputPageJsConfigHookHandler::class )
			->factory();
	}

	public function testShowSearchHitHandler(): void {
		TestingAccessWrapper::newFromClass( ShowSearchHitHandler::class )
			->factory( RequestContext::getMain() );
	}

	public function testTermboxFlag(): void {
		TermboxFlag::getInstance();
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

	private function mockApiMain(): ApiMain {
		$request = new FauxRequest();
		$ctx = new ApiTestContext();
		$ctx = $ctx->newTestContext( $request );
		return new ApiMain( $ctx );
	}

	private function mockApiQuery(): ApiQuery {
		return new ApiQuery( $this->mockApiMain(), 'query' );
	}

}
