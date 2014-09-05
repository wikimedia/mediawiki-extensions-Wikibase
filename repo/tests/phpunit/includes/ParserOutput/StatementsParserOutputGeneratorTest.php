<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\InMemoryDataTypeLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\EntityId;
use Wikibase\Repo\ParserOutput\StatementsParserOutputGenerator;

/**
 * @covers Wikibase\Repo\ParserOutput\StatementsParserOutputGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @group Database
 *		^---- needed because we rely on Title objects internally
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class StatementsParserOutputGeneratorTest extends \PHPUnit_Framework_TestCase {

	public function testAssignSiteLinksToParserOutput() {
		$statementsParserOutputGenerator = $this->getStatementsParserOutputGenerator();
		$pout = new ParserOutput();

		$statements = new StatementList();
		$statements->addNewStatement( new PropertyNoValueSnak( 42 ) );
		$statements->addNewStatement( new PropertyValueSnak( 42, new StringValue( 'foo bar' ) ) );
		$statements->addNewStatement( new PropertyValueSnak( 43, new EntityIdValue( new ItemId( 'Q10' ) ) ) );
		$statements->addNewStatement( new PropertyValueSnak( 44, new StringValue( 'http://foo.bar/' ) ) );
		$statements->addNewStatement( new PropertyValueSnak( 45, new StringValue( 'File:Image.jpg' ) ) );
		$statements->addNewStatement( new PropertyValueSnak( 45, new StringValue( 'File:Foo bar.jpg' ) ) );

		$statementsParserOutputGenerator->assignToParserOutput( $pout, $statements );

		$this->assertEquals( 6, $pout->getProperty( 'wb-statements' ) );

		$links = $pout->getLinks();
		$this->assertEquals( array( 'P42', 'P43', 'Q10', 'P44', 'P45' ), array_keys( $links[NS_MAIN] ) );

		$urls = $pout->getExternalLinks();
		$this->assertEquals( array( 'http://foo.bar/' ), array_keys( $urls ) );

		$images = $pout->getImages();
		$this->assertEquals( array( 'File:Image.jpg', 'File:Foo_bar.jpg' ), array_keys( $images ) );
	}

	private function getStatementsParserOutputGenerator() {
		$entityTitleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $entityId ) {
				return Title::makeTitle( NS_MAIN, $entityId->getPrefixedId() );
			} ) );

		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P42' ), 'string' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P43' ), 'entityId' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P44' ), 'url' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P45' ), 'commonsMedia' );

		return new StatementsParserOutputGenerator( $entityTitleLookup, $dataTypeLookup );
	}

}
