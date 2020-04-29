<?php

namespace Wikibase\Lib\Tests;

use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @covers \Wikibase\Lib\EntityTypeDefinitions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Kreuz
 */
class EntityTypeDefinitionsTest extends \PHPUnit\Framework\TestCase {

	private function getDefinitions() {
		return [
			'foo' => [
				EntityTypeDefinitions::ENTITY_STORE_FACTORY_CALLBACK => 'foo-store',
				EntityTypeDefinitions::ENTITY_REVISION_LOOKUP_FACTORY_CALLBACK => 'foo-revision-lookup',
				EntityTypeDefinitions::SERIALIZER_FACTORY_CALLBACK => 'foo-serializer',
				EntityTypeDefinitions::STORAGE_SERIALIZER_FACTORY_CALLBACK => 'foo-storage-serializer',
				EntityTypeDefinitions::DESERIALIZER_FACTORY_CALLBACK => 'foo-deserializer',
				EntityTypeDefinitions::VIEW_FACTORY_CALLBACK => 'foo-view',
				EntityTypeDefinitions::CONTENT_MODEL_ID => 'foo-model',
				EntityTypeDefinitions::CONTENT_HANDLER_FACTORY_CALLBACK => 'foo-handler',
				EntityTypeDefinitions::ENTITY_FACTORY_CALLBACK => 'new-foo',
				EntityTypeDefinitions::ENTITY_DIFFER_STRATEGY_BUILDER => 'foo-differ',
				EntityTypeDefinitions::ENTITY_PATCHER_STRATEGY_BUILDER => 'foo-patcher',
				EntityTypeDefinitions::JS_DESERIALIZER_FACTORY_FUNCTION => 'foo-js-deserializer',
				EntityTypeDefinitions::ENTITY_ID_PATTERN => 'foo-id-pattern',
				EntityTypeDefinitions::ENTITY_ID_BUILDER => 'new-foo-id',
				EntityTypeDefinitions::ENTITY_ID_COMPOSER_CALLBACK => 'new-composed-foo-id',
				EntityTypeDefinitions::CHANGEOP_DESERIALIZER_CALLBACK => 'new-changeop-deserializer-callback',
				EntityTypeDefinitions::RDF_BUILDER_FACTORY_CALLBACK => 'new-rdf-builder-factory-callback',
				EntityTypeDefinitions::ENTITY_DIFF_VISUALIZER_CALLBACK => 'new-entity-diff-visualizer-callback',
				EntityTypeDefinitions::ENTITY_SEARCH_CALLBACK => 'foo-search',
				EntityTypeDefinitions::PREFETCHING_TERM_LOOKUP_CALLBACK => 'foo-prefetching-term-lookup-instantiator',
				EntityTypeDefinitions::ARTICLE_ID_LOOKUP_CALLBACK => 'foo-article-id-lookup',
				EntityTypeDefinitions::TITLE_TEXT_LOOKUP_CALLBACK => 'foo-title-text-lookup',
				EntityTypeDefinitions::URL_LOOKUP_CALLBACK => 'foo-url-lookup',
			],
			'bar' => [
				EntityTypeDefinitions::SERIALIZER_FACTORY_CALLBACK => 'bar-serializer',
				EntityTypeDefinitions::DESERIALIZER_FACTORY_CALLBACK => 'bar-deserializer',
				EntityTypeDefinitions::VIEW_FACTORY_CALLBACK => 'bar-view',
				EntityTypeDefinitions::CONTENT_MODEL_ID => 'bar-model',
				EntityTypeDefinitions::CONTENT_HANDLER_FACTORY_CALLBACK => 'bar-handler',
				EntityTypeDefinitions::ENTITY_FACTORY_CALLBACK => 'new-bar',
				EntityTypeDefinitions::ENTITY_SEARCH_CALLBACK => 'bar-search',
				EntityTypeDefinitions::ARTICLE_ID_LOOKUP_CALLBACK => 'bar-article-id-lookup',
				EntityTypeDefinitions::TITLE_TEXT_LOOKUP_CALLBACK => 'bar-title-text-lookup',
				EntityTypeDefinitions::URL_LOOKUP_CALLBACK => 'bar-url-lookup',
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

	public function testGetEntitySearchHelperCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame(
			[ 'foo' => 'foo-search', 'bar' => 'bar-search' ],
			$definitions->getEntitySearchHelperCallbacks()
		);
	}

	public function testGetPrefetchingTermLookupCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame(
			[ 'foo' => 'foo-prefetching-term-lookup-instantiator' ],
			$definitions->getPrefetchingTermLookupCallbacks()
		);
	}

	public function testGetEntityArticleIdLookupCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame(
			[ 'foo' => 'foo-article-id-lookup', 'bar' => 'bar-article-id-lookup' ],
			$definitions->getEntityArticleIdLookupCallbacks()
		);
	}

	public function testGetEntityTitleTextLookupCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame(
			[ 'foo' => 'foo-title-text-lookup', 'bar' => 'bar-title-text-lookup' ],
			$definitions->getEntityTitleTextLookupCallbacks()
		);
	}

	public function testGetEntityUrlLookupCallbacks() {
		$definitions = new EntityTypeDefinitions( $this->getDefinitions() );

		$this->assertSame(
			[ 'foo' => 'foo-url-lookup', 'bar' => 'bar-url-lookup' ],
			$definitions->getEntityUrlLookupCallbacks()
		);
	}

}
