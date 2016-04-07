<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Fixtures\FakeEntityDocument;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;

/**
 * @covers Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class InMemoryEntityLookupTest extends \PHPUnit_Framework_TestCase {

	public function testGivenUnknownEntityId_getEntityReturnsNull() {
		$lookup = new InMemoryEntityLookup();
		$this->assertNull( $lookup->getEntity( new ItemId( 'Q1' ) ) );
	}

	public function testGivenKnownEntityId_getEntityReturnsTheEntity() {
		$lookup = new InMemoryEntityLookup();

		$lookup->addEntity( new FakeEntityDocument( new ItemId( 'Q1' ) ) );
		$lookup->addEntity( new FakeEntityDocument( new ItemId( 'Q2' ) ) );
		$lookup->addEntity( new FakeEntityDocument( new ItemId( 'Q3' ) ) );

		$this->assertEquals(
			new FakeEntityDocument( new ItemId( 'Q2' ) ),
			$lookup->getEntity( new ItemId( 'Q2' ) )
		);
	}

	public function testGivenUnknownEntityId_hasEntityReturnsFalse() {
		$lookup = new InMemoryEntityLookup();
		$this->assertFalse( $lookup->hasEntity( new ItemId( 'Q1' ) ) );
	}

	public function testGivenKnownEntityId_hasEntityReturnsTrue() {
		$lookup = new InMemoryEntityLookup();

		$lookup->addEntity( new FakeEntityDocument( new ItemId( 'Q1' ) ) );
		$lookup->addEntity( new FakeEntityDocument( new ItemId( 'Q2' ) ) );
		$lookup->addEntity( new FakeEntityDocument( new ItemId( 'Q3' ) ) );

		$this->assertTrue( $lookup->hasEntity( new ItemId( 'Q2' ) ) );
	}

	public function testGivenIdInExceptionList_getEntityThrowsException() {
		$lookup = new InMemoryEntityLookup();

		$lookup->addException( new EntityLookupException( new ItemId( 'Q1' ) ) );

		$lookup->getEntity( new ItemId( 'Q2' ) );
		$this->setExpectedException( 'Wikibase\DataModel\Services\Lookup\EntityLookupException' );
		$lookup->getEntity( new ItemId( 'Q1' ) );
	}

	public function testGivenIdInExceptionList_hasEntityThrowsException() {
		$lookup = new InMemoryEntityLookup();

		$lookup->addException( new EntityLookupException( new ItemId( 'Q1' ) ) );

		$lookup->hasEntity( new ItemId( 'Q2' ) );
		$this->setExpectedException( 'Wikibase\DataModel\Services\Lookup\EntityLookupException' );
		$lookup->hasEntity( new ItemId( 'Q1' ) );
	}

}
