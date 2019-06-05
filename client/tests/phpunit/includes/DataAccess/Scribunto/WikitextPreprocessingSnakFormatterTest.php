<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use DataValues\StringValue;
use InvalidArgumentException;
use MediaWikiTestCase;
use Parser;
use ParserOptions;
use Wikibase\Client\DataAccess\Scribunto\WikitextPreprocessingSnakFormatter;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;

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
class WikitextPreprocessingSnakFormatterTest extends MediaWikiTestCase {

	/**
	 * @param Snak $expectedSnak
	 * @param string|null $formattedValue
	 *
	 * @return SnakFormatter
	 */
	private function newMockSnakFormatter( Snak $expectedSnak = null, $formattedValue = null ): SnakFormatter {
		$mockFormatter = $this->getMock( SnakFormatter::class );

		$mockFormatter->expects( $expectedSnak ? $this->once() : $this->never() )
			->method( 'formatSnak' )
			->with( $expectedSnak )
			->will( $this->returnValue( $formattedValue ) );
		$mockFormatter->expects( $this->atLeast( 1 ) )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_WIKI ) );

		return $mockFormatter;
	}

	public function testConstructor_wrongFormat() {
		$mockFormatter = $this->getMock( SnakFormatter::class );
		$mockFormatter->expects( $this->once() )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_PLAIN ) );

		$this->setExpectedException( InvalidArgumentException::class );
		new WikitextPreprocessingSnakFormatter(
			$mockFormatter,
			new Parser( [] )
		);
	}

	public function testFormatSnak() {
		$parser = new Parser( [] );
		$parser->setHook(
			'stripme',
			function() {
				return '!STRIPME_WIKITEXT!';
			}
		);
		$parser->startExternalParse(
			null,
			new ParserOptions(),
			Parser::OT_HTML,
			true
		);

		$snak = new PropertyValueSnak(
			new PropertyId( 'P42' ),
			new StringValue( 'blah' )
		);

		$formatter = new WikitextPreprocessingSnakFormatter(
			$this->newMockSnakFormatter( $snak, '<stripme>blah</stripme>' ),
			$parser
		);

		$formattedSnak = $formatter->formatSnak( $snak );
		// Make sure that the parser stripped <stripme> (looks like UNIQ--stripme-00000000-QINU)
		$this->assertContains( 'UNIQ-', $formattedSnak );
		$this->assertContains( '-stripme-', $formattedSnak );
		$this->assertNotContains( '<stripme>', $formattedSnak );
	}

	public function testFormatSnak_onlyPreprocessed() {
		$wikitext = '[[A|B]][[Image:A.jpg]]<thistagdoesnotexist>C</thistagdoesnotexist>';
		$parser = new Parser( [] );
		$parser->startExternalParse(
			null,
			new ParserOptions(),
			Parser::OT_HTML,
			true
		);

		$snak = new PropertyValueSnak(
			new PropertyId( 'P42' ),
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
			new Parser( [] )
		);

		$this->assertEquals(
			SnakFormatter::FORMAT_WIKI,
			$formatter->getFormat(),
			'getFormat'
		);
	}

}
