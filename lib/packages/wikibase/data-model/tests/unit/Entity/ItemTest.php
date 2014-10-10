<?php

namespace Wikibase\Test\Entity;

use DataValues\StringValue;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Diff\ItemDiff;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @covers Wikibase\DataModel\Entity\Item
 * @covers Wikibase\DataModel\Entity\Entity
 *
 * Some tests for this class are located in ItemMultilangTextsTest,
 * ItemNewEmptyTest and ItemNewFromArrayTest.
 *
 * @since 0.1
 *
 * @group Wikibase
 * @group WikibaseItem
 * @group WikibaseDataModel
 * @group WikibaseItemTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Michał Łazowik
 */
class ItemTest extends EntityTest {

	/**
	 * Returns several more or less complex claims
	 *
	 * @return Claim[]
	 */
	public function makeClaims() {
		$id9001 = new EntityIdValue( new ItemId( 'q9001' ) );
		$id1 = new EntityIdValue( new ItemId( 'q1' ) );

		$claims = array();

		$claims[] = new Claim( new PropertyNoValueSnak( 42 ) );

		$claims[] = new Statement(
			new PropertyNoValueSnak( 42 ),
			null,
			new ReferenceList( array(
				new Reference( new SnakList( array(
					new PropertyNoValueSnak( 24 ),
					new PropertyValueSnak( 1, new StringValue( 'onoez' ) ) ) )
				),
				new Reference( new SnakList( array(
					new PropertyValueSnak( 1, $id9001 ) ) )
				)
			) )
		);

		$claims[] = new Claim( new PropertySomeValueSnak( 43 ) );

		$claims[] = new Claim(
			new PropertyNoValueSnak( 42 ),
			new SnakList( array(
				new PropertyNoValueSnak( 42 ),
				new PropertySomeValueSnak( 43 ),
				new PropertyValueSnak( 1, new StringValue( 'onoez' ) ),
			) )
		);

		$claims[] = new Claim(
			new PropertyValueSnak( 2, $id9001 ),
			new SnakList( array(
				new PropertyNoValueSnak( 42 ),
				new PropertySomeValueSnak( 43 ),
				new PropertyValueSnak( 1, new StringValue( 'onoez' ) ),
				new PropertyValueSnak( 2, $id1 ),
			) )
		);

		/**
		 * @var Claim $claim
		 */
		foreach ( $claims as $i => $claim ) {
			$claim->setGuid( "ItemTest\$claim-$i" );
		}

		return $claims;
	}

	/**
	 * @see EntityTest::getNewEmpty
	 *
	 * @since 0.1
	 *
	 * @return Item
	 */
	protected function getNewEmpty() {
		return Item::newEmpty();
	}

	public function testGetId() {
		/**
		 * @var Item $item
		 */
		foreach ( TestItems::getItems() as $item ) {
			$this->assertTrue( is_null( $item->getId() ) || $item->getId() instanceof ItemId );
		}
	}

	public function testSetIdUsingNumber() {
		foreach ( TestItems::getItems() as $item ) {
			$item->setId( 42 );
			$this->assertEquals( new ItemId( 'Q42' ), $item->getId() );
		}
	}

	public function itemProvider() {
		$items = array();

		$items[] = Item::newEmpty();

		$item = Item::newEmpty();
		$item->setDescription( 'en', 'foo' );
		$items[] = $item;

		$item = Item::newEmpty();
		$item->setDescription( 'en', 'foo' );
		$item->setDescription( 'de', 'foo' );
		$item->setLabel( 'en', 'foo' );
		$item->setAliases( 'de', array( 'bar', 'baz' ) );
		$items[] = $item;

		/**
		 * @var Item $item;
		 */
		$item = $item->copy();
		$item->addClaim( new Statement(
			new PropertyNoValueSnak( new PropertyId( 'P42' ) )
		) );
		$items[] = $item;

		$argLists = array();

		foreach ( $items as $item ) {
			$argLists[] = array( $item );
		}

		return $argLists;
	}

	public function diffProvider() {
		$argLists = parent::diffProvider();

		// Addition of a sitelink
		$entity0 = $this->getNewEmpty();
		$entity1 = $this->getNewEmpty();
		$entity1->addSiteLink( new SiteLink( 'enwiki', 'Berlin' ) );

		$expected = new ItemDiff( array(
			'links' => new Diff( array(
				'enwiki' => new Diff( array(
					'page'   => new DiffOpAdd( 'Berlin' )
				), true),
			), true ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );


		// Addition of badges
		$entity0 = $this->getNewEmpty();
		$entity0->addSiteLink(
			new SiteLink(
				'enwiki',
				'Berlin',
				array(
					new ItemId( 'Q42' )
				)
			)
		);
		$entity1 = $this->getNewEmpty();
		$entity1->addSiteLink(
			new SiteLink(
				'enwiki',
				'Berlin',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);

		$expected = new ItemDiff( array(
			'links' => new Diff( array(
				'enwiki' => new Diff( array(
					'badges' => new Diff( array(
						new DiffOpAdd( 'Q3' )
					), false ),
				), true ),
			), true ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );


		// Addition of a sitelink with badges
		$entity0 = $this->getNewEmpty();
		$entity1 = $this->getNewEmpty();
		$entity1->addSiteLink(
			new SiteLink(
				'enwiki',
				'Berlin',
				array(
					new ItemId( 'Q42' )
				)
			)
		);

		$expected = new ItemDiff( array(
			'links' => new Diff( array(
				'enwiki' => new Diff( array(
					'page'   => new DiffOpAdd( 'Berlin' ),
					'badges' => new Diff( array(
						new DiffOpAdd( 'Q42' )
					), false ),
				), true ),
			), true ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );


		// Removal of a sitelink
		$entity0 = $this->getNewEmpty();
		$entity0->addSiteLink( new SiteLink( 'enwiki', 'Berlin' ) );
		$entity1 = $this->getNewEmpty();

		$expected = new ItemDiff( array(
			'links' => new Diff( array(
				'enwiki' => new Diff( array(
					'page'   => new DiffOpRemove( 'Berlin' ),
				), true ),
			), true ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );


		// Removal of badges
		$entity0 = $this->getNewEmpty();
		$entity0->addSiteLink(
			new SiteLink(
				'enwiki',
				'Berlin',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);
		$entity1 = $this->getNewEmpty();
		$entity1->addSiteLink(
			new SiteLink(
				'enwiki',
				'Berlin',
				array(
					new ItemId( 'Q42' )
				)
			)
		);

		$expected = new ItemDiff( array(
			'links' => new Diff( array(
				'enwiki' => new Diff( array(
					'badges' => new Diff( array(
						new DiffOpRemove( 'Q3' )
					), false ),
				), true ),
			), true ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );


		// Removal of sitelink with badges
		$entity0 = $this->getNewEmpty();
		$entity0->addSiteLink(
			new SiteLink(
				'enwiki',
				'Berlin',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);
		$entity1 = $this->getNewEmpty();

		$expected = new ItemDiff( array(
			'links' => new Diff( array(
				'enwiki' => new Diff( array(
					'name'   => new DiffOpRemove( 'Berlin' ),
					'badges' => new Diff( array(
						new DiffOpRemove( 'Q42' ),
						new DiffOpRemove( 'Q3' )
					), false ),
				), true ),
			), true ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );


		// Modification of a sitelink
		$entity0 = $this->getNewEmpty();
		$entity0->addSiteLink(
			new SiteLink(
				'enwiki',
				'Berlin',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);
		$entity1 = $this->getNewEmpty();
		$entity1->addSiteLink(
			new SiteLink(
				'enwiki',
				'Foobar',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);

		$expected = new ItemDiff( array(
			'links' => new Diff( array(
				'enwiki' => new Diff( array(
					'name'   => new DiffOpChange( 'Berlin', 'Foobar' ),
				), true ),
			), true ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );


		// Modification of badges
		$entity0 = $this->getNewEmpty();
		$entity0->addSiteLink(
			new SiteLink(
				'enwiki',
				'Berlin',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);
		$entity1 = $this->getNewEmpty();
		$entity1->addSiteLink(
			new SiteLink(
				'enwiki',
				'Berlin',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q4' )
				)
			)
		);

		$expected = new ItemDiff( array(
			'links' => new Diff( array(
				'enwiki' => new Diff( array(
					'badges' => new Diff( array(
						new DiffOpRemove( 'Q3' ),
						new DiffOpAdd( 'Q4' )
					), false ),
				), true ),
			), true ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );


		// Modification of a sitelink and badges
		$entity0 = $this->getNewEmpty();
		$entity0->addSiteLink(
			new SiteLink(
				'enwiki',
				'Berlin',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);
		$entity1 = $this->getNewEmpty();
		$entity1->addSiteLink(
			new SiteLink(
				'enwiki',
				'Foobar',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q4' )
				)
			)
		);

		$expected = new ItemDiff( array(
			'links' => new Diff( array(
				'enwiki' => new Diff( array(
					'name'   => new DiffOpChange( 'Berlin', 'Foobar' ),
					'badges' => new Diff( array(
						new DiffOpRemove( 'Q3' ),
						new DiffOpAdd( 'Q4' )
					), false ),
				), true ),
			), true ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );

		return $argLists;
	}

	public function testGetSiteLinkWithNonSetSiteId() {
		$item = Item::newEmpty();

		$this->setExpectedException( 'OutOfBoundsException' );
		$item->getSiteLink( 'enwiki' );
	}

	/**
	 * @dataProvider simpleSiteLinkProvider
	 */
	public function testAddSiteLink( SiteLink $siteLink ) {
		$item = Item::newEmpty();

		$item->addSiteLink( $siteLink );

		$this->assertEquals(
			$siteLink,
			$item->getSiteLink( $siteLink->getSiteId() )
		);
	}

	public function simpleSiteLinkProvider() {
		$argLists = array();

		$argLists[] = array(
			new SiteLink(
				'enwiki',
				'Wikidata',
				array(
					new ItemId( 'Q42' )
				)
			)
		);
		$argLists[] = array(
			new SiteLink(
				'nlwiki',
				'Wikidata'
			)
		);
		$argLists[] = array(
			new SiteLink(
				'enwiki',
				'Nyan!',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q149' )
				)
			)
		);
		$argLists[] = array(
			new SiteLink(
				'foo bar',
				'baz bah',
				array(
					new ItemId( 'Q3' ),
					new ItemId( 'Q7' )
				)
			)
		);

		return $argLists;
	}

	/**
	 * @dataProvider simpleSiteLinksProvider
	 */
	public function testGetSiteLinks() {
		$siteLinks = func_get_args();
		$item = Item::newEmpty();

		foreach ( $siteLinks as $siteLink ) {
			$item->addSiteLink( $siteLink );
		}

		$this->assertInternalType( 'array', $item->getSiteLinks() );
		$this->assertEquals( $siteLinks, $item->getSiteLinks() );
	}

	public function simpleSiteLinksProvider() {
		$argLists = array();

		$argLists[] = array();

		$argLists[] = array( new SiteLink( 'enwiki', 'Wikidata', array( new ItemId( 'Q42' ) ) ) );

		$argLists[] = array(
			new SiteLink( 'enwiki', 'Wikidata' ),
			new SiteLink( 'nlwiki', 'Wikidata', array( new ItemId( 'Q3' ) ) )
		);

		$argLists[] = array(
			new SiteLink( 'enwiki', 'Wikidata' ),
			new SiteLink( 'nlwiki', 'Wikidata' ),
			new SiteLink( 'foo bar', 'baz bah', array( new ItemId( 'Q2' ) ) )
		);

		return $argLists;
	}

	public function testHasLinkToSiteForFalse() {
		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'ENWIKI', 'Wikidata', array( new ItemId( 'Q42' ) ) ) );

		$this->assertFalse( $item->hasLinkToSite( 'enwiki' ) );
		$this->assertFalse( $item->hasLinkToSite( 'dewiki' ) );
		$this->assertFalse( $item->hasLinkToSite( 'foo bar' ) );
	}

	public function testHasLinkToSiteForTrue() {
		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'enwiki', 'Wikidata', array( new ItemId( 'Q42' ) ) ) );
		$item->addSiteLink( new SiteLink( 'dewiki', 'Wikidata' ) );
		$item->addSiteLink( new SiteLink( 'foo bar', 'Wikidata' ) );

		$this->assertTrue( $item->hasLinkToSite( 'enwiki' ) );
		$this->assertTrue( $item->hasLinkToSite( 'dewiki' ) );
		$this->assertTrue( $item->hasLinkToSite( 'foo bar' ) );
	}

	public function testNewClaimReturnsStatementWithProvidedMainSnak() {
		/** @var Snak $snak */
		$snak = $this->getMock( 'Wikibase\DataModel\Snak\Snak' );

		$item = Item::newEmpty();
		$statement = $item->newClaim( $snak );

		$this->assertInstanceOf( 'Wikibase\DataModel\Statement\Statement', $statement );
		$this->assertEquals( $snak, $statement->getMainSnak() );
	}

	public function testSetClaims() {
		$item = Item::newEmpty();

		$statement0 = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement0->setGuid( 'TEST$NVS42' );

		$statement1 = new Statement( new PropertySomeValueSnak( 42 ) );
		$statement1->setGuid( 'TEST$SVS42' );

		$statements = array( $statement0, $statement1 );

		$item->setClaims( new Claims( $statements ) );
		$this->assertEquals( count( $statements ), $item->getStatements()->count(), "added some statements" );

		$item->setClaims( new Claims() );
		$this->assertTrue( $item->getStatements()->isEmpty(), "should be empty again" );
	}


	public function testEmptyItemReturnsEmptySiteLinkList() {
		$this->assertTrue( Item::newEmpty()->getSiteLinkList()->isEmpty() );
	}

	public function testAddSiteLinkOverridesOldLinks() {
		$item = Item::newEmpty();

		$item->addSiteLink( new SiteLink( 'kittens', 'foo' ) );

		$newLink = new SiteLink( 'kittens', 'bar' );
		$item->addSiteLink( $newLink );

		$this->assertTrue( $item->getSiteLinkList()->getBySiteId( 'kittens' )->equals( $newLink ) );
	}

	public function testEmptyItemIsEmpty() {
		$this->assertTrue( Item::newEmpty()->isEmpty() );
	}

	public function testItemWithIdIsEmpty() {
		$item = Item::newEmpty();
		$item->setId( 1337 );
		$this->assertTrue( $item->isEmpty() );
	}

	public function testItemWithStuffIsNotEmpty() {
		$item = Item::newEmpty();
		$item->getFingerprint()->setAliasGroup( 'en', array( 'foo' ) );
		$this->assertFalse( $item->isEmpty() );

		$item = Item::newEmpty();
		$item->getSiteLinkList()->addNewSiteLink( 'en', 'o_O' );
		$this->assertFalse( $item->isEmpty() );

		$item = Item::newEmpty();
		$item->addClaim( $this->newStatement() );
		$this->assertFalse( $item->isEmpty() );
	}

	public function testItemWithSitelinksHasSitelinks() {
		$item = Item::newEmpty();
		$item->getSiteLinkList()->addNewSiteLink( 'en', 'foo' );
		$this->assertTrue( $item->hasSiteLinks() );
	}

	public function testItemWithoutSitelinksHasNoSitelinks() {
		$item = Item::newEmpty();
		$this->assertFalse( $item->hasSiteLinks() );
	}

	private function newStatement() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'kittens' );
		return $statement;
	}

	public function testClearRemovesAllButId() {
		$item = Item::newEmpty();

		$item->setId( 42 );
		$item->getFingerprint()->setLabel( 'en', 'foo' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );
		$item->addClaim( $this->newStatement() );

		$item->clear();

		$this->assertEquals( new ItemId( 'Q42' ), $item->getId() );
		$this->assertTrue( $item->getFingerprint()->isEmpty() );
		$this->assertTrue( $item->getSiteLinkList()->isEmpty() );
		$this->assertTrue( $item->getStatements()->isEmpty() );
	}

	public function testCanConstructWithStatementList() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'meh' );

		$statements = new StatementList( array( $statement ) );

		$item = new Item(
			null,
			Fingerprint::newEmpty(),
			new SiteLinkList(),
			$statements
		);

		$this->assertEquals(
			$statements,
			$item->getStatements()
		);
	}

	public function testSetStatements() {
		$item = Item::newEmpty();
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$item->setStatements( new StatementList() );
		$this->assertTrue( $item->getStatements()->isEmpty() );
	}

	public function testGetStatementsReturnsCorrectTypeAfterClear() {
		$item = Item::newEmpty();
		$item->clear();

		$this->assertTrue( $item->getStatements()->isEmpty() );
	}

	public function equalsProvider() {
		$firstItem = Item::newEmpty();
		$secondItem = Item::newEmpty();

		$firstItem->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );
		$secondItem->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		return array(
			array( Item::newEmpty(), Item::newEmpty() ),
			array( $firstItem, $secondItem ),
		);
	}

	/**
	 * @dataProvider equalsProvider
	 */
	public function testEquals( Item $firstItem, Item $secondItem ) {
		$this->assertTrue( $firstItem->equals( $secondItem ) );
		$this->assertTrue( $secondItem->equals( $firstItem ) );
	}

	private function getBaseItem() {
		$item = Item::newEmpty();

		$item->setId( 42 );
		$item->getFingerprint()->setLabel( 'en', 'Same' );
		$item->getFingerprint()->setDescription( 'en', 'Same' );
		$item->getFingerprint()->setAliasGroup( 'en', array( 'Same' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Same' );
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		return $item;
	}

	public function notEqualsProvider() {
		$differentLabel = $this->getBaseItem();
		$differentLabel->getFingerprint()->setLabel( 'en', 'Different' );

		$differentDescription = $this->getBaseItem();
		$differentDescription->getFingerprint()->setDescription( 'en', 'Different' );

		$differentAlias = $this->getBaseItem();
		$differentAlias->getFingerprint()->setAliasGroup( 'en', array( 'Different' ) );

		$differentSiteLink = $this->getBaseItem();
		$differentSiteLink->getSiteLinkList()->removeLinkWithSiteId( 'enwiki' );
		$differentSiteLink->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Different' );

		$differentStatement = $this->getBaseItem();
		$differentStatement->setStatements( new StatementList() );
		$differentStatement->getStatements()->addNewStatement( new PropertyNoValueSnak( 24 ) );

		$item = $this->getBaseItem();

		return array(
			'empty' => array( $item, Item::newEmpty() ),
			'label' => array( $item, $differentLabel ),
			'description' => array( $item, $differentDescription ),
			'alias' => array( $item, $differentAlias ),
			'siteLink' => array( $item, $differentSiteLink ),
			'statement' => array( $item, $differentStatement ),
		);
	}

	/**
	 * @dataProvider notEqualsProvider
	 */
	public function testNotEquals( Item $firstItem, Item $secondItem ) {
		$this->assertFalse( $firstItem->equals( $secondItem ) );
		$this->assertFalse( $secondItem->equals( $firstItem ) );
	}

}
