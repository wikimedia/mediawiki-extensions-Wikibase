<?php

namespace Wikibase\DataAccess\Tests\PropertyParserFunction;

use Language;
use Parser;
use ParserOptions;
use Status;
use Title;
use User;
use Wikibase\DataAccess\PropertyParserFunction\Renderer;
use Wikibase\DataAccess\PropertyParserFunction\Runner;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Test\MockPropertyLabelResolver;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunction\Runner
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RunnerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return Runner
	 */
	private function getRunner() {
		return new Runner(
			$this->getRendererFactory(),
			$this->getSiteLinkLookup(),
			'enwiki'
		);
	}

	/**
	 * @dataProvider runPropertyParserFunctionProvider
	 */
	public function testRunPropertyParserFunction(
		$expectedRendered,
		$languageCode,
		$interfaceMessage,
		$disableContentConversion,
		$disableTitleConversion,
		$outputType
	) {
		$parser = $this->getParser( $languageCode, $interfaceMessage, $disableContentConversion,
			$disableTitleConversion, $outputType );

		$runner = $this->getRunner();
		$result = $runner->runPropertyParserFunction( $parser, 'gato' );

		$expected = array(
			$expectedRendered,
			'noparse' => false,
			'nowiki' => false
		);

		$this->assertEquals( $expected, $result );
	}

	public function runPropertyParserFunctionProvider() {
		return array(
			array( 'meow!', 'en', false, false, false, Parser::OT_HTML ),
			array( 'meow!', 'ku', false, false, false, Parser::OT_PLAIN ),
			array( 'meow!', 'zh', false, false, false, Parser::OT_WIKI ),
			array( 'meow!', 'zh', false, true, false, Parser::OT_HTML ),
			array( 'meow!', 'zh', true, false, false, Parser::OT_HTML ),
			array( 'meow!', 'zh', false, false, false, Parser::OT_PREPROCESS ),
			array( 'm30w!', 'zh', false, false, true, Parser::OT_HTML ),
			array( 'm30w!', 'ku', false, false, false, Parser::OT_HTML )
		);
	}

	private function getSiteLinkLookup() {
		$siteLinkLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\SiteLinkLookup' )
			->getMock();

		$siteLinkLookup->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->will( $this->returnValue( new ItemId( 'Q3' ) ) );

		return $siteLinkLookup;
	}

	private function getRendererFactory() {
		$languageRenderer = $this->getLanguageRenderer();
		$variantsRenderer = $this->getVariantsRenderer();

		$rendererFactory = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\RendererFactory'
			)
			->disableOriginalConstructor()
			->getMock();

		$rendererFactory->expects( $this->any() )
			->method( 'newFromLanguage' )
			->will( $this->returnValue( $languageRenderer ) );

		$rendererFactory->expects( $this->any() )
			->method( 'newVariantsRenderer' )
			->will( $this->returnValue( $variantsRenderer ) );

		return $rendererFactory;
	}

	private function getLanguageRenderer() {
		$languageRenderer = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\LanguageRenderer'
			)
			->disableOriginalConstructor()
			->getMock();

		$languageRenderer->expects( $this->any() )
			->method( 'render' )
			->will( $this->returnValue( 'meow!' ) );

		return $languageRenderer;
	}

	private function getVariantsRenderer() {
		$variantsRenderer = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\VariantsRenderer'
			)
			->disableOriginalConstructor()
			->getMock();

		$variantsRenderer->expects( $this->any() )
			->method( 'render' )
			->will( $this->returnValue( 'm30w!' ) );

		return $variantsRenderer;
	}

	private function getParser( $languageCode, $interfaceMessage, $disableContentConversion,
		$disableTitleConversion, $outputType
	) {
		$parserConfig = array( 'class' => 'Parser' );

		$parser = new Parser( $parserConfig );
		$parser->setTitle( Title::newFromText( 'Cat' ) );

		$language = Language::factory( $languageCode );

		$parserOptions = new ParserOptions( User::newFromId( 0 ), $languageCode );
		$parserOptions->setTargetLanguage( $language );
		$parserOptions->setInterfaceMessage( $interfaceMessage );
		$parserOptions->disableContentConversion( $disableContentConversion );
		$parserOptions->disableTitleConversion( $disableTitleConversion );

		$parser->startExternalParse( null, $parserOptions, $outputType );

		return $parser;
	}

}
