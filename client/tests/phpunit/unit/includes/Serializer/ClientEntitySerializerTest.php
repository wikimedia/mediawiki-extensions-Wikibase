<?php

namespace Wikibase\Client\Tests\Unit\Serializer;

use DataValues\Serializers\DataValueSerializer;
use Serializers\Serializer;
use Wikibase\Client\Serializer\ClientEntitySerializer;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * @covers \Wikibase\Client\Serializer\ClientEntitySerializer
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ClientEntitySerializerTest extends \PHPUnit\Framework\TestCase {

	private function newInstance() {
		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		$fallbackChain = $this->createMock( TermLanguageFallbackChain::class );
		$fallbackChain->method( 'extractPreferredValue' )
			->willReturn( [ 'source' => '<SOURCE>' ] );

		$dataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$dataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturn( '<DATATYPE>' );

		return new ClientEntitySerializer(
			$serializerFactory->newItemSerializer(),
			$dataTypeLookup,
			WikibaseClient::getEntityIdParser(),
			[ 'en' ],
			[ 'en' => $fallbackChain ]
		);
	}

	public function testSerialize() {
		$item = new Item();
		$item->setLabel( 'de', 'German' );
		$item->setDescription( 'de', 'German' );
		$item->setAliases( 'de', [ 'German' ] );
		$item->setAliases( 'en', [ 'English' ] );
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 1 ) );

		$instance = $this->newInstance();
		$serialization = $instance->serialize( $item );

		$expected = [
			'type' => 'item',
			'labels' => [
				'en' => [ 'source-language' => '<SOURCE>' ],
			],
			'descriptions' => [
				'en' => [ 'source-language' => '<SOURCE>' ],
			],
			'aliases' => [
				'en' => [ [ 'language' => 'en', 'value' => 'English' ] ],
			],
			'claims' => [
				'P1' => [ [
					'mainsnak' => [
						'snaktype' => 'novalue',
						'property' => 'P1',
						'datatype' => '<DATATYPE>',
					],
					'type' => 'statement',
					'rank' => 'normal',
				] ],
			],
		];
		$this->assertSame( $expected, $serialization );
	}

	public function testSerializeEmptySerialization() {
		$serializer = $this->createMock( Serializer::class );
		$serializer->method( 'serialize' )
			->willReturn( [] );
		$instance = new ClientEntitySerializer(
			$serializer,
			new InMemoryDataTypeLookup(),
			WikibaseClient::getEntityIdParser(),
			[ 'en' ],
			[ 'en' => new TermLanguageFallbackChain( [], $this->createStub( ContentLanguages::class ) ) ]
		);

		$serialization = $instance->serialize( new Item() );

		$this->assertSame( [], $serialization );
	}

}
