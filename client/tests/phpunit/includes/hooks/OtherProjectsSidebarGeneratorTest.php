<?php

namespace Wikibase\Client\Hooks\Test;

use Title;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\Test\MockRepository;
use Wikibase\Test\MockSiteStore;

/**
 * @covers Wikibase\Client\Hooks\OtherProjectsSidebarGenerator
 *
 * @since 0.5
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class OtherProjectsSidebarGeneratorTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider projectLinkSidebarProvider
	 */
	public function testBuildProjectLinkSidebar( array $siteIdsToOutput, array $result ) {
		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'enwiki', 'Nyan Cat' ) );
		$item->addSiteLink( new SiteLink( 'enwiktionary', 'Nyan Cat' ) );

		$mockRepository = new MockRepository();
		$mockRepository->putEntity( $item );

		$siteStore = MockSiteStore::newFromTestSites();

		$otherProjectSidebarGenerator = new OtherProjectsSidebarGenerator(
			'enwiki',
			$mockRepository,
			$siteStore->getSites(),
			$siteIdsToOutput
		);

		$this->assertEquals(
			$result,
			$otherProjectSidebarGenerator->buildProjectLinkSidebar( Title::makeTitle( NS_MAIN, 'Nyan Cat' ) )
		);
	}

	public function projectLinkSidebarProvider() {
		return array(
			array(
				array(),
				array()
			),
			array(
				array( 'spam', 'spam2' ),
				array()
			),
			array(
				array( 'enwiktionary' ),
				array(
					array(
						'msg' => 'wikibase-otherprojects-wiktionary',
						'class' => 'wb-otherproject-link wb-otherproject-wiktionary',
						'href' => 'https://en.wiktionary.org/wiki/Nyan_Cat',
						'hreflang' => 'en'
					)
				)
			)
		);
	}

}
