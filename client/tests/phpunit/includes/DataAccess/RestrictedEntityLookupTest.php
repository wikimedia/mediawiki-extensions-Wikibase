<?php

namespace Wikibase\Client\Tests\DataAccess;

use Wikibase\Client\DataAccess\RestrictedEntityLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\DataAccess\RestrictedEntityLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch
 */
class RestrictedEntityLookupTest extends \PHPUnit_Framework_TestCase {

	private function getEntityLookup() {
		$entityLookup = $this->getMock( 'Wikibase\Lib\Store\EntityLookup' );

		$entityLookup->expects( $this->any() )
			->method( 'hasEntity' )
			->will( $this->returnValue( true ) );

		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnCallback( function( EntityId $entityId ) {
				return $entityId->getSerialization();
			} ) );

		return $entityLookup;
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
		$lookup->getEntity( new ItemId( 'Q3' ) ); // Q3 has already been loaded, thus doesn't count

		$this->assertSame( 5, $lookup->getEntityAccessCount() );
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

	/**
	 * @expectedException Wikibase\Client\DataAccess\EntityAccessLimitException
	 */
	public function testGetEntity_exception() {
		$lookup = new RestrictedEntityLookup( $this->getEntityLookup(), 3 );

		for ( $i = 1; $i < 6; $i++ ) {
			$lookup->getEntity( new ItemId( 'Q' . $i ) );
		}
	}

}
