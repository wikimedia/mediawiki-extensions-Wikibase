<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use Wikibase\Lib\Formatters\InterWikiLinkWikitextFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\InterWikiLinkWikitextFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class InterWikiLinkWikitextFormatterTest extends \PHPUnit\Framework\TestCase {

	public function testSomeTitleGiven_FormatsItAsAnExternalLink() {
		$formatter = new InterWikiLinkWikitextFormatter( '//site.org/wiki/' );

		$link = $formatter->format( new StringValue( 'Namespace:Title' ) );

		$this->assertSame(
			'[//site.org/wiki/Namespace:Title Namespace:Title]',
			$link
		);
	}

	public function testTitleContainsSpaces_ReplacesSpacesWithUnderscoresWhenFormats() {
		$formatter = new InterWikiLinkWikitextFormatter( '//site.org/wiki/' );

		$link = $formatter->format( new StringValue( 'Namespace:Title with spaces' ) );

		$this->assertSame(
			'[//site.org/wiki/Namespace:Title_with_spaces Namespace:Title with spaces]',
			$link
		);
	}

	public function testTitleDoubleSingleQuotes_EncodesThemWhenFormats() {
		$formatter = new InterWikiLinkWikitextFormatter( '//base.url/' );

		$link = $formatter->format( new StringValue( "Namespace:Title ''with'' quotes" ) );

		$this->assertSame(
			'[//base.url/Namespace:Title_%27%27with%27%27_quotes '
				. 'Namespace:Title &#39;&#39;with&#39;&#39; quotes]',
			$link
		);
	}

	public function testBasePathContainsSpace_EncodesSpaceWhenFormats() {
		$formatter = new InterWikiLinkWikitextFormatter( '//base.url/some wiki/' );

		$link = $formatter->format( new StringValue( 'Namespace:Title' ) );

		$this->assertStringStartsWith(
			'[//base.url/some+wiki/',
			$link
		);
	}

	public function testBasePathContainsPercent_EncodesPercentWhenFormats() {
		$formatter = new InterWikiLinkWikitextFormatter( '//base.url/100%wiki/' );

		$link = $formatter->format( new StringValue( 'Namespace:Title' ) );

		$this->assertStringStartsWith(
			'[//base.url/100%25wiki/',
			$link
		);
	}

}
