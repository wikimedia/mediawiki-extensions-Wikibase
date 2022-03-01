<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Serializers\ItemSerializer as LegacyItemSerializer;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\Tests\NewStatement;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemSerializerTest extends TestCase {

	public function testSerialize(): void {
		$item = NewItem::withId( 'Q123' )
			->andStatement( NewStatement::someValueFor( 'P123' ) )
			->build();

		$expectedSerializedStatements = [ 'P123' => [] ];

		$legacyItemSerializer = $this->createMock( LegacyItemSerializer::class );
		$legacyItemSerializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $item )
			->willReturn( [
				'id' => $item->getId()->getSerialization(),
				'claims' => $expectedSerializedStatements,
			] );
		$serializer = new ItemSerializer( $legacyItemSerializer );

		$serializedItem = $serializer->serialize( $item );

		$this->assertEquals(
			$expectedSerializedStatements,
			$serializedItem['statements']
		);
		$this->assertArrayNotHasKey( 'claims', $serializedItem );
	}

}
