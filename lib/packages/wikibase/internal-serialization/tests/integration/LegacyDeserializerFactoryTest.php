<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\InternalSerialization\DeserializerFactory;

/**
 * @covers Wikibase\InternalSerialization\LegacyDeserializerFactory
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyDeserializerFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var DeserializerFactory
	 */
	private $factory;

	protected function setUp() {
		$this->factory = TestLegacyDeserializerFactory::newInstance( $this );
	}

	public function testEntityDeserializer() {
		$this->assertEquals(
			Property::newFromType( 'foo' ),
			$this->factory->newEntityDeserializer()->deserialize( array( 'datatype' => 'foo' ) )
		);
	}

	public function testSnakDeserializer() {
		$this->assertEquals(
			new PropertyNoValueSnak( 1 ),
			$this->factory->newSnakDeserializer()->deserialize( array( 'novalue', 1 ) )
		);
	}

}