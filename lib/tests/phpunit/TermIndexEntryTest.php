<?php

namespace Wikibase\Lib\Tests;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\TermIndexEntry;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers \Wikibase\Lib\TermIndexEntry
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 */
class TermIndexEntryTest extends \PHPUnit\Framework\TestCase {

	public function testConstructor() {
		$term = new TermIndexEntry( [
			'entityId' => new ItemId( 'Q23' ),
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'foo',
		] );

		$this->assertEquals( new ItemId( 'Q23' ), $term->getEntityId() );
		$this->assertSame( Item::ENTITY_TYPE, $term->getEntityType() );
		$this->assertSame( TermIndexEntry::TYPE_LABEL, $term->getTermType() );
		$this->assertSame( 'en', $term->getLanguage() );
		$this->assertSame( 'foo', $term->getText() );
	}

	public function testGivenInvalidField_constructorThrowsException() {
		$this->expectException( ParameterAssertionException::class );
		new TermIndexEntry( [
			'entityId' => new ItemId( 'Q23' ),
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'foo',
			'fooField' => 'bar',
		] );
	}

	public function provideIncompleteFields() {
		return [
			[
				[
					'termType' => TermIndexEntry::TYPE_LABEL,
					'termLanguage' => 'en',
					'termText' => 'foo',
				],
			],
			[
				[
					'entityId' => new ItemId( 'Q23' ),
					'termType' => TermIndexEntry::TYPE_LABEL,
				],
			],
			[
				[
					'entityId' => new ItemId( 'Q23' ),
				],
			],
			[
				[],
			],
		];
	}

	/**
	 * @dataProvider provideIncompleteFields
	 */
	public function testGivenIncompleteFields_constructorThrowsException( $fields ) {
		$this->expectException( ParameterAssertionException::class );
		new TermIndexEntry( $fields );
	}

	public function provideInvalidValues() {
		$goodFields = [
			'entityId' => new ItemId( 'Q23' ),
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'foo',
		];

		return [
			'non-string term type' => [ array_merge( $goodFields, [ 'termType' => 100 ] ) ],
			'invalid term type' => [ array_merge( $goodFields, [ 'termType' => 'foo' ] ) ],
			'non-string term language' => [ array_merge( $goodFields, [ 'termLanguage' => 100 ] ) ],
			'non-string term text' => [ array_merge( $goodFields, [ 'termText' => 100 ] ) ],
			'non-EntityId as entity id' => [ array_merge( $goodFields, [ 'entityId' => 'foo' ] ) ],
		];
	}

	/**
	 * @dataProvider provideInvalidValues
	 */
	public function testGivenInvalidValues_constructorThrowsException( $fields ) {
		$this->expectException( ParameterAssertionException::class );
		new TermIndexEntry( $fields );
	}

	public function testClone() {
		$term = new TermIndexEntry( [
			'entityId' => new ItemId( 'Q23' ),
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'foo',
		] );

		$clone = clone $term;
		$this->assertEquals( $term, $clone, 'clone must be equal to original' );
	}

	/**
	 * @param array $extraFields
	 *
	 * @return TermIndexEntry
	 */
	private function newInstance( array $extraFields = [] ) {
		return new TermIndexEntry( $extraFields + [
				'entityId' => new ItemId( 'Q23' ),
				'termType' => TermIndexEntry::TYPE_LABEL,
				'termLanguage' => 'en',
				'termText' => 'foo',
			] );
	}

	public function provideCompare() {
		$term = $this->newInstance();

		return [
			'the same object' => [
				$term,
				$term,
				true,
			],
			'clone' => [
				$term,
				clone $term,
				true,
			],
			'other text' => [
				$term,
				$this->newInstance( [ 'termText' => 'bar' ] ),
				false,
			],
			'other entity id' => [
				$term,
				$this->newInstance( [ 'entityId' => new NumericPropertyId( 'P11' ) ] ),
				false,
			],
			'other language' => [
				$term,
				$this->newInstance( [ 'termLanguage' => 'fr' ] ),
				false,
			],
			'other term type' => [
				$term,
				$this->newInstance( [ 'termType' => TermIndexEntry::TYPE_DESCRIPTION ] ),
				false,
			],
		];
	}

	/**
	 * @dataProvider provideCompare
	 * @depends testClone
	 */
	public function testCompare( TermIndexEntry $a, TermIndexEntry $b, $equal ) {
		$ab = TermIndexEntry::compare( $a, $b );
		$ba = TermIndexEntry::compare( $b, $a );

		if ( $equal ) {
			$this->assertSame( 0, $ab, 'Comparison of equal terms is expected to return 0' );
			$this->assertSame( 0, $ba, 'Comparison of equal terms is expected to return 0' );
		} else {
			// NOTE: We don't know or care whether this is larger or smaller
			$this->assertNotSame( 0, $ab, 'Comparison of unequal terms is expected to not return 0' );
			$this->assertSame( -$ab, $ba, 'Comparing A to B should return the inverse of comparing B to A' );
		}
	}

	public function testGetTerm() {
		$termIndexEntry = new TermIndexEntry( [
			'entityId' => new ItemId( 'Q23' ),
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'foo',
		] );
		$this->assertEquals( new Term( 'en', 'foo' ), $termIndexEntry->getTerm() );
	}

}
