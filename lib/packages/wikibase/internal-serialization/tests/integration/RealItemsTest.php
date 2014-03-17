<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use Deserializers\Deserializer;

/**
 * @covers Wikibase\InternalSerialization\DeserializerFactory
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class RealItemsTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$this->deserializer = TestDeserializerFactory::newInstance( $this )->newItemDeserializer();
	}

	public function testItemDeserialization() {
		$this->markTestSkipped( 'Find a way to run over real items' );
	}

}