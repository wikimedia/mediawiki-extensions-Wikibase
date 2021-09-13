<?php

namespace Wikibase\Client\Tests\Integration\DataAccess\Scribunto;

use DataValues\StringValue;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Parser;
use ParserOptions;
use Wikibase\Client\DataAccess\Scribunto\WikitextPreprocessingSnakFormatter;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * @covers \Wikibase\Client\DataAccess\Scribunto\WikitextPreprocessingSnakFormatter
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class WikitextPreprocessingSnakFormatterTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param Snak $expectedSnak
	 * @param string|null $formattedValue
	 *
	 * @return SnakFormatter
	 */
	private function newMockSnakFormatter( Snak $expectedSnak = null, $formattedValue = null ): SnakFormatter {
		$mockFormatter = $this->createMock( SnakFormatter::class );

		$mockFormatter->expects( $expectedSnak ? $this->once() : $this->never() )
			->method( 'formatSnak' )
			->with( $expectedSnak )
			->willReturn( $formattedValue );
		$mockFormatter->expects( $this->atLeast( 1 ) )
			->method( 'getFormat' )
			->willReturn( SnakFormatter::FORMAT_WIKI );

		return $mockFormatter;
	}

	public function testConstructor_wrongFormat() {
		$mockFormatter = $this->createMock( SnakFormatter::class );
		$mockFormatter->expects( $this->once() )
			->method( 'getFormat' )
			->willReturn( SnakFormatter::FORMAT_PLAIN );

		$this->expectException( InvalidArgumentException::class );
		new WikitextPreprocessingSnakFormatter(
			$mockFormatter,
			MediaWikiServices::getInstance()->getParserFactory()->create()
		);
	}

	public function testFormatSnak() {
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$parser->setHook(
			'stripme',
			function() {
				return '!STRIPME_WIKITEXT!';
			}
		);
		$parser->startExternalParse(
			null,
			ParserOptions::newFromAnon(),
			Parser::OT_HTML,
			true
		);

		$snak = new PropertyValueSnak(
			new NumericPropertyId( 'P42' ),
			new StringValue( 'blah' )
		);

		$formatter = new WikitextPreprocessingSnakFormatter(
			$this->newMockSnakFormatter( $snak, '<stripme>blah</stripme>' ),
			$parser
		);

		$formattedSnak = $formatter->formatSnak( $snak );
		// Make sure that the parser stripped <stripme> (looks like UNIQ--stripme-00000000-QINU)
		$this->assertStringContainsString( 'UNIQ-', $formattedSnak );
		$this->assertStringContainsString( '-stripme-', $formattedSnak );
		$this->assertStringNotContainsString( '<stripme>', $formattedSnak );
	}

	public function testFormatSnak_onlyPreprocessed() {
		$wikitext = '[[A|B]][[Image:A.jpg]]<thistagdoesnotexist>C</thistagdoesnotexist>';
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$parser->startExternalParse(
			null,
			ParserOptions::newFromAnon(),
			Parser::OT_HTML,
			true
		);

		$snak = new PropertyValueSnak(
			new NumericPropertyId( 'P42' ),
			new StringValue( 'blah' )
		);

		$formatter = new WikitextPreprocessingSnakFormatter(
			$this->newMockSnakFormatter( $snak, $wikitext ),
			$parser
		);

		$formattedSnak = $formatter->formatSnak( $snak );
		// Preprocessing should have left the wikitext as is
		$this->assertEquals( $wikitext, $formattedSnak );
	}

	public function testGetFormat() {
		$formatter = new WikitextPreprocessingSnakFormatter(
			$this->newMockSnakFormatter(),
			MediaWikiServices::getInstance()->getParserFactory()->create()
		);

		$this->assertEquals(
			SnakFormatter::FORMAT_WIKI,
			$formatter->getFormat(),
			'getFormat'
		);
	}

}
