<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;

/**
 * @covers Wikibase\Lib\Store\RevisionBasedEntityLookup
 *
 * @group WikibaseLib
 * @group WikibaseEntityLookup
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class RevisionBasedEntityLookupTest extends \PHPUnit_Framework_TestCase {

	public function getEntityProvider() {
		$q10 = new ItemId( 'Q10' );
		$q11 = new ItemId( 'Q11' );

		$item10 = Item::newEmpty();
		$item10->setId( $q10 );
		$item10->setLabel( 'en', 'ten' );

		$repo = new MockRepository();
		$repo->putEntity( $item10 );

		return array(
			'found' => array( $repo, $q10, $q10 ),
			'not found' => array( $repo, $q11, null ),
		);
	}

	/**
	 * @dataProvider getEntityProvider
	 *
	 * @param EntityRevisionLookup $revisionLookup
	 * @param EntityId $id
	 * @param EntityId $expected
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

}
