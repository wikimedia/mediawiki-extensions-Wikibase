<?php

namespace Wikibase\Test;
use \Wikibase\Term;

/**
 * Tests for the Wikibase\Term class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseTerm
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 */
class TermTest extends \MediaWikiTestCase {

	/*
	protected static $fieldNames = array(
		'entityType',
		'entityId',
		'termType',
		'termLanguage',
		'termText',
	);
	 */

	public static function provideContructor() {
		return array(
			array( // #0
				array(
					'entityType' => 'item',
					'entityId' => 23,
					'termType' => Term::TYPE_LABEL,
					'termLanguage' => 'en',
					'termText' => 'foo',
				)
			),
			array( // #1
				array(
					'termType' => Term::TYPE_LABEL,
					'termLanguage' => 'en',
					'termText' => 'foo',
				)
			),
			array( // #2
				array(
					'entityType' => 'item',
					'entityId' => 23,
				)
			),
		);
	}

	/**
	 * @dataProvider provideContructor
	 */
	public function testConstructor( $fields ) {
		$term = new Term( $fields );

		$this->assertEquals( isset( $fields['entityType'] ) ? $fields['entityType'] : null, $term->getEntityType() );
		$this->assertEquals( isset( $fields['entityId'] ) ? $fields['entityId'] : null, $term->getEntityId() );
		$this->assertEquals( isset( $fields['termType'] ) ? $fields['termType'] : null, $term->getType() );
		$this->assertEquals( isset( $fields['termLanguage'] ) ? $fields['termLanguage'] : null, $term->getLanguage() );
		$this->assertEquals( isset( $fields['termText'] ) ? $fields['termText'] : null, $term->getText() );
	}

	public function testClone() {
		$term = new Term( array(
			'termText' => 'Foo'
		) );

		$clone = clone $term;
		$clone->setText( 'Bar' );

		$this->assertEquals( 'Bar', $clone->getText(), "clone must change when modified" ); // sanity
		$this->assertEquals( 'Foo', $term->getText(), "original must stay the same when clone is modified" );

		$clone = clone $term;
		$this->assertTrue( $term->equals( $clone ), "clone must be equal to original" );
	}

	public static function provideCompare() {
		$tests = array();

		$tests[] = array( // #0
			new Term( array() ),
			new Term( array() ),
			true
		);

		$term = new Term( array(
			'entityType' => 'item',
			'entityId' => 23,
			'termType' => Term::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'foo',
		) );

		$other = clone $term;
		$tests[] = array( // #1
			$term,
			$other,
			true
		);

		$other = clone $term;
		$other->setText( 'bar' );
		$tests[] = array( // #2
			$term,
			$other,
			false
		);

		$other = clone $term;
		$other->setEntityId( 11 );
		$tests[] = array( // #3
			$term,
			$other,
			false
		);

		$other = clone $term;
		$other->setEntityType( 'property' );
		$tests[] = array( // #4
			$term,
			$other,
			false
		);

		$other = clone $term;
		$other->setLanguage( 'fr' );
		$tests[] = array( // #5
			$term,
			$other,
			false
		);

		$other = clone $term;
		$other->setType( Term::TYPE_DESCRIPTION  );
		$tests[] = array( // #6
			$term,
			$other,
			false
		);

		return $tests;
	}

	/**
	 * @dataProvider provideCompare
	 * @depends testClone
	 */
	public function testCompare( Term $a, Term $b, $equal ) {
		$ab = Term::compare( $a, $b );
		$ba = Term::compare( $b, $a );

		if ( $equal ) {
			$this->assertEquals( 0, $ab, "Comparison of equal terms is expected to return 0" );
			$this->assertEquals( 0, $ba, "Comparison of equal terms is expected to return 0" );
		} else {
			//NOTE: we don't know or care whether this is larger or smaller
			$this->assertNotEquals( 0, $ab, "Comparison of unequal terms is expected to not return 0" );
			$this->assertEquals( -$ab, $ba, "Comparing A to B should return the inverse of comparing B to A" );
		}
	}

	public static function provideEquals() {
		$tests = array(
			array( // #0
				new Term( array() ),
				null,
				false
			),

			array( // #1
				new Term( array() ),
				false,
				false
			),

			array( // #2
				new Term( array() ),
				"",
				false
			),
		);

		return array_merge( $tests, self::provideCompare() );
	}

	/**
	 * @dataProvider provideEquals
	 * @depends testClone
	 */
	public function testEquals( Term $a, $b, $equal ) {
		$this->assertEquals( $equal, $a->equals( $b ) );

		if ( $b instanceof Term ) {
			$this->assertEquals( $equal, $b->equals( $a ) );
		}
	}
}
