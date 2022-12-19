<?php

namespace Wikibase\DataModel\Tests;

use InvalidArgumentException;
use Traversable;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers \Wikibase\DataModel\ReferenceList
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseReference
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 */
class ReferenceListTest extends \PHPUnit\Framework\TestCase {

	public function instanceProvider() {
		return [
			[ new ReferenceList( [] ) ],
			[ new ReferenceList( [
				new Reference(),
				new Reference( [ new PropertyNoValueSnak( 2 ) ] ),
				new Reference( [ new PropertyNoValueSnak( 3 ) ] ),
			] ) ],
		];
	}

	public function testCanConstructWithReferenceListObject() {
		$reference = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$original = new ReferenceList( [ $reference ] );
		$copy = new ReferenceList( $original );

		$this->assertSame( 1, $copy->count() );
		$this->assertNotNull( $copy->getReference( $reference->getHash() ) );
	}

	public function testConstructorIgnoresIdenticalObjects() {
		$reference = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$list = new ReferenceList( [ $reference, $reference ] );
		$this->assertCount( 1, $list );
	}

	public function testConstructorDoesNotIgnoreCopies() {
		$reference = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$list = new ReferenceList( [ $reference, clone $reference ] );
		$this->assertCount( 2, $list );
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testGivenInvalidConstructorArguments_constructorThrowsException( $input ) {
		$this->expectException( InvalidArgumentException::class );
		new ReferenceList( $input );
	}

	public function invalidConstructorArgumentsProvider() {
		$id1 = new NumericPropertyId( 'P1' );

		return [
			[ null ],
			[ false ],
			[ 1 ],
			[ 0.1 ],
			[ 'string' ],
			[ $id1 ],
			[ new PropertyNoValueSnak( $id1 ) ],
			[ new Reference() ],
			[ new SnakList( [ new PropertyNoValueSnak( $id1 ) ] ) ],
			[ [ new PropertyNoValueSnak( $id1 ) ] ],
			[ [ new ReferenceList() ] ],
			[ [ new SnakList() ] ],
		];
	}

	public function testGetIterator_isTraversable() {
		$references = new ReferenceList();
		$references->addNewReference( new PropertyNoValueSnak( 1 ) );
		$iterator = $references->getIterator();

		$this->assertInstanceOf( Traversable::class, $iterator );
		$this->assertCount( 1, $iterator );
		foreach ( $references as $reference ) {
			$this->assertInstanceOf( Reference::class, $reference );
		}
	}

	/**
	 * @dataProvider instanceProvider
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

		$reference = new Reference( [ new PropertyNoValueSnak( 42 ) ] );
		$sameReference = unserialize( serialize( $reference ) );

		$list->addReference( $reference );

		$this->assertTrue(
			$list->hasReference( $sameReference ),
			'hasReference should return true when a reference with the same value is present, even '
				. 'when its another instance'
		);
	}

	/**
	 * @dataProvider instanceProvider
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
			$this->assertCount( --$elementCount, $array );
		}
		if ( $elementCount === 0 ) {
			$this->assertTrue( true );
		}
	}

	public function testRemoveReferenceRemovesIdenticalObjects() {
		$reference = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$references = new ReferenceList( [ $reference, $reference ] );

		$references->removeReference( $reference );

		$this->assertTrue( $references->isEmpty() );
	}

	public function testRemoveReferenceDoesNotRemoveCopies() {
		$reference = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$references = new ReferenceList( [ $reference, clone $reference ] );

		$references->removeReference( $reference );

		$this->assertFalse( $references->isEmpty() );
		$this->assertTrue( $references->hasReference( $reference ) );
		$this->assertNotSame( $reference, $references->getReference( $reference->getHash() ) );
	}

	public function testAddReferenceOnEmptyList() {
		$reference = new Reference( [ new PropertyNoValueSnak( 1 ) ] );

		$references = new ReferenceList();
		$references->addReference( $reference );

		$this->assertCount( 1, $references );

		$expectedList = new ReferenceList( [ $reference ] );
		$this->assertSameReferenceOrder( $expectedList, $references );
	}

	private function assertSameReferenceOrder( ReferenceList $expectedList, ReferenceList $references ) {
		$this->assertSame(
			iterator_to_array( $expectedList ),
			iterator_to_array( $references )
		);
	}

	public function testAddReferenceAtTheEnd() {
		$reference1 = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$reference2 = new Reference( [ new PropertyNoValueSnak( 2 ) ] );
		$reference3 = new Reference( [ new PropertyNoValueSnak( 3 ) ] );

		$references = new ReferenceList( [ $reference1, $reference2 ] );
		$references->addReference( $reference3 );

		$this->assertCount( 3, $references );

		$expectedList = new ReferenceList( [ $reference1, $reference2, $reference3 ] );
		$this->assertSameReferenceOrder( $expectedList, $references );
	}

	public function testAddReferenceBetweenExistingReferences() {
		$reference1 = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$reference2 = new Reference( [ new PropertyNoValueSnak( 2 ) ] );
		$list = new ReferenceList( [ $reference1, $reference2 ] );

		$reference3 = new Reference( [ new PropertyNoValueSnak( 3 ) ] );
		$list->addReference( $reference3, 1 );

		$this->assertCount( 3, $list );
		$this->assertSame( 1, $list->indexOf( $reference3 ) );
	}

	public function testAddReferenceIgnoresIdenticalObjects() {
		$list = new ReferenceList();
		$reference = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$list->addReference( $reference );
		$list->addReference( $reference );
		$this->assertCount( 1, $list );
	}

	public function testAddReferenceDoesNotIgnoreCopies() {
		$list = new ReferenceList();
		$reference = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$list->addReference( $reference );
		$list->addReference( clone $reference );
		$this->assertCount( 2, $list );
	}

	public function testAddReferenceAtIndexIgnoresIdenticalObjects() {
		$list = new ReferenceList();
		$reference = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$list->addReference( $reference, 0 );
		$list->addReference( $reference, 0 );
		$this->assertCount( 1, $list );
	}

	public function testAddReferenceAtIndexMovesIdenticalObjects() {
		$list = new ReferenceList();
		$list->addNewReference( new PropertyNoValueSnak( 1 ) );
		$reference = new Reference( [ new PropertyNoValueSnak( 2 ) ] );
		$list->addReference( $reference );
		$list->addNewReference( new PropertyNoValueSnak( 3 ) );

		$this->assertSame(
			1,
			$list->indexOf( $reference ),
			'pre-condition is that the element is at index 1'
		);

		$list->addReference( $reference, 0 );

		$this->assertCount( 3, $list, 'not added' );
		$this->assertSame(
			1,
			$list->indexOf( $reference ),
			'make sure calling addReference with a lower index did not changed it'
		);

		$list->addReference( $reference, 2 );

		$this->assertCount( 3, $list, 'not added' );
		$this->assertSame(
			1,
			$list->indexOf( $reference ),
			'make sure calling addReference with a higher index did not changed it'
		);
	}

	public function testAddReferenceAtIndexZero() {
		$reference1 = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$reference2 = new Reference( [ new PropertyNoValueSnak( 2 ) ] );
		$reference3 = new Reference( [ new PropertyNoValueSnak( 3 ) ] );

		$references = new ReferenceList( [ $reference1, $reference2 ] );
		$references->addReference( $reference3, 0 );

		$expectedList = new ReferenceList( [ $reference3, $reference1, $reference2 ] );
		$this->assertSameReferenceOrder( $expectedList, $references );
	}

	public function testAddReferenceAtNegativeIndex() {
		$reference = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$referenceList = new ReferenceList();

		$this->expectException( InvalidArgumentException::class );
		$referenceList->addReference( $reference, -1 );
	}

	public function testGivenEmptyReference_addReferenceDoesNotAdd() {
		$reference1 = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$reference2 = new Reference( [ new PropertyNoValueSnak( 2 ) ] );
		$emptyReference = new Reference( [] );

		$references = new ReferenceList( [ $reference1, $reference2 ] );
		$references->addReference( $emptyReference );

		$expectedList = new ReferenceList( [ $reference1, $reference2 ] );
		$this->assertSameReferenceOrder( $expectedList, $references );
	}

	public function testGivenEmptyReferenceAndIndex_addReferenceDoesNotAdd() {
		$reference1 = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$reference2 = new Reference( [ new PropertyNoValueSnak( 2 ) ] );
		$emptyReference = new Reference( [] );

		$references = new ReferenceList( [ $reference1, $reference2 ] );
		$references->addReference( $emptyReference, 0 );

		$expectedList = new ReferenceList( [ $reference1, $reference2 ] );
		$this->assertSameReferenceOrder( $expectedList, $references );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testIndexOf( ReferenceList $array ) {
		$this->assertFalse( $array->indexOf( new Reference() ) );

		$i = 0;
		foreach ( $array as $reference ) {
			$this->assertSame( $i++, $array->indexOf( $reference ) );
		}
	}

	public function testIndexOf_checksForIdentity() {
		$reference1 = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$reference2 = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$referenceList = new ReferenceList( [ $reference1 ] );

		$this->assertNotSame( $reference1, $reference2, 'post condition' );
		$this->assertTrue( $reference1->equals( $reference2 ), 'post condition' );
		$this->assertSame( 0, $referenceList->indexOf( $reference1 ), 'identity' );
		$this->assertFalse( $referenceList->indexOf( $reference2 ), 'not equality' );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testEquals( ReferenceList $array ) {
		$this->assertTrue( $array->equals( $array ) );
		$this->assertFalse( $array->equals( 42 ) );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetValueHashReturnsString( ReferenceList $array ) {
		$this->assertIsString( $array->getValueHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetValueHashIsTheSameForClone( ReferenceList $array ) {
		$copy = unserialize( serialize( $array ) );
		$this->assertSame( $array->getValueHash(), $copy->getValueHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testHasReferenceHash( ReferenceList $references ) {
		$this->assertFalse( $references->hasReferenceHash( '~=[,,_,,]:3' ) );

		/**
		 * @var Reference $reference
		 */
		foreach ( $references as $reference ) {
			$this->assertTrue( $references->hasReferenceHash( $reference->getHash() ) );
		}
	}

	/**
	 * @dataProvider instanceProvider
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
	 */
	public function testRemoveReferenceHash( ReferenceList $references ) {
		$references->removeReferenceHash( '~=[,,_,,]:3' );

		$hashes = [];

		/**
		 * @var Reference $reference
		 */
		foreach ( $references as $reference ) {
			$hashes[] = $reference->getHash();
		}

		foreach ( $hashes as $hash ) {
			$references->removeReferenceHash( $hash );
		}

		$this->assertTrue( $references->isEmpty() );
	}

	/**
	 * This integration test (relies on ReferenceList::getValueHash) is supposed to break whenever the hash
	 * calculation changes.
	 */
	public function testGetValueHashStability() {
		$array = new ReferenceList();
		$snak1 = new PropertyNoValueSnak( 1 );
		$snak2 = new PropertyNoValueSnak( 3 );
		$snak3 = new PropertyNoValueSnak( 2 );

		$array->addNewReference( $snak1, $snak2, $snak3 );
		$expectedHash = '92244e1a91f60b7fa922d42441995442bf50adb5';
		$this->assertSame( $expectedHash, $array->getValueHash() );
	}

	public function testRemoveReferenceHashRemovesIdenticalObjects() {
		$reference = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$references = new ReferenceList( [ $reference, $reference ] );

		$references->removeReferenceHash( $reference->getHash() );

		$this->assertTrue( $references->isEmpty() );
	}

	public function testRemoveReferenceHashDoesNotRemoveCopies() {
		$reference = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$references = new ReferenceList( [ $reference, clone $reference ] );

		$references->removeReferenceHash( $reference->getHash() );

		$this->assertFalse( $references->isEmpty() );
		$this->assertTrue( $references->hasReference( $reference ) );
		$this->assertNotSame( $reference, $references->getReference( $reference->getHash() ) );
	}

	public function testRemoveReferenceHashUpdatesIndexes() {
		$reference1 = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$reference2 = new Reference( [ new PropertyNoValueSnak( 2 ) ] );
		$references = new ReferenceList( [ $reference1, $reference2 ] );

		$references->removeReferenceHash( $reference1->getHash() );

		$this->assertSame( 0, $references->indexOf( $reference2 ) );
	}

	public function testGivenOneSnak_addNewReferenceAddsSnak() {
		$references = new ReferenceList();
		$snak = new PropertyNoValueSnak( 1 );

		$references->addNewReference( $snak );
		$this->assertTrue( $references->hasReference( new Reference( [ $snak ] ) ) );
	}

	public function testGivenMultipleSnaks_addNewReferenceAddsThem() {
		$references = new ReferenceList();
		$snak1 = new PropertyNoValueSnak( 1 );
		$snak2 = new PropertyNoValueSnak( 3 );
		$snak3 = new PropertyNoValueSnak( 2 );

		$references->addNewReference( $snak1, $snak2, $snak3 );

		$expectedSnaks = [ $snak1, $snak2, $snak3 ];
		$this->assertTrue( $references->hasReference( new Reference( $expectedSnaks ) ) );
	}

	public function testGivenAnArrayOfSnaks_addNewReferenceAddsThem() {
		$references = new ReferenceList();
		$snaks = [
			new PropertyNoValueSnak( 1 ),
			new PropertyNoValueSnak( 3 ),
			new PropertyNoValueSnak( 2 ),
		];

		$references->addNewReference( $snaks );
		$this->assertTrue( $references->hasReference( new Reference( $snaks ) ) );
	}

	public function testAddNewReferenceDoesNotIgnoreIdenticalObjects() {
		$list = new ReferenceList();
		$snak = new PropertyNoValueSnak( 1 );
		$list->addNewReference( $snak );
		$list->addNewReference( $snak );
		$this->assertCount( 2, $list );
	}

	public function testAddNewReferenceDoesNotIgnoreCopies() {
		$list = new ReferenceList();
		$snak = new PropertyNoValueSnak( 1 );
		$list->addNewReference( $snak );
		$list->addNewReference( clone $snak );
		$this->assertCount( 2, $list );
	}

	public function testGivenNoneSnak_addNewReferenceThrowsException() {
		$references = new ReferenceList();

		$this->expectException( InvalidArgumentException::class );
		$references->addNewReference( new PropertyNoValueSnak( 1 ), null );
	}

	public function testEmptySerializationStability() {
		$list = new ReferenceList();
		$this->assertSame( 'a:0:{}', $list->serialize() );
	}

	/**
	 * This test will change when the serialization format changes.
	 * If it is being changed intentionally, the test should be updated.
	 * It is just here to catch unintentional changes.
	 */
	public function testSerializationStability() {
		$list = new ReferenceList();
		$list->addNewReference( new PropertyNoValueSnak( 1 ) );

		/*
		 * https://wiki.php.net/rfc/custom_object_serialization
		 */
		if ( version_compare( phpversion(), '7.4', '>=' ) ) {
			$testString = "a:1:{i:0;O:28:\"Wikibase\DataModel\Reference\":1:{s:35:\"\x00Wikibase\DataModel\Reference"
				. "\x00snaks\";O:32:\"Wikibase\DataModel\Snak\SnakList\":2:{s:4:\"data\";a:1:{i:0;O:43:\"Wikibase\\"
				. 'DataModel\Snak\PropertyNoValueSnak":1:{i:0;s:2:"P1";}}s:5:"index";i:0;}}}';
		} else {
			$testString = "a:1:{i:0;O:28:\"Wikibase\\DataModel\\Reference\":1:{s:35:\"\x00Wikibase\\DataModel\\"
				. "Reference\x00snaks\";C:32:\"Wikibase\\DataModel\\Snak\\SnakList\":100:{a:2:{s:4:\""
				. 'data";a:1:{i:0;C:43:"Wikibase\\DataModel\\Snak\\PropertyNoValueSnak":2:{P1}}s:5'
				. ':"index";i:0;}}}}';
		}

		$this->assertSame(
			$testString,
			$list->serialize()
		);
	}

	public function testSerializeUnserializeRoundtrip() {
		$original = new ReferenceList();
		$original->addNewReference( new PropertyNoValueSnak( 1 ) );

		/** @var ReferenceList $clone */
		$clone = unserialize( serialize( $original ) );

		$this->assertTrue( $original->equals( $clone ) );
		$this->assertSame( $original->getValueHash(), $clone->getValueHash() );
		$this->assertSame( $original->serialize(), $clone->serialize() );
	}

	public function testUnserializeCreatesNonIdenticalClones() {
		$original = new ReferenceList();
		$reference = new Reference( [ new PropertyNoValueSnak( 1 ) ] );
		$original->addReference( $reference );

		/** @var ReferenceList $clone */
		$clone = unserialize( serialize( $original ) );
		$clone->addReference( $reference );

		$this->assertCount( 2, $clone );
	}

	public function testGivenEmptyList_isEmpty() {
		$references = new ReferenceList();
		$this->assertTrue( $references->isEmpty() );
	}

	public function testGivenNonEmptyList_isNotEmpty() {
		$references = new ReferenceList();
		$references->addNewReference( new PropertyNoValueSnak( 1 ) );

		$this->assertFalse( $references->isEmpty() );
	}

}
