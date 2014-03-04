<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\InternalSerialization\Deserializers\SnakDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\SnakDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	public function setUp() {
		$this->deserializer = new SnakDeserializer();
	}

	public function testGivenInvalidSerialization_isDeserializerForReturnsFalse() {
		$this->assertCannotDeserialize( null );
	}

	private function assertCannotDeserialize( $serialization ) {
		$this->assertFalse( $this->deserializer->isDeserializerFor( $serialization ) );
	}

}