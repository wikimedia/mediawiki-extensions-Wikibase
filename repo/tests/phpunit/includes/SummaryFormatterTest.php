<?php

namespace Wikibase\Repo\Tests;

use DataValues\DataValue;
use MediaWikiLangTestCase;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Summary;
use Wikibase\Repo\RepoHooks;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\SummaryFormatter
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class SummaryFormatterTest extends MediaWikiLangTestCase {

	/**
	 * @param EntityIdValue|EntityId $id
	 *
	 * @return string
	 */
	public function formatId( $id ) {
		if ( $id instanceof EntityIdValue ) {
			$id = $id->getEntityId();
		}

		return '[[' . $id->getEntityType() . ':' . $id->getSerialization() . ']]';
	}

	/**
	 * @param DataValue $value
	 *
	 * @return string
	 */
	public function formatValue( DataValue $value ) {
		if ( $value instanceof EntityIdValue ) {
			return $this->formatId( $value );
		}

		$v = $value->getValue();

		if ( is_scalar( $v ) ) {
			return strval( $v );
		}

		return var_export( $v, true );
	}

	/**
	 * @param Snak $snak
	 *
	 * @return string
	 */
	public function formatSnak( Snak $snak ) {
		if ( $snak instanceof PropertyValueSnak ) {
			return $this->formatValue( $snak->getDataValue() );
		} else {
			return $snak->getType();
		}
	}

	/**
	 * @return SummaryFormatter
	 */
	private function newFormatter() {
		$idFormatter = $this->createMock( EntityIdFormatter::class );
		$idFormatter->method( 'formatEntityId' )
			->willReturnCallback( [ $this, 'formatId' ] );

		$valueFormatter = $this->createMock( ValueFormatter::class );
		$valueFormatter->method( 'format' )
			->willReturnCallback( [ $this, 'formatValue' ] );

		$snakFormatter = $this->createMock( SnakFormatter::class );
		$snakFormatter->method( 'formatSnak' )
			->willReturnCallback( [ $this, 'formatSnak' ] );
		$snakFormatter->method( 'getFormat' )
			->willReturn( SnakFormatter::FORMAT_PLAIN );

		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' );

		$formatter = new SummaryFormatter(
			$idFormatter,
			$valueFormatter,
			$snakFormatter,
			$language,
			new BasicEntityIdParser()
		);

		return $formatter;
	}

	/**
	 * @dataProvider providerFormatAutoComment
	 */
	public function testFormatAutoComment( $module, $action, $language, array $parts, $expected ) {
		$summary = new Summary( $module, $action, $language );

		if ( !empty( $parts ) ) {
			$summary->addAutoCommentArgs( ...$parts );
		}

		$formatter = $this->newFormatter();
		$result = $formatter->formatAutoComment( $summary );
		$this->assertSame( $expected, $result, 'Not the expected result' );
	}

	public function providerFormatAutoComment() {
		$p20 = new NumericPropertyId( 'P20' );
		$q5 = new ItemId( 'Q5' );
		$q5Value = new EntityIdValue( $q5 );
		$p20q5Snak = new PropertyValueSnak( $p20, $q5Value );

		return [
			'empty' => [
				'', '', '',
				[],
				':0|',
			],
			'no args' => [
				'foo', 'testing', 'en',
				[],
				'foo-testing:0|en',
			],
			'one arg' => [
				'foo', 'testing', 'en',
				[ 'one' ],
				'foo-testing:0|en|one',
			],
			'two args (no action)' => [
				'foo', '', 'en',
				[ 'one', 'two' ],
				'foo:0|en|one|two',
			],
			'args contains array (no module)' => [
				'', 'testing', 'en',
				[ [ 'one', 'two' ] ],
				'testing:0|en|one|two',
			],
			'args contains map (no module)' => [
				'', 'testing', 'en',
				[ [ [ 'one' => 1, 'two' => 2 ] ] ],
				'testing:0|en|one: 1, two: 2',
			],
			'empty arg' => [
				'foo', 'testing', 'en',
				[ 'one', '', 'three' ],
				'foo-testing:0|en|one||three',
			],
			'number' => [
				'foo', 'testing', 'en',
				[ 23 ],
				'foo-testing:0|en|23',
			],
			'EntityId' => [
				'foo', 'testing', 'en',
				[ $q5 ],
				'foo-testing:0|en|[[item:Q5]]',
			],
			'DataValue' => [
				'foo', 'testing', 'en',
				[ $q5Value ],
				'foo-testing:0|en|[[item:Q5]]',
			],
			'Snak' => [
				'foo', 'testing', 'en',
				[ $p20q5Snak ],
				'foo-testing:0|en|[[item:Q5]]',
			],
			'property-item-map' => [
				'', 'testing', 'en',
				[ [ [ 'P17' => new ItemId( "Q2" ) ] ] ],
				'testing:0|en|[[property:P17]]: [[item:Q2]]',
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
		$p20 = new NumericPropertyId( 'P20' );
		$q5 = new ItemId( 'Q5' );
		$q5Value = new EntityIdValue( $q5 );
		$p20q5Snak = new PropertyValueSnak( $p20, $q5Value );

		return [
			'empty' => [ [], '' ],
			'no args' => [ [], '' ],
			'one arg' => [ [ 'one' ], 'one' ],
			'two args' => [ [ 'one', 'two' ], 'one, two' ],
			'args contains array' => [ [ [ 'one', 'two' ] ], 'one, two' ],
			'args contains map' => [ [ [ [ 'one' => 1, 'two' => 2 ] ] ], 'one: 1, two: 2' ],
			'empty arg' => [ [ 'one', '' ], 'one' ],
			'number' => [ [ 23 ], '23' ],
			'EntityId' => [ [ $q5 ], '[[item:Q5]]' ],
			'DataValue' => [ [ $q5Value ], '[[item:Q5]]' ],
			'Snak' => [ [ $p20q5Snak ], '[[item:Q5]]' ],
			'property-item-map' => [ [ [ [ 'P17' => new ItemId( "Q2" ) ] ] ], '[[property:P17]]: [[item:Q2]]' ],
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
				'/* foobar:0| */',
			],
			[
				[ '' ],
				[ 'This is a test…' ],
				'/* foobar:1|| */ This is a test…',
			],
			[
				[ 'one' ],
				[ 'This is a test…' ],
				'/* foobar:1||one */ This is a test…',
			],
			[
				[ 'one', 'two' ],
				[ 'This is a test…' ],
				'/* foobar:1||one|two */ This is a test…',
			],
			[
				[ 'one', 'two', 'three' ],
				[ 'This is a test…' ],
				'/* foobar:1||one|two|three */ This is a test…',
			],
			[
				[ 'one', 'two', 'three', '…' ],
				[ 'This is a test…' ],
				'/* foobar:1||one|two|three|… */ This is a test…',
			],
			[
				[ 'one', 'two', 'three', '<>' ],
				[ 'This is a test…' ],
				'/* foobar:1||one|two|three|<> */ This is a test…',
			],
			[
				[ 'one', 'two', 'three', '&lt;&gt;' ],
				[ 'This is a test…' ],
				'/* foobar:1||one|two|three|&lt;&gt; */ This is a test…',
			],
			# This comment is "too long", but it will be truncated to an
			# appropriate length by core's CommentStore (not SummaryFormatter)
			[
				[],
				[ str_repeat( 'a', 512 ) ],
				'/* foobar:1| */ ' . str_repeat( 'a', 512 ),
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
			$summary->addAutoCommentArgs( ...$commentArgs );
		}

		if ( $summaryArgs ) {
			$summary->addAutoSummaryArgs( ...$summaryArgs );
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
				'/* summarytest:0| */',
			],
			[ // #1
				'summarytest',
				'testing',
				'nl',
				null,
				null,
				null,
				'/* summarytest-testing:0|nl */',
			],
			[ // #2
				'summarytest',
				null,
				null,
				[ 'x' ],
				null,
				null,
				'/* summarytest:0||x */',
			],
			[ // #3
				'summarytest',
				'testing',
				'nl',
				[ 'x', 'y' ],
				[ 'A', 'B' ],
				null,
				'/* summarytest-testing:2|nl|x|y */ A, B',
			],
			[ // #4
				'summarytest',
				null,
				null,
				null,
				[ 'A', 'B' ],
				null,
				'/* summarytest:2| */ A, B',
			],
			'User summary overrides arguments' => [
				'summarytest',
				'testing',
				'nl',
				[ 'x', 'y' ],
				[ 'A', 'B' ],
				'can I haz world domination?',
				'/* summarytest-testing:2|nl|x|y */ A, B, can I haz world domination?',
			],
			'Trimming' => [
				'summarytest',
				'testing',
				'de',
				[ ' autoArg0 ', ' autoArg1 ' ],
				[ ' userArg0 ', ' userArg1 ' ],
				' userSummary ',
				'/* summarytest-testing:2|de| autoArg0 | autoArg1 */ userArg0 ,  userArg1, userSummary',
			],
			'User summary only' => [
				'summarytest',
				null,
				null,
				null,
				null,
				'can I haz world domination?',
				'/* summarytest:0| */ can I haz world domination?',
			],
			'User summary w/o arguments' => [
				'summarytest',
				'testing',
				'de',
				[ 'autoArg0', 'autoArg1' ],
				null,
				'userSummary',
				'/* summarytest-testing:0|de|autoArg0|autoArg1 */ userSummary',
			],
			'User summary w/o auto comment arguments' => [
				'summarytest',
				'testing',
				'de',
				null,
				[ 'userArg0', 'userArg1' ],
				'userSummary',
				'/* summarytest-testing:2|de */ userArg0, userArg1, userSummary',
			],
			'Array arguments' => [
				'summarytest',
				'testing',
				'nl',
				[ 'x', [ 1, 2, 3 ] ],
				[ 'A', [ 1, 2, 3 ] ],
				null,
				'/* summarytest-testing:2|nl|x|1, 2, 3 */ A, 1, 2, 3',
			],
			'Associative arguments' => [
				'summarytest',
				'testing',
				'nl',
				[ 'x', [ "foo" => 1, "bar" => 2 ] ],
				[ 'A', [ "foo" => 1, "bar" => 2 ] ],
				null,
				'/* summarytest-testing:2|nl|x|foo: 1, bar: 2 */ A, foo: 1, bar: 2',
			],
		];
	}

	/**
	 * Tests the FormatAutocomment hook provided by RepoHooks.
	 *
	 * @todo move to RepoHooksTest
	 *
	 * @dataProvider providerOnFormat
	 */
	public function testOnFormat( $type, $root, $pre, $auto, $post, $title, $local, $expected ) {
		$itemTitle = $this->createMock( $title );
		$itemTitle->expects( $this->once() )
			->method( 'getNamespace' )
			->willReturn(
				WikibaseRepo::getEntityNamespaceLookup()
					->getEntityNamespace( $type )
			);

		$comment = null;

		RepoHooks::onFormat( $comment, $pre, $auto, $post, $itemTitle, $local );

		if ( $expected === null ) {
			$this->assertNull( $comment, 'Didn\'t find the expected null' );
		} else {
			$this->assertMatchesRegularExpression( $expected, $comment, "Didn't find the expected final comment" );
		}
	}

	public function providerOnFormat() {
		return [ //@todo: test other types of entities too!
			[
				'item',
				"wikibase-item",
				false, '', false,
				'Title',
				false,
				null,
			],
			[
				'item',
				"wikibase-item",
				false, '', false,
				'Title',
				false,
				null,
			],
			[
				'item',
				"wikibase-item",
				true, 'wbeditentity', true,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?: </span></span>!',
			],
			[
				'item',
				"wikibase-item",
				true, 'wbsetlabel-set:1|en', true,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?\[en\].*?: </span></span>!',
			],
			[
				'item',
				"wikibase-item",
				false, 'wbsetlabel-set:1|<>', false,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?\[&#60;&#62;\].*?</span></span>!',
			],
			[
				'item',
				"wikibase-item",
				false, 'wbsetlabel-set:1|&lt;&gt;', false,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?\[&#60;&#62;\].*?</span></span>!',
			],
			[
				'item',
				"wikibase-item",
				false, 'wbsetlabel-set:1|&', false,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?\[&#38;\].*?</span></span>!',
			],
			[
				'item',
				"wikibase-item",
				false, 'wbsetlabel-set:1|&amp;', false,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?\[&#38;\].*?</span></span>!',
			],
			[
				'item',
				"wikibase-item",
				false, 'wbsetlabel-set:1|…', false,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?\[…\].*?</span></span>!',
			],
			[
				'item',
				"wikibase-item",
				false, 'wbsetlabel-set:1|\'""\'', false,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?\[&#39;&#34;&#34;&#39;\].*?</span></span>!',
			],
			[
				'item',
				"wikibase-item",
				false, 'wbsetlabel-set:1|&#039;&quot;&quot;&#039;', false,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?\[&#39;&#34;&#34;&#39;\].*?</span></span>!',
			],
		];
	}

}
