<?php

namespace Wikibase\Client\Tests\Scribunto;

use Language;
use ReflectionMethod;
use Wikibase\Client\Scribunto\EntityAccessor;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Client\Scribunto\EntityAccessor
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class EntityAccessorTest extends \PHPUnit_Framework_TestCase {

	public function testConstructor() {
		$entityAccessor = $this->getEntityAccessor();

		$this->assertInstanceOf(
			'Wikibase\Client\Scribunto\EntityAccessor',
			$entityAccessor
		);
	}

	private function getEntityAccessor(
		EntityLookup $entityLookup = null,
		UsageAccumulator $usageAccumulator = null
	) {
		$language = new Language( 'en' );

		$propertyDataTypeLookup = $this->getMock( 'Wikibase\DataModel\Entity\PropertyDataTypeLookup' );
		$propertyDataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'structured-cat' ) );

		$fallbackChainFactory = new LanguageFallbackChainFactory();
		$fallbackChain = $fallbackChainFactory->newFromLanguage( $language,
			LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS
		);

		return new EntityAccessor(
			new BasicEntityIdParser(),
			$entityLookup ?: new MockRepository(),
			$usageAccumulator ? $usageAccumulator : new HashUsageAccumulator(),
			$propertyDataTypeLookup,
			$fallbackChain,
			$language,
			array( 'de', 'en', 'es', 'ja' )
		);
	}

	private function hasUsage( $actualUsages, EntityId $entityId, $aspect ) {
		$usage = new EntityUsage( $entityId, $aspect );
		$key = $usage->getIdentityString();
		return isset( $actualUsages[$key] );
	}

	/**
	 * @dataProvider getEntityProvider
	 */
	public function testGetEntity( array $expected, Item $item, EntityLookup $entityLookup ) {
		$prefixedId = $item->getId()->getSerialization();
		$entityAccessor = $this->getEntityAccessor( $entityLookup );

		$entityArr = $entityAccessor->getEntity( $prefixedId );
		$actual = is_array( $entityArr ) ? array_keys( $entityArr ) : array();
		$this->assertEquals( $expected, $actual );
	}

	public function testGetEntity_usage() {
		$item = $this->getItem();
		$itemId = $item->getId();

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$usages = new HashUsageAccumulator();
		$entityAccessor = $this->getEntityAccessor( $entityLookup, $usages );

		$entityAccessor->getEntity( $itemId->getSerialization() );
		$this->assertTrue(
			$this->hasUsage($usages->getUsages(), $item->getId(), EntityUsage::ALL_USAGE ), 'all usage'
		);
	}

	public function getEntityProvider() {
		$item = $this->getItem();

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$item2 = $item->newEmpty();
		$item2->setId( new ItemId( 'Q9999' ) );

		return array(
			array( array( 'id', 'type', 'descriptions', 'labels', 'sitelinks', 'schemaVersion' ), $item, $entityLookup ),
			array( array(), $item2, $entityLookup )
		);
	}

	protected function getItem() {
		$item = new Item( new ItemId( 'Q666' ) );
		$item->setLabel( 'en', 'Beer' );
		$item->setDescription( 'en', 'yummy beverage' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Beer' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Bier' );

		return $item;
	}

	/**
	 * @dataProvider provideZeroIndexedArray
	 */
	public function testZeroIndexArray ( array $array, array $expected ) {
		$renumber = new ReflectionMethod( 'Wikibase\Client\Scribunto\EntityAccessor', 'renumber' );
		$renumber->setAccessible( true );
		$renumber->invokeArgs( $this->getEntityAccessor(), array( &$array ) );

		$this->assertSame( $expected, $array );
	}

	public function provideZeroIndexedArray() {
		return array(
			array(
				array( 'nyancat' => array( 0 => 'nyan', 1 => 'cat' ) ),
				array( 'nyancat' => array( 1 => 'nyan', 2 => 'cat' ) )
			),
			array(
				array( array( 'a', 'b' ) ),
				array( array( 1 => 'a', 2 => 'b' ) )
			),
			array(
				// Nested arrays
				array( array( 'a', 'b', array( 'c', 'd' ) ) ),
				array( array( 1 => 'a', 2 => 'b', 3 => array( 1 => 'c', 2 => 'd' ) ) )
			),
			array(
				// Already 1-based
				array( array( 1 => 'a', 4 => 'c', 3 => 'b' ) ),
				array( array( 1 => 'a', 4 => 'c', 3 => 'b' ) )
			),
			array(
				// Associative array
				array( array( 'foo' => 'bar', 1337 => 'Wikidata' ) ),
				array( array( 'foo' => 'bar', 1337 => 'Wikidata' ) )
			),
		);
	}

}
