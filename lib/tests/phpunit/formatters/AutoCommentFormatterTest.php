<?php

namespace Wikibase\Lib\Test;

use Language;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\AutoCommentFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\WikibaseRepo;

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
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var string LEFT-TO-RIGHT MARK, commonly abbreviated LRM from Language.php
	 */
	private static $lrm = "\xE2\x80\x8E";

	public function setUp() {
		parent::setUp();
		$this->language = Language::factory( 'qqx' );
		$this->setMwGlobals( 'wgLang', $this->language );
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$this->idParser = $wikibaseRepo->getEntityIdParser();
		$this->titleLookup = $wikibaseRepo->getEntityTitleLookup();
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
			$this->idParser,
			$this->titleLookup
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
			$this->idParser,
			$this->titleLookup
		);
		$value = $formatter->wrapAutoComment( $pre, $comment, $post );
		$this->assertEquals( $expected, $value );
	}

}
