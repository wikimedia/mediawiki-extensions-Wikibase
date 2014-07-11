<?php

namespace Wikibase\DataAccess\Tests;

use Language;
use Parser;
use ParserOptions;
use Title;
use User;
use Wikibase\DataAccess\PropertyParserFunctionHandler;
use Wikibase\DataModel\Entity\ItemId;

class PropertyParserFunctionHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider handlerProvider
	 */
	public function testHandler( $expectedResult, $languageCode, $interfaceMessage,
		$disableContentConversion, $disableTitleConversion, $outputType
	) {
		$parser = $this->getParser(
			$languageCode,
			$interfaceMessage,
			$disableContentConversion,
			$disableTitleConversion,
			$outputType
		);

		$propertyParserFunctionHandler = new PropertyParserFunctionHandler(
			$this->getSiteLinkLookup(),
			$this->getPropertyParserFunctionLanguageRenderer(),
			$this->getPropertyParserFunctionVariantsRenderer(),
			'enwiki'
		);

		$result = $propertyParserFunctionHandler->handle( $parser, 'cat' );
		$expected = array(
			$expectedResult,
			'noparse' => false,
			'nowiki' => false,
		);

		$this->assertEquals( $expected, $result );
	}

	public function handlerProvider() {
		return array(
			array( 'meow!', 'en', false, false, false, Parser::OT_HTML ),
			array( 'meow!', 'ku', false, false, false, Parser::OT_PLAIN ),
			array( 'meow!', 'zh', false, false, false, Parser::OT_WIKI ),
			array( 'meow!', 'zh', false, true, false, Parser::OT_HTML ),
			array( 'meow!', 'zh', true, false, false, Parser::OT_HTML ),
			array( 'meow!', 'zh', false, false, false, Parser::OT_PREPROCESS ),
			array( 'm30w!', 'zh', false, false, true, Parser::OT_HTML ),
			array( 'm30w!', 'ku', false, false, false, Parser::OT_HTML ),
		);
	}

	private function getParser( $languageCode, $interfaceMessage, $disableContentConversion,
		$disableTitleConversion, $outputType
	) {
		$parserConfig = array( 'class' => 'Parser' );

		$parserOptions = $this->getParserOptions(
			$languageCode,
			$interfaceMessage,
			$disableContentConversion,
			$disableTitleConversion
		);

		$parser = new Parser( $parserConfig );

		$parser->setTitle( Title::newFromText( 'Cat' ) );
		$parser->startExternalParse( null, $parserOptions, $outputType );

		return $parser;
	}

	private function getParserOptions( $languageCode, $interfaceMessage, $disableContentConversion,
		$disableTitleConversion
	) {
		$language = Language::factory( $languageCode );

		$parserOptions = new ParserOptions( User::newFromId( 0 ), $languageCode );
		$parserOptions->setTargetLanguage( $language );
		$parserOptions->setInterfaceMessage( $interfaceMessage );
		$parserOptions->disableContentConversion( $disableContentConversion );
		$parserOptions->disableTitleConversion( $disableTitleConversion );

		return $parserOptions;
	}

	private function getSiteLinkLookup() {
		$siteLinkLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\SiteLinkLookup' )
			->getMock();

		$siteLinkLookup->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->will( $this->returnValue( new ItemId( 'Q3' ) ) );

		return $siteLinkLookup;
	}

	private function getPropertyParserFunctionLanguageRenderer() {
		$propertyParserFunctionLanguageRenderer = $this->getMockBuilder(
				'\Wikibase\DataAccess\PropertyParserFunctionLanguageRenderer'
			)
			->disableOriginalConstructor()
			->getMock();

		$propertyParserFunctionLanguageRenderer->expects( $this->any() )
			->method( 'render' )
			->will( $this->returnValue( 'meow!' ) );

		return $propertyParserFunctionLanguageRenderer;
	}

	private function getPropertyParserFunctionVariantsRenderer() {
		$propertyParserFunctionVariantsRenderer = $this->getMockBuilder(
				'\Wikibase\DataAccess\PropertyParserFunctionVariantsRenderer'
			)
			->disableOriginalConstructor()
			->getMock();

		$propertyParserFunctionVariantsRenderer->expects( $this->any() )
			->method( 'render' )
			->will( $this->returnValue( 'm30w!' ) );

		return $propertyParserFunctionVariantsRenderer;
	}

}
