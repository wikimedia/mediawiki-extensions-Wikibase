<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Edrsf\DispatchingEntityRevisionLookup;
use Wikibase\Edrsf\EntityRevision;
use Wikibase\Edrsf\EntityRevisionLookup;
use Wikibase\Edrsf\StorageException;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers Wikibase\Lib\Store\DispatchingEntityRevisionLookup
 *
 * @group WikibaseStore
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class DispatchingEntityRevisionLookupTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|EntityRevisionLookup
	 */
	private function getDummyEntityRevisionLookup() {
		return $this->getMock( EntityRevisionLookup::class );
	}

	public function testGivenExistingRevision_getEntityRevisionReturnsIt() {
		$itemId = new ItemId( 'Q123' );
		$item = new Item( $itemId );

		$localLookup = $this->getDummyEntityRevisionLookup();
		$localLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->with( $itemId )
			->willReturn( new EntityRevision( $item, 123 ) );

		$foreignItemId = new ItemId( 'foo:Q303' );
		$foreignItem = new Item( $foreignItemId );

		$fooLookup = $this->getDummyEntityRevisionLookup();
		$fooLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->with( $foreignItemId )
			->willReturn( new EntityRevision( $foreignItem, 100 ) );

		$dispatchingLookup = new \Wikibase\Edrsf\DispatchingEntityRevisionLookup(
			array( '' => $localLookup, 'foo' => $fooLookup, )
		);

		$revision = $dispatchingLookup->getEntityRevision( new ItemId( 'Q123' ) );
		$foreignRevision = $dispatchingLookup->getEntityRevision( new ItemId( 'foo:Q303' ) );

		$this->assertTrue( $revision->getEntity()->equals( $item ) );
		$this->assertTrue( $revision->getEntity()->getId()->equals( $itemId ) );
		$this->assertEquals( 123, $revision->getRevisionId() );

		$this->assertTrue( $foreignRevision->getEntity()->equals( $foreignItem ) );
		$this->assertTrue( $foreignRevision->getEntity()->getId()->equals( $foreignItemId ) );
		$this->assertEquals( 100, $foreignRevision->getRevisionId() );
	}

	public function testGivenNotExistingEntityIdFromKnownRepository_getEntityRevisionReturnsNull() {
		$localLookup = $this->getDummyEntityRevisionLookup();
		$localLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->with( $this->anything() )
			->willReturn( null );

		$fooLookup = $this->getDummyEntityRevisionLookup();
		$fooLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->with( $this->anything() )
			->willReturn( null );

		$dispatchingLookup = new DispatchingEntityRevisionLookup(
			array( '' => $localLookup, 'foo' => $fooLookup, )
		);

		$this->assertNull( $dispatchingLookup->getEntityRevision( new ItemId( 'Q124' ) ) );
		$this->assertNull( $dispatchingLookup->getEntityRevision( new ItemId( 'foo:Q808' ) ) );
	}

	public function testGivenForeignEntityFromUnknownRepository_getEntityRevisionReturnsNull() {
		$localLookup = $this->getDummyEntityRevisionLookup();
		$localLookup->expects( $this->never() )->method( 'getEntityRevision' );

		$dispatchingLookup = new DispatchingEntityRevisionLookup( array( '' => $localLookup, ) );

		$this->assertNull( $dispatchingLookup->getEntityRevision( new ItemId( 'foo:Q123' ) ) );
	}

	public function testGivenExistingEntityId_getLatestRevisionIdReturnsTheId() {
		$localLookup = $this->getDummyEntityRevisionLookup();
		$localLookup->expects( $this->any() )
			->method( 'getLatestRevisionId' )
			->with( new ItemId( 'Q123' ) )
			->willReturn( 123 );

		$fooLookup = $this->getDummyEntityRevisionLookup();
		$fooLookup->expects( $this->any() )
			->method( 'getLatestRevisionId' )
			->with( new ItemId( 'foo:Q303' ) )
			->willReturn( 100 );

		$dispatchingLookup = new DispatchingEntityRevisionLookup(
			array( '' => $localLookup, 'foo' => $fooLookup, )
		);

		$this->assertEquals( 123, $dispatchingLookup->getLatestRevisionId( new ItemId( 'Q123' ) ) );
		$this->assertEquals( 100, $dispatchingLookup->getLatestRevisionId( new ItemId( 'foo:Q303' ) ) );
	}

	public function testGivenNotExistingEntityIdFromKnownRepository_getLatestRevisionIdReturnsFalse() {
		$localLookup = $this->getDummyEntityRevisionLookup();
		$localLookup->expects( $this->any() )
			->method( 'getLatestRevisionId' )
			->with( $this->anything() )
			->willReturn( false );

		$fooLookup = $this->getDummyEntityRevisionLookup();
		$fooLookup->expects( $this->any() )
			->method( 'getLatestRevisionId' )
			->with( $this->anything() )
			->willReturn( false );

		$dispatchingLookup = new DispatchingEntityRevisionLookup(
			array( '' => $localLookup, 'foo' => $fooLookup, )
		);

		$this->assertFalse( $dispatchingLookup->getLatestRevisionId( new ItemId( 'Q123' ) ) );
		$this->assertFalse( $dispatchingLookup->getLatestRevisionId( new ItemId( 'foo:Q303' ) ) );
	}

	public function testGivenForeignEntityFromUnknownRepository_getLatestRevisionIdReturnsFalse() {
		$localLookup = $this->getDummyEntityRevisionLookup();
		$localLookup->expects( $this->never() )->method( 'getLatestRevisionId' );

		$dispatchingLookup = new DispatchingEntityRevisionLookup( array( '' => $localLookup, ) );

		$this->assertFalse( $dispatchingLookup->getLatestRevisionId( new ItemId( 'foo:Q123' ) ) );
	}

	public function testLookupExceptionsAreNotCaught() {
		$localLookup = $this->getDummyEntityRevisionLookup();
		$localLookup->expects( $this->any() )
			->method( $this->anything() )
			->willThrowException( new StorageException( 'No such revision for entity Q123: 124' ) );

		$dispatchingLookup = new DispatchingEntityRevisionLookup( array( '' => $localLookup, ) );

		$this->setExpectedException( StorageException::class );
		$dispatchingLookup->getEntityRevision( new ItemId( 'Q123' ), 124 );
	}

	/**
	 * @dataProvider provideInvalidForeignLookups
	 */
	public function testGivenInvalidForeignLookups_exceptionIsThrown( array $lookups ) {
		$this->setExpectedException( ParameterAssertionException::class );
		new DispatchingEntityRevisionLookup( $lookups );
	}

	public function provideInvalidForeignLookups() {
		return array(
			'no lookups given' => array( array() ),
			'not an implementation of EntityRevisionLookup given as a lookup' => array(
				array( '' => new ItemId( 'Q123' ) ),
			),
			'non-string keys' => array(
				array(
					'' => $this->getDummyEntityRevisionLookup(),
					100 => $this->getDummyEntityRevisionLookup(),
				),
			),
			'repo name containing colon' => array(
				array(
					'' => $this->getDummyEntityRevisionLookup(),
					'fo:oo' => $this->getDummyEntityRevisionLookup(),
				),
			),
		);
	}

}
