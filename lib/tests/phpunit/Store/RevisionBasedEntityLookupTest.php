<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers \Wikibase\Lib\Store\RevisionBasedEntityLookup
 *
 * @group WikibaseEntityLookup
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class RevisionBasedEntityLookupTest extends \PHPUnit\Framework\TestCase {

	public function getEntityProvider() {
		$q10 = new ItemId( 'Q10' );
		$q11 = new ItemId( 'Q11' );

		$item10 = new Item( $q10 );
		$item10->setLabel( 'en', 'ten' );

		$repo = new MockRepository();
		$repo->putEntity( $item10 );

		return [
			'found' => [ $repo, $q10, $q10 ],
			'not found' => [ $repo, $q11, null ],
		];
	}

	/**
	 * @dataProvider getEntityProvider
	 *
	 * @param EntityRevisionLookup $revisionLookup
	 * @param EntityId $id
	 * @param EntityId|null $expected
	 */
	public function testGetEntity( EntityRevisionLookup $revisionLookup, EntityId $id, EntityId $expected = null ) {
		$entityLookup = new RevisionBasedEntityLookup( $revisionLookup );
		$entity = $entityLookup->getEntity( $id );

		if ( $expected === null ) {
			$this->assertNull( $entity );
		} else {
			$this->assertTrue( $expected->equals( $entity->getId() ) );
		}
	}

	public function hasEntityProvider() {
		$cases = $this->getEntityProvider();

		$cases = array_map( function( $case ) {
			// true if set an id is expected, false otherwise.
			$case[2] = $case[2] !== null;

			return $case;
		}, $cases );

		return $cases;
	}

	/**
	 * @dataProvider hasEntityProvider
	 *
	 * @param EntityRevisionLookup $revisionLookup
	 * @param EntityId $id
	 * @param bool $exists
	 */
	public function testHasEntity( EntityRevisionLookup $revisionLookup, EntityId $id, $exists ) {
		$entityLookup = new RevisionBasedEntityLookup( $revisionLookup );
		$actual = $entityLookup->hasEntity( $id );

		$this->assertEquals( $exists, $actual );
	}

	public function testWhenEntityLookupExceptionIsThrown_getEntityPassesItAlong() {
		$entityLookup = new RevisionBasedEntityLookup( $this->newEntityLookupExceptionThrowingRevisionLookup() );

		$this->expectException( UnresolvedEntityRedirectException::class );
		$entityLookup->getEntity( new ItemId( 'Q1' ) );
	}

	/**
	 * @return EntityRevisionLookup
	 */
	private function newEntityLookupExceptionThrowingRevisionLookup() {
		$revisionLookup = $this->createMock( EntityRevisionLookup::class );

		$revisionLookup->method( 'getEntityRevision' )
			->willThrowException( new UnresolvedEntityRedirectException(
				new ItemId( 'Q1' ),
				new ItemId( 'Q2' )
			) );

		$revisionLookup->method( 'getLatestRevisionId' )
			->willThrowException( new UnresolvedEntityRedirectException(
				new ItemId( 'Q1' ),
				new ItemId( 'Q2' )
			) );

		return $revisionLookup;
	}

	public function testWhenEntityLookupExceptionIsThrown_hasEntityPassesItAlong() {
		$entityLookup = new RevisionBasedEntityLookup( $this->newEntityLookupExceptionThrowingRevisionLookup() );

		$this->expectException( UnresolvedEntityRedirectException::class );
		$entityLookup->hasEntity( new ItemId( 'Q1' ) );
	}

	public function testWhenBadExceptionIsThrown_hasEntityRethrowsAsEntityLookupException() {
		$entityLookup = new RevisionBasedEntityLookup( $this->newBadExceptionThrowingRevisionLookup() );

		$this->expectException( EntityLookupException::class );
		$entityLookup->hasEntity( new ItemId( 'Q1' ) );
	}

	/**
	 * @return EntityRevisionLookup
	 */
	private function newBadExceptionThrowingRevisionLookup() {
		$revisionLookup = $this->createMock( EntityRevisionLookup::class );

		$revisionLookup->method( 'getEntityRevision' )
			->willThrowException( new \Exception( 'Someone killed a kitten' ) );

		$revisionLookup->method( 'getLatestRevisionId' )
			->willThrowException( new \Exception( 'Someone killed a kitten' ) );

		return $revisionLookup;
	}

	public function testWhenBadExceptionIsThrown_getEntityRethrowsAsEntityLookupException() {
		$entityLookup = new RevisionBasedEntityLookup( $this->newBadExceptionThrowingRevisionLookup() );

		$this->expectException( EntityLookupException::class );
		$entityLookup->getEntity( new ItemId( 'Q1' ) );
	}

}
