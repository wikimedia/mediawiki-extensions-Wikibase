<?php

namespace Wikibase\Lib\Tests;

use MWException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\LegacyIdInterpreter;
use Wikibase\DataModel\Term\Term;
use Wikibase\TermIndexEntry;

/**
 * @covers Wikibase\TermIndexEntry
 *
 * @group Wikibase
 * @group WikibaseTerm
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 */
class TermIndexEntryTest extends PHPUnit_Framework_TestCase {

	public function provideConstructor() {
		return [
			[ // #0
				[
					'entityType' => 'item',
					'entityId' => 23,
					'termType' => TermIndexEntry::TYPE_LABEL,
					'termLanguage' => 'en',
					'termText' => 'foo',
					'termWeight' => 1.234,
				]
			],
			[ // #1
				[
					'termType' => TermIndexEntry::TYPE_LABEL,
					'termLanguage' => 'en',
					'termText' => 'foo',
				]
			],
			[ // #2
				[
					'entityType' => 'item',
					'entityId' => 23,
				]
			],
		];
	}

	/**
	 * @dataProvider provideConstructor
	 */
	public function testConstructor( $fields ) {
		$term = new TermIndexEntry( $fields );

		$entityId = null;
		if ( isset( $fields['entityType'] ) && isset( $fields['entityId'] ) ) {
			// FIXME: This must be removed once we got rid of all legacy numeric ids.
			$entityId = LegacyIdInterpreter::newIdFromTypeAndNumber( $fields['entityType'], $fields['entityId'] );
		}

		$this->assertEquals( isset( $fields['entityType'] ) ? $fields['entityType'] : null, $term->getEntityType() );
		$this->assertEquals( $entityId, $term->getEntityId() );
		$this->assertEquals( isset( $fields['termType'] ) ? $fields['termType'] : null, $term->getType() );
		$this->assertEquals( isset( $fields['termLanguage'] ) ? $fields['termLanguage'] : null, $term->getLanguage() );
		$this->assertEquals( isset( $fields['termText'] ) ? $fields['termText'] : null, $term->getText() );
		$this->assertEquals( isset( $fields['termWeight'] ) ? $fields['termWeight'] : null, $term->getWeight() );
	}

	public function testClone() {
		$term = new TermIndexEntry( [
			'termText' => 'Foo'
		] );

		$clone = clone $term;
		$clone->setText( 'Bar' );

		$this->assertEquals( 'Bar', $clone->getText(), "clone must change when modified" ); // sanity
		$this->assertEquals( 'Foo', $term->getText(), "original must stay the same when clone is modified" );

		$clone = clone $term;
		$this->assertEquals( $term, $clone, "clone must be equal to original" );
	}

	public function provideCompare() {
		$tests = [];

		$tests[] = [ // #0
			new TermIndexEntry(),
			new TermIndexEntry(),
			true
		];

		$term = new TermIndexEntry( [
			'entityType' => 'item',
			'entityId' => 23,
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'foo',
		] );

		$other = clone $term;
		$tests[] = [ // #1
			$term,
			$other,
			true
		];

		$other = clone $term;
		$other->setText( 'bar' );
		$tests[] = [ // #2
			$term,
			$other,
			false
		];

		$other = new TermIndexEntry( [
			'entityType' => 'property',
			'entityId' => 11,
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'foo',
		] );
		$tests[] = [ // #3
			$term,
			$other,
			false
		];

		$other = new TermIndexEntry( [
			'entityType' => 'property',
			'entityId' => 23,
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'foo',
		] );
		$tests[] = [ // #4
			$term,
			$other,
			false
		];

		$other = clone $term;
		$other->setLanguage( 'fr' );
		$tests[] = [ // #5
			$term,
			$other,
			false
		];

		$other = clone $term;
		$other->setType( TermIndexEntry::TYPE_DESCRIPTION );
		$tests[] = [ // #6
			$term,
			$other,
			false
		];

		return $tests;
	}

	/**
	 * @dataProvider provideCompare
	 * @depends testClone
	 */
	public function testCompare( TermIndexEntry $a, TermIndexEntry $b, $equal ) {
		$ab = TermIndexEntry::compare( $a, $b );
		$ba = TermIndexEntry::compare( $b, $a );

		if ( $equal ) {
			$this->assertEquals( 0, $ab, "Comparison of equal terms is expected to return 0" );
			$this->assertEquals( 0, $ba, "Comparison of equal terms is expected to return 0" );
		} else {
			//NOTE: we don't know or care whether this is larger or smaller
			$this->assertNotEquals( 0, $ab, "Comparison of unequal terms is expected to not return 0" );
			$this->assertEquals( -$ab, $ba, "Comparing A to B should return the inverse of comparing B to A" );
		}
	}

	public function testGetTerm() {
		$termIndexEntry = new TermIndexEntry( [
			'termLanguage' => 'en',
			'termText' => 'foo',
		] );
		$expectedTerm = new Term( 'en', 'foo' );
		$this->assertEquals( $expectedTerm, $termIndexEntry->getTerm() );
	}

	public function provideTermIndexEntryData() {
		return [
			[ [
				'termText' => 'foo',
			] ],
			[ [
				'termLanguage' => 'en',
			] ],
		];
	}

	/**
	 * @dataProvider provideTermIndexEntryData
	 */
	public function testGetTerm_throwsException( $termIndexEntryData ) {
		$termIndexEntry = new TermIndexEntry( $termIndexEntryData );
		$this->setExpectedException( MWException::class, 'Can not construct Term from partial TermIndexEntry' );
		$termIndexEntry->getTerm();
	}

}
