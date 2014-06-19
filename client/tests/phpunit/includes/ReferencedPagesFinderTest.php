<?php

namespace Wikibase\Test;

use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Item;
use Wikibase\ItemChange;
use Wikibase\ReferencedPagesFinder;

/**
 * @covers Wikibase\ReferencedPagesFinder
 *
 * @group Database
 * @group WikibaseClient
 * @group Wikibase
 * @group ReferencedPagesFinder
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ReferencedPagesFinderTest extends \MediaWikiTestCase {

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

		$referencedPagesFinder = new ReferencedPagesFinder(
			$itemUsageIndex,
			$namespaceChecker,
			'enwiki',
			false
		);

		$referencedPages = $this->getPrefixedTitles( $referencedPagesFinder->getPages( $change ) );
		$expectedPages = $this->getPrefixedTitles( $expected );

		$this->assertEquals( $expectedPages, $referencedPages, $message );
	}

	public function getPagesProvider() {
		$berlin = Title::makeTitle( NS_MAIN, 'Berlin' );
		$rome = Title::makeTitle( NS_MAIN, 'Rome' );

		$changeFactory = TestChanges::getEntityChangeFactory();

		$cases = array();

		$cases[] = array(
			array( $berlin ),
			array(),
			$changeFactory->newFromUpdate(
				ItemChange::ADD,
				null,
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Berlin' ) )
			),
			'created item with site link to client'
		);

		$cases[] = array(
			array( $berlin ),
			array(),
			$changeFactory->newFromUpdate(
				ItemChange::UPDATE,
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Berlin' ) ),
				$this->getEmptyItem()
			),
			'removed site link to client'
		);

		$cases[] = array(
			array( $rome ),
			array(),
			$changeFactory->newFromUpdate(
				ItemChange::UPDATE,
				$this->getEmptyItem(),
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Rome' ) )
			),
			'added site link to client'
		);

		$cases[] = array(
			array( $berlin, $rome ),
			array(),
			$changeFactory->newFromUpdate(
				ItemChange::UPDATE,
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Rome' ) ),
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Berlin' ) )
			),
			'changed client site link'
		);

		$cases[] = array(
			array( $rome ),
			array(),
			$changeFactory->newFromUpdate(
				ItemChange::REMOVE,
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Rome' ) ),
				null
			),
			'item connected to client was deleted'
		);

		$cases[] = array(
			array( $rome ),
			array( 'Rome' ),
			$changeFactory->newFromUpdate(
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
			$changeFactory->newFromUpdate(
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
			$changeFactory->newFromUpdate( ItemChange::UPDATE, $connectedItem, $connectedItemWithLabel ),
			'connected item label change'
		);

		$itemWithBadge = $this->getEmptyItem();
		$badges = array( new ItemId( 'Q34' ) );
		$itemWithBadge->addSiteLink( new SimpleSiteLink( 'enwiki', 'Rome', $badges  ) );

		$cases[] = array(
			array(),
			array(),
			$changeFactory->newFromUpdate( ItemChange::UPDATE,
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
			$item->addSiteLink(
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

	private function getPrefixedTitles( array $titles ) {
		return array_map( function( $title ) {
			return $title->getPrefixedText();
		}, $titles );
	}
}
