<?php

namespace Wikibase\DataAccess\Tests\PropertyParserFunction;

use Parser;
use Title;
use Wikibase\DataAccess\PropertyParserFunction\Runner;
use Wikibase\DataModel\Entity\ItemId;

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

	public function testRunPropertyParserFunction() {
		$runner = new Runner(
			$this->getRendererFactory(),
			$this->getSiteLinkLookup(),
			'enwiki'
		);

		$parser = $this->getParser();
		$result = $runner->runPropertyParserFunction( $parser, 'Cat' );

		$expected = array(
			'meow!',
			'noparse' => false,
			'nowiki' => false
		);

		$this->assertEquals( $expected, $result );
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
		$renderer = $this->getRenderer();

		$rendererFactory = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\RendererFactory'
			)
			->disableOriginalConstructor()
			->getMock();

		$rendererFactory->expects( $this->any() )
			->method( 'newRendererFromParser' )
			->will( $this->returnValue( $renderer ) );

		return $rendererFactory;
	}

	private function getRenderer() {
		$renderer = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\Renderer'
			)
			->disableOriginalConstructor()
			->getMock();

		$renderer->expects( $this->any() )
			->method( 'render' )
			->will( $this->returnValue( 'meow!' ) );

		return $renderer;
	}

	private function getParser() {
		$parserConfig = array( 'class' => 'Parser' );

		$parser = new Parser( $parserConfig );
		$parser->setTitle( Title::newFromText( 'Cat' ) );

		return $parser;
	}

}
