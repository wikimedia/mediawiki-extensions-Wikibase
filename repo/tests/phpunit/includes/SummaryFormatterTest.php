<?php

namespace Wikibase\Test;

use DataValues\DataValue;
use Language;
use MediaWikiLangTestCase;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;
use Wikibase\RepoHooks;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * @covers Wikibase\SummaryFormatter
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseSummary
 * @group Database
 *
 * @license GPL-2.0+
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
			$value = $snak->getDataValue();
			return $this->formatValue( $value );
		} else {
			return $snak->getType();
		}
	}

	/**
	 * @return SummaryFormatter
	 */
	private function newFormatter() {
		$idFormatter = $this->getMock( EntityIdFormatter::class );
		$idFormatter->expects( $this->any() )->method( 'formatEntityId' )
			->will( $this->returnCallback( array( $this, 'formatId' ) ) );

		$valueFormatter = $this->getMock( ValueFormatter::class );
		$valueFormatter->expects( $this->any() )->method( 'format' )
			->will( $this->returnCallback( array( $this, 'formatValue' ) ) );
		$valueFormatter->expects( $this->any() )->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_PLAIN ) );

		$snakFormatter = $this->getMock( SnakFormatter::class );
		$snakFormatter->expects( $this->any() )->method( 'formatSnak' )
			->will( $this->returnCallback( array( $this, 'formatSnak' ) ) );
		$snakFormatter->expects( $this->any() )->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_PLAIN ) );

		$language = Language::factory( 'en' );

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
		$summary = new Summary();
		$summary->setModuleName( $module );
		$summary->setAction( $action );
		$summary->setLanguage( $language );

		if ( !empty( $parts ) ) {
			call_user_func_array( array( $summary, 'addAutoCommentArgs' ), $parts );
		}

		$formatter = $this->newFormatter();
		$result = $formatter->formatAutoComment( $summary );
		$this->assertEquals( $expected, $result, 'Not the expected result' );
	}

	public function providerFormatAutoComment() {
		$p20 = new PropertyId( 'P20' );
		$q5 = new ItemId( 'Q5' );
		$q5Value = new EntityIdValue( $q5 );
		$p20q5Snak = new PropertyValueSnak( $p20, $q5Value );

		return array(
			'empty' => array(
				'', '', '',
				array(),
				':0|'
			),
			'no args' => array(
				'foo', 'testing', 'en',
				array(),
				'foo-testing:0|en'
			),
			'one arg' => array(
				'foo', 'testing', 'en',
				array( 'one' ),
				'foo-testing:0|en|one'
			),
			'two args (no action)' => array(
				'foo', '', 'en',
				array( 'one', 'two' ),
				'foo:0|en|one|two'
			),
			'args contains array (no module)' => array(
				'', 'testing', 'en',
				array( array( 'one', 'two' ) ),
				'testing:0|en|one|two'
			),
			'args contains map (no module)' => array(
				'', 'testing', 'en',
				array( array( array( 'one' => 1, 'two' => 2 ) ) ),
				'testing:0|en|one: 1, two: 2'
			),
			'empty arg' => array(
				'foo', 'testing', 'en',
				array( 'one', '', 'three' ),
				'foo-testing:0|en|one||three'
			),
			'number' => array(
				'foo', 'testing', 'en',
				array( 23 ),
				'foo-testing:0|en|23'
			),
			'EntityId' => array(
				'foo', 'testing', 'en',
				array( $q5 ),
				'foo-testing:0|en|[[item:Q5]]'
			),
			'DataValue' => array(
				'foo', 'testing', 'en',
				array( $q5Value ),
				'foo-testing:0|en|[[item:Q5]]'
			),
			'Snak' => array(
				'foo', 'testing', 'en',
				array( $p20q5Snak ),
				'foo-testing:0|en|[[item:Q5]]'
			),
			'property-item-map' => array(
				'', 'testing', 'en',
				array( array( array( 'P17' => new ItemId( "Q2" ) ) ) ),
				'testing:0|en|[[property:P17]]: [[item:Q2]]'
			),
		);
	}

	/**
	 * @dataProvider providerFormatAutoSummary
	 */
	public function testFormatAutoSummary( array $parts, $expected ) {
		$summary = new Summary();
		$summary->addAutoSummaryArgs( $parts );

		$formatter = $this->newFormatter();
		$result = $formatter->formatAutoSummary( $summary );
		$this->assertEquals( $expected, $result, 'Not the expected result' );
	}

	public function providerFormatAutoSummary() {
		$p20 = new PropertyId( 'P20' );
		$q5 = new ItemId( 'Q5' );
		$q5Value = new EntityIdValue( $q5 );
		$p20q5Snak = new PropertyValueSnak( $p20, $q5Value );

		return array(
			'empty' => array( array(), '' ),
			'no args' => array( array(), '' ),
			'one arg' => array( array( 'one' ), 'one' ),
			'two args' => array( array( 'one', 'two' ), 'one, two' ),
			'args contains array' => array( array( array( 'one', 'two' ) ), 'one, two' ),
			'args contains map' => array( array( array( array( 'one' => 1, 'two' => 2 ) ) ), 'one: 1, two: 2' ),
			'empty arg' => array( array( 'one', '' ), 'one' ),
			'number' => array( array( 23 ), '23' ),
			'EntityId' => array( array( $q5 ), '[[item:Q5]]' ),
			'DataValue' => array( array( $q5Value ), '[[item:Q5]]' ),
			'Snak' => array( array( $p20q5Snak ), '[[item:Q5]]' ),
			'property-item-map' => array( array( array( array( 'P17' => new ItemId( "Q2" ) ) ) ), '[[property:P17]]: [[item:Q2]]' ),
		);
	}

	/**
	 * @dataProvider provideToStringArgs
	 */
	public function testToStringArgHandling( array $commentArgs, array $summaryArgs, $expected ) {
		$summary = new Summary( 'foobar' );
		$summary->addAutoCommentArgs( $commentArgs );
		$summary->addAutoSummaryArgs( $summaryArgs );

		$formatter = $this->newFormatter();
		$this->assertEquals( $expected, $formatter->formatSummary( $summary ) );
	}

	public function provideToStringArgs() {
		return array(
			array(
				array(),
				array(),
				'/* foobar:0| */'
			),
			array(
				array( '' ),
				array( 'This is a test…' ),
				'/* foobar:1|| */ This is a test…'
			),
			array(
				array( 'one' ),
				array( 'This is a test…' ),
				'/* foobar:1||one */ This is a test…'
			),
			array(
				array( 'one', 'two' ),
				array( 'This is a test…' ),
				'/* foobar:1||one|two */ This is a test…'
			),
			array(
				array( 'one', 'two', 'three' ),
				array( 'This is a test…' ),
				'/* foobar:1||one|two|three */ This is a test…'
			),
			array(
				array( 'one', 'two', 'three', '…' ),
				array( 'This is a test…' ),
				'/* foobar:1||one|two|three|… */ This is a test…'
			),
			array(
				array( 'one', 'two', 'three', '<>' ),
				array( 'This is a test…' ),
				'/* foobar:1||one|two|three|<> */ This is a test…'
			),
			array(
				array( 'one', 'two', 'three', '&lt;&gt;' ),
				array( 'This is a test…' ),
				'/* foobar:1||one|two|three|&lt;&gt; */ This is a test…'
			),
			array(
				array(),
				array( str_repeat( 'a', 2 * SUMMARY_MAX_LENGTH ) ),
				'/* foobar:1| */ ' . str_repeat( 'a', SUMMARY_MAX_LENGTH - 19 ) . '...'
			),
		);
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
			call_user_func_array( array( $summary, 'addAutoCommentArgs' ), $commentArgs );
		}

		if ( $summaryArgs ) {
			call_user_func_array( array( $summary, 'addAutoSummaryArgs' ), $summaryArgs );
		}

		if ( $userSummary !== null ) {
			$summary->setUserSummary( $userSummary );
		}

		$formatter = $this->newFormatter();
		$this->assertEquals( $expected, $formatter->formatSummary( $summary ) );
	}

	public function provideFormatSummary() {
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
				array( 'A', 'B' ),
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
			'User summary overrides arguments' => array(
				'summarytest',
				'testing',
				'nl',
				array( 'x', 'y' ),
				array( 'A', 'B' ),
				'can I haz world domination?',
				'/* summarytest-testing:2|nl|x|y */ A, B, can I haz world domination?'
			),
			'Trimming' => array(
				'summarytest',
				'testing',
				'de',
				array( ' autoArg0 ', ' autoArg1 ' ),
				array( ' userArg0 ', ' userArg1 ' ),
				' userSummary ',
				'/* summarytest-testing:2|de| autoArg0 | autoArg1 */ userArg0 ,  userArg1, userSummary'
			),
			'User summary only' => array(
				'summarytest',
				null,
				null,
				null,
				null,
				'can I haz world domination?',
				'/* summarytest:0| */ can I haz world domination?'
			),
			'User summary w/o arguments' => array(
				'summarytest',
				'testing',
				'de',
				array( 'autoArg0', 'autoArg1' ),
				null,
				'userSummary',
				'/* summarytest-testing:0|de|autoArg0|autoArg1 */ userSummary'
			),
			'User summary w/o auto comment arguments' => array(
				'summarytest',
				'testing',
				'de',
				null,
				array( 'userArg0', 'userArg1' ),
				'userSummary',
				'/* summarytest-testing:2|de */ userArg0, userArg1, userSummary'
			),
			'Array arguments' => array(
				'summarytest',
				'testing',
				'nl',
				array( 'x', array( 1, 2, 3 ) ),
				array( 'A', array( 1, 2, 3 ) ),
				null,
				'/* summarytest-testing:2|nl|x|1, 2, 3 */ A, 1, 2, 3'
			),
			'Associative arguments' => array(
				'summarytest',
				'testing',
				'nl',
				array( 'x', array( "foo" => 1, "bar" => 2 ) ),
				array( 'A', array( "foo" => 1, "bar" => 2 ) ),
				null,
				'/* summarytest-testing:2|nl|x|foo: 1, bar: 2 */ A, foo: 1, bar: 2'
			),
		);
	}

	/**
	 * Tests the FormatAutocomment hook provided by RepoHooks.
	 *
	 * @todo move to RepoHooksTest
	 *
	 * @dataProvider providerOnFormat
	 */
	public function testOnFormat( $type, $root, $pre, $auto, $post, $title, $local, $expected ) {
		$itemTitle = $this->getMock( $title );
		$itemTitle->expects( $this->once() )->method( 'getNamespace' )->will( $this->returnValue(
			WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup()->getEntityNamespace( $type )
		) );

		$comment = null;

		RepoHooks::onFormat( $comment, $pre, $auto, $post, $itemTitle, $local );

		if ( is_null( $expected ) ) {
			$this->assertEquals( $expected, $comment, "Didn't find the expected null" );
		} else {
			$this->assertRegExp( $expected, $comment, "Didn't find the expected final comment" );
		}
	}

	public function providerOnFormat() {
		return array( //@todo: test other types of entities too!
			array(
				'item',
				"wikibase-item",
				false, '', false,
				'Title',
				false,
				null
			),
			array(
				'item',
				"wikibase-item",
				false, '', false,
				'Title',
				false,
				null
			),
			array(
				'item',
				"wikibase-item",
				true, 'wbeditentity', true,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?: </span></span>!'
			),
			array(
				'item',
				"wikibase-item",
				true, 'wbsetlabel-set:1|en', true,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?\[en\].*?: </span></span>!'
			),
			array(
				'item',
				"wikibase-item",
				false, 'wbsetlabel-set:1|<>', false,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?\[&lt;&gt;\].*?</span></span>!'
			),
			array(
				'item',
				"wikibase-item",
				false, 'wbsetlabel-set:1|&lt;&gt;', false,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?\[&lt;&gt;\].*?</span></span>!'
			),
			array(
				'item',
				"wikibase-item",
				false, 'wbsetlabel-set:1|&', false,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?\[&amp;\].*?</span></span>!'
			),
			array(
				'item',
				"wikibase-item",
				false, 'wbsetlabel-set:1|…', false,
				'Title',
				false,
				'!<span dir="auto"><span class="autocomment">.*?\[…\].*?</span></span>!'
			)
		);
	}

}
