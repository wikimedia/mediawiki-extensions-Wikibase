<?php

namespace Wikibase\Lib\Tests;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityIdComposer;

/**
 * @covers Wikibase\Lib\EntityIdComposer
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class EntityIdComposerTest extends PHPUnit_Framework_TestCase {

	private function getComposer() {
		return new EntityIdComposer( [
			'numeric-item' => function( $uniquePart ) {
				return new ItemId( 'Q' . $uniquePart );
			},
			'custom-item' => function( $uniquePart ) {
				return new ItemId( 'Q100' . $uniquePart );
			},
		] );
	}

	public function invalidConstructorArgumentProvider() {
		$callable = function( $uniquePart ) {
		};

		return [
			[ [ 0 => $callable ] ],
			[ [ '' => $callable ] ],
			[ [ 'string' => null ] ],
			[ [ 'string' => 'not a callable' ] ],
		];
	}

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testGivenInvalidComposer_constructorFails( $composers ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new EntityIdComposer( $composers );
	}

	public function testGivenInvalidCallback_buildFails() {
		$composer = new EntityIdComposer( [
			'item' => function( $uniquePart ) {
				return null;
			},
		] );
		$this->setExpectedException( InvalidArgumentException::class );
		$composer->composeEntityId( 'item', 1 );
	}

	public function validUniquePartProvider() {
		return [
			'Items are always supported' => [ 'item', 1, new ItemId( 'Q1' ) ],
			'Properties are always supported' => [ 'property', 2, new PropertyId( 'P2' ) ],

			'int' => [ 'numeric-item', 3, new ItemId( 'Q3' ) ],
			'float' => [ 'numeric-item', 4.0, new ItemId( 'Q4' ) ],
			'string' => [ 'numeric-item', '5', new ItemId( 'Q5' ) ],

			'custom' => [ 'custom-item', 6, new ItemId( 'Q1006' ) ],
		];
	}

	/**
	 * @dataProvider validUniquePartProvider
	 */
	public function testGivenValidFragment_buildSucceeds( $entityType, $uniquePart, EntityId $expected ) {
		$id = $this->getComposer()->composeEntityId( $entityType, $uniquePart );
		$this->assertEquals( $expected, $id );
	}

	public function invalidUniquePartProvider() {
		return [
			[ null, 1 ],
			[ 'unknown', 2 ],
			[ 'item', null ],
			[ 'item', new ItemId( 'Q4' ) ],
		];
	}

	/**
	 * @dataProvider invalidUniquePartProvider
	 */
	public function testGivenInvalidFragment_buildFails( $entityType, $uniquePart ) {
		$composer = $this->getComposer();
		$this->setExpectedException( InvalidArgumentException::class );
		$composer->composeEntityId( $entityType, $uniquePart );
	}

}
