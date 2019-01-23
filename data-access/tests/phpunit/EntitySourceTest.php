<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\EntitySource;

/**
 * @covers \Wikibase\DataAccess\EntitySource
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenInvalidArg_constructorThrowsException(
		$slotName,
		$databaseName,
		array $entityNamespaceIdsAndSlots
	) {
		new EntitySource( $slotName, $databaseName, $entityNamespaceIdsAndSlots );
	}

	public function provideInvalidConstructorArguments() {
		$validSourceName = 'testsource';
		$validDatabaseName = 'somedb';
		$validEntityData = [
			'item' => [ 'namespaceId' => 100, 'slot' => 'main' ],
			'property' => [ 'namespaceId' => 666, 'slot' => 'otherslot' ]
		];

		yield 'Source name not a string' => [
			1000,
			$validDatabaseName,
			$validEntityData,
		];
		yield 'database name not a string nor false' => [
			$validSourceName,
			303,
			$validEntityData,
		];
		yield 'database name true' => [
			$validSourceName,
			true,
			$validEntityData,
		];
		yield 'entity type not a string' => [
			$validSourceName,
			$validDatabaseName,
			[ 1 => [ 'namespaceId' => 'foo', 'slot' => 'main' ] ],
		];
		yield 'entity type namespace and slot data not an array' => [
			$validSourceName,
			$validDatabaseName,
			[ 'item' => 1000 ],
		];
		yield 'entity namespace ID not defined' => [
			$validSourceName,
			$validDatabaseName,
			[ 'item' => [ 'slot' => 'main' ] ],
		];
		yield 'entity slot name not defined' => [
			$validSourceName,
			$validDatabaseName,
			[ 'item' => [ 'slot' => 'main' ] ],
		];
		yield 'entity namespace ID not an int' => [
			$validSourceName,
			$validDatabaseName,
			[ 'item' => [ 'namespaceId' => 'foo', 'slot' => 'main' ] ],
		];
		yield 'entity slot name not a string' => [
			$validSourceName,
			$validDatabaseName,
			[ 'item' => [ 'namespaceId' => 100, 'slot' => 123 ] ],
		];
	}

	public function testGetEntityTypes() {
		$source = new EntitySource(
			'test',
			'foodb',
			[ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ], 'property' => [ 'namespaceId' => 200, 'slot' => 'main' ] ]
		);

		$this->assertEquals( [ 'item', 'property' ], $source->getEntityTypes() );
	}

	public function testGetEntityNamespaceIds() {
		$source = new EntitySource(
			'test',
			'foodb',
			[ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ], 'property' => [ 'namespaceId' => 200, 'slot' => 'main' ] ]
		);

		$this->assertEquals( [ 'item' => 100, 'property' => 200 ], $source->getEntityNamespaceIds() );
	}

	public function testGetEntitySlotNames() {
		$source = new EntitySource(
			'test',
			'foodb',
			[ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ], 'property' => [ 'namespaceId' => 200, 'slot' => 'other' ] ]
		);

		$this->assertEquals( [ 'item' => 'main', 'property' => 'other' ], $source->getEntitySlotNames() );
	}

}
