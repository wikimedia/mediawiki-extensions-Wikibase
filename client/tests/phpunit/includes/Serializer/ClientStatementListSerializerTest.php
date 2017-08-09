<?php

namespace Wikibase\Client\Tests\Serializer;

use DataValues\Serializers\DataValueSerializer;
use PHPUnit_Framework_TestCase;
use Wikibase\Client\Serializer\ClientStatementListSerializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\LanguageFallbackChain;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\Client\Serializer\ClientStatementListSerializer
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class ClientStatementListSerializerTest extends PHPUnit_Framework_TestCase {

	private function newInstance() {
		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		$fallbackChain = $this->getMockBuilder( LanguageFallbackChain::class )
			->disableOriginalConstructor()
			->getMock();
		$fallbackChain->expects( $this->any() )
			->method( 'extractPreferredValue' )
			->will( $this->returnValue( [ 'source' => '<SOURCE>' ] ) );

		$dataTypeLookup = $this->getMock( PropertyDataTypeLookup::class );
		$dataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( '<DATATYPE>' ) );

		return new ClientStatementListSerializer(
			$serializerFactory->newStatementListSerializer(),
			$dataTypeLookup,
			[ 'en' ]
		);
	}

	public function testSerialize() {
		$item = new Item();
		$item->setLabel( 'de', 'German' );
		$item->setDescription( 'de', 'German' );
		$item->setAliases( 'de', [ 'German' ] );
		$item->setAliases( 'en', [ 'English' ] );
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 1 ) );
		$statements = $item->getStatements()->getByPropertyId( new PropertyId( 'P1' ) );

		$instance = $this->newInstance();
		$serialization = $instance->serialize( $statements );

		$expected = [
			'P1' => [ [
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P1',
					'datatype' => '<DATATYPE>',
				],
				'type' => 'statement',
				'rank' => 'normal'
			] ],
		];
		$this->assertSame( $expected, $serialization );
	}

}
