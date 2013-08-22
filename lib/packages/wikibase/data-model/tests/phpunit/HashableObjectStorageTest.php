<?php

namespace Wikibase\Test;

use Wikibase\HashableObjectStorage;
use Wikibase\Hashable;

/**
 * @covers Wikibase\HashableObjectStorage
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group HashableObjectStorageTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class HashableObjectStorageTest extends \PHPUnit_Framework_TestCase {

	public function testRemoveDuplicates() {
		$list = new HashableObjectStorage();

		$list->attach( new HashableObject( 1 ) );
		$list->attach( new HashableObject( 2 ) );
		$list->attach( new HashableObject( 3 ) );

		$this->assertEquals(
			3,
			count( $list ),
			'Adding 3 elements should result in a size of 3'
		);

		$list->removeDuplicates();

		$this->assertEquals(
			3,
			count( $list ),
			'Removing duplicates from a HashableObjectStorage without duplicates should not alter its size'
		);

		$list->attach( new HashableObject( 1 ) );
		$list->attach( new HashableObject( 2 ) );
		$list->attach( new HashableObject( 4 ) );

		$this->assertEquals(
			6,
			count( $list ),
			'Adding duplicates to HashableObjectStorage should increase its size'
		);

		$list->removeDuplicates();

		$this->assertEquals(
			4,
			count( $list ),
			'Removing duplicates from a HashableObjectStorage with duplicates should decrease its size'
		);
	}

	public function testGetValueHash() {
		$list = new HashableObjectStorage();
		$originalList = clone $list;

		$hash = $list->getValueHash();
		$this->assertInternalType( 'string', $hash );

		$one = new HashableObject( 1 );
		$two = new HashableObject( 1 );

		$list->attach( $one );
		$list->attach( $two );

		$newHash = $list->getValueHash();

		$this->assertNotEquals(
			$hash,
			$newHash,
			'The hash of HashableObjectStorage with different content should be different'
		);

		$this->assertFalse( $list->equals( $originalList ) );

		$originalList = clone $list;

		$list->detach( $one );
		$list->detach( $two );

		$list->attach( $two );
		$list->attach( $one );

		$this->assertEquals(
			$newHash,
			$list->getValueHash(),
			'The hash of HashableObjectStorage with the same elements in different order should be the same'
		);

		$this->assertTrue( $list->equals( $originalList ) );

		$list->detach( $one );
		$list->detach( $two );

		$list->attach( new HashableObject( 1 ) );
		$list->attach( new HashableObject( 1 ) );

		$this->assertEquals(
			$newHash,
			$list->getValueHash(),
			'The hash of HashableObjectStorage with different instances of the same elemnets should be the same'
		);

		$this->assertTrue( $list->equals( $originalList ) );
	}

	public function testEquals() {
		$list = new HashableObjectStorage();

		$this->assertTrue( $list->equals( $list ), 'Empty list should be equal to itself' );

		$newList = clone $list;

		$this->assertTrue( $list->equals( $newList ), 'Empty list should be equal to a clone of itself' );

		$newList->attach( new HashableObject( 1 ) );

		$this->assertFalse( $list->equals( $newList ), 'Empty list should not be equal to a list with an element' );

		$list->attach( new HashableObject( 1 ) );

		$this->assertTrue( $list->equals( $newList ), 'Two lists with the same element should be equal' );
	}

}

class HashableObject implements \Hashable {

	protected $var;

	public function __construct( $var ) {
		$this->var = $var;
	}

	public function getHash() {
		return sha1( $this->var );
	}

}