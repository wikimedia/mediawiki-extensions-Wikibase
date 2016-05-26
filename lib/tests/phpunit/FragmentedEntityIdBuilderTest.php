<?php

namespace Wikibase\Lib\Tests;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\FragmentedEntityIdBuilder;

/**
 * @covers Wikibase\Lib\FragmentedEntityIdBuilder
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class FragmentedEntityIdBuilderTest extends PHPUnit_Framework_TestCase {

	private function getBuilder() {
		return new FragmentedEntityIdBuilder( [
			'numeric-item' => function( $fragment ) {
				return new ItemId( 'Q' . $fragment );
			},
			'custom-item' => function( $fragment ) {
				return new ItemId( 'Q100' . $fragment );
			},
		] );
	}

	public function invalidConstructorArgumentProvider() {
		$callable = function( $fragment ) {
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
	public function testGivenInvalidBuilder_constructorFails( $builders ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new FragmentedEntityIdBuilder( $builders );
	}

	public function testGivenInvalidCallback_buildFails() {
		$builder = new FragmentedEntityIdBuilder( [
			'item' => function( $fragment ) {
				return null;
			},
		] );
		$this->setExpectedException( InvalidArgumentException::class );
		$builder->build( 'item', 1 );
	}

	public function validEntityIdFragmentProvider() {
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
	 * @dataProvider validEntityIdFragmentProvider
	 */
	public function testGivenValidFragment_buildSucceeds( $entityType, $fragment, EntityId $expected ) {
		$id = $this->getBuilder()->build( $entityType, $fragment );
		$this->assertEquals( $expected, $id );
	}

	public function invalidEntityIdFragmentProvider() {
		return [
			[ null, 1 ],
			[ 'unknown', 2 ],
			[ 'item', null ],
			[ 'item', new ItemId( 'Q4' ) ],
		];
	}

	/**
	 * @dataProvider invalidEntityIdFragmentProvider
	 */
	public function testGivenInvalidFragment_buildFails( $entityType, $fragment ) {
		$builder = $this->getBuilder();
		$this->setExpectedException( InvalidArgumentException::class );
		$builder->build( $entityType, $fragment );
	}

}
