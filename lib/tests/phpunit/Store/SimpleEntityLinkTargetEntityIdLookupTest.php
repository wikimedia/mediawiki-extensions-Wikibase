<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use TitleValue;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\SimpleEntityLinkTargetEntityIdLookup;

/**
 * @covers \Wikibase\Lib\Store\SimpleEntityLinkTargetEntityIdLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SimpleEntityLinkTargetEntityIdLookupTest extends TestCase {

	public function provideTestGetEntityId() {
		yield 'good namespace and parsable ID' => [ new TitleValue( 111, 'Q1' ), new ItemId( 'Q1' ) ];
		yield 'bad namespace and parsable ID' => [ new TitleValue( 222, 'Q1' ), RuntimeException::class ];
		yield 'good namespace and not parsable ID' => [ new TitleValue( 111, 'XXYz' ), null ];
	}

	/**
	 * @dataProvider provideTestGetEntityId
	 */
	public function testGetEntityId( $inLinkTarget, $expected ) {
		$lookup = new SimpleEntityLinkTargetEntityIdLookup(
			$this->getMockEntityNamespaceLookupWhere111IsItemNamespace(),
			$this->getMockEntityIdParserWhereQ1IsParseable()
		);
		if ( is_string( $expected ) ) {
			$this->expectException( $expected );
		}
		$entityId = $lookup->getEntityId( $inLinkTarget );
		$this->assertEquals( $expected, $entityId );
	}

	private function getMockEntityNamespaceLookupWhere111IsItemNamespace() {
		$mock = $this->createMock( EntityNamespaceLookup::class );
		$mock->expects( $this->any() )->method( 'getEntityType' )->willReturnCallback(
			function ( $namespace ) {
				return $namespace === 111 ? 'item' : 'otherEntityType';
			}
		);
		return $mock;
	}

	private function getMockEntityIdParserWhereQ1IsParseable() {
		$mock = $this->createMock( EntityIdParser::class );
		$mock->expects( $this->any() )->method( 'parse' )->willReturnCallback(
			function ( $toParse ) {
				if ( $toParse !== 'Q1' ) {
					throw new EntityIdParsingException( 'mock' );
				}
				return new ItemId( 'Q1' );
			}
		);
		return $mock;
	}

}
