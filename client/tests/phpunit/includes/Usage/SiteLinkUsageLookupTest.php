<?php

namespace Wikibase\Client\Tests\Usage\Sql;

use Title;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\SiteLinkUsageLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Client\Usage\SiteLinkUsageLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SiteLinkUsageLookupTest extends \MediaWikiTestCase {

	/**
	 * @param ItemId[] $links
	 *
	 * @return SiteLinkLookup
	 */
	private function getSiteLinkLookup( array $links ) {
		$repo = new MockRepository();

		foreach ( $links as $name => $itemId ) {
			$item = Item::newEmpty();
			$item->setId( $itemId );
			$item->getSiteLinkList()->addSiteLink( new SiteLink( 'testwiki', "$name" ) );
			$item->getSiteLinkList()->addSiteLink( new SiteLink( 'badwiki', "$name" ) );
			$item->getSiteLinkList()->addSiteLink( new SiteLink( 'sadwiki', "42" ) );

			$repo->putEntity( $item );
		}

		return $repo;
	}

	/**
	 * @note Assumptions: page titles are the same as page IDs.
	 *
	 * @param SiteLinkLookup $siteLinks
	 * @param TitleFactory $titleFactory
	 *
	 * @return SiteLinkUsageLookup
	 */
	private function getUsageLookup( SiteLinkLookup $siteLinks, TitleFactory $titleFactory ) {
		return new SiteLinkUsageLookup(
			'testwiki',
			$siteLinks,
			$titleFactory
		);
	}

	private function getTitleFactory() {
		$titleFactory = $this->getMock( 'Wikibase\Client\Store\TitleFactory' );
		$titleFactory->expects( $this->any() )
			->method( 'newFromText' )
			->will( $this->returnCallback( function ( $text ) {
				$id = intval( $text );
				$title = Title::newFromText( $text );
				$title->resetArticleID( $id );
				return $title;
			} ) );

		return $titleFactory;
	}

	public function testGetUsagesForPage() {
		$links = $this->getSiteLinkLookup( array(
			'23' => new ItemId( 'Q23' ),
		) );

		$titleFactory = $this->getTitleFactory();
		$lookup = $this->getUsageLookup( $links, $titleFactory );

		$actual = $lookup->getUsagesForPage( 42 );
		$this->assertEmpty( $actual );

		$actual = $lookup->getUsagesForPage( 23 );
		$this->assertCount( 1, $actual );
		$this->assertEquals( 'Q23#S', $actual[0]->getIdentityString() );
	}

	public function testGetPagesUsing() {
		$q23 = new ItemId( 'Q23' );
		$q42 = new ItemId( 'Q42' );
		$p11 = new PropertyId( 'P11' );

		$links = $this->getSiteLinkLookup( array(
			'23' => $q23,
		) );

		$titleFactory = $this->getTitleFactory();
		$lookup = $this->getUsageLookup( $links, $titleFactory );

		$actual = $lookup->getPagesUsing( array( $q42, $p11 ) );
		$this->assertInstanceOf( 'Iterator', $actual );

		$actual = iterator_to_array( $actual );
		$this->assertEmpty( $actual );

		$actual = $lookup->getPagesUsing( array( $q23 ), array( EntityUsage::OTHER_USAGE ) );
		$this->assertInstanceOf( 'Iterator', $actual );

		$actual = iterator_to_array( $actual );
		$usages = $actual[0]->getUsages();
		$usage = reset( $usages );
		$this->assertEquals( $q23, $usage->getEntityId() );

		$actual = $lookup->getPagesUsing( array( $q42, $q23, $p11 ) );
		$this->assertInstanceOf( 'Iterator', $actual );

		$actual = iterator_to_array( $actual );
		$this->assertCount( 1, $actual );
		$this->assertInstanceOf( 'Wikibase\Client\Usage\PageEntityUsages', $actual[0] );

		/** @var PageEntityUsages $pageUsageObject */
		/** @var EntityUsage[] $usages */
		$pageUsageObject = reset( $actual );
		$usages = $pageUsageObject->getUsages();

		$this->assertEquals( 23, $pageUsageObject->getPageId() );
		$this->assertCount( 1, $usages );

		$usage = reset( $usages );
		$this->assertEquals( EntityUsage::ALL_USAGE, $usage->getAspect() );
		$this->assertEquals( $q23, $usage->getEntityId() );
	}

	public function testGetUnusedEntities() {
		$q23 = new ItemId( 'Q23' );
		$q42 = new ItemId( 'Q42' );
		$p11 = new PropertyId( 'P11' );

		$links = $this->getSiteLinkLookup( array(
			'23' => $q23,
		) );

		$titleFactory = $this->getTitleFactory();
		$lookup = $this->getUsageLookup( $links, $titleFactory );

		$actual = $lookup->getUnusedEntities( array() );
		$this->assertEmpty( $actual );

		$actual = $lookup->getUnusedEntities( array( $q23 ) );
		$this->assertEmpty( $actual );

		$actual = $lookup->getUnusedEntities( array( $q42, $q23 ) );
		$this->assertCount( 1, $actual );
		$this->assertEquals( $q42, $actual[0] );

		$actual = $lookup->getUnusedEntities( array( $q23, $p11 ) );
		$this->assertCount( 1, $actual );
		$this->assertEquals( $p11, $actual[0] );
	}

	public function testGetPagesUsing_withDeletePage() {
		$itemId = new ItemId( 'Q23' );

		$links = $this->getSiteLinkLookup(
			array(
				'randomkitten2u8!kgxhkl4v3' => $itemId
			)
		);

		$titleFactory = new TitleFactory();
		$lookup = $this->getUsageLookup( $links, $titleFactory );

		$usages = $lookup->getPagesUsing( array( $itemId ), array() );

		$this->assertInstanceOf( 'Iterator', $usages );
	}

}
