<?php

namespace Wikibase\Lib\Tests\Formatters;

use Language;
use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Formatters\AutoCommentFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\AutoCommentFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Jonas Kress
 */
class AutoCommentFormatterTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var string LEFT-TO-RIGHT MARK, commonly abbreviated LRM from Language.php
	 */
	private static $lrm = "\xE2\x80\x8E";

	protected function setUp(): void {
		parent::setUp();
		$this->language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );
	}

	public function provideTestAutoComment() {
		return [
			'Empty comment' => [ [ '' ], '', null ],
			'Non existant message' => [ [ 'wikibase' ], '##########', null ],
			'Existing message with no params' => [
				[ 'wikibase-item' ],
				'wbsetitem',
				'(wikibase-item-summary-wbsetitem)',
			],
			'Existing message with 1 parameter' => [
				[ 'wikibase-item', 'wikibase-entity' ],
				'wbsetlabel-add:|FOO',
				'(wikibase-item-summary-wbsetlabel-add: , FOO)',
			],
			'Existing message with 2 parameters' => [
				[ 'wikibase-entity' ],
				'wbsetaliases-set:10|FOO',
				'(wikibase-entity-summary-wbsetaliases-set: 10, FOO)',
			],
		];
	}

	/**
	 * @dataProvider provideTestAutoComment
	 */
	public function testFormatAutoComment( array $prefixes, $auto, $expected ) {
		$formatter = new AutoCommentFormatter( $this->language, $prefixes );
		$value = $formatter->formatAutoComment( $auto );
		$this->assertEquals( $expected, $value );
	}

	public function provideWrapAutoComment() {
		return [
			'Pre and Post set to false' => [
				false,
				'--FOO--',
				false,
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'--FOO--</span></span>',
			],
			'Pre is true, post is false' => [
				true,
				'--FOO--',
				false,
				'(autocomment-prefix)' .
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'--FOO--</span></span>',
			],
			'Pre is false, post is true' => [
				false,
				'--FOO--',
				true,
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'--FOO--(colon-separator)</span></span>',
			],
			'Pre and post set to strings' => [
				true,
				'--FOO--',
				true,
				'(autocomment-prefix)' .
				self::$lrm .
				'<span dir="auto"><span class="autocomment">' .
				'--FOO--(colon-separator)</span></span>',
			],
		];
	}

	/**
	 * @dataProvider provideWrapAutoComment
	 */
	public function testWrapAutoComment( $pre, $comment, $post, $expected ) {
		$formatter = new AutoCommentFormatter( $this->language, [] );
		$value = $formatter->wrapAutoComment( $pre, $comment, $post );
		$this->assertEquals( $expected, $value );
	}

}
