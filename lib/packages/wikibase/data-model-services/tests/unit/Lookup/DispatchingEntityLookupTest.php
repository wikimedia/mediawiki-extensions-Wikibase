<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Fixtures\FakeEntityDocument;
use Wikibase\DataModel\Services\Lookup\DispatchingEntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\DispatchingEntityLookup
 *
 * @license GPL-2.0-or-later
 */
class DispatchingEntityLookupTest extends TestCase {

	/**
	 * @dataProvider provideInvalidForeignLookups
	 */
	public function testGivenInvalidForeignLookups_exceptionIsThrown( array $lookups ) {
		$this->expectException( ParameterAssertionException::class );
		new DispatchingEntityLookup( $lookups );
	}

	public function provideInvalidForeignLookups() {
		return [
			'no lookups given' => [ [] ],
			'not an implementation of EntityLookup given as a lookup' => [
				[ '' => new ItemId( 'Q123' ) ],
			],
			'non-string keys' => [
				[ '' => new InMemoryEntityLookup(), 100 => new InMemoryEntityLookup() ],
			],
			'repo name containing colon' => [
				[ '' => new InMemoryEntityLookup(),	'fo:oo' => new InMemoryEntityLookup() ],
			],
		];
	}

	public function testGivenExistingEntityId_getEntityReturnsTheEntity() {
		$localLookup = new InMemoryEntityLookup();
		$localLookup->addEntity( new FakeEntityDocument( new ItemId( 'Q1' ) ) );
		$fooLookup = new InMemoryEntityLookup();
		$fooLookup->addEntity( new FakeEntityDocument( new NumericPropertyId( 'foo:P11' ) ) );

		$dispatchingLookup = new DispatchingEntityLookup( [ '' => $localLookup, 'foo' => $fooLookup ] );

		$expected = new FakeEntityDocument( new ItemId( 'Q1' ) );
		$actual = $dispatchingLookup->getEntity( new ItemId( 'Q1' ) );
		$this->assertTrue( $actual->equals( $expected ) );
		$this->assertTrue( $actual->getId()->equals( new ItemId( 'Q1' ) ) );

		$expected = new FakeEntityDocument( new NumericPropertyId( 'foo:P11' ) );
		$actual = $dispatchingLookup->getEntity( new NumericPropertyId( 'foo:P11' ) );
		$this->assertTrue( $actual->equals( $expected ) );
		$this->assertTrue( $actual->getId()->equals( new NumericPropertyId( 'foo:P11' ) ) );
	}

	public function testGivenNotExistingEntityIdFromKnownRepository_getEntityReturnsNull() {
		$localLookup = new InMemoryEntityLookup();
		$fooLookup = new InMemoryEntityLookup();
		$dispatchingLookup = new DispatchingEntityLookup( [ '' => $localLookup, 'foo' => $fooLookup ] );
		$this->assertNull( $dispatchingLookup->getEntity( new ItemId( 'Q1' ) ) );
		$this->assertNull( $dispatchingLookup->getEntity( new ItemId( 'foo:Q19' ) ) );
	}

	public function testGivenEntityIdFromUnknownRepository_getEntityReturnsNull() {
		$dispatchingLookup = new DispatchingEntityLookup( [ '' => new InMemoryEntityLookup() ] );

		$this->assertNull( $dispatchingLookup->getEntity( new ItemId( 'foo:Q1' ) ) );
	}

	/**
	 * @param Exception $exception
	 *
	 * @return EntityLookup
	 */
	private function getExceptionThrowingLookup( Exception $exception ) {
		$lookup = $this->createMock( EntityLookup::class );
		$lookup->expects( $this->any() )
			->method( $this->anything() )
			->will( $this->throwException( $exception ) );
		return $lookup;
	}

	public function testLookupExceptionsAreNotCaughtInGetEntity() {
		$lookup = $this->getExceptionThrowingLookup( new EntityLookupException( new ItemId( 'Q321' ) ) );

		$dispatchingLookup = new DispatchingEntityLookup( [ '' => $lookup ] );

		$this->expectException( EntityLookupException::class );
		$dispatchingLookup->getEntity( new ItemId( 'Q321' ) );
	}

	public function testGivenExistingEntityId_hasEntityReturnsTrue() {
		$localLookup = new InMemoryEntityLookup();
		$localLookup->addEntity( new FakeEntityDocument( new ItemId( 'Q1' ) ) );
		$fooLookup = new InMemoryEntityLookup();
		$fooLookup->addEntity( new FakeEntityDocument( new NumericPropertyId( 'foo:P11' ) ) );

		$dispatchingLookup = new DispatchingEntityLookup( [ '' => $localLookup, 'foo' => $fooLookup ] );

		$this->assertTrue( $dispatchingLookup->hasEntity( new ItemId( 'Q1' ) ) );
		$this->assertTrue( $dispatchingLookup->hasEntity( new NumericPropertyId( 'foo:P11' ) ) );
	}

	public function testGivenNotExistingEntityIdFromKnownRepository_hasEntityReturnsFalse() {
		$localLookup = new InMemoryEntityLookup();
		$fooLookup = new InMemoryEntityLookup();

		$dispatchingLookup = new DispatchingEntityLookup( [ '' => $localLookup, 'foo' => $fooLookup ] );

		$this->assertFalse( $dispatchingLookup->hasEntity( new ItemId( 'Q1' ) ) );
		$this->assertFalse( $dispatchingLookup->hasEntity( new ItemId( 'foo:Q19' ) ) );
	}

	public function testGivenEntityIdFromUnknownRepository_hasEntityReturnsFalse() {
		$dispatchingLookup = new DispatchingEntityLookup( [ '' => $this->createMock( EntityLookup::class ) ] );

		$this->assertFalse( $dispatchingLookup->hasEntity( new ItemId( 'foo:Q1' ) ) );
	}

	public function testLookupExceptionsAreNotCaughtInHasEntity() {
		$lookup = $this->getExceptionThrowingLookup( new EntityLookupException( new ItemId( 'Q321' ) ) );

		$dispatchingLookup = new DispatchingEntityLookup( [ '' => $lookup ] );

		$this->expectException( EntityLookupException::class );
		$dispatchingLookup->hasEntity( new ItemId( 'Q321' ) );
	}

}
