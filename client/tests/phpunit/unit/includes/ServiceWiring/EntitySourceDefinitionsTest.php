<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceDefinitionsTest extends ServiceWiringTestCase {

	private function mockServices( array $settingsArray ) {
		$this->mockService( 'WikibaseClient.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] ) );
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( $settingsArray ) );
	}

	public function testWithEntitySourcesSettings() {
		$this->mockServices( [
			'entitySources' => [ 'test' => [
				'entityNamespaces' => [ 'my-entity' => 0 ],
				'repoDatabase' => 'repo',
				'baseUri' => 'https://repo.example/',
				'interwikiPrefix' => 'repoiw',
				'rdfNodeNamespacePrefix' => 'rw',
				'rdfPredicateNamespacePrefix' => 'r',
			] ],
		] );

		/** @var EntitySourceDefinitions $entitySourceDefinitions */
		$entitySourceDefinitions = $this->getService( 'WikibaseClient.EntitySourceDefinitions' );

		$this->assertInstanceOf( EntitySourceDefinitions::class, $entitySourceDefinitions );
		$source = $entitySourceDefinitions->getSourceForEntityType( 'my-entity' );
		$this->assertSame( 'repo', $source->getDatabaseName() );
		$this->assertSame( 'https://repo.example/', $source->getConceptBaseUri() );
		$this->assertSame( 'repoiw', $source->getInterwikiPrefix() );
		$this->assertSame( 'rw', $source->getRdfNodeNamespacePrefix() );
		$this->assertSame( 'r', $source->getRdfPredicateNamespacePrefix() );
	}

	public function testWithRepositoriesSettings() {
		$this->mockServices( [
			'repositories' => [ '' => [
				'entityNamespaces' => [ 'my-entity' => 0 ],
				'repoDatabase' => 'repo',
				'baseUri' => 'https://repo.example/',
			] ],
		] );

		/** @var EntitySourceDefinitions $entitySourceDefinitions */
		$entitySourceDefinitions = $this->getService( 'WikibaseClient.EntitySourceDefinitions' );

		$this->assertInstanceOf( EntitySourceDefinitions::class, $entitySourceDefinitions );
		$source = $entitySourceDefinitions->getSourceForEntityType( 'my-entity' );
		$this->assertSame( 'repo', $source->getDatabaseName() );
		$this->assertSame( 'https://repo.example/', $source->getConceptBaseUri() );
		$this->assertSame( '', $source->getInterwikiPrefix() );
		$this->assertSame( 'wd', $source->getRdfNodeNamespacePrefix() );
		$this->assertSame( '', $source->getRdfPredicateNamespacePrefix() );
	}

	public function testWithTopLevelSettings() {
		$this->mockServices( [
			'entityNamespaces' => [ 'my-entity' => 0 ],
			'repoDatabase' => 'repo',
			'repoConceptBaseUri' => 'https://repo.example/',
			'repositories' => [],
		] );

		/** @var EntitySourceDefinitions $entitySourceDefinitions */
		$entitySourceDefinitions = $this->getService( 'WikibaseClient.EntitySourceDefinitions' );

		$this->assertInstanceOf( EntitySourceDefinitions::class, $entitySourceDefinitions );
		$source = $entitySourceDefinitions->getSourceForEntityType( 'my-entity' );
		$this->assertSame( 'repo', $source->getDatabaseName() );
		$this->assertSame( 'https://repo.example/', $source->getConceptBaseUri() );
		$this->assertSame( '', $source->getInterwikiPrefix() );
		$this->assertSame( 'wd', $source->getRdfNodeNamespacePrefix() );
		$this->assertSame( '', $source->getRdfPredicateNamespacePrefix() );
	}

}
