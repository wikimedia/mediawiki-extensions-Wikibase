<?php

namespace Wikibase\Client\Hooks\Test;

use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Test\MockRepository;
use Wikibase\Test\MockSiteStore;

/**
 * @covers Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory
 *
 * @since 0.5
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OtherProjectsSidebarGeneratorFactoryTest extends \MediaWikiTestCase {

	public function testGetOtherProjectsSidebarGenerator() {
		$siteLinkLookup = new MockRepository();
		$siteStore = MockSiteStore::newFromTestSites();

		$factory = new OtherProjectsSidebarGeneratorFactory(
			'enwiki',
			$siteLinkLookup,
			$siteStore,
			array( 'enwiktionary' )
		);

		$otherProjectSidebarGenerator = $factory->getOtherProjectsSidebarGenerator();

		$this->assertInstanceOf(
			'Wikibase\Client\Hooks\OtherProjectsSidebarGenerator',
			$otherProjectSidebarGenerator
		);
	}

}
