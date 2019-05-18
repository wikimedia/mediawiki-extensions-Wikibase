<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\EntitySourceDefinitionsConfigParser;

/**
 * @covers \Wikibase\DataAccess\EntitySourceDefinitionsConfigParser
 *
 * @group Wikibase
 * @group NotLegitUnitTest
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceDefinitionsConfigParserTest extends \PHPUnit_Framework_TestCase {

	use \PHPUnit4And6Compat;

	public function testGivenSingleSourceConfig_newDefinitionsFromConfigArrayParsesSourcaData() {
		$config = [
			'local' => [
				'entityNamespaces' => [ 'item' => 100, 'property' => 200 ],
				'repoDatabase' => false,
				'baseUri' => 'http://example.com/entity/',
				'interwikiPrefix' => 'localwiki'
			]
		];

		$parser = new EntitySourceDefinitionsConfigParser();

		$sourceDefinitions = $parser->newDefinitionsFromConfigArray( $config );

		$sources = $sourceDefinitions->getSources();

		$this->assertCount( 1, $sources );
		$this->assertSame( 'local', $sources[0]->getSourceName() );
		$this->assertSame( false, $sources[0]->getDatabaseName() );
		$this->assertSame( [ 'item', 'property' ], $sources[0]->getEntityTypes() );
		$this->assertSame( 'localwiki', $sources[0]->getInterwikiPrefix() );
		$this->assertSame( 'http://example.com/entity/', $sources[0]->getConceptBaseUri() );
		$this->assertEquals( [ 'item' => 100, 'property' => 200 ], $sources[0]->getEntityNamespaceIds() );
		$this->assertEquals( [ 'item' => 'main', 'property' => 'main' ], $sources[0]->getEntitySlotNames() );
	}

	public function testGivenMultipleSourceConfig_newDefinitionsFromConfigArrayParsesAllSourceData() {
		$config = [
			'wikidata' => [
				'entityNamespaces' => [ 'item' => 100, 'property' => 200 ],
				'repoDatabase' => 'wikidatadb',
				'baseUri' => 'http://wikidata.xyz/entity/',
				'interwikiPrefix' => 'wikidata'
			],
			'commons' => [
				'entityNamespaces' => [ 'mediainfo' => '100/mediainfo' ],
				'repoDatabase' => 'commonsdb',
				'baseUri' => 'http://commons.xyz/entity/',
				'interwikiPrefix' => 'commons'
			]
		];

		$parser = new EntitySourceDefinitionsConfigParser();

		$sourceDefinitions = $parser->newDefinitionsFromConfigArray( $config );

		$sources = $sourceDefinitions->getSources();

		$this->assertCount( 2, $sources );

		$this->assertSame( 'wikidata', $sources[0]->getSourceName() );
		$this->assertSame( 'wikidatadb', $sources[0]->getDatabaseName() );
		$this->assertSame( [ 'item', 'property' ], $sources[0]->getEntityTypes() );
		$this->assertSame( 'wikidata', $sources[0]->getInterwikiPrefix() );
		$this->assertSame( 'http://wikidata.xyz/entity/', $sources[0]->getConceptBaseUri() );
		$this->assertEquals( [ 'item' => 100, 'property' => 200 ], $sources[0]->getEntityNamespaceIds() );
		$this->assertEquals( [ 'item' => 'main', 'property' => 'main' ], $sources[0]->getEntitySlotNames() );

		$this->assertSame( 'commons', $sources[1]->getSourceName() );
		$this->assertSame( 'commonsdb', $sources[1]->getDatabaseName() );
		$this->assertEquals( [ 'mediainfo' ], $sources[1]->getEntityTypes() );
		$this->assertSame( 'commons', $sources[1]->getInterwikiPrefix() );
		$this->assertSame( 'http://commons.xyz/entity/', $sources[1]->getConceptBaseUri() );
		$this->assertEquals( [ 'mediainfo' => 100 ], $sources[1]->getEntityNamespaceIds() );
		$this->assertEquals( [ 'mediainfo' => 'mediainfo' ], $sources[1]->getEntitySlotNames() );
	}

	/**
	 * @dataProvider provideInvalidConfig
	 */
	public function testGivenInvalidConfig_throwsException( $config ) {
		$parser = new EntitySourceDefinitionsConfigParser();

		$this->expectException( \InvalidArgumentException::class );

		$parser->newDefinitionsFromConfigArray( $config );
	}

	public function provideInvalidConfig() {
		$validNamespaces = [ 'item' => 100, 'property' => '200/boo' ];
		$validDatabaseName = 'testdb';
		$validBaseUri = 'http://example.com/entity/';
		$validInterwikiPrefix = 'testwiki';
		$validSourceName = 'test';

		yield 'source name not a string' => [
			[
				0 => [
					'entityNamespaces' => $validNamespaces,
					'repoDatabase' => $validDatabaseName,
					'baseUri' => $validBaseUri,
					'interwikiPrefix' => $validInterwikiPrefix,
				]
			]
		];

		yield 'source data not an array' => [
			[
				$validSourceName => 'CONFIG'
			]
		];

		yield 'no repoDatabase key' => [
			[
				$validSourceName => [
					'entityNamespaces' => $validNamespaces,
					'baseUri' => $validBaseUri,
					'interwikiPrefix' => $validInterwikiPrefix,
				]
			]
		];

		yield 'database not a string neither false (int)' => [
			[
				$validSourceName => [
					'entityNamespaces' => $validNamespaces,
					'repoDatabase' => 11,
					'baseUri' => $validBaseUri,
					'interwikiPrefix' => $validInterwikiPrefix,
				]
			]
		];

		yield 'database not a string neither false (true)' => [
			[
				$validSourceName => [
					'entityNamespaces' => $validNamespaces,
					'repoDatabase' => true,
					'baseUri' => $validBaseUri,
					'interwikiPrefix' => $validInterwikiPrefix,
				]
			]
		];

		yield 'no baseUri key' => [
			[
				$validSourceName => [
					'entityNamespaces' => $validNamespaces,
					'repoDatabase' => $validDatabaseName,
					'interwikiPrefix' => $validInterwikiPrefix,
				]
			]
		];

		yield 'Base URI not a string' => [
			[
				$validSourceName => [
					'entityNamespaces' => $validNamespaces,
					'repoDatabase' => $validDatabaseName,
					'baseUri' => 100,
					'interwikiPrefix' => $validInterwikiPrefix,
				]
			]
		];

		yield 'no interwikiPrefix key' => [
			[
				$validSourceName => [
					'entityNamespaces' => $validNamespaces,
					'repoDatabase' => $validDatabaseName,
					'baseUri' => $validBaseUri,
				]
			]
		];

		yield 'interwiki prefix not a string' => [
			[
				$validSourceName => [
					'entityNamespaces' => $validNamespaces,
					'repoDatabase' => $validDatabaseName,
					'baseUri' => $validBaseUri,
					'interwikiPrefix' => 1000,
				]
			]
		];

		yield 'no entityNamespaces key' => [
			[
				$validSourceName => [
					'repoDatabase' => $validDatabaseName,
					'baseUri' => $validBaseUri,
					'interwikiPrefix' => $validInterwikiPrefix,
				]
			]
		];

		yield 'entity namespace definition not an array' => [
			[
				$validSourceName => [
					'entityNamespaces' => 'item',
					'repoDatabase' => $validDatabaseName,
					'baseUri' => $validBaseUri,
					'interwikiPrefix' => $validInterwikiPrefix,
				]
			]
		];

		yield 'entity namespace definition not index by strings' => [
			[
				$validSourceName => [
					'entityNamespaces' => [ 100, '200/boo' ],
					'repoDatabase' => $validDatabaseName,
					'baseUri' => $validBaseUri,
					'interwikiPrefix' => $validInterwikiPrefix,
				]
			]
		];

		yield 'entity namespace not a string neither an integer' => [
			[
				$validSourceName => [
					'entityNamespaces' => [ 'item' => [ 'foo' ] ],
					'repoDatabase' => $validDatabaseName,
					'baseUri' => $validBaseUri,
					'interwikiPrefix' => $validInterwikiPrefix,
				]
			]
		];

		yield 'entity namespace and slot definition in incorrect format' => [
			[
				$validSourceName => [
					'entityNamespaces' => [ 'item' => 'foobarbaz' ],
					'repoDatabase' => $validDatabaseName,
					'baseUri' => $validBaseUri,
					'interwikiPrefix' => $validInterwikiPrefix,
				]
			]
		];

		yield 'entity namespace given with slot but not an integer' => [
			[
				$validSourceName => [
					'entityNamespaces' => [ 'item' => 'foo/main' ],
					'repoDatabase' => $validDatabaseName,
					'baseUri' => $validBaseUri,
					'interwikiPrefix' => $validInterwikiPrefix,
				]
			]
		];
	}

}
