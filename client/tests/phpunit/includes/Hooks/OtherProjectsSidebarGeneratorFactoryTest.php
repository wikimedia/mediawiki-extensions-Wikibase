<?php

namespace Wikibase\Client\Tests\Hooks;

use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\SettingsArray;
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
		$settings = new SettingsArray( array(
			'siteGlobalID' => 'enwiki',
			'otherProjectsLinks' => array( 'enwiktionary' )
		) );

		$siteLinkLookup = new MockRepository();
		$siteStore = MockSiteStore::newFromTestSites();

		$factory = new OtherProjectsSidebarGeneratorFactory(
			$settings,
			$siteLinkLookup,
			$siteStore,
			$this->getMockBuilder( 'Wikibase\Client\Hooks\SidebarLinkBadgeDisplay' )
				->disableOriginalConstructor()
				->getMock()
		);

		$otherProjectSidebarGenerator = $factory->getOtherProjectsSidebarGenerator();

		$this->assertInstanceOf(
			'Wikibase\Client\Hooks\OtherProjectsSidebarGenerator',
			$otherProjectSidebarGenerator
		);
	}

}
