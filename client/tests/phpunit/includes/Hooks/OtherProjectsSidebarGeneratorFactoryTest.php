<?php

namespace Wikibase\Client\Tests\Hooks;

use HashSiteStore;
use Language;
use TestSites;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\SettingsArray;

/**
 * @covers \Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group NotIsolatedUnitTest
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OtherProjectsSidebarGeneratorFactoryTest extends \MediaWikiTestCase {

	public function testGetOtherProjectsSidebarGenerator() {
		$settings = new SettingsArray( [
			'siteGlobalID' => 'enwiki',
			'otherProjectsLinks' => [ 'enwiktionary' ]
		] );

		$siteLinkLookup = new MockRepository();
		$siteStore = new HashSiteStore( TestSites::getSites() );
		$sidebarLinkBadgeDisplay = new SidebarLinkBadgeDisplay(
			$this->getMock( LabelDescriptionLookup::class ),
			[],
			Language::factory( 'en' )
		);

		$factory = new OtherProjectsSidebarGeneratorFactory(
			$settings,
			$siteLinkLookup,
			$siteStore,
			$this->getMock( EntityLookup::class ),
			$sidebarLinkBadgeDisplay
		);

		$otherProjectSidebarGenerator = $factory->getOtherProjectsSidebarGenerator( $this->getMock( UsageAccumulator::class ) );

		$this->assertInstanceOf(
			OtherProjectsSidebarGenerator::class,
			$otherProjectSidebarGenerator
		);
	}

}
