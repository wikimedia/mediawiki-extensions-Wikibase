<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;

/**
 * @covers Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class RedirectResolvingEntityLookupTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param EntityId $id
	 *
	 * @return bool
	 */
	public function hasEntity( EntityId $id ) {
		return $this->getEntity( $id ) !== null;
	}

	/**
	 * @param EntityId $id
	 *
	 * @return null|Item
	 * @throws UnresolvedEntityRedirectException
	 */
	public function getEntity( EntityId $id ) {
		if ( $id->getSerialization() == 'Q11' ) {
			throw new UnresolvedEntityRedirectException( new ItemId( 'Q11' ), new ItemId( 'Q10' ) );
		}

		if ( $id->getSerialization() == 'Q12' ) {
			throw new UnresolvedEntityRedirectException( new ItemId( 'Q12' ), new ItemId( 'Q11' ) );
		}

		if ( $id->getSerialization() == 'Q21' ) {
			throw new UnresolvedEntityRedirectException( new ItemId( 'Q21' ), new ItemId( 'Q20' ) );
		}

		if ( $id->getSerialization() == 'Q10' ) {
			return new Item( $id );
		}

		return null;
	}

	/**
	 * @return EntityLookup
	 */
	public function getLookupDouble() {
		$mock = $this->getMock( 'Wikibase\DataModel\Services\Lookup\EntityLookup' );

		$mock->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnCallback( array( $this, 'getEntity' ) ) );

		$mock->expects( $this->any() )
			->method( 'hasEntity' )
			->will( $this->returnCallback( array( $this, 'hasEntity' ) ) );

		return $mock;
	}

	public function getEntityProvider() {
		return array(
			'no redirect' => array( new ItemId( 'Q10' ), new ItemId( 'Q10' ) ),
			'one redirect' => array( new ItemId( 'Q11' ), new ItemId( 'Q10' ) ),
		);
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

		$this->setExpectedException( 'Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException' );
		$lookup->getEntity( $id );
	}

	public function hasEntityProvider() {
		return array(
			'unknown entity' => array( new ItemId( 'Q7' ), false ),
			'no redirect' => array( new ItemId( 'Q10' ), true ),
			'one redirect' => array( new ItemId( 'Q11' ), true ),
			'broken redirect' => array( new ItemId( 'Q21' ), false ),
		);
	}

	/**
	 * @dataProvider hasEntityProvider
	 */
	public function testHasEntity( EntityId $id, $exists ) {
		$lookup = new RedirectResolvingEntityLookup( $this->getLookupDouble() );

		$this->assertEquals( $exists, $lookup->hasEntity( $id ) );
	}

}
