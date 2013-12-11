<?php

namespace Wikibase\Test;

use ContentHandler;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Item;
use Wikibase\ItemChange;
use Wikibase\ReferencedPagesFinder;
use WikiPage;

/**
 * @covers Wikibase\ReferencedPagesFinder
 *
 * @since 0.5
 *
 *
 * @group Database
 *        ^---- This calls WikiPage::doEditContent in setUp
 * @group WikibaseClient
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ReferencedPagesFinderTest extends \PHPUnit_Framework_TestCase {

	static protected $titles;

	public function setUp() {
		parent::setUp();

		if ( !self::$titles ) {
			self::$titles = array(
				Title::newFromText( 'Berlin' ),
				Title::newFromText( 'Rome' )
			);

			foreach( self::$titles as $title ) {
				$content = ContentHandler::makeContent( 'edit page', $title );
				$page = WikiPage::factory( $title );
				$page->doEditContent( $content, 'edit page' );
			}
		}
	}

	/**
	 * @dataProvider getPagesProvider
	 */
	public function testGetPages( $expected, $usage, $change, $message ) {
		$itemUsageIndex = $this->getMockBuilder( '\Wikibase\ItemUsageIndex' )
							->disableOriginalConstructor()->getMock();

		$itemUsageIndex->expects( $this->any() )
			->method( 'getEntityUsage' )
			->will( $this->returnValue( $usage ) );

		$namespaceChecker = $this->getMockBuilder( '\Wikibase\NamespaceChecker' )
							->disableOriginalConstructor()->getMock();

		$namespaceChecker->expects( $this->any() )
			->method( 'isWikibaseEnabled' )
			->will( $this->returnValue( true ) );

		$referencedPagesFinder = new ReferencedPagesFinder( $itemUsageIndex, $namespaceChecker, 'enwiki' );

		$this->assertEquals( $expected, $referencedPagesFinder->getPages( $change ), $message );
	}

	public function getPagesProvider() {
		$berlin = Title::newFromText( 'Berlin' );
		$rome = Title::newFromText( 'Rome' );

		$cases = array();

		$cases[] = array(
			array( $berlin ),
			array(),
			ItemChange::newFromUpdate(
				ItemChange::ADD,
				null,
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Berlin' ) )
			),
			'created item with site link to client'
		);

		$cases[] = array(
			array( $berlin ),
			array(),
			ItemChange::newFromUpdate(
				ItemChange::UPDATE,
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Berlin' ) ),
				$this->getEmptyItem()
			),
			'removed site link to client'
		);

		$cases[] = array(
			array( $rome ),
			array(),
			ItemChange::newFromUpdate(
				ItemChange::UPDATE,
				$this->getEmptyItem(),
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Rome' ) )
			),
			'added site link to client'
		);

		$cases[] = array(
			array( $berlin, $rome ),
			array(),
			ItemChange::newFromUpdate(
				ItemChange::UPDATE,
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Rome' ) ),
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Berlin' ) )
			),
			'changed client site link'
		);

		$cases[] = array(
			array( $rome ),
			array(),
			ItemChange::newFromUpdate(
				ItemChange::REMOVE,
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Rome' ) ),
				null
			),
			'item connected to client was deleted'
		);

		$cases[] = array(
			array( $rome ),
			array( 'Rome' ),
			ItemChange::newFromUpdate(
				ItemChange::UPDATE,
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Rome' ) ),
				$this->getItemWithSiteLinks( array(
					'enwiki' => 'Rome',
					'itwiki' => 'Roma'
				) )
			),
			'added site link on connected item'
		);

		$cases[] = array(
			array(),
			array(),
			ItemChange::newFromUpdate(
				ItemChange::UPDATE,
				$this->getEmptyItem(),
				$this->getItemWithLabel( 'de', 'Berlin' )
			),
			'unrelated label change'
		);

		$connectedItem = $this->getItemWithSiteLinks( array( 'enwiki' => 'Berlin' ) );
		$connectedItemWithLabel = $this->getItemWithSiteLinks( array( 'enwiki' => 'Berlin' ) );
		$connectedItemWithLabel->setLabel( 'enwiki', 'Berlin' );

		$cases[] = array(
			array( $berlin ),
			array( 'Berlin' ),
			ItemChange::newFromUpdate( ItemChange::UPDATE, $connectedItem, $connectedItemWithLabel ),
			'connected item label change'
		);

		$itemWithBadge = $this->getEmptyItem();
		$badges = array( new ItemId( 'Q34' ) );
		$itemWithBadge->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Rome', $badges  ) );

		$cases[] = array(
			array(),
			array(),
			ItemChange::newFromUpdate( ItemChange::UPDATE,
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Rome' ) ),
				$itemWithBadge ),
			'badge change'
		);

		return $cases;
	}

	private function getEmptyItem() {
		$item = Item::newEmpty();
		$item->setId( 2 );

		return $item->copy();
	}

	private function getItemWithSiteLinks( $links ) {
		$item = $this->getEmptyItem();

		foreach( $links as $siteId => $page ) {
			$item->addSimpleSiteLink(
				new SimpleSiteLink( $siteId, $page )
			);
		}

		return $item->copy();
	}

	private function getItemWithLabel( $lang, $label ) {
		$item = $this->getEmptyItem();
		$item->setLabel( $lang, $label );

		return $item;
	}

}
