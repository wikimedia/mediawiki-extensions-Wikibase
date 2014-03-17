<?php

namespace Tests\Integration\Wikibase\InternalSerialization\Deserializers;

use DataValues\StringValue;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\InternalSerialization\DeserializerFactory;

/**
 * @covers Wikibase\InternalSerialization\DeserializerFactory
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DeserializerFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var DeserializerFactory
	 */
	private $factory;

	protected function setUp() {
		$dataValueDeserializer = $this->getMock( 'Deserializers\Deserializer' );

		$dataValueDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array( 'type' => 'string', 'value' => 'foo' ) ) )
			->will( $this->returnValue( new StringValue( 'foo' ) ) );

		$this->factory = new DeserializerFactory(
			$dataValueDeserializer,
			new BasicEntityIdParser()
		);
	}

	public function testEntityIdDeserializer() {
		$this->assertEquals(
			new ItemId( 'Q1' ),
			$this->factory->newEntityIdDeserializer()->deserialize( 'Q1' )
		);
	}

	public function testSnakDeserializer() {
		$this->assertEquals(
			new PropertyNoValueSnak( 1 ),
			$this->factory->newSnakDeserializer()->deserialize( array( 'novalue', 1 ) )
		);
	}

	public function testClaimDeserializer() {
		$this->assertEquals(
			new Claim( new PropertyNoValueSnak( 1 ) ),
			$this->factory->newClaimDeserializer()->deserialize(
				array(
					'm' => array( 'novalue', 1 ),
					'q' => array(),
					'g' => null
				)
			)
		);
	}

	public function testSiteLinkListDeserializer() {
		$this->assertEquals(
			new SiteLinkList( array( new SiteLink( 'foo', 'bar' ) ) ),
			$this->factory->newSiteLinkListDeserializer()->deserialize(
				array(
					'foo' => 'bar',
				)
			)
		);
	}

}