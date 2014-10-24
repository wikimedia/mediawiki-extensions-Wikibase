<?php

namespace Wikibase\Test;

use ArrayIterator;
use Title;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\ItemChange;
use Wikibase\Lib\Store\StorageException;
use Wikibase\AffectedPagesFinder;

/**
 * @covers Wikibase\AffectedPagesFinder
 *
 * @group Database
 * @group WikibaseClient
 * @group Wikibase
 * @group AffectedPagesFinder
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class AffectedPagesFinderTest extends \MediaWikiTestCase {

	/**
	 * @return TitleFactory
	 */
	private function getTitleFactory() {
		$titleFactory = $this->getMock( 'Wikibase\Client\Store\TitleFactory' );

		$titleFactory->expects( $this->any() )
			->method( 'newFromID' )
			->will( $this->returnCallback( function( $id ) {
				switch ( $id ) {
					case 1:
						return Title::makeTitle( NS_MAIN, 'Berlin' );
					case 2:
						return Title::makeTitle( NS_MAIN, 'Rome' );
					default:
						throw new StorageException( 'Unknown ID: ' . $id );
				}
			} ) );

		$titleFactory->expects( $this->any() )
			->method( 'newFromText' )
			->will( $this->returnCallback( function( $text, $defaultNs = NS_MAIN ) {
				$title = Title::newFromText( $text, $defaultNs );

				if ( !$title ) {
					throw new StorageException( 'Bad title text: ' . $text );
				}

				return $title;
			} ) );

		return $titleFactory;
	}

	/**
	 * @dataProvider getPagesProvider
	 */
	public function testGetPages( array $expected, array $usage, ItemChange $change, $message ) {
		$usageLookup = $this->getMock( 'Wikibase\Client\Usage\UsageLookup' );

		$usageLookup->expects( $this->any() )
			->method( 'getPagesUsing' )
			->will( $this->returnValue( new ArrayIterator( $usage ) ) );

		$namespaceChecker = $this->getMockBuilder( '\Wikibase\NamespaceChecker' )
							->disableOriginalConstructor()->getMock();

		$namespaceChecker->expects( $this->any() )
			->method( 'isWikibaseEnabled' )
			->will( $this->returnValue( true ) );

		$titleFactory = $this->getTitleFactory();

		$referencedPagesFinder = new AffectedPagesFinder(
			$usageLookup,
			$namespaceChecker,
			$titleFactory,
			'enwiki',
			false
		);

		$referencedPages = $referencedPagesFinder->getPages( $change );
		$referencedPageNames = $this->getPrefixedTitles( $referencedPages );
		$expectedPageNames = $this->getPrefixedTitles( $expected );

		$this->assertEquals( $expectedPageNames, $referencedPageNames, $message );
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
			array( 2 ),
			$changeFactory->newFromUpdate(
				ItemChange::UPDATE,
				$this->getItemWithSiteLinks( array( 'enwiki' => 'Rome' ) ),
				$this->getItemWithSiteLinks( array(
					'enwiki' => 'Rome',
					'itwiki' => 'Roma',
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
			array( 1 ),
			$changeFactory->newFromUpdate( ItemChange::UPDATE, $connectedItem, $connectedItemWithLabel ),
			'connected item label change'
		);

		$itemWithBadge = $this->getEmptyItem();
		$badges = array( new ItemId( 'Q34' ) );
		$itemWithBadge->addSiteLink( new SiteLink( 'enwiki', 'Rome', $badges  ) );

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

	/**
	 * @return Item
	 */
	private function getEmptyItem() {
		$item = Item::newEmpty();
		$item->setId( 2 );

		return $item->copy();
	}

	/**
	 * @param string[] $links
	 *
	 * @return Item
	 */
	private function getItemWithSiteLinks( array $links ) {
		$item = $this->getEmptyItem();

		foreach( $links as $siteId => $page ) {
			$item->addSiteLink(
				new SiteLink( $siteId, $page )
			);
		}

		return $item->copy();
	}

	/**
	 * @param string $languageCode
	 * @param string $label
	 *
	 * @return Item
	 */
	private function getItemWithLabel( $languageCode, $label ) {
		$item = $this->getEmptyItem();
		$item->setLabel( $languageCode, $label );

		return $item;
	}

	/**
	 * @param Title[] $titles
	 *
	 * @return string[]
	 */
	private function getPrefixedTitles( array $titles ) {
		return array_values(
			array_map( function( Title $title ) {
				return $title->getPrefixedText();
			}, $titles )
		);
	}

}
