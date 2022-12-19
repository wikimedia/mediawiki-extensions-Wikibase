<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use DataValues\StringValue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Entity\NullEntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingClosestReferencedEntityIdLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\MaxReferencedEntityVisitsExhaustedException;
use Wikibase\DataModel\Services\Lookup\MaxReferenceDepthExhaustedException;
use Wikibase\DataModel\Services\Lookup\ReferencedEntityIdLookupException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\EntityRetrievingClosestReferencedEntityIdLookup
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class EntityRetrievingClosestReferencedEntityIdLookupTest extends TestCase {

	/**
	 * @param EntityLookup $entityLookup
	 * @param int|null $expectedNumberOfGetEntityCalls
	 * @return EntityLookup
	 */
	private function restrictEntityLookup( EntityLookup $entityLookup, $expectedNumberOfGetEntityCalls = null ) {
		$entityLookupMock = $this->createMock( EntityLookup::class );

		$entityLookupMock->expects(
			$expectedNumberOfGetEntityCalls === null ? $this->any() : $this->exactly( $expectedNumberOfGetEntityCalls )
		)
			->method( 'getEntity' )
			->willReturnCallback( static function ( EntityId $entityId ) use ( $entityLookup ) {
				return $entityLookup->getEntity( $entityId );
			} );

		return $entityLookupMock;
	}

	/**
	 * @param int $expectedPrefetches
	 * @return EntityPrefetcher
	 */
	private function newEntityPrefetcher( $expectedPrefetches ) {
		$entityPrefetcher = $this->createMock( EntityPrefetcher::class );
		$entityPrefetcher->expects( $this->exactly( $expectedPrefetches ) )
			->method( 'prefetch' )
			->with( $this->isType( 'array' ) );

		return $entityPrefetcher;
	}

	/**
	 * @param NumericPropertyId $via
	 * @param EntityId[] $to
	 *
	 * @return StatementList
	 */
	private function newReferencingStatementList( NumericPropertyId $via, array $to ) {
		$statementList = new StatementList();

		foreach ( $to as $toId ) {
			$value = new EntityIdValue( $toId );
			$mainSnak = new PropertyValueSnak( $via, $value );
			$statementList->addStatement( new Statement( $mainSnak ) );
		}

		return $statementList;
	}

	/**
	 * @return EntityLookup
	 */
	private function newReferencingEntityStructure() {
		// This returns the following entity structure (all entities linked by P599)
		// Q1 -> Q5 -> Q599 -> Q1234
		//   \             \
		//    \             -- Q12 -> Q404
		//     --- Q90 -> Q3
		// Note: Q404 doesn't exist

		$pSubclassOf = new NumericPropertyId( 'P599' );
		$q1 = new ItemId( 'Q1' );
		$q5 = new ItemId( 'Q5' );
		$q599 = new ItemId( 'Q599' );
		$q12 = new ItemId( 'Q12' );
		$q404 = new ItemId( 'Q404' );
		$q1234 = new ItemId( 'Q1234' );
		$q90 = new ItemId( 'Q90' );
		$q3 = new ItemId( 'Q3' );

		$lookup = new InMemoryEntityLookup();

		$lookup->addEntity(
			new Item( $q1, null, null, $this->newReferencingStatementList( $pSubclassOf, [ $q5, $q90 ] ) )
		);
		$lookup->addEntity(
			new Item( $q5, null, null, $this->newReferencingStatementList( $pSubclassOf, [ $q599 ] ) )
		);
		$lookup->addEntity(
			new Item( $q599, null, null, $this->newReferencingStatementList( $pSubclassOf, [ $q12, $q1234 ] ) )
		);
		$lookup->addEntity(
			new Item( $q12, null, null, $this->newReferencingStatementList( $pSubclassOf, [ $q404 ] ) )
		);
		$lookup->addEntity(
			new Item( $q90, null, null, $this->newReferencingStatementList( $pSubclassOf, [ $q3 ] ) )
		);
		$lookup->addEntity( new Item( $q1234, null, null, null ) );
		$lookup->addEntity( new Item( $q3, null, null, null ) );

		return $lookup;
	}

	/**
	 * @return EntityLookup
	 */
	private function newCircularReferencingEntityStructure() {
		// This returns the following entity structure (all entities linked by P599)
		// Q1 -> Q5 -> Q1 -> Q5 -> …
		//   \           \
		//    --- Q90     --- Q90

		$pSubclassOf = new NumericPropertyId( 'P599' );
		$q1 = new ItemId( 'Q1' );
		$q5 = new ItemId( 'Q5' );
		$q90 = new ItemId( 'Q90' );

		$lookup = new InMemoryEntityLookup();

		$lookup->addEntity(
			new Item( $q1, null, null, $this->newReferencingStatementList( $pSubclassOf, [ $q5, $q90 ] ) )
		);
		$lookup->addEntity(
			new Item( $q5, null, null, $this->newReferencingStatementList( $pSubclassOf, [ $q1 ] ) )
		);
		$lookup->addEntity(
			new Item( $q90, null, null, null )
		);

		return $lookup;
	}

	public function provideGetReferencedEntityIdNoError() {
		$pSubclassOf = new NumericPropertyId( 'P599' );
		$q1 = new ItemId( 'Q1' );
		$q3 = new ItemId( 'Q3' );
		$q5 = new ItemId( 'Q5' );
		$q12 = new ItemId( 'Q12' );
		$q403 = new ItemId( 'Q403' );
		$q404 = new ItemId( 'Q404' );
		$referencingEntityStructureLookup = $this->newReferencingEntityStructure();
		$circularReferencingEntityStructure = $this->newCircularReferencingEntityStructure();

		return [
			'empty list of target ids' => [
				null,
				0,
				0,
				$referencingEntityStructureLookup,
				$q1,
				$pSubclassOf,
				[],
			],
			'no such statement' => [
				null,
				1,
				0,
				$referencingEntityStructureLookup,
				$q1,
				new NumericPropertyId( 'P12345' ),
				[ $q5 ],
			],
			'from id does not exist' => [
				null,
				1,
				0,
				$referencingEntityStructureLookup,
				$q404,
				$pSubclassOf,
				[ $q5 ],
			],
			'directly referenced entity #1' => [
				$q5,
				1,
				0,
				$referencingEntityStructureLookup,
				$q1,
				$pSubclassOf,
				[ $q5 ],
			],
			'directly referenced entity #2' => [
				$q1,
				1,
				0,
				$circularReferencingEntityStructure,
				$q5,
				$pSubclassOf,
				[ $q12, $q403, $q1, $q404 ],
			],
			'directly referenced entity, two target ids' => [
				$q5,
				1,
				0,
				$referencingEntityStructureLookup,
				$q1,
				$pSubclassOf,
				[ $q5, $q404 ],
			],
			'indirectly referenced entity #1' => [
				$q3,
				3,
				1,
				$referencingEntityStructureLookup,
				$q1,
				$pSubclassOf,
				[ $q3 ],
			],
			'indirectly referenced entity #2' => [
				$q12,
				4,
				2,
				$referencingEntityStructureLookup,
				$q1,
				$pSubclassOf,
				[ $q12 ],
			],
			'indirectly referenced entity, multiple target ids' => [
				$q12,
				4,
				2,
				$referencingEntityStructureLookup,
				$q1,
				$pSubclassOf,
				[ $q12, $q403, $q404 ],
			],
			'indirectly referenced entity, multiple target ids' => [
				$q12,
				4,
				2,
				$referencingEntityStructureLookup,
				$q1,
				$pSubclassOf,
				[ $q12, $q403, $q404 ],
			],
			'circular reference detection' => [
				null,
				3,
				1,
				$circularReferencingEntityStructure,
				$q1,
				$pSubclassOf,
				[ $q403, $q404 ],
			],
		];
	}

	/**
	 * @dataProvider provideGetReferencedEntityIdNoError
	 */
	public function testGetReferencedEntityIdNoError(
		?EntityId $expectedToId,
		$maxEntityVisits,
		$maxDepth,
		EntityLookup $entityLookup,
		EntityId $fromId,
		NumericPropertyId $propertyId,
		array $toIds
	) {
		// Number of prefetching operations to expect (Note: We call getReferencedEntityId twice)
		$expectedNumberOfPrefetches = $maxEntityVisits ? ( $maxDepth + 1 ) * 2 : 0;

		$lookup = new EntityRetrievingClosestReferencedEntityIdLookup(
			$this->restrictEntityLookup( $entityLookup, $maxEntityVisits * 2 ),
			$this->newEntityPrefetcher( $expectedNumberOfPrefetches ),
			$maxDepth,
			$maxEntityVisits
		);
		$result = $lookup->getReferencedEntityId( $fromId, $propertyId, $toIds );

		$this->assertEquals( $expectedToId, $result );

		// Run again to see if the maxDepth/visitedEntityRelated state is properly resetted
		$this->assertEquals(
			$expectedToId,
			$lookup->getReferencedEntityId( $fromId, $propertyId, $toIds )
		);
	}

	public function provideGetReferencedEntityIdMaxDepthExceeded() {
		$cases = $this->provideGetReferencedEntityIdNoError();

		foreach ( $cases as $caseName => $case ) {
			if ( end( $case ) === [] ) {
				// In case we search for nothing, the max depth can't ever be exceeded
				continue;
			}

			// Remove expected to id
			array_shift( $case );
			// Reduce max depth by 1
			$case[1]--;

			yield $caseName => $case;
		}
	}

	/**
	 * @dataProvider provideGetReferencedEntityIdMaxDepthExceeded
	 */
	public function testGetReferencedEntityIdMaxDepthExceeded(
		$maxEntityVisits,
		$maxDepth,
		EntityLookup $entityLookup,
		EntityId $fromId,
		NumericPropertyId $propertyId,
		array $toIds
	) {
		$lookup = new EntityRetrievingClosestReferencedEntityIdLookup(
			$this->restrictEntityLookup( $entityLookup ),
			new NullEntityPrefetcher(),
			$maxDepth,
			$maxEntityVisits
		);

		try {
			$lookup->getReferencedEntityId( $fromId, $propertyId, $toIds );
		} catch ( MaxReferenceDepthExhaustedException $exception ) {
			$this->assertSame( $maxDepth, $exception->getMaxDepth() );

			return;
		}
		$this->fail( 'No exception thrown!' );
	}

	public function provideGetReferencedEntityIdMaxEntityVisitsExceeded() {
		$cases = $this->provideGetReferencedEntityIdNoError();

		foreach ( $cases as $caseName => $case ) {
			if ( end( $case ) === [] ) {
				// In case we search for nothing, no entity will ever be loaded
				continue;
			}

			// Remove expected to id
			array_shift( $case );
			// Reduce max entity visits by 1
			$case[0]--;

			yield $caseName => $case;
		}
	}

	/**
	 * @dataProvider provideGetReferencedEntityIdMaxEntityVisitsExceeded
	 */
	public function testGetReferencedEntityIdMaxEntityVisitsExceeded(
		$maxEntityVisits,
		$maxDepth,
		EntityLookup $entityLookup,
		EntityId $fromId,
		NumericPropertyId $propertyId,
		array $toIds
	) {
		$lookup = new EntityRetrievingClosestReferencedEntityIdLookup(
			$this->restrictEntityLookup( $entityLookup, $maxEntityVisits ),
			new NullEntityPrefetcher(),
			$maxDepth,
			$maxEntityVisits
		);

		try {
			$lookup->getReferencedEntityId( $fromId, $propertyId, $toIds );
		} catch ( MaxReferencedEntityVisitsExhaustedException $exception ) {
			$this->assertSame( $maxEntityVisits, $exception->getMaxEntityVisits() );

			return;
		}
		$this->fail( 'No exception thrown!' );
	}

	public function provideGetReferencedEntityIdTestInvalidSnak() {
		$q42 = new ItemId( 'Q42' );
		$p1 = new NumericPropertyId( 'P1' );
		$p2 = new NumericPropertyId( 'P2' );
		$statementList = new StatementList();

		$statementList->addStatement(
			new Statement( new PropertyNoValueSnak( $p1 ) )
		);

		$statementList->addStatement(
			new Statement( new PropertyValueSnak( $p2, new StringValue( '12' ) ) )
		);

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity( new Item( $q42, null, null, $statementList ) );

		return [
			'no value snak' => [
				$entityLookup,
				$q42,
				$p1,
				[ $q42 ],
			],
			'wrong datatype' => [
				$entityLookup,
				$q42,
				$p2,
				[ $q42 ],
			],
		];
	}

	/**
	 * @dataProvider provideGetReferencedEntityIdTestInvalidSnak
	 */
	public function testGetReferencedEntityIdTestInvalidSnak(
		EntityLookup $entityLookup,
		EntityId $fromId,
		NumericPropertyId $propertyId,
		array $toIds
	) {
		$lookup = new EntityRetrievingClosestReferencedEntityIdLookup(
			$this->restrictEntityLookup( $entityLookup, 1 ),
			new NullEntityPrefetcher(),
			0,
			1
		);

		$this->assertNull(
			$lookup->getReferencedEntityId( $fromId, $propertyId, $toIds )
		);
	}

	public function testGetReferencedEntityIdEntityLookupException() {
		$q2013 = new ItemId( 'Q2013' );

		$entityLookupException = new EntityLookupException( $q2013 );
		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addException( $entityLookupException );

		$lookup = new EntityRetrievingClosestReferencedEntityIdLookup(
			$entityLookup,
			new NullEntityPrefetcher(),
			50,
			50
		);

		try {
			$lookup->getReferencedEntityId( $q2013, new NumericPropertyId( 'P31' ), [ new ItemId( 'Q154187' ) ] );
		} catch ( ReferencedEntityIdLookupException $exception ) {
			$this->assertInstanceOf( EntityLookupException::class, $exception->getPrevious() );

			return;
		}
		$this->fail( 'No exception thrown!' );
	}

}
