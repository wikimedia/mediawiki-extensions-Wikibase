<?php

namespace Wikibase\DataAccess\Tests;

use InvalidArgumentException;
use MediaWiki\Revision\SlotRecord;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikimedia\AtEase\AtEase;

/**
 * @covers \Wikibase\DataAccess\DatabaseEntitySource
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DatabaseEntitySourceTest extends TestCase {

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 */
	public function testGivenInvalidArg_constructorThrowsException(
		$slotName,
		$databaseName,
		array $entityNamespaceIdsAndSlots,
		$conceptBaseUri,
		$validRdfNodeNamespacePrefix,
		$validRdfPredicateNamespacePrefix,
		$interwikiPrefix
	) {
		$this->expectException( InvalidArgumentException::class );
		AtEase::suppressWarnings();
		new DatabaseEntitySource(
			$slotName,
			$databaseName,
			$entityNamespaceIdsAndSlots,
			$conceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$interwikiPrefix
		);
		AtEase::restoreWarnings();
	}

	public function provideInvalidConstructorArguments() {
		$validSourceName = 'testsource';
		$validDatabaseName = 'somedb';
		$validEntityData = [
			'item' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ],
			'property' => [ 'namespaceId' => 666, 'slot' => 'otherslot' ],
		];
		$validRdfNodeNamespacePrefix = 'wd';
		$validRdfPredicateNamespacePrefix = '';
		$validConceptBaseUri = 'concept:';
		$validInterwikiPrefix = 'test';

		yield 'Source name not a string' => [
			1000,
			$validDatabaseName,
			$validEntityData,
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'database name not a string nor false' => [
			$validSourceName,
			303,
			$validEntityData,
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'database name true' => [
			$validSourceName,
			true,
			$validEntityData,
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'entity type not a string' => [
			$validSourceName,
			$validDatabaseName,
			[ 1 => [ 'namespaceId' => 'foo', 'slot' => SlotRecord::MAIN ] ],
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'entity type namespace and slot data not an array' => [
			$validSourceName,
			$validDatabaseName,
			[ 'item' => 1000 ],
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'entity namespace ID not defined' => [
			$validSourceName,
			$validDatabaseName,
			[ 'item' => [ 'slot' => SlotRecord::MAIN ] ],
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'entity slot name not defined' => [
			$validSourceName,
			$validDatabaseName,
			[ 'item' => [ 'slot' => SlotRecord::MAIN ] ],
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'entity namespace ID not an int' => [
			$validSourceName,
			$validDatabaseName,
			[ 'item' => [ 'namespaceId' => 'foo', 'slot' => SlotRecord::MAIN ] ],
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'entity slot name not a string' => [
			$validSourceName,
			$validDatabaseName,
			[ 'item' => [ 'namespaceId' => 100, 'slot' => 123 ] ],
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'Concept base URI not a string' => [
			$validSourceName,
			$validDatabaseName,
			$validEntityData,
			100,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'RDF node namespace prefix not a string' => [
			$validSourceName,
			$validDatabaseName,
			$validEntityData,
			$validConceptBaseUri,
			100,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'RDF predicate namespace prefix not a string' => [
			$validSourceName,
			$validDatabaseName,
			$validEntityData,
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			100,
			$validInterwikiPrefix,
		];
		yield 'Interwiki prefix not a string' => [
			$validSourceName,
			$validDatabaseName,
			$validEntityData,
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			100,
		];
	}

	public function testGetEntityTypes() {
		$source = new DatabaseEntitySource(
			'test',
			'foodb',
			[
				'item' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ],
				'property' => [ 'namespaceId' => 200, 'slot' => SlotRecord::MAIN ],
			],
			'concept:',
			'wd',
			'',
			'testwiki'
		);

		$this->assertEquals( [ 'item', 'property' ], $source->getEntityTypes() );
	}

	public function testGetEntityNamespaceIds() {
		$source = new DatabaseEntitySource(
			'test',
			'foodb',
			[
				'item' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ],
				'property' => [ 'namespaceId' => 200, 'slot' => SlotRecord::MAIN ],
			],
			'concept:',
			'wd',
			'',
			'testwiki'
		);

		$this->assertEquals( [ 'item' => 100, 'property' => 200 ], $source->getEntityNamespaceIds() );
	}

	public function testGetEntitySlotNames() {
		$source = new DatabaseEntitySource(
			'test',
			'foodb',
			[ 'item' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ], 'property' => [ 'namespaceId' => 200, 'slot' => 'other' ] ],
			'concept:',
			'wd',
			'',
			'testwiki'
		);

		$this->assertEquals( [ 'item' => SlotRecord::MAIN, 'property' => 'other' ], $source->getEntitySlotNames() );
	}

	public function testGetType() {
		$source = new DatabaseEntitySource(
			'test',
			'foodb',
			[ 'item' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ], 'property' => [ 'namespaceId' => 200, 'slot' => 'other' ] ],
			'concept:',
			'wd',
			'',
			'testwiki'
		);
		$this->assertEquals( DatabaseEntitySource::TYPE, $source->getType() );
	}

}
