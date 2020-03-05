<?php

namespace Wikibase\Lib\Tests;

use MediaWikiCoversValidator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Summary;

/**
 * @covers \Wikibase\Lib\Summary
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class SummaryTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;

	public function testAddAutoCommentArgs() {
		$summary = new Summary( 'summarytest' );
		$summary->addAutoCommentArgs( "one" );
		$summary->addAutoCommentArgs( 2, new ItemId( 'Q3' ) );
		$summary->addAutoCommentArgs( [ "four", "five" ] );

		$expected = [ 'one', 2, new ItemId( 'Q3' ), 'four', 'five' ];
		$this->assertEquals( $expected, $summary->getCommentArgs() );
	}

	public function testSetLanguage() {
		$summary = new Summary( 'summarytest' );
		$summary->setLanguage( "xyz" );

		$this->assertSame( 'xyz', $summary->getLanguageCode() );
	}

	public function testSetUserSummary() {
		$summary = new Summary( 'summarytest' );
		$summary->setUserSummary( "xyz" );

		$this->assertSame( 'xyz', $summary->getUserSummary() );
	}

	public function testAddAutoSummaryArgs() {
		$summary = new Summary( 'summarytest' );
		$summary->addAutoSummaryArgs( "one" );
		$summary->addAutoSummaryArgs( 2, new ItemId( 'Q3' ) );
		$summary->addAutoSummaryArgs( [ "four", "five" ] );

		$expected = [ 'one', 2, new ItemId( 'Q3' ), 'four', 'five' ];
		$this->assertEquals( $expected, $summary->getAutoSummaryArgs() );
	}

	public function testSetAutoSummaryArgs() {
		$summary = new Summary( 'summarytest' );
		$summary->setAutoSummaryArgs( [ 'two', 'only' ] );

		$this->assertEquals( [ 'two', 'only' ], $summary->getAutoSummaryArgs() );
	}

	public function testSetAutoCommentArgs() {
		$summary = new Summary( 'summarytest' );
		$summary->setAutoCommentArgs( [ 'three', 'alone' ] );

		$this->assertEquals( [ 'three', 'alone' ], $summary->getCommentArgs() );
	}

	public function testSetAction() {
		$summary = new Summary( 'summarytest' );

		$summary->setAction( "testing" );
		$this->assertSame( 'summarytest-testing', $summary->getMessageKey() );

		$summary->setAction( "besting" );
		$this->assertSame( 'summarytest-besting', $summary->getMessageKey() );
	}

	public function testGetMessageKey() {
		$summary = new Summary( 'summarytest' );
		$this->assertSame( 'summarytest', $summary->getMessageKey() );

		$summary->setAction( "testing" );
		$this->assertSame( 'summarytest-testing', $summary->getMessageKey() );
	}

}
