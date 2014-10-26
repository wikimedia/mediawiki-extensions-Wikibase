<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\InMemoryDataTypeLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;
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

		$item = $this->newItem();
		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $item, 13044, $timestamp );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $revision );

		$this->assertEquals( self::$html, $parserOutput->getText() );
		$this->assertEquals( self::$placeholders, $parserOutput->getExtensionData( 'wikibase-view-chunks' ) );
		$this->assertEquals( self::$configVars, $parserOutput->getJsConfigVars() );
		$this->assertEquals( array( 'en' => 'kittens' ), $parserOutput->getExtensionData( 'wikibase-entity-labels' ) );

		$this->assertEquals( array( 'http://an.url.com', 'https://another.url.org' ), array_keys( $parserOutput->getExternalLinks() ) );
		$this->assertEquals( array( 'File:This_is_a_file.pdf', 'File:Selfie.jpg' ), array_keys( $parserOutput->getImages() ) );
	}

	private function newEntityParserOutputGenerator() {
		return new EntityParserOutputGenerator(
			$this->getEntityViewMock(),
			$this->getConfigBuilderMock(),
			$this->getMock( 'Wikibase\Lib\Serializers\SerializationOptions' ),
			$this->getEntityTitleLookupMock(),
			$this->getDataTypeLookup()
		);
	}

	private function newItem() {
		$item = Item::newEmpty();

		$fingerprint = Fingerprint::newEmpty();
		$fingerprint->setLabel( 'en', 'kittens' );
		$item->setFingerprint( $fingerprint );

		$statements = new StatementList();

		$statements->addNewStatement( new PropertyValueSnak( 42, new StringValue( 'http://an.url.com' ) ) );
		$statements->addNewStatement( new PropertyValueSnak( 42, new StringValue( 'https://another.url.org' ) ) );

		$statements->addNewStatement( new PropertyValueSnak( 10, new StringValue( 'File:This is a file.pdf' ) ) );
		$statements->addNewStatement( new PropertyValueSnak( 10, new StringValue( 'File:Selfie.jpg' ) ) );

		$item->setStatements( $statements );

		return $item;
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
				$name = $id->getEntityType() . ':' . $id->getSerialization();
				return Title::makeTitle( NS_MAIN, $name );
			} ) );

		return $entityTitleLookup;
	}

	private function getDataTypeLookup() {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P42' ), 'url' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P10' ), 'commonsMedia' );
		return $dataTypeLookup;
	}

}
