<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class RedirectResolvingEntityLookupTest extends TestCase {

	/**
	 * @param EntityId $id
	 *
	 * @return null|Item
	 * @throws UnresolvedEntityRedirectException
	 */
	public function getEntity( EntityId $id ) {
		switch ( $id->getSerialization() ) {
			case 'Q10':
				return new Item( $id );
			case 'Q11':
				throw new UnresolvedEntityRedirectException( new ItemId( 'Q11' ), new ItemId( 'Q10' ) );
			case 'Q12':
				throw new UnresolvedEntityRedirectException( new ItemId( 'Q12' ), new ItemId( 'Q11' ) );
			case 'Q21':
				throw new UnresolvedEntityRedirectException( new ItemId( 'Q21' ), new ItemId( 'Q20' ) );
			default:
				return null;
		}
	}

	/**
	 * @return EntityLookup
	 */
	public function getLookupDouble() {
		$mock = $this->createMock( EntityLookup::class );

		$mock->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnCallback( [ $this, 'getEntity' ] ) );

		$mock->expects( $this->any() )
			->method( 'hasEntity' )
			->will( $this->returnCallback( function ( EntityId $id ) {
				return $this->getEntity( $id ) !== null;
			} ) );

		return $mock;
	}

	public function getEntityProvider() {
		return [
			'no redirect' => [ new ItemId( 'Q10' ), new ItemId( 'Q10' ) ],
			'one redirect' => [ new ItemId( 'Q11' ), new ItemId( 'Q10' ) ],
		];
	}

	/**
	 * @dataProvider getEntityProvider
	 */
	public function testGetEntity( EntityId $id, EntityId $expected ) {
		$lookup = new RedirectResolvingEntityLookup( $this->getLookupDouble() );

		$entity = $lookup->getEntity( $id );

		if ( $expected === null ) {
			$this->assertNull( $entity );
		} else {
			$this->assertTrue( $expected->equals( $entity->getId() ) );
		}
	}

	public function testGetEntity_missing() {
		$lookup = new RedirectResolvingEntityLookup( $this->getLookupDouble() );

		$id = new ItemId( 'Q7' ); // entity Q7 is not known
		$this->assertNull( $lookup->getEntity( $id ) );
	}

	public function testGetEntity_brokenRedirect() {
		$lookup = new RedirectResolvingEntityLookup( $this->getLookupDouble() );

		$id = new ItemId( 'Q20' ); // Q20 is a broken redirect
		$this->assertNull( $lookup->getEntity( $id ) );
	}

	public function testGetEntity_doubleRedirect() {
		$lookup = new RedirectResolvingEntityLookup( $this->getLookupDouble() );

		$id = new ItemId( 'Q12' ); // Q12 is a double redirect

		$this->expectException( UnresolvedEntityRedirectException::class );
		$lookup->getEntity( $id );
	}

	public function hasEntityProvider() {
		return [
			'unknown entity' => [ new ItemId( 'Q7' ), false ],
			'no redirect' => [ new ItemId( 'Q10' ), true ],
			'one redirect' => [ new ItemId( 'Q11' ), true ],
			'broken redirect' => [ new ItemId( 'Q21' ), false ],
		];
	}

	/**
	 * @dataProvider hasEntityProvider
	 */
	public function testHasEntity( EntityId $id, $exists ) {
		$lookup = new RedirectResolvingEntityLookup( $this->getLookupDouble() );

		$this->assertEquals( $exists, $lookup->hasEntity( $id ) );
	}

}
