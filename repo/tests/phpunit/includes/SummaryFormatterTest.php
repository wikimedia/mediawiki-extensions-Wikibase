<?php

namespace Wikibase\Test;

use DataValues\DataValue;
use Language;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;
use Wikibase\RepoHooks;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * @covers Wikibase\SummaryFormatter
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseSummary
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class SummaryFormatterTest extends \MediaWikiLangTestCase {

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
	protected function newFormatter() {
		$idFormatter = $this->getMockBuilder( 'Wikibase\Lib\EntityIdFormatter' )
			->disableOriginalConstructor()
			->getMock();
		$idFormatter->expects( $this->any() )->method( 'format' )
			->will( $this->returnCallback( array( $this, 'formatId' ) ) );

		$valueFormatter = $this->getMock( 'ValueFormatters\ValueFormatter' );
		$valueFormatter->expects( $this->any() )->method( 'format' )
			->will( $this->returnCallback( array( $this, 'formatValue' ) ) );
		$valueFormatter->expects( $this->any() )->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_PLAIN ) );

		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );
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

		call_user_func_array( array( $summary, 'addAutoCommentArgs' ), $parts );

		$formatter = $this->newFormatter();
		$result = $formatter->formatAutoComment( $summary );
		$this->assertEquals( $expected, $result, 'Not the expected result' );
	}

	public static function providerFormatAutoComment() {
		$p20 = new PropertyId( 'P20' );
		$q5 = new ItemId( 'Q5' );
		$q5Value = new EntityIdValue( $q5 );
		$p20q5Snak = new PropertyValueSnak( $p20, $q5Value );

		return array(
			'empty' => array( '', '', '', array(), ':0|' ),
			'no args' => array( 'foo', 'testing', 'en', array(), 'foo-testing:0|en' ),
			'one arg' => array( 'foo', 'testing', 'en', array( 'one' ), 'foo-testing:0|en|one' ),
			'two args (no action)' => array( 'foo', '', 'en', array( 'one', 'two' ), 'foo:0|en|one|two' ),
			'args contains array (no module)' => array( '', 'testing', 'en', array( array( 'one', 'two' ) ), 'testing:0|en|one|two' ),
			'args contains map (no module)' => array( '', 'testing', 'en', array( array( array( 'one' => 1, 'two' => 2 ) ) ), 'testing:0|en|one: 1, two: 2' ),
			'empty arg' => array( 'foo', 'testing', 'en', array( 'one', '', 'three' ), 'foo-testing:0|en|one||three' ),
			'number' => array( 'foo', 'testing', 'en', array( 23 ), 'foo-testing:0|en|23' ),
			'EntityId' => array( 'foo', 'testing', 'en', array( $q5 ), 'foo-testing:0|en|[[item:Q5]]' ),
			'DataValue' => array( 'foo', 'testing', 'en', array( $q5Value ), 'foo-testing:0|en|[[item:Q5]]' ),
			'Snak' => array( 'foo', 'testing', 'en', array( $p20q5Snak ), 'foo-testing:0|en|[[item:Q5]]' ),
			'property-item-map' => array( '', 'testing', 'en', array( array( array( 'P17' => new ItemId( "Q2" ) ) ) ), 'testing:0|en|[[property:P17]]: [[item:Q2]]' ),
		);
	}

	/**
	 * @dataProvider providerFormatAutoSummary
	 */
	public function testFormatAutoSummary( array $parts, $expected ) {
		$summary = new Summary();

		call_user_func_array( array( $summary, 'addAutoSummaryArgs' ), $parts );

		$formatter = $this->newFormatter();
		$result = $formatter->formatAutoSummary( $summary );
		$this->assertEquals( $expected, $result, 'Not the expected result' );
	}

	public static function providerFormatAutoSummary() {
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
		call_user_func_array( array( $summary, 'addAutoCommentArgs' ), $commentArgs );
		call_user_func_array( array( $summary, 'addAutoSummaryArgs' ), $summaryArgs );

		$formatter = $this->newFormatter();
		$this->assertEquals( $expected, $formatter->formatSummary( $summary ) );
	}

	public static function provideToStringArgs() {
		return array(
			array( array(), array(), '/* foobar:0| */' ),
			array( array( '' ), array( 'This is a test…' ), '/* foobar:1|| */ This is a test…' ),
			array( array( 'one' ), array( 'This is a test…' ), '/* foobar:1||one */ This is a test…' ),
			array( array( 'one', 'two' ), array( 'This is a test…' ), '/* foobar:1||one|two */ This is a test…' ),
			array( array( 'one', 'two', 'three' ), array( 'This is a test…' ), '/* foobar:1||one|two|three */ This is a test…' ),
			array( array( 'one', 'two', 'three', '…' ), array( 'This is a test…' ), '/* foobar:1||one|two|three|… */ This is a test…' ),
			array( array( 'one', 'two', 'three', '<>' ), array( 'This is a test…' ), '/* foobar:1||one|two|three|<> */ This is a test…' ),
			array( array( 'one', 'two', 'three', '&lt;&gt;' ), array( 'This is a test…' ), '/* foobar:1||one|two|three|&lt;&gt; */ This is a test…' ),
			array( array(), array( str_repeat( 'a', 2 * SUMMARY_MAX_LENGTH ) ), '/* foobar:1| */ ' . str_repeat( 'a', SUMMARY_MAX_LENGTH - 19 ) . '...' ),
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

	public static function provideFormatSummary() {
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
			array( // #7
				'summarytest',
				'testing',
				'nl',
				array( 'x', array( 1, 2, 3 ) ),
				array( 'A', array( 1, 2, 3 ) ),
				null,
				'/* summarytest-testing:2|nl|x|1, 2, 3 */ A, 1, 2, 3'
			),
			array( // #8
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
	 * @dataProvider providerOnFormat
	 */
	public function testOnFormat( $model, $root, $pre, $auto, $post, $title, $local, $expected ) {
		$itemTitle = $this->getMock( $title );
		$itemTitle->expects( $this->once() )->method( 'getContentModel' )->will( $this->returnValue( $model ) );
		$comment = null;
		RepoHooks::onFormat( array($model, $root), $comment, $pre, $auto, $post, $itemTitle, $local );
		if ( is_null( $expected ) ) {
			$this->assertEquals( $expected, $comment, "Didn't find the expected null" );
		}
		else {
			$this->assertRegExp( $expected, $comment, "Didn't find the expected final comment" );
		}
	}

	public static function providerOnFormat() {
		return array( //@todo: test other types of entities too!
			array(
				CONTENT_MODEL_WIKIBASE_ITEM,
				"wikibase-item",
				'', '', '',
				'Title',
				false,
				null
			),
			array(
				CONTENT_MODEL_WIKIBASE_ITEM,
				"wikibase-item",
				'foo', '', 'bar',
				'Title',
				false,
				null
			),
			array(
				CONTENT_MODEL_WIKIBASE_ITEM,
				"wikibase-item",
				'foo', 'wbeditentity', 'bar',
				'Title',
				false,
				'!foo‎<span dir="auto"><span class="autocomment">.*?</span>bar</span>!'
			),
			array(
				CONTENT_MODEL_WIKIBASE_ITEM,
				"wikibase-item",
				'foo', 'wbsetlabel-set:1|en', 'bar',
				'Title',
				false,
				'!foo‎<span dir="auto"><span class="autocomment">.*?\[en\].*?</span>bar</span>!'
			),
			array(
				CONTENT_MODEL_WIKIBASE_ITEM,
				"wikibase-item",
				'foo', 'wbsetlabel-set:1|<>', 'bar',
				'Title',
				false,
				'!foo‎<span dir="auto"><span class="autocomment">.*?\[&lt;&gt;\].*?</span>bar</span>!'
			),
			array(
				CONTENT_MODEL_WIKIBASE_ITEM,
				"wikibase-item",
				'foo', 'wbsetlabel-set:1|&lt;&gt;', 'bar',
				'Title',
				false,
				'!foo‎<span dir="auto"><span class="autocomment">.*?\[&lt;&gt;\].*?</span>bar</span>!'
			),
			array(
				CONTENT_MODEL_WIKIBASE_ITEM,
				"wikibase-item",
				'foo', 'wbsetlabel-set:1|&', 'bar',
				'Title',
				false,
				'!foo‎<span dir="auto"><span class="autocomment">.*?\[&amp;\].*?</span>bar</span>!'
			),
			array(
				CONTENT_MODEL_WIKIBASE_ITEM,
				"wikibase-item",
				'foo', 'wbsetlabel-set:1|…', 'bar',
				'Title',
				false,
				'!foo‎<span dir="auto"><span class="autocomment">.*?\[…\].*?</span>bar</span>!'
			)
		);
	}

}
