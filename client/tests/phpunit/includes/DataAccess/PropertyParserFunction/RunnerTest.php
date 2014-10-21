<?php

namespace Wikibase\Client\Tests\DataAccess\PropertyParserFunction;

use Parser;
use ParserOptions;
use ParserOutput;
use Title;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
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
		$itemId = new ItemId( 'Q3' );

		$runner = new Runner(
			$this->getPropertyClaimsRendererFactory(),
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
		$this->assertUsageTracking( $itemId, EntityUsage::OTHER_USAGE, $parser->getOutput() );
	}

	private function assertUsageTracking( ItemId $id, $aspect, ParserOutput $parserOutput ) {
		$usageAcc = new ParserOutputUsageAccumulator( $parserOutput );
		$usages = $usageAcc->getUsages();
		$expected = new EntityUsage( $id, $aspect );

		$usageIdentities = array_map(
			function ( EntityUsage $usage ) {
				return $usage->getIdentityString();
			},
			$usages
		);

		$expectedIdentities = array( $expected->getIdentityString() );

		$this->assertEquals( $expectedIdentities, array_values( $usageIdentities ) );
	}

	private function getSiteLinkLookup( ItemId $itemId ) {
		$siteLinkLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\SiteLinkLookup' )
			->getMock();

		$siteLinkLookup->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->will( $this->returnValue( $itemId ) );

		return $siteLinkLookup;
	}

	private function getPropertyClaimsRendererFactory() {
		$renderer = $this->getRenderer();

		$rendererFactory = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\PropertyClaimsRendererFactory'
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
				'Wikibase\DataAccess\PropertyParserFunction\PropertyClaimsRenderer'
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
