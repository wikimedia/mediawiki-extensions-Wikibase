<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;

/**
 * @covers Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class RestrictedEntityLookupTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {
		$entityLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\EntityLookup' );

		$entityLookup->expects( $this->any() )
			->method( 'hasEntity' )
			->will( $this->returnValue( true ) );

		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return $id->getSerialization();
			} ) );

		return $entityLookup;
	}

	public function testConstructor() {
		$lookup = new RestrictedEntityLookup( $this->getEntityLookup(), 1 );
		$this->assertInstanceOf(
			'Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup',
			$lookup
		);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConstructor_exception() {
		new RestrictedEntityLookup( $this->getEntityLookup(), 0 );
	}

	public function testHasEntity() {
		$lookup = new RestrictedEntityLookup( $this->getEntityLookup(), 200 );

		$this->assertTrue( $lookup->hasEntity( new ItemId( 'Q22' ) ) );
	}

	public function testGetEntityAccessCount() {
		$lookup = new RestrictedEntityLookup( $this->getEntityLookup(), 200 );

		for ( $i = 1; $i < 6; $i++ ) {
			$lookup->getEntity( new ItemId( 'Q' . $i ) );
		}
		// Q3 has already been loaded, thus doesn't count
		$lookup->getEntity( new ItemId( 'Q3' ) );

		$this->assertSame( 5, $lookup->getEntityAccessCount() );
	}

	public function testReset() {
		$lookup = new RestrictedEntityLookup( $this->getEntityLookup(), 200 );
		$lookup->getEntity( new ItemId( 'Q1' ) );

		$lookup->reset();

		$lookup->getEntity( new ItemId( 'Q2' ) );
		$this->assertSame( 1, $lookup->getEntityAccessCount() );

		// An entity accessed before, but after reset counts again
		$lookup->getEntity( new ItemId( 'Q1' ) );
		$this->assertSame( 2, $lookup->getEntityAccessCount() );
	}

	public function testGetEntity() {
		$lookup = new RestrictedEntityLookup( $this->getEntityLookup(), 200 );

		for ( $i = 1; $i < 6; $i++ ) {
			$this->assertSame(
				'Q' . $i,
				$lookup->getEntity( new ItemId( 'Q' . $i ) )
			);
		}
	}

	public function testGetEntity_exception() {
		$lookup = new RestrictedEntityLookup( $this->getEntityLookup(), 3 );

		$lookup->getEntity( new ItemId( 'Q1' ) );
		$lookup->getEntity( new ItemId( 'Q2' ) );
		$lookup->getEntity( new ItemId( 'Q3' ) );

		$this->setExpectedException( '\Wikibase\DataModel\Services\Lookup\EntityAccessLimitException' );
		$lookup->getEntity( new ItemId( 'Q4' ) );
	}

	public function testHasEntityBeenAccessed() {
		$lookup = new RestrictedEntityLookup( $this->getEntityLookup(), 200 );
		$lookup->getEntity( new ItemId( 'Q2' ) );

		$this->assertTrue( $lookup->entityHasBeenAccessed( new ItemId( 'Q2' ) ) );
		$this->assertFalse( $lookup->entityHasBeenAccessed( new ItemId( 'Q42' ) ) );
	}

}
