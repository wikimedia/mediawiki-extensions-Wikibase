<?php

namespace Wikibase\DataAccess\Tests\PropertyParserFunction;

use Parser;
use ParserOptions;
use ParserOutput;
use Title;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataAccess\PropertyParserFunction\PropertyParserFunctionRenderer;
use Wikibase\DataAccess\PropertyParserFunction\PropertyParserFunctionRunner;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunction\PropertyParserFunctionRunner
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyParserFunctionRunnerTest extends \PHPUnit_Framework_TestCase {

	public function testRunPropertyParserFunction() {
		$itemId = new ItemId( 'Q3' );

		$runner = new PropertyParserFunctionRunner(
			$this->getPropertyParserFunctionRendererFactory(),
			$this->getSiteLinkLookup( $itemId ),
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
		$this->assertUsageTracking( $itemId, EntityUsage::ALL_USAGE, $parser->getOutput() );
	}

	private function assertUsageTracking( ItemId $id, $aspect, ParserOutput $parserOutput ) {
		$usageAcc = new ParserOutputUsageAccumulator( $parserOutput );
		$usage = $usageAcc->getUsages();
		$expected = new EntityUsage( $id, $aspect );

		$this->assertContains( $expected, $usage, '', false, false );
	}

	private function getSiteLinkLookup( ItemId $itemId ) {
		$siteLinkLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\SiteLinkLookup' )
			->getMock();

		$siteLinkLookup->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->will( $this->returnValue( $itemId ) );

		return $siteLinkLookup;
	}

	private function getPropertyParserFunctionRendererFactory() {
		$renderer = $this->getRenderer();

		$rendererFactory = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\PropertyParserFunctionRendererFactory'
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
				'Wikibase\DataAccess\PropertyParserFunction\PropertyParserFunctionRenderer'
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
		$title = Title::newFromText( 'Cat' );
		$popt = new ParserOptions();

		$parser = new Parser( $parserConfig );
		$parser->startExternalParse( $title, $popt, Parser::OT_HTML );

		return $parser;
	}

}
