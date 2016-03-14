<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use DataValues\StringValue;
use Language;
use ReflectionMethod;
use Wikibase\Client\DataAccess\Scribunto\EntityAccessor;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Client\DataAccess\Scribunto\EntityAccessor
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class EntityAccessorTest extends \PHPUnit_Framework_TestCase {

	public function testConstructor() {
		$entityAccessor = $this->getEntityAccessor();

		$this->assertInstanceOf( EntityAccessor::class, $entityAccessor );
	}

	private function getEntityAccessor(
		EntityLookup $entityLookup = null,
		UsageAccumulator $usageAccumulator = null,
		$langCode = 'en'
	) {
		$language = new Language( $langCode );

		$propertyDataTypeLookup = $this->getMock( PropertyDataTypeLookup::class );
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
			new StaticContentLanguages( array( 'de', $langCode, 'es', 'ja' ) )
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
		$actual = is_array( $entityArr ) ? $entityArr : array();
		$this->assertSameSize( $expected, $actual );

		foreach ( $expected as $expectedKey ) {
			$this->assertArrayHasKey( $expectedKey, $actual );
		}
	}

	public function testGetEntity_usage() {
		$item = $this->getItem();
		$itemId = $item->getId();

		$entityLookup = new MockRepository();

		$usages = new HashUsageAccumulator();
		$entityAccessor = $this->getEntityAccessor( $entityLookup, $usages );

		$entityAccessor->getEntity( $itemId->getSerialization() );
		$this->assertTrue(
			$this->hasUsage( $usages->getUsages(), $item->getId(), EntityUsage::ALL_USAGE ), 'all usage'
		);
	}

	public function getEntityProvider() {
		$item = $this->getItem();

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$item2 = new Item( new ItemId( 'Q9999' ) );

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
	public function testZeroIndexArray( array $array, array $expected ) {
		$renumber = new ReflectionMethod( 'Wikibase\Client\DataAccess\Scribunto\EntityAccessor', 'renumber' );
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

	public function testFullEntityGetEntityResponse() {
		$item = new Item( new ItemId( 'Q123098' ) );

		//Basic
		$item->setLabel( 'de', 'foo-de' );
		$item->setLabel( 'qu', 'foo-qu' );
		$item->setAliases( 'en', array( 'bar', 'baz' ) );
		$item->setAliases( 'de-formal', array( 'bar', 'baz' ) );
		$item->setDescription( 'en', 'en-desc' );
		$item->setDescription( 'pt', 'ptDesc' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Berlin', array( new ItemId( 'Q333' ) ) );
		$item->getSiteLinkList()->addNewSiteLink( 'zh_classicalwiki', 'User:Addshore', array() );

		$snak = new PropertyValueSnak( 65, new StringValue( 'snakStringValue' ) );

		$qualifiers = new SnakList();
		$qualifiers->addSnak( new PropertyValueSnak( 65, new StringValue( 'string!' ) ) );
		$qualifiers->addSnak( new PropertySomeValueSnak( 65 ) );

		$references = new ReferenceList();
		$references->addNewReference( array(
			new PropertySomeValueSnak( 65 ),
			new PropertySomeValueSnak( 68 )
		) );

		$guid = 'imaguid';
		$item->getStatements()->addNewStatement( $snak, $qualifiers, $references, $guid );

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$entityAccessor = $this->getEntityAccessor( $entityLookup, null, 'qug' );

		$expected = array(
			'id' => 'Q123098',
			'type' => 'item',
			'labels' => array(
				'de' => array(
					'language' => 'de',
					'value' => 'foo-de',
				),
			),
			'descriptions' => array(
				'en' => array(
					'language' => 'en',
					'value' => 'en-desc',
				),
			),
			'aliases' => array(
				'en' => array(
					1 => array(
						'language' => 'en',
						'value' => 'bar',
					),
					2 => array(
						'language' => 'en',
						'value' => 'baz',
					),
				),
			),
			'claims' => array(
				'P65' => array(
					1 => array(
						'id' => 'imaguid',
						'type' => 'statement',
						'mainsnak' => array(
							'snaktype' => 'value',
							'property' => 'P65',
							'datatype' => 'structured-cat',
							'datavalue' => array(
								'value' => 'snakStringValue',
								'type' => 'string',
							),
						),
						'qualifiers' => array(
							'P65' => array(
								1 => array(
									'hash' => 'e95e866e7fa1c18bd06dae9b712cb99545107eb8',
									'snaktype' => 'value',
									'property' => 'P65',
									'datavalue' => array(
										'value' => 'string!',
										'type' => 'string',
									),
									'datatype' => 'structured-cat',
								),
								2 => array(
									'hash' => '210b00274bf03247a89de918f15b12142ebf9e56',
									'snaktype' => 'somevalue',
									'property' => 'P65',
									'datatype' => 'structured-cat',
								),
							),
						),
						'rank' => 'normal',
						'qualifiers-order' => array(
							1 => 'P65'
						),
						'references' => array(
							1 => array(
								'hash' => 'bdc5f7185904d6d3219e13b7443571dda8c4bee8',
								'snaks' => array(
									'P65' => array(
										1 => array(
											'snaktype' => 'somevalue',
											'property' => 'P65',
											'datatype' => 'structured-cat',
										)
									),
									'P68' => array(
										1 => array(
											'snaktype' => 'somevalue',
											'property' => 'P68',
											'datatype' => 'structured-cat',
										)
									),
								),
								'snaks-order' => array(
									1 => 'P65',
									2 => 'P68'
								),
							),
						),
					),
				),
			),
			'sitelinks' => array(
				'enwiki' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'badges' => array( 1 => 'Q333' )
				),
				'zh_classicalwiki' => array(
					'site' => 'zh_classicalwiki',
					'title' => 'User:Addshore',
					'badges' => array()
				),
			),
			'schemaVersion' => 2,
		);

		$this->assertEquals( $expected, $entityAccessor->getEntity( 'Q123098' ) );
	}

}
