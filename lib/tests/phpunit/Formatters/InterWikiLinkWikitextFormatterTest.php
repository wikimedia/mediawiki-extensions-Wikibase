<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use Wikibase\Lib\Formatters\InterWikiLinkWikitextFormatter;

/**
 * @covers Wikibase\Lib\Formatters\InterWikiLinkWikitextFormatter
 *
 * @group Wikibase
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

	public function testTitleDoubleSingleQuotes_HtmlencodesThemInTitleWhenFormats() {
		$formatter = $this->createFormatter( '//base.url/' );

		$link = $formatter->format( new StringValue( "Namespace:Title ''with'' quotes" ) );

		$text = $this->extractLinkTextFromExternalWikitextLink( $link );
		self::assertEquals(
			'Namespace:Title &#39;&#39;with&#39;&#39; quotes',
			$text
		);
	}

	public function testBasePathContainsSpace_EncodesSpaceWhenFormats() {
		$formatter = $this->createFormatter( '//base.url/some wiki/' );

		$link = $formatter->format( new StringValue( 'Namespace:Title' ) );

		$text = $this->extractUrlFromExternalWikitextLink( $link );
		self::assertEquals(
			'//base.url/some+wiki/Namespace:Title',
			$text
		);
	}

	public function testBasePathContainsPercent_EncodesPercentWhenFormats() {
		$formatter = $this->createFormatter( '//base.url/100%wiki/' );

		$link = $formatter->format( new StringValue( 'Namespace:Title' ) );

		$text = $this->extractUrlFromExternalWikitextLink( $link );
		self::assertEquals(
			'//base.url/100%25wiki/Namespace:Title',
			$text
		);
	}

	/**
	 * @return InterWikiLinkWikitextFormatter
	 */
	private function createFormatter( $baseUrl ) {
		return new InterWikiLinkWikitextFormatter( $baseUrl );
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
