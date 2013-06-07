<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Diff\Diff;
use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Diff\DiffOpRemove;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\ReferenceList;
use Wikibase\SnakList;
use Wikibase\Statement;

/**
 * @covers Wikibase\Claims
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimsTest extends \PHPUnit_Framework_TestCase {

	public function getInstanceClass() {
		return '\Wikibase\Claims';
	}

	public function instanceProvider() {
		$class = $this->getInstanceClass();

		$instances = array();

		foreach ( $this->getConstructorArg() as $arg ) {
			$instances[] = array( new $class( $arg ) );
		}

		return $instances;
	}

	public function getElementInstances() {
		$instances = array();

		$instances[] = new Claim(
			new PropertyNoValueSnak(
				new EntityId( Property::ENTITY_TYPE, 23 ) ) );

		$instances[] = new Claim(
			new PropertySomeValueSnak(
				new EntityId( Property::ENTITY_TYPE, 42 ) ) );

		$instances[] = new Claim(
			new PropertyValueSnak(
				new EntityId( Property::ENTITY_TYPE, 42 ),
				new StringValue( "foo" ) ) );

		return $instances;
	}

	public function getConstructorArg() {
		return array(
			null,
			array(),
			$this->getElementInstances(),
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\Claims $array
	 */
	public function testHasClaim( Claims $array ) {
		/**
		 * @var Claim $hashable
		 */
		foreach ( iterator_to_array( $array ) as $hashable ) {
			$this->assertTrue( $array->hasClaim( $hashable ) );
			$array->removeClaim( $hashable );
			$this->assertFalse( $array->hasClaim( $hashable ) );
		}

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\Claims $array
	 */
	public function testRemoveClaim( Claims $array ) {
		$elementCount = count( $array );

		/**
		 * @var Claim $element
		 */
		foreach ( iterator_to_array( $array ) as $element ) {
			$this->assertTrue( $array->hasClaim( $element ) );

			$array->removeClaim( $element );

			$this->assertFalse( $array->hasClaim( $element ) );
			$this->assertEquals( --$elementCount, count( $array ) );
		}

		$elements = $this->getElementInstances();
		$element = array_shift( $elements );

		$array->removeClaim( $element );
		$array->removeClaim( $element );

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\Claims $array
	 */
	public function testAddClaim( Claims $array ) {
		$elementCount = count( $array );

		$elements = $this->getElementInstances();
		$element = array_shift( $elements );

		++$elementCount;

		$array->addClaim( $element );

		$this->assertEquals( $elementCount, count( $array ) );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\Claims $array
	 */
	public function testGetMainSnaks( Claims $array ) {
		$snaks = $array->getMainSnaks();
		$this->assertInternalType( 'array', $snaks );
		$this->assertSameSize( $array, $snaks );
	}

	public function testGetClaimsForProperty() {
		$array = new Claims( $this->getElementInstances() );

		$claims = $array->getClaimsForProperty( 42 );
		$this->assertInstanceOf( 'Wikibase\Claims', $claims );
		$this->assertCount( 2, $claims );

		$claims = $array->getClaimsForProperty( 23 );
		$this->assertInstanceOf( 'Wikibase\Claims', $claims );
		$this->assertCount( 1, $claims );

		$claims = $array->getClaimsForProperty( 9000 );
		$this->assertInstanceOf( 'Wikibase\Claims', $claims );
		$this->assertCount( 0, $claims );
	}

	public function testDuplicateClaims() {
		$firstClaim = new Claim( new PropertyNoValueSnak( 42 ) );
		$secondClaim = new Claim( new PropertyNoValueSnak( 42 ) );

		$list = new Claims();
		$this->assertTrue( $list->addElement( $firstClaim ), 'Adding the first element should work' );
		$this->assertTrue( $list->addElement( $secondClaim ), 'Adding a duplicate element should work' );

		$this->assertEquals( 2, count( $list->getArrayCopy() ), 'Adding two duplicates to an empty list should result in a count of two' );

		$this->assertTrue( $list->addElement( new Claim( new PropertySomeValueSnak( 1 ) ) ) );

		$list->removeDuplicates();

		$this->assertEquals( 2, count( $list->getArrayCopy() ), 'Removing duplicates from a list should work' );
	}

	public function getDiffProvider() {
		$argLists = array();

		$claim0 = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim1 = new Claim( new PropertySomeValueSnak( 42 ) );
		$claim2 = new Claim( new PropertyValueSnak( 42, new StringValue( 'ohi' ) ) );
		$claim3 = new Claim( new PropertyNoValueSnak( 1 ) );
		$claim4 = new Claim( new PropertyNoValueSnak( 2 ) );

		$statement0 = new Statement( new PropertyNoValueSnak( 5 ) );
		$statement0->setRank( Statement::RANK_PREFERRED );

		$statement1 = new Statement( new PropertyNoValueSnak( 5 ) );
		$statement1->setReferences( new ReferenceList( array( new Reference(
			new SnakList( array( new PropertyValueSnak( 10, new StringValue( 'spam' ) ) ) )
		) ) ) );

		$claim0->setGuid( 'claim0' );
		$claim1->setGuid( 'claim1' );
		$claim2->setGuid( 'claim2' );
		$statement1->setGuid( 'statement1' );
		$statement0->setGuid( 'statement0' );

		$claim2v2 = unserialize( serialize( $claim2 ) );
		$claim2v2->setMainSnak( new PropertyValueSnak( 42, new StringValue( 'omnomnom' ) ) );


		$source = new Claims();
		$target = new Claims();
		$expected = new Diff( array(), true );
		$argLists[] = array( $source, $target, $expected, 'Two empty lists should result in an empty diff' );


		$source = new Claims();
		$target = new Claims( array( $claim0 ) );
		$expected = new Diff( array( $claim0->getGuid() => new DiffOpAdd( $claim0 ) ), true );
		$argLists[] = array( $source, $target, $expected, 'List with no entries to list with one should result in one add op' );


		$source = new Claims( array( $claim0 ) );
		$target = new Claims();
		$expected = new Diff( array( $claim0->getGuid() => new DiffOpRemove( $claim0 ) ), true );
		$argLists[] = array( $source, $target, $expected, 'List with one entry to an empty list should result in one remove op' );


		$source = new Claims( array( $claim0, $claim3, $claim2 ) );
		$target = new Claims( array( $claim0, $claim2, $claim3 ) );
		$expected = new Diff( array(), true );
		$argLists[] = array( $source, $target, $expected, 'Two identical lists should result in an empty diff' );


		$source = new Claims( array( $claim0 ) );
		$target = new Claims( array( $claim1 ) );
		$expected = new Diff( array(
			$claim1->getGuid() => new DiffOpAdd( $claim1 ),
			$claim0->getGuid() => new DiffOpRemove( $claim0 )
		), true );
		$argLists[] = array( $source, $target, $expected, 'Two lists with each a single different entry should result into one add and one remove op' );


		$source = new Claims( array( $claim2, $claim3, $claim0, $claim4 ) );
		$target = new Claims( array( $claim2, $claim1, $claim3, $claim4 ) );
		$expected = new Diff( array(
			$claim1->getGuid() => new DiffOpAdd( $claim1 ),
			$claim0->getGuid() => new DiffOpRemove( $claim0 )
		), true );
		$argLists[] = array( $source, $target, $expected, 'Two lists with identical items except for one change should result in one add and one remove op' );


		$source = new Claims( array( $claim0, $claim0, $claim3, $claim2, $claim2, $claim2, $statement0 ) );
		$target = new Claims( array( $claim0, $claim0, $claim2, $claim3, $claim2, $claim2, $statement0 ) );
		$expected = new Diff( array(), true );
		$argLists[] = array( $source, $target, $expected, 'Two identical lists with duplicate items should result in an empty diff' );


		$source = new Claims( array( $statement0, $statement1, $claim0 ) );
		$target = new Claims( array( $claim1, $claim1, $claim0, $statement1 ) );
		$expected = new Diff( array(
			$claim1->getGuid() => new DiffOpAdd( $claim1 ),
			$statement0->getGuid() => new DiffOpRemove( $statement0 ),
		), true );
		$argLists[] = array( $source, $target, $expected, 'Two lists with duplicate items and a different entry should result into one add and one remove op' );

		$source = new Claims( array( $claim0, $claim3, $claim2 ) );
		$target = new Claims( array( $claim0, $claim2v2, $claim3 ) );
		$expected = new Diff( array( $claim2->getGuid() => new DiffOpChange( $claim2, $claim2v2 ) ), true );
		$argLists[] = array( $source, $target, $expected, 'Changing the value of a claim should result in a change op' );

		return $argLists;
	}

	/**
	 * @dataProvider getDiffProvider
	 *
	 * @param \Wikibase\Claims $source
	 * @param \Wikibase\Claims $target
	 * @param Diff $expected
	 * @param string $message
	 */
	public function testGetDiff( Claims $source, Claims $target, Diff $expected, $message ) {
		$actual = $source->getDiff( $target );

		// Note: this makes order of inner arrays relevant, and this order is not guaranteed by the interface
		$this->assertEquals( $expected->getOperations(), $actual->getOperations(), $message );
	}

	public function testCallingGetClaimsForPropertyWithInvalidArgumentCausesException() {
		$claims = new Claims();

		$this->setExpectedException( 'InvalidArgumentException' );
		$claims->getClaimsForProperty( 'foo bar' );
	}

}
