<?php

namespace Wikibase\DataModel\Services\Tests\EntityId;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;

/**
 * @covers \Wikibase\DataModel\Services\EntityId\EntityIdComposer
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class EntityIdComposerTest extends TestCase {

	private function getComposer() {
		return new EntityIdComposer( [
			'numeric-item' => static function( $repositoryName, $uniquePart ) {
				return new ItemId( 'Q' . $uniquePart );
			},
			'custom-item' => static function( $repositoryName, $uniquePart ) {
				return new ItemId( 'Q100' . $uniquePart );
			},
		] );
	}

	public function invalidConstructorArgumentProvider() {
		$callable = static function() {
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
		$this->expectException( InvalidArgumentException::class );
		new EntityIdComposer( $composers );
	}

	public function testGivenInvalidCallback_buildFails() {
		$composer = new EntityIdComposer( [
			'item' => static function() {
				return null;
			},
		] );
		$this->expectException( UnexpectedValueException::class );
		$composer->composeEntityId( '', 'item', 1 );
	}

	public function validUniquePartProvider() {
		return [
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
		$id = $this->getComposer()->composeEntityId( '', $entityType, $uniquePart );
		$this->assertEquals( $expected, $id );
	}

	public function invalidUniquePartProvider() {
		return [
			[ null, 1 ],
			[ 'unknown', 2 ],
			[ 'numeric-item', null ],
			[ 'numeric-item', new ItemId( 'Q4' ) ],
		];
	}

	/**
	 * @dataProvider invalidUniquePartProvider
	 */
	public function testGivenInvalidFragment_buildFails( $entityType, $uniquePart ) {
		$composer = $this->getComposer();
		$this->expectException( InvalidArgumentException::class );
		$composer->composeEntityId( '', $entityType, $uniquePart );
	}

}
