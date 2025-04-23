<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use CirrusSearch\SearchConfig;
use MediaWikiIntegrationTestCase;
use Wikibase\Client\Hooks\CirrusSearchAddQueryFeaturesHookHandler;
use Wikibase\Client\MoreLikeWikibase;

/**
 * @covers \Wikibase\Client\Hooks\CirrusSearchAddQueryFeaturesHookHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CirrusSearchAddQueryFeaturesHookHandlerTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->markTestSkippedIfExtensionNotLoaded( 'CirrusSearch' );
	}

	public function testGetOtherProjectsSidebarGenerator() {
		$handler = new CirrusSearchAddQueryFeaturesHookHandler();
		$extraFeatures = [ 'foo' ];
		$handler->onCirrusSearchAddQueryFeatures(
			$this->createMock( SearchConfig::class ),
			$extraFeatures
		);
		$this->assertCount( 2, $extraFeatures );
		$this->assertSame( 'foo', $extraFeatures[0] );
		$this->assertInstanceOf( MoreLikeWikibase::class, $extraFeatures[1] );
	}

}
