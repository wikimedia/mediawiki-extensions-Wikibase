<?php

namespace Wikibase\Lib\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @covers Wikibase\Lib\EntityTypeDefinitions
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityTypeDefinitionsTest extends PHPUnit_Framework_TestCase {

	private function getDefinitions() {
		return array(
			'foo' => array(
				'serializer-factory-callback' => 'foo-serializer',
				'deserializer-factory-callback' => 'foo-deserializer',
				'view-factory-callback' => 'foo-view'
			),
			'bar' => array(
				'serializer-factory-callback' => 'bar-serializer',
				'deserializer-factory-callback' => 'bar-deserializer',
				'view-factory-callback' => 'bar-view'
			),
			'baz' => array()
		);
	}

	public function testGetSerializerFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertEquals(
			array(
				'foo' => 'foo-serializer',
				'bar' => 'bar-serializer'
			),
			$definitions->getSerializerFactoryCallbacks()
		);
	}

	public function testGetDeserializerFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertEquals(
			array(
				'foo' => 'foo-deserializer',
				'bar' => 'bar-deserializer'
			),
			$definitions->getDeserializerFactoryCallbacks()
		);
	}

	public function testGetChangeFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertEquals(
			array(
				'foo' => 'foo-view',
				'bar' => 'bar-view'
			),
			$definitions->getViewFactoryCallbacks()
		);
	}

}
