<?php

namespace Wikibase\Test;

use Wikibase\Summary;

/**
 * Test Summary.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseSummary
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 *
 */
class SummaryTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider providerFormatAutoComment
	 */
	public function testFormatAutoComment( $msg, $parts, $expected ) {
		$result = Summary::formatAutoComment( $msg, $parts );
		$this->assertEquals( $expected, $result, 'Not the expected result' );
	}

	public static function providerFormatAutoComment() {
		return array(
			array( '', array(), '' ),
			array( 'msgkey', array(), 'msgkey' ),
			array( 'msgkey', array( 'one' ), 'msgkey:one' ),
			array( 'msgkey', array( 'one', 'two' ), 'msgkey:one|two' ),
			array( 'msgkey', array( 'one', 'two', 'three' ), 'msgkey:one|two|three' ),
		);
	}

	/**
	 * @dataProvider providerFormatAutoSummary
	 */
	public function testFormatAutoSummary( $parts, $expected ) {
		$result = Summary::formatAutoSummary( $parts );
		$this->assertEquals( $expected, $result, 'Not the expected result' );
	}

	public static function providerFormatAutoSummary() {
		return array(
			array( array(), '' ),
			array( array( 'one' ), 'one' ),
			array( array( 'one', 'two' ), 'one, two' ),
			array( array( 'one', 'two', 'three' ), 'one, two, three' ),
		);
	}

	/**
	 * @dataProvider providerFormatTotalSummary
	 */
	public function testFormatTotalSummary( $comment, $summary, $expected ) {
		$result = Summary::formatTotalSummary( $comment, $summary );
		$this->assertEquals( $expected, $result, 'Not the expected result' );
	}

	public static function providerFormatTotalSummary() {
		return array(
			array( '', '', '' ),
			array( 'foobar', 'This is a test…', '/* foobar */ This is a test…' ),
			array( 'foobar:one', 'This is a test…', '/* foobar:one */ This is a test…' ),
			array( 'foobar:one|two', 'This is a test…', '/* foobar:one|two */ This is a test…' ),
			array( 'foobar:one|two|three', 'This is a test…', '/* foobar:one|two|three */ This is a test…' ),
			array( 'foobar:one|two|three|…', 'This is a test…', '/* foobar:one|two|three|… */ This is a test…' ),
			array( 'foobar:one|two|three|<>', 'This is a test…', '/* foobar:one|two|three|<> */ This is a test…' ),
			array( 'foobar:one|two|three|&lt;&gt;', 'This is a test…', '/* foobar:one|two|three|&lt;&gt; */ This is a test…' ),
			array(  '', str_repeat( 'a', 2*SUMMARY_MAX_LENGTH ), str_repeat( 'a', SUMMARY_MAX_LENGTH-3 ) . '...' ),
		);
	}

	public function testAddAutoCommentArgs() {
		$summary = new Summary( 'summarytest' );
		$summary->addAutoCommentArgs( "one" );
		$summary->addAutoCommentArgs( "two", "three" );
		$summary->addAutoCommentArgs( array( "four", "five" ) );

		$expected = $summary->getMessageKey() . ':one|two|three|four|five';
		$this->assertEquals( $expected, $summary->getAutoComment() );
	}

	public function testSetLanguage() {
		$summary = new Summary( 'summarytest' );
		$summary->setLanguage( "xyz" );

		$this->assertEquals( 'xyz', $summary->getLanguageCode() );
		$this->assertEquals( "/* summarytest:0|xyz */", $summary->toString() );
	}

	public function testAddAutoSummaryArgs() {
		$summary = new Summary( 'summarytest' );
		$summary->addAutoSummaryArgs( "one" );
		$summary->addAutoSummaryArgs( "two", "three" );
		$summary->addAutoSummaryArgs( array( "four", "five" ) );

		$this->assertEquals( 'one, two, three, four, five', $summary->getAutoSummary() );
		$this->assertEquals( "/* summarytest:5| */ one, two, three, four, five", $summary->toString() );
	}

	public function testSetAction() {
		$summary = new Summary( 'summarytest' );

		$summary->setAction( "testing" );
		$this->assertEquals( "testing", $summary->getActionName() );

		$summary->setAction( "besting" );
		$this->assertEquals( "besting", $summary->getActionName() );

		$this->assertEquals( "summarytest-besting", $summary->getMessageKey() );
	}

	public function testGetMessageKey() {
		$summary = new Summary( 'summarytest' );
		$this->assertEquals( "summarytest", $summary->getMessageKey() );

		$summary->setAction( "testing" );
		$this->assertEquals( "summarytest-testing", $summary->getMessageKey() );
	}

	/**
	 * @dataProvider provideToString
	 */
	public function testToString( $module, $action, $language, $commentArgs, $summaryArgs, $userSummary, $expected ) {
		$summary = new Summary( $module );

		if ( $action !== null ) {
			$summary->setAction( $action );
		}

		if ( $language !== null ) {
			$summary->setLanguage( $language );
		}

		if ( $commentArgs ) {
			$summary->addAutoCommentArgs( $commentArgs );
		}

		if ( $summaryArgs ) {
			$summary->addAutoSummaryArgs( $summaryArgs );
		}

		if ( $userSummary !== null ) {
			$summary->setUserSummary( $userSummary );
		}

		$this->assertEquals( $expected, $summary->toString() );
	}

	public static function provideToString() {
		return array(
			array( // #0
				'summarytest',
				null,
				null,
				null,
				null,
				null,
				'/* summarytest:0| */'
			),
			array( // #1
				'summarytest',
				'testing',
				'nl',
				null,
				null,
				null,
				'/* summarytest-testing:0|nl */'
			),
			array( // #2
				'summarytest',
				null,
				null,
				array( 'x' ),
				null,
				null,
				'/* summarytest:0||x */'
			),
			array( // #3
				'summarytest',
				'testing',
				'nl',
				array( 'x', 'y' ),
				array( 'A', 'B'),
				null,
				'/* summarytest-testing:2|nl|x|y */ A, B'
			),
			array( // #4
				'summarytest',
				null,
				null,
				null,
				array( 'A', 'B' ),
				null,
				'/* summarytest:2| */ A, B'
			),
			array( // #5
				'summarytest',
				'testing',
				'nl',
				array( 'x', 'y' ),
				array( 'A', 'B'),
				'can I haz world domination?',
				'/* summarytest-testing:2|nl|x|y */ can I haz world domination?'
				),
			array( // #6
				'summarytest',
				null,
				null,
				null,
				null,
				'can I haz world domination?',
				'/* summarytest:0| */ can I haz world domination?'
				),
		);
	}
}
