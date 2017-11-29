<?php

namespace Wikibase\Lib\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @covers Wikibase\Lib\EntityTypeDefinitions
 *
 * @group Wikibase
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Kreuz
 */
class EntityTypeDefinitionsTest extends PHPUnit_Framework_TestCase {

	private function getDefinitions() {
		return [
			'foo' => [
				'entity-store-factory-callback' => 'foo-store',
				'entity-revision-lookup-factory-callback' => 'foo-revision-lookup',
				'serializer-factory-callback' => 'foo-serializer',
				'storage-serializer-factory-callback' => 'foo-storage-serializer',
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
				'entity-id-composer-callback' => 'new-composed-foo-id',
				'changeop-deserializer-callback' => 'new-changeop-deserializer-callback',
				'rdf-builder-factory-callback' => 'new-rdf-builder-factory-callback',
				'entity-diff-visualizer-callback' => 'new-entity-diff-visualizer-callback'
			],
			'bar' => [
				'serializer-factory-callback' => 'bar-serializer',
				'deserializer-factory-callback' => 'bar-deserializer',
				'view-factory-callback' => 'bar-view',
				'content-model-id' => 'bar-model',
				'content-handler-factory-callback' => 'bar-handler',
				'entity-factory-callback' => 'new-bar',
			],
			'baz' => []
		];
	}

	public function testGetEntityStoreFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame(
			[ 'foo' => 'foo-store' ],
			$definitions->getEntityStoreFactoryCallbacks()
		);
	}

	public function testGetEntityRevisionLookupFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame(
			[ 'foo' => 'foo-revision-lookup' ],
			$definitions->getEntityRevisionLookupFactoryCallbacks()
		);
	}

	public function testGetSerializerFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertEquals(
			[
				'foo' => 'foo-serializer',
				'bar' => 'bar-serializer'
			],
			$definitions->getSerializerFactoryCallbacks()
		);
	}

	public function testGetStorageSerializerFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertEquals(
			[
				'foo' => 'foo-storage-serializer'
			],
			$definitions->getStorageSerializerFactoryCallbacks()
		);
	}

	public function testGetDeserializerFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertEquals(
			[
				'foo' => 'foo-deserializer',
				'bar' => 'bar-deserializer'
			],
			$definitions->getDeserializerFactoryCallbacks()
		);
	}

	public function testGetChangeFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertEquals(
			[
				'foo' => 'foo-view',
				'bar' => 'bar-view'
			],
			$definitions->getViewFactoryCallbacks()
		);
	}

	public function testGetContentModelIds() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertEquals(
			[
				'foo' => 'foo-model',
				'bar' => 'bar-model'
			],
			$definitions->getContentModelIds()
		);
	}

	public function testGetContentHandlerFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertEquals(
			[
				'foo' => 'foo-handler',
				'bar' => 'bar-handler'
			],
			$definitions->getContentHandlerFactoryCallbacks()
		);
	}

	public function testGetEntityFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertEquals(
			[
				'foo' => 'new-foo',
				'bar' => 'new-bar'
			],
			$definitions->getEntityFactoryCallbacks()
		);
	}

	public function testGetEntityDifferStrategyBuilders() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame( [
			'foo' => 'foo-differ',
		], $definitions->getEntityDifferStrategyBuilders() );
	}

	public function testGetEntityDiffVisualizerCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame(
			[ 'foo' => 'new-entity-diff-visualizer-callback' ],
			$definitions->getEntityDiffVisualizerCallbacks()
		);
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

	public function testGetChangeOpDeserializerCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame(
			[ 'foo' => 'new-changeop-deserializer-callback' ],
			$definitions->getChangeOpDeserializerCallbacks()
		);
	}

	public function testGetRdfBuilderFactoryCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame(
			[ 'foo' => 'new-rdf-builder-factory-callback' ],
			$definitions->getRdfBuilderFactoryCallbacks()
		);
	}

}
