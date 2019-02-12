<?php

namespace Wikibase\Repo\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\EntitySourceDefinitionsLegacyRepoSettingsParser;
use Wikibase\SettingsArray;

/**
 * @covers \Wikibase\Repo\EntitySourceDefinitionsLegacyRepoSettingsParser
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceDefinitionsLegacyRepoSettingsParserTest extends TestCase {

	public function testGivenDedicatedSettingsDefined_localSourceDefinedByThese() {
		$settings = [
			'changesDatabase' => 'testdb',
			'entityNamespaces' => [ 'item' => 100 ],
			'conceptBaseUri' => 'test://concept/',
			'foreignRepositories' => [],
		];

		$parser = new EntitySourceDefinitionsLegacyRepoSettingsParser();

		$sourceDefinitions = $parser->newDefinitionsFromSettings( new SettingsArray( $settings ) );

		$sources = $sourceDefinitions->getSources();

		$this->assertCount( 1, $sources );

		$this->assertSame( 'local', $sources[0]->getSourceName() );
		$this->assertSame( 'testdb', $sources[0]->getDatabaseName() );
		$this->assertEquals( [ 'item' ], $sources[0]->getEntityTypes() );
		$this->assertEquals( [ 'item' => 100 ], $sources[0]->getEntityNamespaceIds() );
		$this->assertEquals( [ 'item' => 'main' ], $sources[0]->getEntitySlotNames() );
		$this->assertEquals( 'test://concept/', $sources[0]->getConceptBaseUri() );
	}

	public function testGivenForeignRepositoriesSettingsDefined_otherSourceDefinedByThese() {
		$settings = [
			'changesDatabase' => 'testdb',
			'entityNamespaces' => [ 'item' => 100 ],
			'conceptBaseUri' => 'test://concept/',
			'foreignRepositories' => [
				'foo' => [
					'repoDatabase' => 'foodb',
					'entityNamespaces' => [ 'property' => 200 ],
					'baseUri' => 'foo://concept/',
				],
			],
		];

		$parser = new EntitySourceDefinitionsLegacyRepoSettingsParser();

		$sourceDefinitions = $parser->newDefinitionsFromSettings( new SettingsArray( $settings ) );

		$sources = $sourceDefinitions->getSources();

		$this->assertCount( 2, $sources );

		$this->assertSame( 'local', $sources[0]->getSourceName() );
		$this->assertSame( 'testdb', $sources[0]->getDatabaseName() );
		$this->assertEquals( [ 'item' ], $sources[0]->getEntityTypes() );
		$this->assertEquals( [ 'item' => 100 ], $sources[0]->getEntityNamespaceIds() );
		$this->assertEquals( [ 'item' => 'main' ], $sources[0]->getEntitySlotNames() );
		$this->assertEquals( 'test://concept/', $sources[0]->getConceptBaseUri() );

		$this->assertSame( 'foo', $sources[1]->getSourceName() );
		$this->assertSame( 'foodb', $sources[1]->getDatabaseName() );
		$this->assertEquals( [ 'property' ], $sources[1]->getEntityTypes() );
		$this->assertEquals( [ 'property' => 200 ], $sources[1]->getEntityNamespaceIds() );
		$this->assertEquals( [ 'property' => 'main' ], $sources[1]->getEntitySlotNames() );
		$this->assertEquals( 'foo://concept/', $sources[1]->getConceptBaseUri() );
	}

	public function testRepositoryPrefixIsUsedAsInterwikiPrefix() {
		$settings = [
			'changesDatabase' => 'testdb',
			'entityNamespaces' => [ 'item' => 100 ],
			'conceptBaseUri' => 'test://concept/',
			'foreignRepositories' => [
				'foo' => [
					'repoDatabase' => 'foodb',
					'entityNamespaces' => [ 'property' => 200 ],
					'baseUri' => 'foo://concept/',
				],
			],
		];

		$parser = new EntitySourceDefinitionsLegacyRepoSettingsParser();

		$sourceDefinitions = $parser->newDefinitionsFromSettings( new SettingsArray( $settings ) );

		$sources = $sourceDefinitions->getSources();

		$this->assertCount( 2, $sources );

		$this->assertSame( 'local', $sources[0]->getSourceName() );
		$this->assertSame( '', $sources[0]->getInterwikiPrefix() );

		$this->assertSame( 'foo', $sources[1]->getSourceName() );
		$this->assertSame( 'foo', $sources[1]->getInterwikiPrefix() );
	}

	public function testSlotDefinitionsAlongWithNamespaceIdAreHandled() {
		$settings = [
			'changesDatabase' => 'testdb',
			'entityNamespaces' => [ 'item' => '100/itemz' ],
			'conceptBaseUri' => 'test://concept/',
			'foreignRepositories' => [],
		];

		$parser = new EntitySourceDefinitionsLegacyRepoSettingsParser();

		$sourceDefinitions = $parser->newDefinitionsFromSettings( new SettingsArray( $settings ) );

		$sources = $sourceDefinitions->getSources();

		$this->assertCount( 1, $sources );

		$this->assertSame( 'local', $sources[0]->getSourceName() );
		$this->assertEquals( [ 'item' ], $sources[0]->getEntityTypes() );
		$this->assertEquals( [ 'item' => 100 ], $sources[0]->getEntityNamespaceIds() );
		$this->assertEquals( [ 'item' => 'itemz' ], $sources[0]->getEntitySlotNames() );
	}

}
