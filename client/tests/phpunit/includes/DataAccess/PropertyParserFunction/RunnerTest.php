<?php

namespace Wikibase\Client\Tests\DataAccess\PropertyParserFunction;

use Parser;
use ParserOptions;
use ParserOutput;
use PHPUnit_Framework_TestCase;
use PPFrame_Hash;
use Preprocessor_Hash;
use Title;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\DataAccess\PropertyParserFunction\Runner;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
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
 * @author Marius Hoch < hoo@online.de >
 */
class RunnerTest extends PHPUnit_Framework_TestCase {

	public function testRunPropertyParserFunction() {
		$itemId = new ItemId( 'Q3' );

		$runner = new Runner(
			$this->getPropertyClaimsRendererFactory( $itemId, 'Cat' ),
			$this->getSiteLinkLookup( $itemId ),
			new BasicEntityIdParser(),
			'enwiki',
			true
		);

		$parser = $this->getParser();
		$frame = new PPFrame_Hash( new Preprocessor_Hash( $parser ) );
		$result = $runner->runPropertyParserFunction( $parser, $frame, array( 'Cat' ) );

		$expected = array(
			'meow!',
			'noparse' => false,
			'nowiki' => false
		);

		$this->assertEquals( $expected, $result );
		$this->assertUsageTracking( $itemId, EntityUsage::OTHER_USAGE, $parser->getOutput() );
	}

	public function testRunPropertyParserFunction_arbitraryAccess() {
		$itemId = new ItemId( 'Q42' );

		$runner = new Runner(
			$this->getPropertyClaimsRendererFactory( $itemId, 'Cat' ),
			$this->getMock( 'Wikibase\Lib\Store\SiteLinkLookup' ),
			new BasicEntityIdParser(),
			'enwiki',
			true
		);

		$parser = $this->getParser();
		$frame = $this->getFromFrame( $itemId->getSerialization() );

		$result = $runner->runPropertyParserFunction( $parser, $frame, array( 'Cat', $this->getMock( 'PPNode' ) ) );

		$expected = array(
			'meow!',
			'noparse' => false,
			'nowiki' => false
		);

		$this->assertEquals( $expected, $result );
		$this->assertUsageTracking( $itemId, EntityUsage::OTHER_USAGE, $parser->getOutput() );
	}

	public function testRunPropertyParserFunction_arbitraryAccessNotFound() {
		$rendererFactory = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\PropertyClaimsRendererFactory'
			)
			->disableOriginalConstructor()
			->getMock();

		$runner = new Runner(
			$rendererFactory,
			$this->getMock( 'Wikibase\Lib\Store\SiteLinkLookup' ),
			new BasicEntityIdParser(),
			'enwiki',
			true
		);

		$parser = $this->getParser();
		$frame = $this->getFromFrame( 'ThisIsNotQuiteAnEntityId' );

		$result = $runner->runPropertyParserFunction( $parser, $frame, array( 'Cat', $this->getMock( 'PPNode' ) ) );

		$expected = array(
			'',
			'noparse' => false,
			'nowiki' => false
		);

		$this->assertEquals( $expected, $result );
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

		$siteLinkLookup->expects( $this->once() )
			->method( 'getItemIdForSiteLink' )
			->will( $this->returnValue( $itemId ) );

		return $siteLinkLookup;
	}

	private function getFromFrame( $itemIdSerialization ) {
		$frame = $this->getMockBuilder( 'PPFrame' )
			->getMock();
		$frame->expects( $this->once() )
			->method( 'expand' )
			->with( 'Cat' )
			->will( $this->returnValue( 'Cat' ) );

		$childFrame = $this->getMockBuilder( 'PPFrame' )
			->getMock();
		$childFrame->expects( $this->once() )
			->method( 'getArgument' )
			->with( 'from' )
			->will( $this->returnValue( $itemIdSerialization ) );

		$frame->expects( $this->once() )
			->method( 'newChild' )
			->will( $this->returnValue( $childFrame ) );

		return $frame;
	}

	private function getPropertyClaimsRendererFactory( $entityId, $propertyLabelOrId ) {
		$renderer = $this->getRenderer( $entityId, $propertyLabelOrId );

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

	private function getRenderer( $entityId, $propertyLabelOrId ) {
		$renderer = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\PropertyClaimsRenderer'
			)
			->disableOriginalConstructor()
			->getMock();

		$renderer->expects( $this->any() )
			->method( 'render' )
			->with( $entityId, $propertyLabelOrId )
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
