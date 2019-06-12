<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\ByTypeDispatchingPrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers \Wikibase\DataAccess\ByTypeDispatchingPrefetchingTermLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingPrefetchingTermLookupTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenNotPrefetchingTermLookupInstance_constructorThrowsException() {
		new ByTypeDispatchingPrefetchingTermLookup( [ 'item' => 'FOOBAR' ] );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenNotStringIndexedArray_constructorThrowsException() {
		new ByTypeDispatchingPrefetchingTermLookup( [ new FakePrefetchingTermLookup() ] );
	}

	public function testPrefetchTermsFillsBuffersOfPerTypeServices() {
		$itemPrefetchingLookup = new FakePrefetchingTermLookup();
		$propertyPrefetchingLookup = new FakePrefetchingTermLookup();

		$lookup = new ByTypeDispatchingPrefetchingTermLookup( [
			'item' => $itemPrefetchingLookup,
			'property' => $propertyPrefetchingLookup
		] );

		$lookup->prefetchTerms( [ new ItemId( 'Q1' ), new PropertyId( 'P1' ) ], [ 'label' ], [ 'en' ] );

		$allPrefetchedTerms = array_merge(
			$itemPrefetchingLookup->getPrefetchedTerms(),
			$propertyPrefetchingLookup->getPrefetchedTerms()
		);

		$this->assertEquals( [ 'Q1 en label', 'P1 en label' ], $allPrefetchedTerms );
	}

	public function testGivenPreviouslyPrefetchedTerm_getPrefetchedTermReturnsTermStrings() {
		$lookup = new ByTypeDispatchingPrefetchingTermLookup( [
			'item' => new FakePrefetchingTermLookup(),
			'property' => new FakePrefetchingTermLookup()
		] );

		$itemId = new ItemId( 'Q1' );
		$propertyId = new PropertyId( 'P1' );

		$lookup->prefetchTerms( [ $itemId, $propertyId ], [ 'label' ], [ 'en' ] );

		$this->assertSame( 'Q1 en label', $lookup->getPrefetchedTerm( $itemId, 'label', 'en' ) );
		$this->assertSame( 'P1 en label', $lookup->getPrefetchedTerm( $propertyId, 'label', 'en' ) );
	}

	public function testGivenNotPrefetchedTermsBefore_getPrefetchedTermReturnsNull() {
		$lookup = new ByTypeDispatchingPrefetchingTermLookup( [
			'item' => new FakePrefetchingTermLookup(),
			'property' => new FakePrefetchingTermLookup()
		] );

		$itemId = new ItemId( 'Q1' );
		$propertyId = new PropertyId( 'P1' );

		$lookup->prefetchTerms( [ $itemId ], [ 'label' ], [ 'en' ] );

		$this->assertNull( $lookup->getPrefetchedTerm( $itemId, 'description', 'en' ) );
		$this->assertNull( $lookup->getPrefetchedTerm( $propertyId, 'label', 'de' ) );
	}

	public function testGivenPreviouslyPrefetchedTerm_getPrefetchedTermReturnsTermStrings_withDefault() {
		$lookup = new ByTypeDispatchingPrefetchingTermLookup(
			[
				'item' => new FakePrefetchingTermLookup(),
			],
			new FakePrefetchingTermLookup()
		);

		$itemId = new ItemId( 'Q1' );
		$propertyId = new PropertyId( 'P1' );

		$lookup->prefetchTerms( [ $itemId, $propertyId ], [ 'label' ], [ 'en' ] );

		$this->assertSame( 'Q1 en label', $lookup->getPrefetchedTerm( $itemId, 'label', 'en' ) );
		$this->assertSame( 'P1 en label', $lookup->getPrefetchedTerm( $propertyId, 'label', 'en' ) );
	}

	public function testGetLabel() {
		$innerLookup = new FakePrefetchingTermLookup();
		$lookup = new ByTypeDispatchingPrefetchingTermLookup( [ 'item' => $innerLookup ] );

		$itemId = new ItemId( 'Q1' );
		$expectedLabel = $innerLookup->getLabel( $itemId, 'en' );

		$this->assertSame( $expectedLabel, $lookup->getLabel( $itemId, 'en' ) );
	}

	public function testGetLabels() {
		$innerLookup = new FakePrefetchingTermLookup();
		$lookup = new ByTypeDispatchingPrefetchingTermLookup( [ 'item' => $innerLookup ] );

		$itemId = new ItemId( 'Q1' );
		$languageCodes = [ 'de', 'en' ];
		$expectedLabels = $innerLookup->getLabels( $itemId, $languageCodes );

		$this->assertSame( $expectedLabels, $lookup->getLabels( $itemId, $languageCodes ) );
	}

	public function testGetDescription() {
		$innerLookup = new FakePrefetchingTermLookup();
		$lookup = new ByTypeDispatchingPrefetchingTermLookup( [ 'item' => $innerLookup ] );

		$itemId = new ItemId( 'Q1' );
		$expectedDescription = $innerLookup->getDescription( $itemId, 'en' );

		$this->assertSame( $expectedDescription, $lookup->getDescription( $itemId, 'en' ) );
	}

	public function testGetDescriptions() {
		$innerLookup = new FakePrefetchingTermLookup();
		$lookup = new ByTypeDispatchingPrefetchingTermLookup( [ 'item' => $innerLookup ] );

		$itemId = new ItemId( 'Q1' );
		$languageCodes = [ 'de', 'en' ];
		$expectedDescriptions = $innerLookup->getDescriptions( $itemId, $languageCodes );

		$this->assertSame( $expectedDescriptions, $lookup->getDescriptions( $itemId, $languageCodes ) );
	}

}
