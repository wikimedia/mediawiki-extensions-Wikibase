<?php

namespace Wikibase\Lib\Test;

use Language;
use Wikibase\Lib\AutoCommentFormatter;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @covers Wikibase\Lib\AutoCommentFormatter
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Jonas Kress
 */
class AutoCommentFormatterTest extends \MediaWikiTestCase {

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var string LEFT-TO-RIGHT MARK, commonly abbreviated LRM from Language.php
	 */
	private static $lrm = "\xE2\x80\x8E";

	protected function setUp() {
		parent::setUp();
		$this->language = Language::factory( 'qqx' );
	}

	public function provideTestAutoComment() {
		return array(
			'Empty comment' => array( array( '' ), '', null ),
			'Non existant message' => array( array( 'wikibase' ), '##########', null ),
			'Existing message with no params' => array(
				array( 'wikibase-item' ),
				'wbsetitem',
				'(wikibase-item-summary-wbsetitem)',
			),
			'Existing message with 1 parameter' => array(
				array( 'wikibase-item', 'wikibase-entity' ),
				'wbsetlabel-add:|FOO',
				'(wikibase-item-summary-wbsetlabel-add: , FOO)',
			),
			'Existing message with 2 parameters' => array(
				array( 'wikibase-entity' ),
				'wbsetaliases-set:10|FOO',
				'(wikibase-entity-summary-wbsetaliases-set: 10, FOO)',
			),
		);
	}

	/**
	 * @dataProvider provideTestAutoComment
	 */
	public function testFormatAutoComment( array $prefixes, $auto, $expected ) {
		$formatter = new AutoCommentFormatter(
			$this->language,
			$prefixes,
			new LanguageNameLookup()
		);
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
		$formatter = new AutoCommentFormatter(
			$this->language,
			array(),
			new LanguageNameLookup()
		);
		$value = $formatter->wrapAutoComment( $pre, $comment, $post );
		$this->assertEquals( $expected, $value );
	}

}
