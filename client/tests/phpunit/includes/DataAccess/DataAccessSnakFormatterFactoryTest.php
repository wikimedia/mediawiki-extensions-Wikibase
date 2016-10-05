<?php

namespace Wikibase\Client\Tests\DataAccess;

use Language;
use PHPUnit_Framework_TestCase;
use ValueFormatters\FormatterOptions;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class DataAccessSnakFormatterFactoryTest extends PHPUnit_Framework_TestCase {

	private function getDataAccessSnakFormatterFactory() {
		return new DataAccessSnakFormatterFactory(
			$this->getLanguageFallbackChainFactory(),
			$this->getOutputFormatSnakFormatterFactory()
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
	private function getOutputFormatSnakFormatterFactory() {
		$factory = $this->getMockBuilder( OutputFormatSnakFormatterFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$factory->expects( $this->once() )
			->method( 'getSnakFormatter' )
			->with( SnakFormatter::FORMAT_WIKI, $this->isInstanceOf( FormatterOptions::class ) )
			->will( $this->returnValue( $this->getMock( SnakFormatter::class ) ) );

		return $factory;
	}

	public function testNewSnakFormatterForLanguage() {
		$factory = $this->getDataAccessSnakFormatterFactory();
		$snakFormatter = $factory->newSnakFormatterForLanguage(
			Language::factory( 'fr' ),
			$this->getMock( UsageAccumulator::class )
		);

		$this->assertInstanceOf( SnakFormatter::class, $snakFormatter );
	}

}
