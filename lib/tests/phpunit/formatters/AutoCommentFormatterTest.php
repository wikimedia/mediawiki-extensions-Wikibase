<?php

namespace Wikibase\Lib\Test;

use Language;
use Wikibase\Lib\AutoCommentFormatter;

/**
 * @covers Wikibase\Lib\AutoCommentFormatter
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Jonas Kress
 */
class AutoCommentFormatterTest extends \MediaWikiTestCase {

	/**
	 * @var Language
	 */
	public $language;

	/**
	 * @var string LEFT-TO-RIGHT MARK, commonly abbreviated LRM from Language.php
	 */
	private static $lrm = "\xE2\x80\x8E";

	public function setUp() {
		parent::setUp();
		$this->language = Language::factory( 'qqx' );
		$this->setMwGlobals( 'wgLang', $this->language );
	}

	public function provideTestAutoComment() {
		return array(
			'Empty comment' => array( '', '', '', '', null ),
			'Non existant message' => array( 'wikibase', '', '##########', '', null ),
			'Existing message with no params' => array(
				'wikibase-item',
				'',
				'wbsetitem',
				'',
				self::$lrm .
				'<span dir="auto"><span class="autocomment">(wikibase-item-summary-wbsetitem)</span></span>',
			),
			'Existing message with 1 parameter' => array(
				'wikibase-item',
				'',
				'wbsetlabel-add:|FOO',
				'',
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'(wikibase-item-summary-wbsetlabel-add: , FOO)</span></span>',
			),
			'Existing message with 2 parameters' => array(
				'wikibase-item',
				'',
				'wbsetaliases-set:10|FOO',
				'',
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'(wikibase-item-summary-wbsetaliases-set: 10, FOO)</span></span>',
			),
			'Pre and Post set to false' => array(
				'wikibase-item',
				false,
				'wbsetitem',
				false,
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'(wikibase-item-summary-wbsetitem)</span></span>',
			),
			'Pre is true, post is false' => array(
				'wikibase-item',
				true,
				'wbsetitem',
				false,
				'(autocomment-prefix)' .
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'(wikibase-item-summary-wbsetitem)</span></span>',
			),
			'Pre is false, post is true' => array(
				'wikibase-item',
				false,
				'wbsetitem',
				true,
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'(wikibase-item-summary-wbsetitem)(colon-separator)</span></span>',
			),
			'Pre and post set to strings' => array(
				'wikibase-item',
				'AAA',
				'wbsetitem',
				'ZZZ',
				'AAA(autocomment-prefix)' .
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'(wikibase-item-summary-wbsetitem)(colon-separator)</span></span>ZZZ',
			),
		);
	}

	/**
	 * @dataProvider provideTestAutoComment
	 */
	public function testFormatAutoComment( $prefix, $pre, $auto, $post, $expected ) {
		$formatter = new AutoCommentFormatter( $this->language, $prefix );
		$value = $formatter->formatAutoComment( $pre, $auto, $post );
		$this->assertEquals( $expected, $value );
	}

}
