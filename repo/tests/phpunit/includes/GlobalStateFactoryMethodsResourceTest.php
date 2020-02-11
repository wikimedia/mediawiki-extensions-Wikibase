<?php

namespace Wikibase\Repo\Tests;

use MediaWikiTestCase;
use RequestContext;
use Wikibase\Repo\Hooks\HtmlPageLinkRendererBeginHookHandler;
use Wikibase\Repo\Hooks\LabelPrefetchHookHandlers;
use Wikibase\Repo\Hooks\OutputPageJsConfigHookHandler;
use Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler;
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

	public function testHtmlPageLinkRendererBeginHookHandler(): void {
		TestingAccessWrapper::newFromClass( HtmlPageLinkRendererBeginHookHandler::class )
			->newFromGlobalState();
	}

	public function testLabelPrefetchHookHandlers(): void {
		TestingAccessWrapper::newFromClass( LabelPrefetchHookHandlers::class )
			->newFromGlobalState();
	}

	public function testOutputPageBeforeHTMLHookHandler(): void {
		OutputPageBeforeHTMLHookHandler::newFromGlobalState();
	}

	public function testOutputPageJsConfigHookHandler(): void {
		TestingAccessWrapper::newFromClass( OutputPageJsConfigHookHandler::class )
			->newFromGlobalState();
	}

	public function testShowSearchHitHandler(): void {
		TestingAccessWrapper::newFromClass( ShowSearchHitHandler::class )
			->newFromGlobalState( RequestContext::getMain() );
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
				$this->fail( 'Service getters must not access HttpRequestFactory.' );
			}
		);
	}

}
