<?php

namespace Wikibase\Test;

use Hashable;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\ReferenceList
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseReference
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferenceListTest extends \PHPUnit_Framework_TestCase {

	public function instanceProvider() {
		$instances = array();

		foreach ( $this->getConstructorArg() as $arg ) {
			$instances[] = array( new ReferenceList( $arg ) );
		}

		return $instances;
	}

	public function getElementInstances() {
		return array(
			new Reference(),
			new Reference( new SnakList( array( new PropertyNoValueSnak( 2 ) ) ) ),
			new Reference( new SnakList( array( new PropertyNoValueSnak( 3 ) ) ) ),
		);
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
	 * @param ReferenceList $array
	 */
	public function testHasReferenceBeforeRemoveButNotAfter( ReferenceList $array ) {
		if ( $array->count() === 0 ) {
			$this->assertTrue( true );
			return;
		}

		/**
		 * @var Reference $hashable
		 */
		foreach ( iterator_to_array( $array ) as $hashable ) {
			$this->assertTrue( $array->hasReference( $hashable ) );
			$array->removeReference( $hashable );
			$this->assertFalse( $array->hasReference( $hashable ) );
		}
	}

	public function testGivenCloneOfReferenceInList_hasReferenceReturnsTrue() {
		$list = new ReferenceList();

		$reference = new Reference( new SnakList( array( new PropertyNoValueSnak( 42 ) ) ) );
		$sameReference = unserialize( serialize( $reference ) );

		$list->addReference( $reference );

		$this->assertTrue(
			$list->hasReference( $sameReference ),
			'hasReference should return true when a reference with the same value is present, even when its another instance'
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param ReferenceList $array
	 */
	public function testRemoveReference( ReferenceList $array ) {
		$elementCount = count( $array );

		/**
		 * @var Reference $element
		 */
		foreach ( iterator_to_array( $array ) as $element ) {
			$this->assertTrue( $array->hasReference( $element ) );

			$array->removeReference( $element );

			$this->assertFalse( $array->hasReference( $element ) );
			$this->assertEquals( --$elementCount, count( $array ) );
		}

		$elements = $this->getElementInstances();
		$element = array_shift( $elements );

		$array->removeReference( $element );
		$array->removeReference( $element );

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param ReferenceList $array
	 */
	public function testAddReference( ReferenceList $array ) {
		// Append object to the end:
		$elementCount = count( $array );

		$elements = $this->getElementInstances();
		$element = array_shift( $elements );
		$array->addReference( $element );

		$this->assertEquals( ++$elementCount, count( $array ) );

		// Insert object at the beginning:
		$elements = $this->getElementInstances();
		$element = array_shift( $elements );
		$array->addReference( $element, 0 );

		$array->rewind();

		$this->assertEquals( ++$elementCount, count( $array ) );
		$this->assertEquals( $array->current(), $element, 'Inserted object at the beginning' );

		// Insert object at another index:
		$elements = $this->getElementInstances();
		$element = array_shift( $elements );
		$array->addReference( $element, 1 );

		$array->rewind();
		$array->next();

		$this->assertEquals( ++$elementCount, count( $array ) );
		$this->assertEquals( $array->current(), $element, 'Inserted object at index 1' );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param ReferenceList $array
	 */
	public function testIndexOf( ReferenceList $array ) {
		$this->assertFalse( $array->indexOf( new Reference() ) );

		$i = 0;
		foreach( $array as $reference ) {
			$this->assertEquals( $i++, $array->indexOf( $reference ) );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param ReferenceList $array
	 */
	public function testEquals( ReferenceList $array ) {
		$this->assertTrue( $array->equals( $array ) );
		$this->assertFalse( $array->equals( 42 ) );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param ReferenceList $array
	 */
	public function testGetHashReturnsString( ReferenceList $array ) {
		$this->assertInternalType( 'string', $array->getValueHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param ReferenceList $array
	 */
	public function testGetHashValueIsTheSameForClone( ReferenceList $array ) {
		$copy = unserialize( serialize( $array ) );
		$this->assertEquals( $array->getValueHash(), $copy->getValueHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param ReferenceList $references
	 */
	public function testHasReferenceHash( ReferenceList $references ) {
		$this->assertFalse( $references->hasReferenceHash( '~=[,,_,,]:3' ) );

		/**
		 * @var Hashable $reference
		 */
		foreach ( $references as $reference ) {
			$this->assertTrue( $references->hasReferenceHash( $reference->getHash() ) );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 * @param ReferenceList $references
	 */
	public function testGetReference( ReferenceList $references ) {
		$this->assertNull( $references->getReference( '~=[,,_,,]:3' ) );

		/**
		 * @var Reference $reference
		 */
		foreach ( $references as $reference ) {
			$this->assertTrue( $reference->equals( $references->getReference( $reference->getHash() ) ) );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 * @param ReferenceList $references
	 */
	public function testRemoveReferenceHash( ReferenceList $references ) {
		$references->removeReferenceHash( '~=[,,_,,]:3' );

		$hashes = array();

		/**
		 * @var Reference $reference
		 */
		foreach ( $references as $reference ) {
			$hashes[] = $reference->getHash();
		}

		foreach( $hashes as $hash ) {
			$references->removeReferenceHash( $hash );
		}

		$this->assertEquals( 0, count( $references ) );
	}

	public function testGivenOneSnak_addNewReferenceAddsSnak() {
		$references = new ReferenceList();
		$snak = new PropertyNoValueSnak( 1 );

		$references->addNewReference( $snak );
		$this->assertTrue( $references->hasReference( new Reference( new SnakList( array( $snak ) ) ) ) );
	}

	public function testGivenMultipleSnaks_addNewReferenceAddsThem() {
		$references = new ReferenceList();

		$references->addNewReference(
			new PropertyNoValueSnak( 1 ),
			new PropertyNoValueSnak( 3 ),
			new PropertyNoValueSnak( 2 )
		);

		$expectedSnaks = array(
			new PropertyNoValueSnak( 1 ),
			new PropertyNoValueSnak( 3 ),
			new PropertyNoValueSnak( 2 )
		);

		$this->assertTrue( $references->hasReference( new Reference( new SnakList( $expectedSnaks ) ) ) );
	}

	public function testGivenNoneSnak_addNewReferenceThrowsException() {
		$references = new ReferenceList();

		$this->setExpectedException( 'InvalidArgumentException' );
		$references->addNewReference( new PropertyNoValueSnak( 1 ), null );
	}

}
