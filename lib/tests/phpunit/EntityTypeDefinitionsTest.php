<?php

namespace Wikibase\Lib\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @covers Wikibase\Lib\EntityTypeDefinitions
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Mättig
 */
class EntityTypeDefinitionsTest extends PHPUnit_Framework_TestCase {

	private function getDefinitions() {
		return array(
			'foo' => array(
				'serializer-factory-callback' => 'foo-serializer',
				'deserializer-factory-callback' => 'foo-deserializer',
				'view-factory-callback' => 'foo-view',
				'content-model-id' => 'foo-model',
				'content-handler-factory-callback' => 'foo-handler',
				'entity-factory-callback' => 'new-foo',
				'entity-differ-strategy-builder' => 'foo-differ',
				'entity-patcher-strategy-builder' => 'foo-patcher',
				'js-deserializer-factory-function' => 'foo-js-deserializer',
				'entity-id-pattern' => 'foo-id-pattern',
				'entity-id-builder' => 'new-foo-id',
				'entity-id-composer' => 'new-composed-foo-id',
			),
			'bar' => array(
				'serializer-factory-callback' => 'bar-serializer',
				'deserializer-factory-callback' => 'bar-deserializer',
				'view-factory-callback' => 'bar-view',
				'content-model-id' => 'bar-model',
				'content-handler-factory-callback' => 'bar-handler',
				'entity-factory-callback' => 'new-bar',
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

	public function testGetContentModelIds() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertEquals(
			array(
				'foo' => 'foo-model',
				'bar' => 'bar-model'
			),
			$definitions->getContentModelIds()
		);
	}

	public function testGetContentHandlerFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertEquals(
			array(
				'foo' => 'foo-handler',
				'bar' => 'bar-handler'
			),
			$definitions->getContentHandlerFactoryCallbacks()
		);
	}

	public function testGetEntityFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertEquals(
			array(
				'foo' => 'new-foo',
				'bar' => 'new-bar'
			),
			$definitions->getEntityFactoryCallbacks()
		);
	}

	public function testGetEntityDifferStrategyBuilders() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame( [
			'foo' => 'foo-differ',
		], $definitions->getEntityDifferStrategyBuilders() );
	}

	public function testGetEntityPatcherStrategyBuilders() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame( [
			'foo' => 'foo-patcher',
		], $definitions->getEntityPatcherStrategyBuilders() );
	}

	public function testGetJsDeserializerFactoryFunctions() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame( [
			'foo' => 'foo-js-deserializer',
		], $definitions->getJsDeserializerFactoryFunctions() );
	}

	public function testGetEntityIdBuilders() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame( [
			'foo-id-pattern' => 'new-foo-id',
		], $definitions->getEntityIdBuilders() );
	}

	public function testGetEntityIdFragmentBuilders() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame( [
			'foo' => 'new-composed-foo-id',
		], $definitions->getEntityIdComposers() );
	}

}
