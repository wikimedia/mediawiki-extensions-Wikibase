<?php

namespace Wikibase\Client\Tests\Serializer;

use DataValues\Serializers\DataValueSerializer;
use PHPUnit_Framework_TestCase;
use Wikibase\Client\Serializer\ClientEntitySerializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\LanguageFallbackChain;

/**
 * @covers Wikibase\Client\Serializer\ClientEntitySerializer
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class ClientEntitySerializerTest extends PHPUnit_Framework_TestCase {

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
			->will( $this->returnValue( array( 'source' => '<SOURCE>' ) ) );

		$dataTypeLookup = $this->getMock( PropertyDataTypeLookup::class );
		$dataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( '<DATATYPE>' ) );

		return new ClientEntitySerializer(
			$serializerFactory->newItemSerializer(),
			$dataTypeLookup,
			array( 'en' ),
			array( 'en' => $fallbackChain )
		);
	}

	public function testSerialize() {
		$item = new Item();
		$item->setLabel( 'de', 'German' );
		$item->setDescription( 'de', 'German' );
		$item->setAliases( 'de', array( 'German' ) );
		$item->setAliases( 'en', array( 'English' ) );
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 1 ) );

		$instance = $this->newInstance();
		$serialization = $instance->serialize( $item );

		$expected = array(
			'type' => 'item',
			'labels' => array(
				'en' => array( 'source-language' => '<SOURCE>' ),
			),
			'descriptions' => array(
				'en' => array( 'source-language' => '<SOURCE>' ),
			),
			'aliases' => array(
				'en' => array( array( 'language' => 'en', 'value' => 'English' ) ),
			),
			'claims' => array(
				'P1' => array( array(
					'mainsnak' => array(
						'snaktype' => 'novalue',
						'property' => 'P1',
						'datatype' => '<DATATYPE>',
					),
					'type' => 'statement',
					'rank' => 'normal'
				) ),
			),
		);
		$this->assertSame( $expected, $serialization );
	}

}
