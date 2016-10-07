<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\MultiplexingEntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers Wikibase\Lib\Store\MultiplexingEntityRevisionLookup;
 *
 * @group WikibaseLib
 * @group WikibaseStore
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class MultiplexingEntityRevisionLookupTest extends \PHPUnit_Framework_TestCase {

	public function testGivenExistingRevisionOfLocalEntity_getEntityRevisionReturnsIt() {
		$localLookup = new MockEntityRevisionLookup();

		$item = new Item( new ItemId( 'Q123' ) );
		$localLookup->addEntity( $item, 123 );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( $localLookup );

		$revision = $multiplexingLookup->getEntityRevision( new ItemId( 'Q123' ) );

		$this->assertEquals( $item, $revision->getEntity() );
		$this->assertEquals( 123, $revision->getRevisionId() );
	}

	public function testGivenNotExistingLocalEntityId_getEntityRevisionReturnsNull() {
		$localLookup = new MockEntityRevisionLookup();
		$multiplexingLookup = new MultiplexingEntityRevisionLookup( $localLookup );

		$this->assertNull( $multiplexingLookup->getEntityRevision( new ItemId( 'Q123' ) ) );
	}

	public function testGivenExistingLocalEntityId_getLatestRevisionIdReturnsTheId() {
		$localLookup = new MockEntityRevisionLookup();

		$item = new Item( new ItemId( 'Q123' ) );
		$localLookup->addEntity( $item, 123 );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( $localLookup );

		$this->assertEquals( 123, $multiplexingLookup->getLatestRevisionId( new ItemId( 'Q123' ) ) );
	}

	public function testGivenNotExistingLocalEntityId_getLatestRevisionIdReturnsFalse() {
		$localLookup = new MockEntityRevisionLookup();
		$multiplexingLookup = new MultiplexingEntityRevisionLookup( $localLookup );

		$this->assertFalse( $multiplexingLookup->getLatestRevisionId( new ItemId( 'Q123' ) ) );
	}

	public function testLocalLookupExceptionsAreNotCaught() {
		$localLookup = new MockEntityRevisionLookup();

		$item = new Item( new ItemId( 'Q123' ) );
		$localLookup->addEntity( $item, 123 );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( $localLookup );

		$this->setExpectedException( StorageException::class );
		$multiplexingLookup->getEntityRevision( new ItemId( 'Q123' ), 124 );
	}

	public function testGivenExistingRevisionOfForeignEntityFromKnownRepository_getEntityRevisionReturnsIt() {
		$localLookup = new MockEntityRevisionLookup();
		$fooLookup = new MockEntityRevisionLookup();

		$item = new Item( new ItemId( 'foo:Q123' ) );
		$fooLookup->addEntity( $item, 123 );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup(
			$localLookup,
			[
				'foo' => $fooLookup,
			]
		);

		$revision = $multiplexingLookup->getEntityRevision( new ItemId( 'foo:Q123' ) );

		$this->assertEquals( $item, $revision->getEntity() );
		$this->assertEquals( 123, $revision->getRevisionId() );
	}

	public function testGivenNotExistingForeignEntityIdFromKnownRepository_getEntityRevisionReturnsNull() {
		$localLookup = new MockEntityRevisionLookup();
		$fooLookup = new MockEntityRevisionLookup();

		$multiplexingLookup = new MultiplexingEntityRevisionLookup(
			$localLookup,
			[
				'foo' => $fooLookup,
			]
		);

		$this->assertNull( $multiplexingLookup->getEntityRevision( new ItemId( 'foo:Q123' ) ) );
	}

	public function testGivenExistingForeignEntityIdFromKnownRepository_getLatestRevisionIdReturnsIheId() {
		$localLookup = new MockEntityRevisionLookup();
		$fooLookup = new MockEntityRevisionLookup();

		$item = new Item( new ItemId( 'foo:Q123' ) );
		$fooLookup->addEntity( $item, 123 );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup(
			$localLookup,
			[
				'foo' => $fooLookup,
			]
		);

		$this->assertEquals( 123, $multiplexingLookup->getLatestRevisionId( new ItemId( 'foo:Q123' ) ) );
	}

	public function testGivenNotExistingForeignEntityIdFromKnownRepository_getLatestRevisionIdReturnsFalse() {
		$localLookup = new MockEntityRevisionLookup();
		$fooLookup = new MockEntityRevisionLookup();

		$multiplexingLookup = new MultiplexingEntityRevisionLookup(
			$localLookup,
			[
				'foo' => $fooLookup,
			]
		);

		$this->assertFalse( $multiplexingLookup->getLatestRevisionId( new ItemId( 'foo:Q123' ) ) );
	}

	public function testForeignLookupExceptionsAreNotCaught() {
		$localLookup = new MockEntityRevisionLookup();
		$fooLookup = new MockEntityRevisionLookup();

		$item = new Item( new ItemId( 'foo:Q123' ) );
		$fooLookup->addEntity( $item, 123 );

		$multiplexingLookup = new MultiplexingEntityRevisionLookup(
			$localLookup,
			[
				'foo' => $fooLookup,
			]
		);

		$this->setExpectedException( StorageException::class );
		$multiplexingLookup->getEntityRevision( new ItemId( 'foo:Q123' ), 124 );
	}

	public function testGivenForeignEntityFromUnknownRepository_getEntityRevisionThrowsException() {
		$localLookup = new MockEntityRevisionLookup();

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( $localLookup );

		$this->setExpectedException( InvalidArgumentException::class );
		$multiplexingLookup->getEntityRevision( new ItemId( 'foo:Q123' ) );
	}

	public function testGivenForeignEntityFromUnknownRepository_getLatestRevisionIdThrowsException() {
		$localLookup = new MockEntityRevisionLookup();

		$multiplexingLookup = new MultiplexingEntityRevisionLookup( $localLookup );

		$this->setExpectedException( InvalidArgumentException::class );
		$multiplexingLookup->getLatestRevisionId( new ItemId( 'foo:Q123' ) );
	}

	/**
	 * @dataProvider provideInvalidForeignLookups
	 */
	public function testGivenInvalidForeignLookups_exceptionIsThrown( array $lookups ) {
		$localLookup = new MockEntityRevisionLookup();
		$this->setExpectedException( ParameterAssertionException::class );
		new MultiplexingEntityRevisionLookup( $localLookup, $lookups );
	}

	public function provideInvalidForeignLookups() {
		return array(
			'not an implementation of EntityRevisionLookup given as a lookup' => array(
				array( 'foo' => new ItemId( 'Q123' ) ),
			),
			'non-string keys' => array(
				array( 100 => new MockEntityRevisionLookup() ),
			),
			'repo name containing colon' => array(
				array( 'fo:oo' => new MockEntityRevisionLookup() ),
			),
			'empty string repo name' => array(
				array( '' => new MockEntityRevisionLookup() ),
			),
		);
	}

}
