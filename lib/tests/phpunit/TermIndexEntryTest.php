<?php

namespace Wikibase\Lib\Tests;

use MWException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
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

	public function testConstructor() {
		$term = new TermIndexEntry( [
			'entityId' => new ItemId( 'Q23' ),
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'foo',
		] );

		$this->assertEquals( new ItemId( 'Q23' ), $term->getEntityId() );
		$this->assertEquals( TermIndexEntry::TYPE_LABEL, $term->getType() );
		$this->assertEquals( 'en', $term->getLanguage() );
		$this->assertEquals( 'foo', $term->getText() );
	}

	public function testGivenInvalidField_constructorThrowsException() {
		$this->setExpectedException( MWException::class );
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
				]
			],
			[
				[
					'entityId' => new ItemId( 'Q23' ),
					'termType' => TermIndexEntry::TYPE_LABEL,
				]
			],
			[
				[
					'entityId' => new ItemId( 'Q23' ),
				]
			],
			[
				[]
			],
		];
	}

	/**
	 * @dataProvider provideIncompleteFields
	 */
	public function testGivenIncompleteFields_constructorThrowsException( $fields ) {
		$this->setExpectedException( MWException::class );
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
				true
			],
			'other text' => [
				$term,
				$this->newInstance( [ 'termText' => 'bar' ] ),
				false
			],
			'other entity id' => [
				$term,
				$this->newInstance( [ 'entityId' => new PropertyId( 'P11' ) ] ),
				false
			],
			'other language' => [
				$term,
				$this->newInstance( [ 'termLanguage' => 'fr' ] ),
				false
			],
			'other term type' => [
				$term,
				$this->newInstance( [ 'termType' => TermIndexEntry::TYPE_DESCRIPTION ] ),
				false
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
			$this->assertEquals( 0, $ab, 'Comparison of equal terms is expected to return 0' );
			$this->assertEquals( 0, $ba, 'Comparison of equal terms is expected to return 0' );
		} else {
			// NOTE: We don't know or care whether this is larger or smaller
			$this->assertNotEquals( 0, $ab, 'Comparison of unequal terms is expected to not return 0' );
			$this->assertEquals( -$ab, $ba, 'Comparing A to B should return the inverse of comparing B to A' );
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
