<?php

namespace Wikibase\DataAccess\Tests;

use Parser;
use Title;
use Wikibase\DataAccess\PropertyParserFunctionHandler;
use Wikibase\DataModel\Entity\ItemId;

class PropertyParserFunctionHandlerTest extends \PHPUnit_Framework_TestCase {

	public function testHandler() {
		$propertyParserFunctionHandler = new PropertyParserFunctionHandler(
			$this->getSiteLinkLookup(),
			$this->getPropertyParserFunctionRendererFactory(),
			'enwiki'
		);

		$parser = $this->getParser();
		$result = $propertyParserFunctionHandler->handle( $parser, 'cat' );

		$expected = array(
			'meow!',
			'noparse' => false,
			'nowiki' => false,
		);

		$this->assertEquals( $expected, $result );
	}

	private function getParser() {
		$parserConfig = array( 'class' => 'Parser' );
		$parser = new Parser( $parserConfig );

		$parser->setTitle( Title::newFromText( 'Cat' ) );

		return $parser;
	}

	private function getSiteLinkLookup() {
		$siteLinkLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\SiteLinkLookup' )
			->getMock();

		$siteLinkLookup->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->will( $this->returnValue( new ItemId( 'Q3' ) ) );

		return $siteLinkLookup;
	}

	private function getPropertyParserFunctionRendererFactory() {
		$propertyParserFunctionLanguageRenderer = $this->getMockBuilder(
				'\Wikibase\DataAccess\PropertyParserFunctionLanguageRenderer'
			)
			->disableOriginalConstructor()
			->getMock();

		$propertyParserFunctionLanguageRenderer->expects( $this->any() )
			->method( 'render' )
			->will( $this->returnValue( 'meow!' ) );

		$propertyParserFunctionRendererFactory = $this->getMockBuilder(
				'\Wikibase\DataAccess\PropertyParserFunctionRendererFactory'
			)
			->disableOriginalConstructor()
			->getMock();

		$propertyParserFunctionRendererFactory->expects( $this->any() )
			->method( 'newFromParser' )
			->will( $this->returnValue( $propertyParserFunctionLanguageRenderer ) );

		return $propertyParserFunctionRendererFactory;
	}

}
