<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Fixtures\FakeEntityDocument;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup
 *
 * @license GPL-2.0-or-later
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
		$this->expectException( EntityLookupException::class );
		$lookup->getEntity( new ItemId( 'Q1' ) );
	}

	public function testGivenIdInExceptionList_hasEntityThrowsException() {
		$lookup = new InMemoryEntityLookup();

		$lookup->addException( new EntityLookupException( new ItemId( 'Q1' ) ) );

		$lookup->hasEntity( new ItemId( 'Q2' ) );
		$this->expectException( EntityLookupException::class );
		$lookup->hasEntity( new ItemId( 'Q1' ) );
	}

	public function testGivenConstructorVarArgEntities_theyCanBeRetrieved() {
		$lookup = new InMemoryEntityLookup(
			new FakeEntityDocument( new ItemId( 'Q1' ) ),
			new FakeEntityDocument( new ItemId( 'Q2' ) )
		);

		$this->assertTrue( $lookup->hasEntity( new ItemId( 'Q1' ) ) );
		$this->assertTrue( $lookup->hasEntity( new ItemId( 'Q2' ) ) );
	}

	public function testGivenEntityWithoutIdInConstructor_exceptionIsThrown() {
		$this->expectException( InvalidArgumentException::class );

		new InMemoryEntityLookup(
			new FakeEntityDocument()
		);
	}

}
