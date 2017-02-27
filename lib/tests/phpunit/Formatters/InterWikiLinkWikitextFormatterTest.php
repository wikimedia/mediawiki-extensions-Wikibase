<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
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

		$this->assertSame(
			'[//site.org/wiki/Namespace:Title Namespace:Title]',
			$link
		);
	}

	public function testTitleContainsSpaces_ReplacesSpacesWithUnderscoresWhenFormats() {
		$formatter = $this->createFormatter( '//site.org/wiki/' );

		$link = $formatter->format( new StringValue( 'Namespace:Title with spaces' ) );

		$this->assertSame(
			'[//site.org/wiki/Namespace:Title_with_spaces Namespace:Title with spaces]',
			$link
		);
	}

	public function testTitleDoubleSingleQuotes_EncodesThemWhenFormats() {
		$formatter = $this->createFormatter( '//base.url/' );

		$link = $formatter->format( new StringValue( "Namespace:Title ''with'' quotes" ) );

		$this->assertSame(
			'[//base.url/Namespace:Title_%27%27with%27%27_quotes '
				. 'Namespace:Title &#39;&#39;with&#39;&#39; quotes]',
			$link
		);
	}

	public function testBasePathContainsSpace_EncodesSpaceWhenFormats() {
		$formatter = $this->createFormatter( '//base.url/some wiki/' );

		$link = $formatter->format( new StringValue( 'Namespace:Title' ) );

		$this->assertStringStartsWith(
			'[//base.url/some+wiki/',
			$link
		);
	}

	public function testBasePathContainsPercent_EncodesPercentWhenFormats() {
		$formatter = $this->createFormatter( '//base.url/100%wiki/' );

		$link = $formatter->format( new StringValue( 'Namespace:Title' ) );

		$this->assertStringStartsWith(
			'[//base.url/100%25wiki/',
			$link
		);
	}

	/**
	 * @param string $baseUrl
	 *
	 * @return InterWikiLinkWikitextFormatter
	 */
	private function createFormatter( $baseUrl ) {
		$options = new FormatterOptions();
		$options->setOption( InterWikiLinkWikitextFormatter::OPTION_BASE_URL, $baseUrl );

		return new InterWikiLinkWikitextFormatter( $options );
	}

}
