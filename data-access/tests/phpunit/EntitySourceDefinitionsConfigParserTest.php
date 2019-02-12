<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\EntitySourceDefinitionsConfigParser;

/**
 * @covers \Wikibase\DataAccess\EntitySourceDefinitionsConfigParser
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceDefinitionsConfigParserTest extends \PHPUnit_Framework_TestCase {

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
		$this->assertEquals( [ 'item', 'property' ], $sources[0]->getEntityTypes() );
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
		$this->assertEquals( [ 'item', 'property' ], $sources[0]->getEntityTypes() );
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

}
