<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Serializers\ItemSerializer as LegacyItemSerializer;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer;
use Wikibase\Repo\Tests\NewItem;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemSerializerTest extends TestCase {

	public function testSerializeClaimsToStatements(): void {
		$item = NewItem::withId( 'Q123' )
			->build();

		$expectedSerializedStatements = [ 'P123' => [] ];

		$legacyItemSerializer = $this->createMock( LegacyItemSerializer::class );
		$legacyItemSerializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $item )
			->willReturn( [
				'id' => $item->getId()->getSerialization(),
				'labels' => [],
				'descriptions' => [],
				'aliases' => [],
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

	public function testFlattenTerms(): void {
		$item = NewItem::withId( 'Q123' )
			->build();

		$legacySerializedLabels = [
			'en' => [ 'language' => 'en', 'value' => 'an-english-label' ],
			'de' => [ 'language' => 'de', 'value' => 'a-german-label' ]
		];
		$legacySerializedDescriptions = [
			'en' => [ 'language' => 'en', 'value' => 'an-english-description' ],
			'de' => [ 'language' => 'de', 'value' => 'a-german-description' ]
		];
		$legacySerializedAliases = [
			'en' => [
				[ 'language' => 'en', 'value' => 'an-english-alias' ],
				[ 'language' => 'en', 'value' => 'another-english-alias' ]
			],
			'de' => [
				[ 'language' => 'de', 'value' => 'a-german-alias' ],
				[ 'language' => 'de', 'value' => 'another-german-alias' ]
			]
		];

		$legacyItemSerializer = $this->createMock( LegacyItemSerializer::class );
		$legacyItemSerializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $item )
			->willReturn( [
				'id' => $item->getId()->getSerialization(),
				'labels' => $legacySerializedLabels,
				'descriptions' => $legacySerializedDescriptions,
				'aliases' => $legacySerializedAliases,
				'claims' => [],
			] );
		$serializer = new ItemSerializer( $legacyItemSerializer );

		$serializedItem = $serializer->serialize( $item );

		$this->assertEquals(
			[
				'en' => $legacySerializedLabels['en']['value'],
				'de' => $legacySerializedLabels['de']['value']
			],
			$serializedItem['labels']
		);

		$this->assertEquals(
			[
				'en' => $legacySerializedDescriptions['en']['value'],
				'de' => $legacySerializedDescriptions['de']['value']
			],
			$serializedItem['descriptions']
		);

		$this->assertEquals(
			[
				'en' => [
					$legacySerializedAliases['en'][0]['value'],
					$legacySerializedAliases['en'][1]['value'],
				],
				'de' => [
					$legacySerializedAliases['de'][0]['value'],
					$legacySerializedAliases['de'][1]['value'],
				],
			],
			$serializedItem['aliases']
		);
	}
}
