<?php

namespace Wikibase\Test;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\InMemoryDataTypeLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityParserOutputGenerator;
use Wikibase\EntityRevision;

/**
 * @covers Wikibase\EntityParserOutputGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityParserOutputGeneratorTest extends \PHPUnit_Framework_TestCase {

	static $html = '<html>Nyan data!!!</html>';
	static $placeholders = array( 'key' => 'value' );
	static $configVars = array( 'foo' => 'bar' );

	public function testGetParserOutput() {
		$entityParserOutputGenerator = $this->newEntityParserOutputGenerator();

		$item = Item::newEmpty();
		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $item, 13044, $timestamp );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $revision );

		$this->assertEquals( self::$html, $parserOutput->getText() );
		$this->assertEquals( self::$placeholders, $parserOutput->getExtensionData( 'wikibase-view-chunks' ) );
		$this->assertEquals( self::$configVars, $parserOutput->getJsConfigVars() );
	}

	private function newEntityParserOutputGenerator() {
		return new EntityParserOutputGenerator(
			$this->getEntityViewMock(),
			$this->getConfigBuilderMock(),
			$this->getMock( 'Wikibase\Lib\Serializers\SerializationOptions' ),
			$this->getEntityTitleLookupMock(),
			new InMemoryDataTypeLookup()
		);
	}

	private function getEntityViewMock() {
		$entityView = $this->getMockBuilder( 'Wikibase\EntityView' )
			->disableOriginalConstructor()
			->getMock();

		$entityView->expects( $this->any() )
			->method( 'getHtml' )
			->will( $this->returnValue( self::$html ) );

		$entityView->expects( $this->any() )
			->method( 'getPlaceholders' )
			->will( $this->returnValue( self::$placeholders ) );

		return $entityView;
	}

	private function getConfigBuilderMock() {
		$configBuilder = $this->getMockBuilder( 'Wikibase\ParserOutputJsConfigBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$configBuilder->expects( $this->any() )
			->method( 'build' )
			->will( $this->returnValue( self::$configVars ) );

		return $configBuilder;
	}

	private function getEntityTitleLookupMock() {
		$entityTitleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$name = $id->getEntityType() . ':' . $id->getPrefixedId();
				return Title::makeTitle( NS_MAIN, $name );
			} ) );

		return $entityTitleLookup;
	}

}
