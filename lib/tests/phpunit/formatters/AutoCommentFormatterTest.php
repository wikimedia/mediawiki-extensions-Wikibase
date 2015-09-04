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
			'Empty comment' => array( '', '', null ),
			'Non existant message' => array( 'wikibase', '##########', null ),
			'Existing message with no params' => array(
				'wikibase-item',
				'wbsetitem',
				'(wikibase-item-summary-wbsetitem)',
			),
			'Existing message with 1 parameter' => array(
				'wikibase-item',
				'wbsetlabel-add:|FOO',
				'(wikibase-item-summary-wbsetlabel-add: , FOO)',
			),
			'Existing message with 2 parameters' => array(
				'wikibase-item',
				'wbsetaliases-set:10|FOO',
				'(wikibase-item-summary-wbsetaliases-set: 10, FOO)',
			),
		);
	}

	/**
	 * @dataProvider provideTestAutoComment
	 */
	public function testFormatAutoComment( $prefix, $auto, $expected ) {
		$formatter = new AutoCommentFormatter( $this->language, $prefix );
		$value = $formatter->formatAutoComment( $auto );
		$this->assertEquals( $expected, $value );
	}

	public function provideWrapAutoComment() {
		return array(
			'Pre and Post set to false' => array(
				false,
				'--FOO--',
				false,
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'--FOO--</span></span>',
			),
			'Pre is true, post is false' => array(
				true,
				'--FOO--',
				false,
				'(autocomment-prefix)' .
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'--FOO--</span></span>',
			),
			'Pre is false, post is true' => array(
				false,
				'--FOO--',
				true,
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'--FOO--(colon-separator)</span></span>',
			),
			'Pre and post set to strings' => array(
				true,
				'--FOO--',
				true,
				'(autocomment-prefix)' .
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'--FOO--(colon-separator)</span></span>',
			),
		);
	}

	/**
	 * @dataProvider provideWrapAutoComment
	 */
	public function testWrapAutoComment( $pre, $comment, $post, $expected ) {
		$formatter = new AutoCommentFormatter( $this->language, 'DUMMY' );
		$value = $formatter->wrapAutoComment( $pre, $comment, $post );
		$this->assertEquals( $expected, $value );
	}

	public function provideExpandAutoComments() {
		return array(
			'empty' => array(
				'',
				''
			),
			'no comment block' => array(
				'foo bar',
				'foo bar'
			),
			'unparsable comment block' => array(
				'foo /* who likes kittens */ bar',
				'foo /* who likes kittens */ bar'
			),
			'simple comment block' => array(
				'/* test-message */ bar',
				'/* (testing-summary-test-message) */ bar'
			),
			'comment block with params' => array(
				'foo /* test-message:one|two */',
				'foo /* (testing-summary-test-message: one, two) */'
			),
			'multiple comment blocks' => array(
				'foo /* test-message-one */ bar /* test-message-two */ zap',
				'foo /* (testing-summary-test-message-one) */ bar /* (testing-summary-test-message-two) */ zap'
			),
		);
	}

	/**
	 * @dataProvider provideExpandAutoComments
	 */
	public function testExpandAutoComments( $summary, $expected ) {
		$formatter = new AutoCommentFormatter( $this->language, 'testing' );
		$value = $formatter->expandAutoComments( $summary );
		$this->assertEquals( $expected, $value );
	}

}
