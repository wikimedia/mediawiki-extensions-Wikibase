<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use TitleValue;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\EntityLinkTargetEntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * @covers \Wikibase\Lib\Store\EntityLinkTargetEntityIdLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityLinkTargetEntityIdLookupTest extends TestCase {

	private const ITEM_SOURCE_INTERWIKI_PREFIX = 'd';
	private const ITEM_NAMESPACE = 111;

	public function provideTestGetEntityId() {
		yield 'good namespace and parsable ID' => [ new TitleValue( self::ITEM_NAMESPACE, 'Q1' ), new ItemId( 'Q1' ) ];
		yield 'bad namespace and parsable ID' => [ new TitleValue( 222, 'Q1' ), null ];
		yield 'good namespace and not parsable ID' => [ new TitleValue( self::ITEM_NAMESPACE, 'XXYz' ), null ];
		yield 'interwiki special entity page for known entity source' => [
			new TitleValue( 0, 'Special:EntityPage/Q1', '', self::ITEM_SOURCE_INTERWIKI_PREFIX ),
			new ItemId( 'Q1' ),
		];
		yield 'interwiki special entity page for known entity source and no parsable ID' => [
			new TitleValue( 0, 'Special:EntityPage', '', self::ITEM_SOURCE_INTERWIKI_PREFIX ),
			null,
		];
		yield 'interwiki special entity page for unknown entity source' => [
			new TitleValue( 0, 'Special:EntityPage/Q1', '', 'unknown' ),
			null,
		];
	}

	/**
	 * @dataProvider provideTestGetEntityId
	 */
	public function testGetEntityId( $inLinkTarget, $expected ) {
		$lookup = new EntityLinkTargetEntityIdLookup(
			$this->getMockEntityNamespaceLookupWhere111IsItemNamespace(),
			$this->newMockEntityIdParserForId( new ItemId( 'Q1' ) ),
			$this->newMockEntitySourceDefinitions(),
			$this->newMockEntitySource()
		);
		$entityId = $lookup->getEntityId( $inLinkTarget );
		$this->assertEquals( $expected, $entityId );
	}

	public function testGivenLocalLinkParsedToNonLocalEntityType_returnsNull() {
		$entityId = new ItemId( 'Q123' );
		$link = new TitleValue( self::ITEM_NAMESPACE, 'Q123' );
		$entityIdParser = $this->newMockEntityIdParserForId( $entityId );
		$localSource = $this->createMock( DatabaseEntitySource::class );
		$localSource->expects( $this->once() )
			->method( 'getEntityTypes' )
			->willReturn( [ 'mediainfo' ] );

		$this->assertNull(
			( new EntityLinkTargetEntityIdLookup(
				$this->getMockEntityNamespaceLookupWhere111IsItemNamespace(),
				$entityIdParser,
				$this->newMockEntitySourceDefinitions(),
				$localSource
			) )->getEntityId( $link )
		);
	}

	private function getMockEntityNamespaceLookupWhere111IsItemNamespace() {
		$mock = $this->createMock( EntityNamespaceLookup::class );
		$mock->method( 'getEntityType' )->willReturnCallback(
			function ( $namespace ) {
				return $namespace === self::ITEM_NAMESPACE ? 'item' : 'otherEntityType';
			}
		);
		return $mock;
	}

	private function newMockEntityIdParserForId( EntityId $id ) {
		$mock = $this->createMock( EntityIdParser::class );
		$mock->method( 'parse' )->willReturnCallback(
			function ( $toParse ) use ( $id ) {
				if ( $toParse !== $id->getSerialization() ) {
					throw new EntityIdParsingException( 'mock' );
				}
				return $id;
			}
		);

		return $mock;
	}

	private function newMockEntitySourceDefinitions() {
		$itemSource = $this->createMock( DatabaseEntitySource::class );
		$itemSource->method( 'getInterwikiPrefix' )
			->willReturn( self::ITEM_SOURCE_INTERWIKI_PREFIX );

		$sourceDefs = $this->createMock( EntitySourceDefinitions::class );
		$sourceDefs->method( 'getDatabaseSourceForEntityType' )
			->with( Item::ENTITY_TYPE )
			->willReturn( $itemSource );

		return $sourceDefs;
	}

	private function newMockEntitySource() {
		$entitySource = $this->createMock( DatabaseEntitySource::class );
		$entitySource->method( 'getEntityTypes' )
			->willReturn( [ Item::ENTITY_TYPE, Property::ENTITY_TYPE ] );

		return $entitySource;
	}

}
