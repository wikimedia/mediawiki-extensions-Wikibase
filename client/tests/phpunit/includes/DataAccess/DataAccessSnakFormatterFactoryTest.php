<?php

namespace Wikibase\Client\Tests\DataAccess;

use Language;
use PHPUnit_Framework_TestCase;
use ValueFormatters\FormatterOptions;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory
 *
 * @note We also have integration tests for this at DataAccessSnakFormatterOutputFormatTest.
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class DataAccessSnakFormatterFactoryTest extends PHPUnit_Framework_TestCase {

	private function getDataAccessSnakFormatterFactory( $expectedFormat ) {
		return new DataAccessSnakFormatterFactory(
			$this->getLanguageFallbackChainFactory(),
			$this->getOutputFormatSnakFormatterFactory( $expectedFormat ),
			$this->getMock( PropertyDataTypeLookup::class )
		);
	}

	/**
	 * @return LanguageFallbackChainFactory
	 */
	private function getLanguageFallbackChainFactory() {
		$realFactory = new LanguageFallbackChainFactory();

		$factory = $this->getMock( LanguageFallbackChainFactory::class );

		$factory->expects( $this->once() )
			->method( 'newFromLanguage' )
			->with( $this->isInstanceOf( Language::class ), LanguageFallbackChainFactory::FALLBACK_ALL )
			->will( $this->returnCallback( [ $realFactory, 'newFromLanguage' ] ) );

		return $factory;
	}

	/**
	 * @return OutputFormatSnakFormatterFactory
	 */
	private function getOutputFormatSnakFormatterFactory( $expectedFormat ) {
		$factory = $this->getMockBuilder( OutputFormatSnakFormatterFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$snakFormatter = $this->getMock( SnakFormatter::class );
		$snakFormatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( $expectedFormat ) );

		$factory->expects( $this->once() )
			->method( 'getSnakFormatter' )
			->with( $expectedFormat, $this->isInstanceOf( FormatterOptions::class ) )
			->will( $this->returnValue( $snakFormatter ) );

		return $factory;
	}

	public function testNewEscapedPlainTextSnakFormatter() {
		$factory = $this->getDataAccessSnakFormatterFactory( SnakFormatter::FORMAT_PLAIN );
		$snakFormatter = $factory->newEscapedPlainTextSnakFormatter(
			Language::factory( 'fr' ),
			$this->getMock( UsageAccumulator::class )
		);

		$this->assertInstanceOf( SnakFormatter::class, $snakFormatter );
		$this->assertSame( SnakFormatter::FORMAT_PLAIN, $snakFormatter->getFormat() );
	}

	public function testNewRichWikitextSnakFormatter() {
		$factory = $this->getDataAccessSnakFormatterFactory( SnakFormatter::FORMAT_WIKI );
		$snakFormatter = $factory->newRichWikitextSnakFormatter(
			Language::factory( 'fr' ),
			$this->getMock( UsageAccumulator::class )
		);

		$this->assertInstanceOf( SnakFormatter::class, $snakFormatter );
		$this->assertSame( SnakFormatter::FORMAT_WIKI, $snakFormatter->getFormat() );
	}

}
