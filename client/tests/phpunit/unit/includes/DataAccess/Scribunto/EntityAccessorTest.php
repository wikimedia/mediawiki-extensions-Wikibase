<?php

namespace Wikibase\Client\Tests\Unit\DataAccess\Scribunto;

use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Wikibase\Client\DataAccess\Scribunto\EntityAccessor;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers \Wikibase\Client\DataAccess\Scribunto\EntityAccessor
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class EntityAccessorTest extends \PHPUnit\Framework\TestCase {

	public function testConstructor() {
		$entityAccessor = $this->getEntityAccessor();

		$this->assertInstanceOf( EntityAccessor::class, $entityAccessor );
	}

	private function getEntityAccessor(
		EntityLookup $entityLookup = null,
		UsageAccumulator $usageAccumulator = null,
		$langCode = 'en',
		$logger = null
	) {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $langCode );
		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);
		$entitySerializer = $serializerFactory->newItemSerializer();
		$statementSerializer = $serializerFactory->newStatementListSerializer();

		$propertyDataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$propertyDataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturn( 'structured-cat' );

		$fallbackChainFactory = new LanguageFallbackChainFactory();
		$fallbackChain = $fallbackChainFactory->newFromLanguage( $language );

		return new EntityAccessor(
			WikibaseClient::getEntityIdParser(),
			$entityLookup ?: new MockRepository(),
			$usageAccumulator ?: new HashUsageAccumulator(),
			$entitySerializer,
			$statementSerializer,
			$propertyDataTypeLookup,
			$fallbackChain,
			$language,
			new StaticContentLanguages( [ 'de', $langCode, 'es', 'ja' ] ),
			$logger
		);
	}

	private function hasUsage( $actualUsages, EntityId $entityId, $aspect, $modifier = null ) {
		$usage = new EntityUsage( $entityId, $aspect, $modifier );
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
		$actual = is_array( $entityArr ) ? $entityArr : [];
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
		$entityAccessor = $this->getEntityAccessor( $entityLookup, $usages, 'en' );

		$entityAccessor->getEntity( $itemId->getSerialization() );
		// Only access to specific labels/claims/etc will result in actual usage
		$this->assertFalse(
			$this->hasUsage( $usages->getUsages(), $item->getId(), EntityUsage::ALL_USAGE )
		);
	}

	public function getEntityProvider() {
		$item = $this->getItem();

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$item2 = new Item( new ItemId( 'Q9999' ) );

		return [
			[ [ 'id', 'type', 'descriptions', 'labels', 'sitelinks', 'schemaVersion' ], $item, $entityLookup ],
			[ [], $item2, $entityLookup ],
		];
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
		$renumber = new ReflectionMethod( EntityAccessor::class, 'renumber' );
		$renumber->setAccessible( true );
		$renumber->invokeArgs( $this->getEntityAccessor(), [ &$array ] );

		$this->assertSame( $expected, $array );
	}

	public function provideZeroIndexedArray() {
		return [
			[
				[ 'nyancat' => [ 0 => 'nyan', 1 => 'cat' ] ],
				[ 'nyancat' => [ 1 => 'nyan', 2 => 'cat' ] ],
			],
			[
				[ [ 'a', 'b' ] ],
				[ [ 1 => 'a', 2 => 'b' ] ],
			],
			[
				// Nested arrays
				[ [ 'a', 'b', [ 'c', 'd' ] ] ],
				[ [ 1 => 'a', 2 => 'b', 3 => [ 1 => 'c', 2 => 'd' ] ] ],
			],
			[
				// Already 1-based
				[ [ 1 => 'a', 4 => 'c', 3 => 'b' ] ],
				[ [ 1 => 'a', 4 => 'c', 3 => 'b' ] ],
			],
			[
				// Associative array
				[ [ 'foo' => 'bar', 1337 => 'Wikidata' ] ],
				[ [ 'foo' => 'bar', 1337 => 'Wikidata' ] ],
			],
		];
	}

	public function testFullEntityGetEntityResponse() {
		$item = $this->getItemWithStatements();

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$entityAccessor = $this->getEntityAccessor( $entityLookup );

		$this->assertEquals(
			$this->getItemWithStatementsClientSerialization(),
			$entityAccessor->getEntity( $item->getId()->getSerialization() )
		);
	}

	public function getEntityStatementsProvider() {
		return [
			'Normal Statement, get best Statements' => [
				$this->getItemWithStatementsClaimClientSerialization( false ),
				false,
				'best',
			],
			'Normal Statement, get all Statements' => [
				$this->getItemWithStatementsClaimClientSerialization( false ),
				false,
				'all',
			],
			'Deprecated Statement, get best Statements' => [
				[],
				true,
				'best',
			],
			'Deprecated Statement, get all Statements' => [
				$this->getItemWithStatementsClaimClientSerialization( true ),
				true,
				'all',
			],
		];
	}

	/**
	 * @dataProvider getEntityStatementsProvider
	 */
	public function testGetEntityStatements( $expected, $statementDeprecated, $bestStatementsOnly ) {
		$item = $this->getItemWithStatements( $statementDeprecated );

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$usages = new HashUsageAccumulator();
		$entityAccessor = $this->getEntityAccessor( $entityLookup, $usages );
		$actual = $entityAccessor->getEntityStatements( 'Q123099', 'P65', $bestStatementsOnly );

		$this->assertSameSize( $expected, $actual );
		$this->assertEquals( $expected, $actual );

		$this->assertEquals( [ 'Q123099#C.P65' ], array_keys( $usages->getUsages() ) );
		$this->assertFalse(
			$this->hasUsage( $usages->getUsages(), $item->getId(), EntityUsage::ALL_USAGE ), 'all usage'
		);
	}

	/**
	 * @return Item
	 */
	private function getItemWithStatements( $statementDeprecated = false ) {
		$p65 = new NumericPropertyId( 'P65' );
		$p68 = new NumericPropertyId( 'P68' );

		$item = new Item( new ItemId( 'Q123099' ) );
		$item->setLabel( 'de', 'foo-de' );
		$item->setLabel( 'qu', 'foo-qu' );
		$item->setAliases( 'en', [ 'bar', 'baz' ] );
		$item->setAliases( 'de-formal', [ 'bar', 'baz' ] );
		$item->setDescription( 'en', 'en-desc' );
		$item->setDescription( 'pt', 'ptDesc' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Berlin', [ new ItemId( 'Q333' ) ] );
		$item->getSiteLinkList()->addNewSiteLink( 'zh_classicalwiki', 'User:Addshore' );

		$statement = new Statement( new PropertyValueSnak( $p65, new StringValue( 'snakStringValue' ) ) );

		$statement->getQualifiers()->addSnak( new PropertyValueSnak( $p65, new StringValue( 'string!' ) ) );
		$statement->getQualifiers()->addSnak( new PropertySomeValueSnak( $p65 ) );

		$statement->addNewReference(
			new PropertySomeValueSnak( $p65 ),
			new PropertySomeValueSnak( $p68 )
		);

		$statement->setGuid( 'imaguid' );

		if ( $statementDeprecated ) {
			$statement->setRank( Statement::RANK_DEPRECATED );
		}

		$item->getStatements()->addStatement( $statement );

		return $item;
	}

	private function getItemWithStatementsClientSerialization() {
		return [
			'id' => 'Q123099',
			'type' => 'item',
			'labels' => [
				'de' => [
					'language' => 'de',
					'value' => 'foo-de',
				],
			],
			'descriptions' => [
				'en' => [
					'language' => 'en',
					'value' => 'en-desc',
				],
			],
			'aliases' => [
				'en' => [
					1 => [
						'language' => 'en',
						'value' => 'bar',
					],
					2 => [
						'language' => 'en',
						'value' => 'baz',
					],
				],
			],
			'claims' => $this->getItemWithStatementsClaimClientSerialization(),
			'sitelinks' => [
				'enwiki' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'badges' => [ 1 => 'Q333' ],
				],
				'zh_classicalwiki' => [
					'site' => 'zh_classicalwiki',
					'title' => 'User:Addshore',
					'badges' => [],
				],
			],
			'schemaVersion' => 2,
		];
	}

	private function getItemWithStatementsClaimClientSerialization( $statementDeprecated = false ) {
		return [
			'P65' => [
				1 => [
					'id' => 'imaguid',
					'type' => 'statement',
					'mainsnak' => [
						'snaktype' => 'value',
						'property' => 'P65',
						'datatype' => 'structured-cat',
						'datavalue' => [
							'value' => 'snakStringValue',
							'type' => 'string',
						],
					],
					'qualifiers' => [
						'P65' => [
							1 => [
								'hash' => '3ea0f5404dd4e631780b3386d17a15a583e499a6',
								'snaktype' => 'value',
								'property' => 'P65',
								'datavalue' => [
									'value' => 'string!',
									'type' => 'string',
								],
								'datatype' => 'structured-cat',
							],
							2 => [
								'hash' => 'aa9a5f05e20d7fa5cda7d98371e44c0bdd5de35e',
								'snaktype' => 'somevalue',
								'property' => 'P65',
								'datatype' => 'structured-cat',
							],
						],
					],
					'rank' => $statementDeprecated ? 'deprecated' : 'normal',
					'qualifiers-order' => [
						1 => 'P65',
					],
					'references' => [
						1 => [
							'hash' => '8445204eb74e636cb53687e2f947c268d5186075',
							'snaks' => [
								'P65' => [
									1 => [
										'snaktype' => 'somevalue',
										'property' => 'P65',
										'datatype' => 'structured-cat',
									],
								],
								'P68' => [
									1 => [
										'snaktype' => 'somevalue',
										'property' => 'P68',
										'datatype' => 'structured-cat',
									],
								],
							],
							'snaks-order' => [
								1 => 'P65',
								2 => 'P68',
							],
						],
					],
				],
			],
		];
	}

	public function bestStatementsOnlyProvider() {
		return [
			[ 'best' ],
			[ 'all' ],
		];
	}

	/**
	 * @dataProvider bestStatementsOnlyProvider
	 */
	public function testGetEntityStatementsBadProperty( $bestStatementsOnly ) {
		$entityLookup = new MockRepository();
		$entityAccessor = $this->getEntityAccessor( $entityLookup );

		$this->expectException( InvalidArgumentException::class );
		$entityAccessor->getEntityStatements( 'Q123099', 'ffsdfs', $bestStatementsOnly );
	}

	/**
	 * @dataProvider bestStatementsOnlyProvider
	 */
	public function testGetEntityStatementsMissingStatement( $bestStatementsOnly ) {
		$item = new Item( new ItemId( 'Q123099' ) );
		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );
		$entityAccessor = $this->getEntityAccessor( $entityLookup );

		$actual = $entityAccessor->getEntityStatements( 'Q123099', 'P13', $bestStatementsOnly );

		$this->assertSame( [], $actual );
	}

	public function testEntityExists() {
		$item = new Item( new ItemId( 'Q123099' ) );
		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );
		$entityAccessor = $this->getEntityAccessor( $entityLookup );

		$this->assertSame( true, $entityAccessor->entityExists( 'Q123099' ) );
		$this->assertSame( false, $entityAccessor->entityExists( 'Q1239' ) );
	}

	/**
	 * @dataProvider doubleRedirectMethodProvider
	 */
	public function testGetEntityStatementsLogsDoubleRedirects(
		EntityId $entityId,
		string $methodName,
		array $methodParameters,
		string $lookupMethodCalled
	) {
		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->expects( $this->once() )
			->method( $lookupMethodCalled )
			->with( $entityId )
			->willThrowException( new UnresolvedEntityRedirectException( $entityId, $entityId ) );

		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->once() )
			->method( 'info' )
			->with(
				'Unresolved redirect encountered loading {prefixedEntityId}. This is typically cleaned up asynchronously.',
				[
					'prefixedEntityId' => $entityId->serialize(),
				]
			);

		$entityAccessor = $this->getEntityAccessor(
			$entityLookup,
			null,
			'en',
			$logger
		);

		$entityAccessor->$methodName( ...$methodParameters );
	}

	public function doubleRedirectMethodProvider() {
		$entityId = new ItemId( 'Q1' );
		$serialization = $entityId->getSerialization();
		return [
			[ $entityId, 'getEntityStatements', [ $serialization, 'P1', 'best' ], 'getEntity' ],
			[ $entityId, 'getEntity', [ $serialization ], 'getEntity' ],
			[ $entityId, 'entityExists', [ $serialization ], 'hasEntity' ],
		];
	}

}
