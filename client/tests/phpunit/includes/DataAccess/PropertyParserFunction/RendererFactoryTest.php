<?php

namespace Wikibase\DataAccess\Tests;

use Language;
use Parser;
use ParserOptions;
use Title;
use User;
use Wikibase\DataAccess\PropertyParserFunction\RendererFactory;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunction\RendererFactory
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyParserFunctionRendererFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testNewFromLanguage() {
		$rendererFactory = new RendererFactory(
			$this->getEntityLookup(),
			$this->getPropertyLabelResolver(),
			$this->getLanguageFallbackChainFactory(),
			$this->getSnakFormatterFactory()
		);

		$language = Language::factory( 'he' );
		$renderer = $rendererFactory->newFromLanguage( $language );

		$expectedClass = 'Wikibase\DataAccess\PropertyParserFunction\Renderer';
		$this->assertInstanceOf( $expectedClass, $renderer );
	}

	private function getEntityLookup() {
		$entityLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\EntityLookup' )
			->disableOriginalConstructor()
			->getMock();

		return $entityLookup;
	}

	private function getPropertyLabelResolver() {
		$propertyLabelResolver = $this->getMockBuilder( 'Wikibase\PropertyLabelResolver' )
			->disableOriginalConstructor()
			->getMock();

		return $propertyLabelResolver;
	}

	private function getLanguageFallbackChainFactory() {
		$languageFallbackChainFactory = $this->getMockBuilder(
				'Wikibase\LanguageFallbackChainFactory'
			)
			->disableOriginalConstructor()
			->getMock();

		return $languageFallbackChainFactory;
	}

	private function getSnakFormatterFactory() {
		$snakFormatter = $this->getMockBuilder( 'Wikibase\Lib\SnakFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$snakFormatterFactory = $this->getMockBuilder(
				'Wikibase\Lib\OutputFormatSnakFormatterFactory'
			)
			->disableOriginalConstructor()
			->getMock();

		$snakFormatterFactory->expects( $this->any() )
			->method( 'getSnakFormatter' )
			->will( $this->returnValue( $snakFormatter ) );

		return $snakFormatterFactory;
	}

}
