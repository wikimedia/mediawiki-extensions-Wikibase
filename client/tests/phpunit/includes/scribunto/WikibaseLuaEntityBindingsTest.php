<?php

namespace Wikibase\Client\Tests\Scribunto;

use Language;
use Wikibase\Client\Scribunto\WikibaseLuaEntityBindings;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Client\Scribunto\WikibaseLuaEntityBindings
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLuaEntityBindingsTest extends \PHPUnit_Framework_TestCase {

	public function testConstructor() {
		$wikibaseLuaEntityBindings = $this->getWikibaseLuaEntityBindings();

		$this->assertInstanceOf(
			'Wikibase\Client\Scribunto\WikibaseLuaEntityBindings',
			$wikibaseLuaEntityBindings
		);
	}

	private function getWikibaseLuaEntityBindings(
		EntityLookup $entityLookup = null,
		UsageAccumulator $usageAccumulator = null
	) {
		$language = new Language( 'en' );

		return new WikibaseLuaEntityBindings(
			$this->getSnakFormatter(),
			$entityLookup ?: new MockRepository(),
			$usageAccumulator ?: new HashUsageAccumulator(),
			'enwiki',
			$language
		);
	}

	/**
	 * @return Item
	 */
	private function getItem() {
		$propertyId = new PropertyId( 'P123456' );
		$snak = new PropertyValueSnak( $propertyId, new EntityIdValue( new ItemId( 'Q11' ) ));
		$statement = new Statement( new Claim( $snak ) );
		$statement->setGuid( 'gsdfgsadg' );

		$item = Item::newEmpty();
		$item->addClaim( $statement );

		return $item;
	}

	/**
	 * @param Entity|null $entity
	 *
	 * @return EntityLookup
	 */
	private function getEntityLookup( Entity $entity = null ) {
		$entityLookup = $this->getMock( 'Wikibase\Lib\Store\EntityLookup' );

		$entityLookup->expects( $this->any() )->method( 'getEntity' )
			->will( $this->returnValue( $entity ) );

		return $entityLookup;
	}

	/**
	 * @return SnakFormatter
	 */
	private function getSnakFormatter() {
		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$snakFormatter->expects( $this->any() )->method( 'formatSnak' )
			->will( $this->returnValue( 'Snak snak snak' ) );

		return $snakFormatter;
	}


	public function testFormatPropertyValues() {
		$item = $this->getItem();

		$entityLookup = $this->getEntityLookup( $item );
		$usageAccumulator = new HashUsageAccumulator();
		$wikibaseLuaEntityBindings = $this->getWikibaseLuaEntityBindings( $entityLookup, $usageAccumulator );

		$ret = $wikibaseLuaEntityBindings->formatPropertyValues( 'Q1', 'P123456' );

		$this->assertSame( 'Snak snak snak', $ret );

		$expectedUsage = new EntityUsage( new ItemId( 'Q11' ), EntityUsage::LABEL_USAGE );
		$usages = $usageAccumulator->getUsages();
		$this->assertArrayHasKey( $expectedUsage->getIdentityString(), $usages );
	}

	public function testFormatPropertyValuesNoProperty() {
		$entityLookup = $this->getEntityLookup( Item::newEmpty() );

		$wikibaseLuaEntityBindings = $this->getWikibaseLuaEntityBindings( $entityLookup );
		$ret = $wikibaseLuaEntityBindings->formatPropertyValues( 'Q2', 'P123456' );

		$this->assertSame( '', $ret );
	}

	public function testFormatPropertyValuesNoEntity() {
		$entityLookup = $this->getEntityLookup();

		$wikibaseLuaEntityBindings = $this->getWikibaseLuaEntityBindings( $entityLookup );
		$ret = $wikibaseLuaEntityBindings->formatPropertyValues( 'Q3', 'P123456' );

		$this->assertSame( '', $ret );
	}

	public function testGetGlobalSiteId() {
		$wikibaseLuaEntityBindings = $this->getWikibaseLuaEntityBindings();

		$this->assertEquals( 'enwiki', $wikibaseLuaEntityBindings->getGlobalSiteId() );
	}

}
