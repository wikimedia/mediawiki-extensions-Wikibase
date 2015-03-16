<?php

namespace Wikibase\Repo\Test;

use PHPUnit_Framework_TestCase;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\WikibaseHtmlSnakFormatterFactory;

/**
 * @covers Wikibase\Repo\WikibaseHtmlSnakFormatterFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPL 2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class WikibaseHtmlSnakFormatterFactoryTest extends PHPUnit_Framework_TestCase {

	public function testGetSnakFormatter() {
		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );
		$languageFallbackChain = new LanguageFallbackChain( array() );
		$labelDescriptionLookup = $this->getMock( 'Wikibase\Lib\Store\LabelDescriptionLookup' );

		$outputFormatSnakFormatterFactory = $this->getMockBuilder( 'Wikibase\Lib\OutputFormatSnakFormatterFactory' )
			->disableOriginalConstructor()
			->getMock();

		$outputFormatSnakFormatterFactory->expects( $this->once() )
			->method( 'getSnakFormatter' )
			->with(
				SnakFormatter::FORMAT_HTML_WIDGET,
				new FormatterOptions( array(
					ValueFormatter::OPT_LANG => 'en',
					FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $languageFallbackChain,
					FormatterLabelDescriptionLookupFactory::OPT_LABEL_DESCRIPTION_LOOKUP => $labelDescriptionLookup
				) )
			)
			->will( $this->returnValue( $snakFormatter ) );

		$factory = new WikibaseHtmlSnakFormatterFactory( $outputFormatSnakFormatterFactory );

		$snakFormatterReturned = $factory->getSnakFormatter(
			'en',
			$languageFallbackChain,
			$labelDescriptionLookup
		);
		$this->assertEquals( $snakFormatter, $snakFormatterReturned );
	}

}
