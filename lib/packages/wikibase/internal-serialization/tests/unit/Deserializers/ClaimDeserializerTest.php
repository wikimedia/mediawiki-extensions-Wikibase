<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use PHPUnit_Framework_TestCase;
use Wikibase\InternalSerialization\Deserializers\ClaimDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\ClaimDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class ClaimDeserializerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$legacyDeserializer = $this->getMock( 'Deserializers\Deserializer' );
		$currentDeserializer = $this->getMock( 'Deserializers\DispatchableDeserializer' );
		$this->deserializer = new ClaimDeserializer( $legacyDeserializer, $currentDeserializer );
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_exceptionIsThrown( $serialization ) {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$this->deserializer->deserialize( $serialization );
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),
			array( 5 ),
		);
	}

}
