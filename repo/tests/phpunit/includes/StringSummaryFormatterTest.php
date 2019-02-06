<?php

namespace Wikibase\Repo\Tests;

use Language;
use MediaWikiLangTestCase;
use Wikibase\StringSummaryFormatter;
use Wikibase\Summary;

/**
 * @covers \Wikibase\StringSummaryFormatter
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class StringSummaryFormatterTest extends MediaWikiLangTestCase {

	/**
	 * @return StringSummaryFormatter
	 */
	private function newFormatter() {
		return new StringSummaryFormatter(
			Language::factory( 'en' )
		);
	}

	/**
	 * @dataProvider providerFormatAutoComment
	 */
	public function testFormatAutoComment( $module, $action, $language, array $parts, $expected ) {
		$summary = new Summary( $module, $action, $language );

		if ( !empty( $parts ) ) {
			call_user_func_array( [ $summary, 'addAutoCommentArgs' ], $parts );
		}

		$formatter = $this->newFormatter();
		$result = $formatter->formatAutoComment( $summary );
		$this->assertSame( $expected, $result, 'Not the expected result' );
	}

	public function providerFormatAutoComment() {
		return [
			'empty' => [
				'', '', '',
				[],
				':0|'
			],
			'no args' => [
				'foo', 'testing', 'en',
				[],
				'foo-testing:0|en'
			],
			'one arg' => [
				'foo', 'testing', 'en',
				[ 'one' ],
				'foo-testing:0|en|one'
			],
			'two args (no action)' => [
				'foo', '', 'en',
				[ 'one', 'two' ],
				'foo:0|en|one|two'
			],
			'args contains array (no module)' => [
				'', 'testing', 'en',
				[ [ 'one', 'two' ] ],
				'testing:0|en|one|two'
			],
			'args contains map (no module)' => [
				'', 'testing', 'en',
				[ [ [ 'one' => 1, 'two' => 2 ] ] ],
				'testing:0|en|one: 1, two: 2'
			],
			'empty arg' => [
				'foo', 'testing', 'en',
				[ 'one', '', 'three' ],
				'foo-testing:0|en|one||three'
			],
			'number' => [
				'foo', 'testing', 'en',
				[ 23 ],
				'foo-testing:0|en|23'
			],
		];
	}

	/**
	 * @dataProvider providerFormatAutoSummary
	 */
	public function testFormatAutoSummary( array $parts, $expected ) {
		$summary = new Summary();
		$summary->addAutoSummaryArgs( $parts );

		$formatter = $this->newFormatter();
		$result = $formatter->formatAutoSummary( $summary );
		$this->assertSame( $expected, $result, 'Not the expected result' );
	}

	public function providerFormatAutoSummary() {
		return [
			'empty' => [ [], '' ],
			'no args' => [ [], '' ],
			'one arg' => [ [ 'one' ], 'one' ],
			'two args' => [ [ 'one', 'two' ], 'one, two' ],
			'args contains array' => [ [ [ 'one', 'two' ] ], 'one, two' ],
			'args contains map' => [ [ [ [ 'one' => 1, 'two' => 2 ] ] ], 'one: 1, two: 2' ],
			'empty arg' => [ [ 'one', '' ], 'one' ],
			'number' => [ [ 23 ], '23' ],
		];
	}

	/**
	 * @dataProvider provideToStringArgs
	 */
	public function testToStringArgHandling( array $commentArgs, array $summaryArgs, $expected ) {
		$summary = new Summary( 'foobar' );
		$summary->addAutoCommentArgs( $commentArgs );
		$summary->addAutoSummaryArgs( $summaryArgs );

		$formatter = $this->newFormatter();
		$this->assertSame( $expected, $formatter->formatSummary( $summary ) );
	}

	public function provideToStringArgs() {
		return [
			[
				[],
				[],
				'/* foobar:0| */'
			],
			[
				[ '' ],
				[ 'This is a test…' ],
				'/* foobar:1|| */ This is a test…'
			],
			[
				[ 'one' ],
				[ 'This is a test…' ],
				'/* foobar:1||one */ This is a test…'
			],
			[
				[ 'one', 'two' ],
				[ 'This is a test…' ],
				'/* foobar:1||one|two */ This is a test…'
			],
			[
				[ 'one', 'two', 'three' ],
				[ 'This is a test…' ],
				'/* foobar:1||one|two|three */ This is a test…'
			],
			[
				[ 'one', 'two', 'three', '…' ],
				[ 'This is a test…' ],
				'/* foobar:1||one|two|three|… */ This is a test…'
			],
			[
				[ 'one', 'two', 'three', '<>' ],
				[ 'This is a test…' ],
				'/* foobar:1||one|two|three|<> */ This is a test…'
			],
			[
				[ 'one', 'two', 'three', '&lt;&gt;' ],
				[ 'This is a test…' ],
				'/* foobar:1||one|two|three|&lt;&gt; */ This is a test…'
			],
			# This comment is "too long", but it will be truncated to an
			# appropriate length by core's CommentStore (not SummaryFormatter)
			[
				[],
				[ str_repeat( 'a', 512 ) ],
				'/* foobar:1| */ ' . str_repeat( 'a', 512 )
			],
		];
	}

	/**
	 * @dataProvider provideFormatSummary
	 */
	public function testFormatSummary( $module, $action, $language, $commentArgs, $summaryArgs, $userSummary, $expected ) {
		$summary = new Summary( $module );

		if ( $action !== null ) {
			$summary->setAction( $action );
		}

		if ( $language !== null ) {
			$summary->setLanguage( $language );
		}

		if ( $commentArgs ) {
			call_user_func_array( [ $summary, 'addAutoCommentArgs' ], $commentArgs );
		}

		if ( $summaryArgs ) {
			call_user_func_array( [ $summary, 'addAutoSummaryArgs' ], $summaryArgs );
		}

		if ( $userSummary !== null ) {
			$summary->setUserSummary( $userSummary );
		}

		$formatter = $this->newFormatter();
		$this->assertSame( $expected, $formatter->formatSummary( $summary ) );
	}

	public function provideFormatSummary() {
		return [
			[ // #0
				'summarytest',
				null,
				null,
				null,
				null,
				null,
				'/* summarytest:0| */'
			],
			[ // #1
				'summarytest',
				'testing',
				'nl',
				null,
				null,
				null,
				'/* summarytest-testing:0|nl */'
			],
			[ // #2
				'summarytest',
				null,
				null,
				[ 'x' ],
				null,
				null,
				'/* summarytest:0||x */'
			],
			[ // #3
				'summarytest',
				'testing',
				'nl',
				[ 'x', 'y' ],
				[ 'A', 'B' ],
				null,
				'/* summarytest-testing:2|nl|x|y */ A, B'
			],
			[ // #4
				'summarytest',
				null,
				null,
				null,
				[ 'A', 'B' ],
				null,
				'/* summarytest:2| */ A, B'
			],
			'User summary overrides arguments' => [
				'summarytest',
				'testing',
				'nl',
				[ 'x', 'y' ],
				[ 'A', 'B' ],
				'can I haz world domination?',
				'/* summarytest-testing:2|nl|x|y */ A, B, can I haz world domination?'
			],
			'Trimming' => [
				'summarytest',
				'testing',
				'de',
				[ ' autoArg0 ', ' autoArg1 ' ],
				[ ' userArg0 ', ' userArg1 ' ],
				' userSummary ',
				'/* summarytest-testing:2|de| autoArg0 | autoArg1 */ userArg0 ,  userArg1, userSummary'
			],
			'User summary only' => [
				'summarytest',
				null,
				null,
				null,
				null,
				'can I haz world domination?',
				'/* summarytest:0| */ can I haz world domination?'
			],
			'User summary w/o arguments' => [
				'summarytest',
				'testing',
				'de',
				[ 'autoArg0', 'autoArg1' ],
				null,
				'userSummary',
				'/* summarytest-testing:0|de|autoArg0|autoArg1 */ userSummary'
			],
			'User summary w/o auto comment arguments' => [
				'summarytest',
				'testing',
				'de',
				null,
				[ 'userArg0', 'userArg1' ],
				'userSummary',
				'/* summarytest-testing:2|de */ userArg0, userArg1, userSummary'
			],
			'Array arguments' => [
				'summarytest',
				'testing',
				'nl',
				[ 'x', [ 1, 2, 3 ] ],
				[ 'A', [ 1, 2, 3 ] ],
				null,
				'/* summarytest-testing:2|nl|x|1, 2, 3 */ A, 1, 2, 3'
			],
			'Associative arguments' => [
				'summarytest',
				'testing',
				'nl',
				[ 'x', [ "foo" => 1, "bar" => 2 ] ],
				[ 'A', [ "foo" => 1, "bar" => 2 ] ],
				null,
				'/* summarytest-testing:2|nl|x|foo: 1, bar: 2 */ A, foo: 1, bar: 2'
			],
		];
	}

}
