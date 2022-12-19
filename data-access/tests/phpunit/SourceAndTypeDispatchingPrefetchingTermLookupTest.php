<?php

declare( strict_types = 1 );

namespace Wikibase\DataAccess\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\SourceAndTypeDispatchingPrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;

/**
 * @covers \Wikibase\DataAccess\SourceAndTypeDispatchingPrefetchingTermLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SourceAndTypeDispatchingPrefetchingTermLookupTest extends TestCase {

	/**
	 * @var array
	 */
	private $callbacks;
	/**
	 * @var EntitySourceLookup
	 */
	private $entitySourceLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->callbacks = [];
		$this->entitySourceLookup = $this->createMock( EntitySourceLookup::class );
		$sourceName = 'some-source';
		$source = NewDatabaseEntitySource::havingName( $sourceName )->build();
		$this->entitySourceLookup->method( 'getEntitySourceById' )->willReturn( $source );
	}

	public function testPrefetchTermsFillsBuffersOfPerTypeServices() {
		$itemPrefetchingLookup = new FakePrefetchingTermLookup();
		$propertyPrefetchingLookup = new FakePrefetchingTermLookup();

		$this->callbacks = [
			'some-source' => [
				'item' => function () use ( $itemPrefetchingLookup ) { return $itemPrefetchingLookup;
				},
				'property' => function () use ( $propertyPrefetchingLookup ) { return $propertyPrefetchingLookup;
				},
			],
		];

		$lookup = $this->getLookup();

		$lookup->prefetchTerms( [ new ItemId( 'Q1' ), new NumericPropertyId( 'P1' ) ], [ 'label' ], [ 'en' ] );

		$allPrefetchedTerms = array_merge(
			$itemPrefetchingLookup->getPrefetchedTerms(),
			$propertyPrefetchingLookup->getPrefetchedTerms()
		);

		$this->assertEquals( [ 'Q1 en label', 'P1 en label' ], $allPrefetchedTerms );
	}

	public function testGivenPreviouslyPrefetchedTerm_getPrefetchedTermReturnsTermStrings() {
		$itemPrefetchingLookup = new FakePrefetchingTermLookup();
		$propertyPrefetchingLookup = new FakePrefetchingTermLookup();

		$this->callbacks = [
			'some-source' => [
				'item' => function () use ( $itemPrefetchingLookup ) { return $itemPrefetchingLookup;
				},
				'property' => function () use ( $propertyPrefetchingLookup ) { return $propertyPrefetchingLookup;
				},
			],
		];

		$lookup = $this->getLookup();

		$itemId = new ItemId( 'Q1' );
		$propertyId = new NumericPropertyId( 'P1' );

		$lookup->prefetchTerms( [ $itemId, $propertyId ], [ 'label' ], [ 'en' ] );

		$this->assertSame( 'Q1 en label', $lookup->getPrefetchedTerm( $itemId, 'label', 'en' ) );
		$this->assertSame( 'P1 en label', $lookup->getPrefetchedTerm( $propertyId, 'label', 'en' ) );
	}

	public function testGivenNotPrefetchedTermsBefore_getPrefetchedTermReturnsNull() {
		$itemPrefetchingLookup = new FakePrefetchingTermLookup();
		$propertyPrefetchingLookup = new FakePrefetchingTermLookup();

		$this->callbacks = [
			'some-source' => [
				'item' => function () use ( $itemPrefetchingLookup ) { return $itemPrefetchingLookup;
				},
				'property' => function () use ( $propertyPrefetchingLookup ) { return $propertyPrefetchingLookup;
				},
			],
		];

		$lookup = $this->getLookup();

		$itemId = new ItemId( 'Q1' );
		$propertyId = new NumericPropertyId( 'P1' );

		$lookup->prefetchTerms( [ $itemId ], [ 'label' ], [ 'en' ] );

		$this->assertNull( $lookup->getPrefetchedTerm( $itemId, 'description', 'en' ) );
		$this->assertNull( $lookup->getPrefetchedTerm( $propertyId, 'label', 'de' ) );
	}

	public function testGetLabel() {
		$itemPrefetchingLookup = new FakePrefetchingTermLookup();

		$this->callbacks = [
			'some-source' => [
				'item' => function () use ( $itemPrefetchingLookup ) { return $itemPrefetchingLookup;
				},
			],
		];

		$lookup = $this->getLookup();

		$itemId = new ItemId( 'Q1' );
		$expectedLabel = $itemPrefetchingLookup->getLabel( $itemId, 'en' );

		$this->assertSame( $expectedLabel, $lookup->getLabel( $itemId, 'en' ) );
	}

	public function testGetLabels() {
		$innerLookup = new FakePrefetchingTermLookup();

		$this->callbacks = [
			'some-source' => [
				'item' => function () use ( $innerLookup ) { return $innerLookup;
				},
			],
		];

		$lookup = $this->getLookup();

		$itemId = new ItemId( 'Q1' );
		$languageCodes = [ 'de', 'en' ];
		$expectedLabels = $innerLookup->getLabels( $itemId, $languageCodes );

		$this->assertSame( $expectedLabels, $lookup->getLabels( $itemId, $languageCodes ) );
	}

	public function testGetDescription() {
		$innerLookup = new FakePrefetchingTermLookup();

		$this->callbacks = [
			'some-source' => [
				'item' => function () use ( $innerLookup ) { return $innerLookup;
				},
			],
		];

		$lookup = $this->getLookup();

		$itemId = new ItemId( 'Q1' );
		$expectedDescription = $innerLookup->getDescription( $itemId, 'en' );

		$this->assertSame( $expectedDescription, $lookup->getDescription( $itemId, 'en' ) );
	}

	public function testGetDescriptions() {
		$innerLookup = new FakePrefetchingTermLookup();

		$this->callbacks = [
			'some-source' => [
				'item' => function () use ( $innerLookup ) { return $innerLookup;
				},
			],
		];

		$lookup = $this->getLookup();

		$itemId = new ItemId( 'Q1' );
		$languageCodes = [ 'de', 'en' ];
		$expectedDescriptions = $innerLookup->getDescriptions( $itemId, $languageCodes );

		$this->assertSame( $expectedDescriptions, $lookup->getDescriptions( $itemId, $languageCodes ) );
	}

	private function getLookup(): SourceAndTypeDispatchingPrefetchingTermLookup {
		$lookup = new SourceAndTypeDispatchingPrefetchingTermLookup(
			new ServiceBySourceAndTypeDispatcher(
				PrefetchingTermLookup::class, $this->callbacks
			),
			$this->entitySourceLookup
		);

		return $lookup;
	}

}
