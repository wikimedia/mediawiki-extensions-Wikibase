<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use MediaTransformOutput;
use MediaWiki\FileRepo\File\File;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOptions;
use MediaWikiIntegrationTestCase;
use RepoGroup;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\Formatters\CommonsInlineImageFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\CommonsInlineImageFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Marius Hoch
 */
class CommonsInlineImageFormatterTest extends MediaWikiIntegrationTestCase {

	public static function commonsInlineImageFormatterProvider() {
		$fileUrl = '.*//upload\.wikimedia\.org/wikipedia/commons/.*/120px-Example\.jpg';
		$pageUrl = 'https://commons\.wikimedia\.org/wiki/File:Example\.jpg';
		$exampleJpgHtmlRegex = '@<div .*<a[^>]+href="' . $pageUrl . '"[^>]*>' .
				'<img.*src="' . $fileUrl . '".*/></a></div>.*' .
				'<div .*><a[^>]+href="' . $pageUrl . '"[^>]*>Example\.jpg</a>.*\d+.*</div>@s';

		return [
			[
				new StringValue( 'example.jpg' ), // Lower-case file name
				$exampleJpgHtmlRegex,
			],
			[
				new StringValue( 'Example.jpg' ),
				$exampleJpgHtmlRegex,
			],
			[
				new StringValue( 'Example-That-Does-Not-Exist.jpg' ),
				'@^.*<a[^>]+href="https://commons.wikimedia.org/wiki/File:Example-That-Does-Not-Exist.jpg"[^>]*>@s',
			],
			[
				new StringValue( 'Dangerous-quotes""' ),
				'@/""/@s',
				false,
			],
			[
				new StringValue( '<eviltag>' ),
				'@/<eviltag>/@s',
				false,
			],
		];
	}

	/**
	 * @dataProvider commonsInlineImageFormatterProvider
	 */
	public function testFormat( StringValue $value, $pattern, $shouldContain = true ) {
		if ( $shouldContain &&
			!MediaWikiServices::getInstance()->getRepoGroup()->findFile( 'Example.jpg' )
		) {
			$this->markTestSkipped( '"Example.jpg" not found? Instant commons disabled?' );
		}

		$formatter = $this->newSubjectInstance();

		$html = $formatter->format( $value );
		if ( $shouldContain ) {
			$this->assertMatchesRegularExpression( $pattern, $html );
		} else {
			$this->assertDoesNotMatchRegularExpression( $pattern, $html );
		}
	}

	public function testFormatError() {
		$formatter = $this->newSubjectInstance();
		$value = new NumberValue( 23 );

		$this->expectException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

	public function testFormat_whenThumbsizeNotAvailable_usesFallback() {
		$formatter = $this->newSubjectInstance( 1, [ 0 => 120 ] );
		$html = $formatter->format( new StringValue( 'example.jpg' ) );

		// fallback to using CommonsInLineImageFormatter::FALLBACK_THUMBNAIL_WIDTH
		$this->assertMatchesRegularExpression( '/320px-Example\.jpg/', $html );
	}

	public function testFormat_unsafe_getDimensionsString(): void {
		$file = $this->createConfiguredMock( File::class, [
			'transform' => $this->createMock( MediaTransformOutput::class ),
			'getDimensionsString' => '<script>alert("T389369")</script>',
			'getSize' => 0,
		] );
		$repoGroup = $this->createConfiguredMock( RepoGroup::class, [
			'findFile' => $file,
		] );

		$formatter = new CommonsInlineImageFormatter(
			ParserOptions::newFromAnon(),
			[ 120 ],
			$this->getServiceContainer()->getLanguageFactory(),
			$this->newFormatterOptions(),
			$repoGroup
		);
		$html = $formatter->format( new StringValue( 'Example.jpg' ) );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( 'T389369', $html );
	}

	private function newSubjectInstance(
		$thumbSize = 0,
		$thumbLimits = [ 120 ]
	): CommonsInlineImageFormatter {
		if ( !MediaWikiServices::getInstance()->getRepoGroup()->findFile( 'Example.jpg' ) ) {
			$this->markTestSkipped( '"Example.jpg" not found? Instant commons disabled?' );
		}

		$parserOptions = ParserOptions::newFromAnon();
		$parserOptions->setThumbSize( $thumbSize );

		return new CommonsInlineImageFormatter(
			$parserOptions,
			$thumbLimits,
			$this->getServiceContainer()->getLanguageFactory(),
			$this->newFormatterOptions()
		);
	}

	private function newFormatterOptions() {
		$options = [
			ValueFormatter::OPT_LANG => 'en',
		];

		return new FormatterOptions( $options );
	}

}
