<?php

namespace Wikibase\Test;

use Wikibase\Claim;
use Wikibase\Claims;

/**
 * Tests for the Wikibase\Claims class.
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
class ClaimsTest extends \MediaWikiTestCase {

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

		$instances[] = new \Wikibase\Claim(
			new \Wikibase\PropertyNoValueSnak(
				new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 23 ) ) );

		$instances[] = new \Wikibase\Claim(
			new \Wikibase\PropertySomeValueSnak(
				new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 42 ) ) );

		$instances[] = new \Wikibase\Claim(
			new \Wikibase\PropertyValueSnak(
				new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 42 ),
				new \DataValues\StringValue( "foo" ) ) );

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
		$this->assertType( 'array', $snaks );
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
		$firstClaim = new Claim( new \Wikibase\PropertyNoValueSnak( 42 ) );
		$secondClaim = new Claim( new \Wikibase\PropertyNoValueSnak( 42 ) );

		$list = new Claims();
		$this->assertTrue( $list->addElement( $firstClaim ), 'Adding the first element should work' );
		$this->assertTrue( $list->addElement( $secondClaim ), 'Adding a duplicate element should work' );

		$this->assertEquals( 2, count( $list->getArrayCopy() ), 'Adding two duplicates to an empty list should result in a count of two' );

		$this->assertTrue( $list->addElement( new Claim( new \Wikibase\PropertySomeValueSnak( 1 ) ) ) );

		$list->removeDuplicates();

		$this->assertEquals( 2, count( $list->getArrayCopy() ), 'Removing duplicates from a list should work' );
	}

	public function getDiffProvider() {
		$argLists = array();

		$claim0 = new Claim( new \Wikibase\PropertyNoValueSnak( 42 ) );
		$claim1 = new Claim( new \Wikibase\PropertySomeValueSnak( 42 ) );
		$claim2 = new Claim( new \Wikibase\PropertyValueSnak( 42, new \DataValues\StringValue( 'ohi' ) ) );
		$claim3 = new Claim( new \Wikibase\PropertyNoValueSnak( 1 ) );
		$claim4 = new Claim( new \Wikibase\PropertyNoValueSnak( 2 ) );

		$statement0 = new \Wikibase\Statement( new \Wikibase\PropertyNoValueSnak( 5 ) );
		$statement0->setRank( \Wikibase\Statement::RANK_PREFERRED );

		$statement1 = new \Wikibase\Statement( new \Wikibase\PropertyNoValueSnak( 5 ) );
		$statement1->setReferences( new \Wikibase\ReferenceList( array( new \Wikibase\Reference(
			new \Wikibase\SnakList( array( new \Wikibase\PropertyValueSnak( 10, new \DataValues\StringValue( 'spam' ) ) ) )
		) ) ) );

		$guidGen = new \Wikibase\Lib\V4GuidGenerator();
		$claim0->setGuid( $guidGen->newGuid() );
		$claim1->setGuid( $guidGen->newGuid() );
		$claim2->setGuid( $guidGen->newGuid() );
		$statement1->setGuid( $guidGen->newGuid() );
		$statement0->setGuid( $guidGen->newGuid() );

		$claim2v2 = unserialize( serialize( $claim2 ) );
		$claim2v2->setMainSnak( new \Wikibase\PropertyValueSnak( 42, new \DataValues\StringValue( 'omnomnom' ) ) );


		$source = new Claims();
		$target = new Claims();
		$expected = new \Diff\Diff( array(), true );
		$argLists[] = array( $source, $target, $expected, 'Two empty lists should result in an empty diff' );


		$source = new Claims();
		$target = new Claims( array( $claim0 ) );
		$expected = new \Diff\Diff( array( $claim0->getGuid() => new \Diff\DiffOpAdd( $claim0 ) ), true );
		$argLists[] = array( $source, $target, $expected, 'List with no entries to list with one should result in one add op' );


		$source = new Claims( array( $claim0 ) );
		$target = new Claims();
		$expected = new \Diff\Diff( array( $claim0->getGuid() => new \Diff\DiffOpRemove( $claim0 ) ), true );
		$argLists[] = array( $source, $target, $expected, 'List with one entry to an empty list should result in one remove op' );


		$source = new Claims( array( $claim0, $claim3, $claim2 ) );
		$target = new Claims( array( $claim0, $claim2, $claim3 ) );
		$expected = new \Diff\Diff( array(), true );
		$argLists[] = array( $source, $target, $expected, 'Two identical lists should result in an empty diff' );


		$source = new Claims( array( $claim0 ) );
		$target = new Claims( array( $claim1 ) );
		$expected = new \Diff\Diff( array(
			$claim1->getGuid() => new \Diff\DiffOpAdd( $claim1 ),
			$claim0->getGuid() => new \Diff\DiffOpRemove( $claim0 )
		), true );
		$argLists[] = array( $source, $target, $expected, 'Two lists with each a single different entry should result into one add and one remove op' );


		$source = new Claims( array( $claim2, $claim3, $claim0, $claim4 ) );
		$target = new Claims( array( $claim2, $claim1, $claim3, $claim4 ) );
		$expected = new \Diff\Diff( array(
			$claim1->getGuid() => new \Diff\DiffOpAdd( $claim1 ),
			$claim0->getGuid() => new \Diff\DiffOpRemove( $claim0 )
		), true );
		$argLists[] = array( $source, $target, $expected, 'Two lists with identical items except for one change should result in one add and one remove op' );


		$source = new Claims( array( $claim0, $claim0, $claim3, $claim2, $claim2, $claim2, $statement0 ) );
		$target = new Claims( array( $claim0, $claim0, $claim2, $claim3, $claim2, $claim2, $statement0 ) );
		$expected = new \Diff\Diff( array(), true );
		$argLists[] = array( $source, $target, $expected, 'Two identical lists with duplicate items should result in an empty diff' );


		$source = new Claims( array( $statement0, $statement1, $claim0 ) );
		$target = new Claims( array( $claim1, $claim1, $claim0, $statement1 ) );
		$expected = new \Diff\Diff( array(
			$claim1->getGuid() => new \Diff\DiffOpAdd( $claim1 ),
			$statement0->getGuid() => new \Diff\DiffOpRemove( $statement0 ),
		), true );
		$argLists[] = array( $source, $target, $expected, 'Two lists with duplicate items and a different entry should result into one add and one remove op' );

		$source = new Claims( array( $claim0, $claim3, $claim2 ) );
		$target = new Claims( array( $claim0, $claim2v2, $claim3 ) );
		$expected = new \Diff\Diff( array( $claim2->getGuid() => new \Diff\DiffOpChange( $claim2, $claim2v2 ) ), true );
		$argLists[] = array( $source, $target, $expected, 'Changing the value of a claim should result in a change op' );

		return $argLists;
	}

	/**
	 * @dataProvider getDiffProvider
	 *
	 * @param \Wikibase\Claims $source
	 * @param \Wikibase\Claims $target
	 * @param \Diff\Diff $expected
	 * @param string $message
	 */
	public function testGetDiff( Claims $source, Claims $target, \Diff\Diff $expected, $message ) {
		$actual = $source->getDiff( $target );

		// Note: this makes order of inner arrays relevant, and this order is not guaranteed by the interface
		$this->assertArrayEquals( $expected->getOperations(), $actual->getOperations(), false, true, $message );
	}

}
