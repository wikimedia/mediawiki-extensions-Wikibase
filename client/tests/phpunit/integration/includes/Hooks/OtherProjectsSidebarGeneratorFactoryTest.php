<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWikiIntegrationTestCase;
use Psr\Log\LoggerInterface;
use SiteLookup;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers \Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OtherProjectsSidebarGeneratorFactoryTest extends MediaWikiIntegrationTestCase {

	public function testGetOtherProjectsSidebarGenerator() {
		$settings = new SettingsArray( [
			'siteGlobalID' => 'enwiki',
			'otherProjectsLinks' => [ 'enwiktionary' ],
		] );

		$factory = new OtherProjectsSidebarGeneratorFactory(
			$settings,
			$this->createMock( SiteLinkLookup::class ),
			$this->createMock( SiteLookup::class ),
			$this->createMock( EntityLookup::class ),
			$this->createMock( SidebarLinkBadgeDisplay::class ),
			$this->createMock( HookContainer::class ),
			$this->createMock( LoggerInterface::class )
		);

		$otherProjectSidebarGenerator = $factory->getOtherProjectsSidebarGenerator( $this->createMock( UsageAccumulator::class ) );

		$this->assertInstanceOf(
			OtherProjectsSidebarGenerator::class,
			$otherProjectSidebarGenerator
		);
	}

}
