<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\ByPropertyIdArray;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Snak;
use Wikibase\Statement;

/**
 * @covers Wikibase\ByPropertyIdArray
 *
 * @file
 * @since 0.2
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group ByPropertyIdArrayTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ByPropertyIdArrayTest extends \PHPUnit_Framework_TestCase {

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
				return new Claim( $snak );
			},
			$snaks
		);

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
	 * @dataProvider listProvider
	 * @param Snak[] $objects
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
	 * @param array $objects
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

}
