<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\DisabledEntityTypesEntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\DisabledEntityTypesEntityLookup
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani
 */
class DisabledEntityTypesEntityLookupTest extends TestCase {

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {
		$entityLookup = $this->createMock( EntityLookup::class );

		$entityLookup->expects( $this->any() )
			->method( 'hasEntity' )
			->will( $this->returnValue( true ) );

		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnCallback( static function( EntityId $id ) {
				return $id->getSerialization();
			} ) );

		return $entityLookup;
	}

	public function testConstructor() {
		$lookup = new DisabledEntityTypesEntityLookup( $this->getEntityLookup(), [] );
		$this->assertInstanceOf(
			DisabledEntityTypesEntityLookup::class,
			$lookup
		);
	}

	public function testConstructor_exception() {
		$this->expectException( InvalidArgumentException::class );
		new DisabledEntityTypesEntityLookup( $this->getEntityLookup(), [ 0 ] );
	}

	public function testHasEntity() {
		$lookup = new DisabledEntityTypesEntityLookup( $this->getEntityLookup(), [] );

		$this->assertTrue( $lookup->hasEntity( new ItemId( 'Q22' ) ) );
	}

	public function testGetEntity() {
		$lookup = new DisabledEntityTypesEntityLookup( $this->getEntityLookup(), [] );

		for ( $i = 1; $i < 6; $i++ ) {
			$this->assertSame(
				'Q' . $i,
				$lookup->getEntity( new ItemId( 'Q' . $i ) )
			);
		}
	}

	public function testGetEntity_exceptionDisabledEntityType() {
		$lookup = new DisabledEntityTypesEntityLookup( $this->getEntityLookup(), [ 'item' ] );
		$this->expectException( EntityLookupException::class );
		$lookup->getEntity( new ItemId( 'Q1' ) );
	}

}
