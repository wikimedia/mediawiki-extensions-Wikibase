<?php
namespace Wikibase\Client\Tests\Usage\Sql;

use PHPUnit_Framework_Assert as Assert;
use Title;
use Wikibase\Client\Tests\Usage\UsageLookupContractTester;
use Wikibase\Client\Usage\EntityUsage;
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
class SiteLinkUsageTrackerTest extends \MediaWikiTestCase {

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
	 *
	 * @return SiteLinkUsageLookup
	 */
	private function getUsageLookup( SiteLinkLookup $siteLinks ) {
		$titleFactory = $this->getMock( 'Wikibase\Client\Store\TitleFactory' );
		$titleFactory->expects( $this->any() )
			->method( 'newFromText' )
			->willReturnCallback( function ( $text ) {
				$id = intval( $text );
				$title = Title::newFromText( $text );
				$title->resetArticleID( $id );
				return $title;
			} );

		return new SiteLinkUsageLookup(
			'testwiki',
			$siteLinks,
			$titleFactory
		);
	}


	public function testGetUsageForPage() {
		$links = $this->getSiteLinkLookup( array(
			'23' => new ItemId( 'Q23' ),
		) );

		$lookup = $this->getUsageLookup( $links );

		$actual = $lookup->getUsageForPage( 42 );
		$this->assertEmpty( $actual );

		$actual = $lookup->getUsageForPage( 23 );
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

		$lookup = $this->getUsageLookup( $links );

		$actual = $lookup->getPagesUsing( array( $q42, $p11 ) );
		$this->assertInstanceOf( 'Iterator', $actual );

		$actual = iterator_to_array( $actual );
		$this->assertEmpty( $actual );


		$actual = $lookup->getPagesUsing( array( $q42, $q23, $p11 ) );
		$this->assertInstanceOf( 'Iterator', $actual );

		$actual = iterator_to_array( $actual );
		$this->assertCount( 1, $actual );
		$this->assertEquals( 23, $actual[0] );
	}

	public function testGetUnusedEntities() {
		$q23 = new ItemId( 'Q23' );
		$q42 = new ItemId( 'Q42' );
		$p11 = new PropertyId( 'P11' );

		$links = $this->getSiteLinkLookup( array(
			'23' => $q23,
		) );

		$lookup = $this->getUsageLookup( $links );

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

}
