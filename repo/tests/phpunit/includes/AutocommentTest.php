<?php

namespace Wikibase\Test;
use Wikibase\Autocomment;

/**
 * Test Autocomment.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group Autocomment
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 *
 */
class AutocommentTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider providerOnFormat
	 */
	public function testOnFormat( $model, $root, $pre, $auto, $post, $title, $local, $expected ) {
		$itemTitle = $this->getMock( $title );
		$itemTitle->expects( $this->once() )->method( 'getContentModel' )->will( $this->returnValue( $model ) );
		$comment = null;
		Autocomment::onFormat( array($model, $root), $comment, $pre, $auto, $post, $itemTitle, $local );
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
				'!foo<span dir="auto"><span class="autocomment">.*?\[&amp;\].*?</span>bar</span>!'
			),
			array(
				CONTENT_MODEL_WIKIBASE_ITEM,
				"wikibase-item",
				'foo', 'wbsetlabel-set:1|…', 'bar',
				'Title',
				false,
				'!foo<span dir="auto"><span class="autocomment">.*?\[…\].*?</span>bar</span>!'
			)
		);
	}

	/**
	 * @dataProvider providerPickValuesFromParams
	 */
	public function testPickValuesFromParams( array $params, array $sequence, array $expected ) {
		$result = Autocomment::pickValuesFromParams( $params, $sequence );
		$this->assertEquals( $expected, $result, 'Not the expected result' );
	}

	public static function providerPickValuesFromParams() {
		return array(
			array(
				array( 'one' => 'one-value', 'two' => 'two-value', 'three' => 'three-value' ),
				array(),
				array()
			),
			array(
				array( 'one' => 'one-value', 'two' => 'two-value', 'three' => 'three-value' ),
				array( 'one' ),
				array( 'one-value' )
			),
			array(
				array( 'one' => 'one-value', 'two' => 'two-value', 'three' => 'three-value' ),
				array( 'one', 'two' ),
				array( 'one-value', 'two-value' )
			),
			array(
				array( 'one' => 'one-value', 'two' => 'two-value', 'three' => 'three-value' ),
				array( 'one', 'two', 'three' ),
				array( 'one-value', 'two-value', 'three-value' )
			),
			array(
				array( 'one' => 'one-value', 'two' => 'two-value', 'three' => 'three-value' ),
				array( 'one', 'three', 'four' ),
				array( 'one-value', 'three-value' )
			),
		);
	}

	/**
	 * @dataProvider providerPickKeysFromParams
	 */
	public function testPickKeysFromParams( array $params, array $sequence, array $expected ) {
		$result = Autocomment::pickKeysFromParams( $params, $sequence );
		$this->assertEquals( $expected, $result, 'Not the expected result' );
	}

	public static function providerPickKeysFromParams() {
		return array(
			array(
				array( 'one' => 'one-value', 'two' => 'two-value', 'three' => 'three-value' ),
				array(),
				array()
			),
			array(
				array( 'one' => 'one-value', 'two' => 'two-value', 'three' => 'three-value' ),
				array( 'one' ),
				array( 'one' )
			),
			array(
				array( 'one' => 'one-value', 'two' => 'two-value', 'three' => 'three-value' ),
				array( 'one', 'two' ),
				array( 'one', 'two' )
			),
			array(
				array( 'one' => 'one-value', 'two' => 'two-value', 'three' => 'three-value' ),
				array( 'one', 'two', 'three' ),
				array( 'one', 'two', 'three' )
			),
			array(
				array( 'one' => 'one-value', 'two' => 'two-value', 'three' => 'three-value' ),
				array( 'one', 'three', 'four' ),
				array( 'one', 'three' )
			),
		);
	}

	/**
	 * @dataProvider providerFormatAutoComment
	 */
	public function testFormatAutoComment( $msg, $parts, $expected ) {
		$result = Autocomment::formatAutoComment( $msg, $parts );
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
	public function testFormatAutoSummary( $parts, $lang, $expected ) {
		$result = Autocomment::formatAutoSummary( $parts, $lang );
		$this->assertEquals( $expected, $result, 'Not the expected result' );
	}

	public static function providerFormatAutoSummary() {
		$lang = \Language::factory( 'en' );
		return array(
			array( array(), $lang, array( 0, '', $lang ) ),
			array( array( 'one' ), $lang, array( 1, 'one', $lang ) ),
			array( array( 'one', 'two' ), $lang, array( 2, 'one, two', $lang ) ),
			array( array( 'one', 'two', 'three' ), $lang, array( 3, 'one, two, three', $lang ) ),
		);
	}

	/**
	 * @dataProvider providerFormatTotalSummary
	 */
	public function testFormatTotalSummary( $comment, $summary, $lang, $expected ) {
		$result = Autocomment::formatTotalSummary( $comment, $summary, $lang );
		$this->assertEquals( $expected, $result, 'Not the expected result' );
	}

	public static function providerFormatTotalSummary() {
		$lang = \Language::factory( 'en' );
		return array(
			array( '', '', $lang, '' ),
			array( 'foobar', 'This is a test…', $lang, '/* foobar */ This is a test…' ),
			array( 'foobar:one', 'This is a test…', $lang, '/* foobar:one */ This is a test…' ),
			array( 'foobar:one|two', 'This is a test…', $lang, '/* foobar:one|two */ This is a test…' ),
			array( 'foobar:one|two|three', 'This is a test…', $lang, '/* foobar:one|two|three */ This is a test…' ),
			array( 'foobar:one|two|three|…', 'This is a test…', $lang, '/* foobar:one|two|three|… */ This is a test…' ),
			array( 'foobar:one|two|three|<>', 'This is a test…', $lang, '/* foobar:one|two|three|<> */ This is a test…' ),
			array( 'foobar:one|two|three|&lt;&gt;', 'This is a test…', $lang, '/* foobar:one|two|three|&lt;&gt; */ This is a test…' ),
			array(  '', str_repeat( 'a', 2*SUMMARY_MAX_LENGTH ), $lang, str_repeat( 'a', SUMMARY_MAX_LENGTH-3 ) . '...' ),
		);
	}
}
