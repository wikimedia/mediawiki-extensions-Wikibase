<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Tests\Integration\Wikibase\InternalSerialization\TestFactoryBuilder;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\EntityDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	public function setUp() {
		$this->deserializer = TestFactoryBuilder::newFactory( $this )->newEntityDeserializer();
	}

	public function testTodo() {
		$this->assertTrue(true); // TODO
	}

}