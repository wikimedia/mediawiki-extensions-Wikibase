<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\Formatters\InterWikiLinkWikitextFormatter;

/**
 * @covers Wikibase\Lib\Formatters\InterWikiLinkWikitextFormatter
 *
 * @license GPL-2.0+
 */
class InterWikiLinkWikitextFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testSomeTitleGiven_FormatsItAsAnExternalLink() {
		$formatter = $this->createFormatter( '//site.org/wiki/' );

		$link = $formatter->format( new StringValue( 'Namespace:Title' ) );

		self::assertEquals(
			'[//site.org/wiki/Namespace:Title Namespace:Title]',
			$link
		);
	}

	public function testTitleContainsSpaces_ReplacesSpacesWithUnderscoresWhenFormats() {
		$formatter = $this->createFormatter( '//site.org/wiki/' );

		$link = $formatter->format( new StringValue( 'Namespace:Title with spaces' ) );

		$url = $this->extractUrlFromExternalWikitextLink( $link );
		self::assertEquals(
			'//site.org/wiki/Namespace:Title_with_spaces',
			$url
		);
	}

	public function testTitleContainsSpaces_EncodesSpacesInTitleWhenFormats() {
		$this->markTestIncomplete( "Don't know what output should look like" );
		$formatter = $this->createFormatter( '//base.url/' );

		$link = $formatter->format( new StringValue( 'Namespace:Title with spaces' ) );

		$text = $this->extractLinkTextFromExternalWikitextLink( $link );
		self::assertEquals(
			'Namespace:Title<escaped space>with<escaped space>spaces',
			$text
		);
	}

	/**
	 * @return InterWikiLinkWikitextFormatter
	 */
	private function createFormatter( $baseUrl ) {
		$options = new FormatterOptions();
		$options->setOption(
			InterWikiLinkWikitextFormatter::OPTION_BASE_URL,
			$baseUrl
		);
		$formatter = new InterWikiLinkWikitextFormatter( $options );

		return $formatter;
	}

	/**
	 * @param $link
	 *
	 * @return string
	 */
	private function extractLinkTextFromExternalWikitextLink( $link ) {
		list( , $text ) = explode( ' ', $link, 2 );
		$text = substr( $text, 0, strlen( $text ) - 1 );

		return $text;
	}

	/**
	 * @param $link
	 *
	 * @return string
	 */
	private function extractUrlFromExternalWikitextLink( $link ) {
		list( $url ) = explode( ' ', $link, 2 );
		$url = substr( $url, 1 );

		return $url;
	}

}
