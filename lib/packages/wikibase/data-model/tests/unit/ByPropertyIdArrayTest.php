<?php

namespace Wikibase\DataModel\Tests;

use ArrayObject;
use DataValues\StringValue;
use PHPUnit_Framework_TestCase;
use ReflectionClass;
use ReflectionMethod;
use stdClass;
use Wikibase\DataModel\ByPropertyIdArray;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\PropertyIdProvider;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\ByPropertyIdArray
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group ByPropertyIdArrayTest
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */
class ByPropertyIdArrayTest extends PHPUnit_Framework_TestCase {

	public function testGivenNull_constructorAssumesEmptyArray() {
		$indexedArray = new ByPropertyIdArray( null );

		$this->assertSame( 0, $indexedArray->count() );
	}

	public function testGivenNonTraversableObject_constructorDoesNotCastObjectToArray() {
		$object = new stdClass();
		$object->property = true;

		$this->setExpectedException( 'InvalidArgumentException' );
		new ByPropertyIdArray( $object );
	}

	public function testArrayObjectNotConstructedFromObject() {
		$statement1 = new Statement( new PropertyNoValueSnak( 1 ) );
		$statement1->setGuid( '1' );
		$statement2 = new Statement( new PropertyNoValueSnak( 2 ) );
		$statement2->setGuid( '2' );

		$object = new ArrayObject();
		$object->append( $statement1 );

		$byPropertyIdArray = new ByPropertyIdArray( $object );
		// According to the documentation append() "cannot be called when the ArrayObject was
		// constructed from an object." This test makes sure it was not constructed from an object.
		$byPropertyIdArray->append( $statement2 );

		$this->assertCount( 2, $byPropertyIdArray );
	}

	/**
	 * Returns an accessible ReflectionMethod of ByPropertyIdArray.
	 *
	 * @param string $methodName
	 * @return ReflectionMethod
	 */
	protected static function getMethod( $methodName ) {
		$class = new ReflectionClass( 'Wikibase\DataModel\ByPropertyIdArray' );
		$method = $class->getMethod( $methodName );
		$method->setAccessible( true );
		return $method;
	}

	public function listProvider() {
		$lists = array();

		$snaks = array(
			new PropertyNoValueSnak( new PropertyId( 'P42' ) ),
			new PropertySomeValueSnak( new PropertyId( 'P42' ) ),
			new PropertySomeValueSnak( new PropertyId( 'P10' ) ),
			new PropertyValueSnak( new PropertyId( 'P10' ), new StringValue( 'ohi' ) ),
			new PropertySomeValueSnak( new PropertyId( 'P1' ) ),
		);

		$lists[] = $snaks;

		$lists[] = array_map(
			function( Snak $snak ) {
				return new Statement( $snak );
			},
			$snaks
		);

		$argLists = array();

		foreach ( $lists as $list ) {
			$argLists[] = array( $list );
		}

		return $argLists;
	}

	/**
	 * @return Statement[]
	 */
	protected function statementsProvider() {
		$snaks = array(
			new PropertyNoValueSnak( new PropertyId( 'P1' ) ),
			new PropertySomeValueSnak( new PropertyId( 'P1' ) ),
			new PropertyValueSnak( new PropertyId( 'P2' ), new StringValue( 'a' ) ),
			new PropertyValueSnak( new PropertyId( 'P2' ), new StringValue( 'b' ) ),
			new PropertyValueSnak( new PropertyId( 'P2' ), new StringValue( 'c' ) ),
			new PropertySomeValueSnak( new PropertyId( 'P3' ) ),
		);

		return array_map(
			function( Snak $snak ) {
				return new Statement( $snak );
			},
			$snaks
		);
	}

	/**
	 * @dataProvider listProvider
	 * @param PropertyIdProvider[] $objects
	 */
	public function testGetIds( array $objects ) {
		$indexedArray = new ByPropertyIdArray( $objects );

		$expected = array();

		foreach ( $objects as $object ) {
			$expected[] = $object->getPropertyId();
		}

		$expected = array_unique( $expected );

		$indexedArray->buildIndex();

		$this->assertEquals(
			array_values( $expected ),
			array_values( $indexedArray->getPropertyIds() )
		);
	}

	/**
	 * @dataProvider listProvider
	 * @param PropertyIdProvider[] $objects
	 */
	public function testGetById( array $objects ) {
		$indexedArray = new ByPropertyIdArray( $objects );

		$ids = array();

		foreach ( $objects as $object ) {
			$ids[] = $object->getPropertyId();
		}

		$ids = array_unique( $ids );

		$indexedArray->buildIndex();

		$allObtainedObjects = array();

		foreach ( $ids as $id ) {
			foreach ( $indexedArray->getByPropertyId( $id ) as $obtainedObject ) {
				$allObtainedObjects[] = $obtainedObject;
				$this->assertEquals( $id, $obtainedObject->getPropertyId() );
			}
		}

		$this->assertEquals(
			array_values( $objects ),
			array_values( $allObtainedObjects )
		);
	}

	/**
	 * @dataProvider listProvider
	 * @param PropertyIdProvider[] $objects
	 */
	public function testRemoveObject( array $objects ) {
		$lastIndex = count( $objects ) - 1;
		$indexedArray = new ByPropertyIdArray( $objects );
		$indexedArray->buildIndex();

		$removeObject = self::getMethod( 'removeObject' );

		$removeObject->invokeArgs( $indexedArray, array( $objects[0] ) );
		$removeObject->invokeArgs( $indexedArray, array( $objects[$lastIndex] ) );

		$this->assertFalse(
			in_array( $objects[0], $indexedArray->getByPropertyId( $objects[0]->getPropertyId() ) )
		);

		$this->assertFalse( in_array(
			$objects[$lastIndex],
			$indexedArray->getByPropertyId( $objects[1]->getPropertyId() )
		) );

		$this->assertFalse( in_array( $objects[0], $indexedArray->toFlatArray() ) );
		$this->assertFalse( in_array( $objects[$lastIndex], $indexedArray->toFlatArray() ) );
	}

	public function testGetByNotSetIdThrowsException() {
		$indexedArray = new ByPropertyIdArray();
		$indexedArray->buildIndex();

		$this->setExpectedException( 'OutOfBoundsException' );

		$indexedArray->getByPropertyId( PropertyId::newFromNumber( 9000 ) );
	}

	public function testNotBuildExceptionIsThrownForByPropertyId() {
		$indexedArray = new ByPropertyIdArray();

		$this->setExpectedException( 'RuntimeException' );
		$indexedArray->getByPropertyId( PropertyId::newFromNumber( 9000 ) );
	}

	public function testNotBuildExceptionIsThrownForGetPropertyIds() {
		$indexedArray = new ByPropertyIdArray();

		$this->setExpectedException( 'RuntimeException' );
		$indexedArray->getPropertyIds();
	}

	/**
	 * @dataProvider listProvider
	 */
	public function testGetFlatArrayIndexOfObject( array $objects ) {
		$indexedArray = new ByPropertyIdArray( $objects );
		$indexedArray->buildIndex();

		$indicesSource = array();
		$indicesDestination = array();

		$i = 0;
		foreach ( $objects as $object ) {
			$indicesSource[$i++] = $object;
			$indicesDestination[$indexedArray->getFlatArrayIndexOfObject( $object )] = $object;
		}

		$this->assertEquals( $indicesSource, $indicesDestination );
	}

	/**
	 * @dataProvider listProvider
	 */
	public function testToFlatArray( array $objects ) {
		$indexedArray = new ByPropertyIdArray( $objects );
		$indexedArray->buildIndex();

		$this->assertEquals( $objects, $indexedArray->toFlatArray() );
	}

	public function moveProvider() {
		$c = $this->statementsProvider();
		$argLists = array();

		$argLists[] = array( $c, $c[0], 0, $c );
		$argLists[] = array( $c, $c[0], 1, array( $c[1], $c[0], $c[2], $c[3], $c[4], $c[5] ) );
		$argLists[] = array( $c, $c[0], 2, array( $c[1], $c[0], $c[2], $c[3], $c[4], $c[5] ) );
		$argLists[] = array( $c, $c[0], 3, array( $c[2], $c[3], $c[4], $c[1], $c[0], $c[5] ) );
		$argLists[] = array( $c, $c[0], 4, array( $c[2], $c[3], $c[4], $c[1], $c[0], $c[5] ) );
		$argLists[] = array( $c, $c[0], 5, array( $c[2], $c[3], $c[4], $c[1], $c[0], $c[5] ) );
		$argLists[] = array( $c, $c[0], 6, array( $c[2], $c[3], $c[4], $c[5], $c[1], $c[0] ) );

		$argLists[] = array( $c, $c[1], 0, array( $c[1], $c[0], $c[2], $c[3], $c[4], $c[5] ) );
		$argLists[] = array( $c, $c[1], 1, $c );
		$argLists[] = array( $c, $c[1], 2, $c );
		$argLists[] = array( $c, $c[1], 3, array( $c[2], $c[3], $c[4], $c[0], $c[1], $c[5] ) );
		$argLists[] = array( $c, $c[1], 4, array( $c[2], $c[3], $c[4], $c[0], $c[1], $c[5] ) );
		$argLists[] = array( $c, $c[1], 5, array( $c[2], $c[3], $c[4], $c[0], $c[1], $c[5] ) );
		$argLists[] = array( $c, $c[1], 6, array( $c[2], $c[3], $c[4], $c[5], $c[0], $c[1] ) );

		$argLists[] = array( $c, $c[2], 0, array( $c[2], $c[3], $c[4], $c[0], $c[1], $c[5] ) );
		$argLists[] = array( $c, $c[2], 1, $c );
		$argLists[] = array( $c, $c[2], 2, $c );
		$argLists[] = array( $c, $c[2], 3, array( $c[0], $c[1], $c[3], $c[2], $c[4], $c[5] ) );
		$argLists[] = array( $c, $c[2], 4, array( $c[0], $c[1], $c[3], $c[4], $c[2], $c[5] ) );
		$argLists[] = array( $c, $c[2], 5, array( $c[0], $c[1], $c[3], $c[4], $c[2], $c[5] ) );
		$argLists[] = array( $c, $c[2], 6, array( $c[0], $c[1], $c[5], $c[3], $c[4], $c[2] ) );

		$argLists[] = array( $c, $c[3], 0, array( $c[3], $c[2], $c[4], $c[0], $c[1], $c[5] ) );
		$argLists[] = array( $c, $c[3], 1, array( $c[0], $c[1], $c[3], $c[2], $c[4], $c[5] ) );
		$argLists[] = array( $c, $c[3], 2, array( $c[0], $c[1], $c[3], $c[2], $c[4], $c[5] ) );
		$argLists[] = array( $c, $c[3], 3, $c );
		$argLists[] = array( $c, $c[3], 4, array( $c[0], $c[1], $c[2], $c[4], $c[3], $c[5] ) );
		$argLists[] = array( $c, $c[3], 5, array( $c[0], $c[1], $c[2], $c[4], $c[3], $c[5] ) );
		$argLists[] = array( $c, $c[3], 6, array( $c[0], $c[1], $c[5], $c[2], $c[4], $c[3] ) );

		$argLists[] = array( $c, $c[4], 0, array( $c[4], $c[2], $c[3], $c[0], $c[1], $c[5] ) );
		$argLists[] = array( $c, $c[4], 1, array( $c[0], $c[1], $c[4], $c[2], $c[3], $c[5] ) );
		$argLists[] = array( $c, $c[4], 2, array( $c[0], $c[1], $c[4], $c[2], $c[3], $c[5] ) );
		$argLists[] = array( $c, $c[4], 3, array( $c[0], $c[1], $c[2], $c[4], $c[3], $c[5] ) );
		$argLists[] = array( $c, $c[4], 4, $c );
		$argLists[] = array( $c, $c[4], 5, $c );
		$argLists[] = array( $c, $c[4], 6, array( $c[0], $c[1], $c[5], $c[2], $c[3], $c[4] ) );

		$argLists[] = array( $c, $c[5], 0, array( $c[5], $c[0], $c[1], $c[2], $c[3], $c[4] ) );
		$argLists[] = array( $c, $c[5], 1, array( $c[0], $c[1], $c[5], $c[2], $c[3], $c[4] ) );
		$argLists[] = array( $c, $c[5], 2, array( $c[0], $c[1], $c[5], $c[2], $c[3], $c[4] ) );
		$argLists[] = array( $c, $c[5], 3, $c );
		$argLists[] = array( $c, $c[5], 4, $c );
		$argLists[] = array( $c, $c[5], 5, $c );
		$argLists[] = array( $c, $c[5], 6, $c );

		return $argLists;
	}

	/**
	 * @dataProvider moveProvider
	 */
	public function testMoveObjectToIndex(
		array $objectsSource,
		PropertyIdProvider $object,
		$toIndex,
		array $objectsDestination
	) {
		$indexedArray = new ByPropertyIdArray( $objectsSource );
		$indexedArray->buildIndex();

		$indexedArray->moveObjectToIndex( $object, $toIndex );

		// Not using $indexedArray->toFlatArray() here to test whether native array has been
		// exchanged:
		$reindexedArray = array();
		foreach ( $indexedArray as $o ) {
			$reindexedArray[] = $o;
		}

		$this->assertEquals( $objectsDestination, $reindexedArray );
	}

	public function testMoveThrowingOutOfBoundsExceptionIfObjectNotPresent() {
		$statements = $this->statementsProvider();
		$indexedArray = new ByPropertyIdArray( $statements );
		$indexedArray->buildIndex();

		$this->setExpectedException( 'OutOfBoundsException' );

		$indexedArray->moveObjectToIndex( new Statement( new PropertyNoValueSnak( new PropertyId( 'P9999' ) ) ), 0 );
	}

	public function testMoveThrowingOutOfBoundsExceptionOnInvalidIndex() {
		$statements = $this->statementsProvider();
		$indexedArray = new ByPropertyIdArray( $statements );
		$indexedArray->buildIndex();

		$this->setExpectedException( 'OutOfBoundsException' );

		$indexedArray->moveObjectToIndex( $statements[0], 9999 );
	}

	public function addProvider() {
		$c = $this->statementsProvider();

		$argLists = array();

		$argLists[] = array( array(), $c[0], null, array( $c[0] ) );
		$argLists[] = array( array(), $c[0], 1, array( $c[0] ) );
		$argLists[] = array( array( $c[0] ), $c[2], 0, array( $c[2], $c[0] ) );
		$argLists[] = array( array( $c[2], $c[1] ), $c[0], 0, array( $c[0], $c[1], $c[2] ) );
		$argLists[] = array(
			array( $c[0], $c[1], $c[3] ),
			$c[5],
			1,
			array( $c[0], $c[1], $c[5], $c[3] )
		);
		$argLists[] = array(
			array( $c[0], $c[1], $c[5], $c[3] ),
			$c[2],
			2,
			array( $c[0], $c[1], $c[2], $c[3], $c[5] )
		);
		$argLists[] = array(
			array( $c[0], $c[1], $c[2], $c[3], $c[5] ),
			$c[4],
			null,
			array( $c[0], $c[1], $c[2], $c[3], $c[4], $c[5] )
		);

		return $argLists;
	}

	/**
	 * @dataProvider addProvider
	 */
	public function testAddObjectAtIndex(
		array $objectsSource,
		PropertyIdProvider $object,
		$index,
		array $objectsDestination
	) {
		$indexedArray = new ByPropertyIdArray( $objectsSource );
		$indexedArray->buildIndex();

		$indexedArray->addObjectAtIndex( $object, $index );

		$this->assertEquals( $objectsDestination, $indexedArray->toFlatArray() );
	}

}
