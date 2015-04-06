<?php

namespace Wikibase\Lib\Test\Store;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @covers Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor
 *
 * @group WikibaseLib
 * @group WikibaseStore
 * @group Wikibase
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class PrefetchingWikiPageEntityMetaDataAccessorTest extends PHPUnit_Framework_TestCase {

	public function testPrefetch() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );

		$lookup = $this->getMock( 'Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor' );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformation' )
			->with( array( $q1, $q3, 3 => $q2 ) )
			->willReturn( array(
				'Q1' => 'Nyan',
				'Q2' => 'cat',
				'Q3' => '~=[,,_,,]:3'
			) );

		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup );

		// Prefetch Q1 and Q3
		$accessor->prefetch( array( $q1, $q3 ) );
		// Prefetch Q1 once more to test de-duplication
		$accessor->prefetch( array( $q1 ) );

		// This will trigger all three to be loaded
		$result = $accessor->loadRevisionInformationByEntityId(
			$q2, EntityRevisionLookup::LATEST_FROM_SLAVE );

		$this->assertSame( 'cat', $result );

		// No need to load this, already in cache
		$result = $accessor->loadRevisionInformationByEntityId(
			$q3, EntityRevisionLookup::LATEST_FROM_SLAVE );

		$this->assertSame( '~=[,,_,,]:3', $result );
	}

	public function testLoadRevisionInformation() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$fromMaster = EntityRevisionLookup::LATEST_FROM_MASTER;
		$fromSlave = EntityRevisionLookup::LATEST_FROM_SLAVE;

		$lookup = $this->getMock( 'Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor' );
		$lookup->expects( $this->exactly( 3 ) )
			->method( 'loadRevisionInformation' )
			->willReturnCallback( function( array $entityIds, $mode ) {
				$ret = array();
				foreach ( $entityIds as $entityId ) {
					$ret[$entityId->getSerialization()] = $mode . ':' . $entityId->getSerialization();
				}

				return $ret;
			} );

		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup );
		// Prefetch Q1 and Q3
		$accessor->prefetch( array( $q1, $q3 ) );

		// This will trigger loading Q1, Q2 and Q3
		$result = $accessor->loadRevisionInformation( array( $q2 ), $fromSlave );

		$this->assertSame( array( 'Q2' => "$fromSlave:Q2" ), $result );

		// This can be served entirely from cache
		$result = $accessor->loadRevisionInformation( array( $q1, $q3 ), $fromSlave );

		$this->assertSame(
			array( 'Q1' => "$fromSlave:Q1", 'Q3' => "$fromSlave:Q3" ),
			$result
		);

		// Fetch Q2 and Q5. Q2 is already cached Q5 needs to be loaded
		$result = $accessor->loadRevisionInformation( array( $q2, $q5 ), $fromSlave );

		$this->assertSame(
			array( 'Q2' => "$fromSlave:Q2", 'Q5' => "$fromSlave:Q5" ),
			$result
		);

		// Fetch Q4 from master
		$result = $accessor->loadRevisionInformation( array( $q4 ), $fromMaster );

		$this->assertSame( array( 'Q4' => "$fromMaster:Q4" ), $result );

		// Fetch Q2 and Q4, both from cache
		$result = $accessor->loadRevisionInformation( array( $q2, $q4 ), $fromSlave );

		$this->assertSame(
			array( 'Q2' => "$fromSlave:Q2", 'Q4' => "$fromMaster:Q4" ),
			$result
		);
	}

	public function testLoadRevisionInformationByEntityId() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );

		$lookup = $this->getMock( 'Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor' );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformation' )
			->with( array( $q2 ), EntityRevisionLookup::LATEST_FROM_SLAVE )
			->willReturnCallback( function() {
				return array( 'Q2' => 'foo' );
			} );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformationByEntityId' )
			->with( $q1, EntityRevisionLookup::LATEST_FROM_MASTER )
			->willReturnCallback( function() {
				return 'bar';
			} );

		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup );

		$result = $accessor->loadRevisionInformationByEntityId(
			$q2, EntityRevisionLookup::LATEST_FROM_SLAVE );

		$this->assertSame( 'foo', $result );

		$result = $accessor->loadRevisionInformationByEntityId(
			$q1, EntityRevisionLookup::LATEST_FROM_MASTER );

		$this->assertSame( 'bar', $result );

		// Fetch again, doesn't re-invoke the lookup
		$result = $accessor->loadRevisionInformationByEntityId(
			$q1, EntityRevisionLookup::LATEST_FROM_SLAVE );

		$this->assertSame( 'bar', $result );
	}

	public function testLoadRevisionInformationByRevisionId() {
		// This function is a very simple, it's just a wrapper around the
		// lookup function.
		$q1 = new ItemId( 'Q1' );

		$lookup = $this->getMock( 'Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor' );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformationByRevisionId' )
			->with( $q1, 123 )
			->willReturn( 'passthrough' );

		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup );

		$result = $accessor->loadRevisionInformationByRevisionId( $q1, 123 );

		$this->assertSame( 'passthrough', $result );
	}

	/**
	 * Makes sure that calling $method with $params will purge the cache
	 * for Q1.
	 *
	 * @param string $method
	 * @param array $params
	 */
	private function purgeMethodTest( $method, array $params ) {
		$q1 = new ItemId( 'Q1' );

		$lookup = $this->getMock( 'Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor' );
		$lookup->expects( $this->exactly( 2 ) )
			->method( 'loadRevisionInformation' )
			->with( array( $q1 ) )
			->willReturnCallback( function( array $entityIds ) {
				static $firstCall = true;
				if ( $firstCall ) {
					$firstCall = false;
					return array( 'Q1' => 'Foo' );
				} else {
					return array( 'Q1' => 'Bar' );
				}
			} );

		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup );

		$result = $accessor->loadRevisionInformationByEntityId(
			$q1, EntityRevisionLookup::LATEST_FROM_SLAVE );

		$this->assertSame( 'Foo', $result );

		call_user_func_array( array( $accessor, $method ), $params );

		// Load it again after purge
		$result = $accessor->loadRevisionInformationByEntityId(
			$q1, EntityRevisionLookup::LATEST_FROM_SLAVE );

		$this->assertSame( 'Bar', $result );
	}

	public function testPurge() {
		$this->purgeMethodTest( 'purge', array( new ItemId( 'Q1' ) ) );
	}

	public function testEntityDeleted() {
		$this->purgeMethodTest( 'entityDeleted', array( new ItemId( 'Q1' ) ) );
	}

	public function testEntityUpdated() {
		$entityRevision = new EntityRevision(
			new Item( new ItemId( 'Q1' ) ),
			123
		);

		$this->purgeMethodTest( 'entityUpdated', array( $entityRevision ) );
	}

	public function testRedirectUpdated() {
		$entityRedirect = new EntityRedirect(
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' )
		);

		$this->purgeMethodTest( 'redirectUpdated', array( $entityRedirect, 123 ) );
	}

}
