<?php

namespace Wikibase\Repo\Tests\DataUpdates;

use DataValues\StringValue;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\DataUpdates\ExternalLinksDataUpdate;

/**
 * @covers Wikibase\Repo\DataUpdates\ExternalLinksDataUpdate
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class ExternalLinksDataUpdateTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return ExternalLinksDataUpdate
	 */
	private function newInstance() {
		$matcher = $this->getMockBuilder( 'Wikibase\Lib\Store\PropertyDataTypeMatcher' )
			->disableOriginalConstructor()
			->getMock();
		$matcher->expects( $this->any() )
			->method( 'isMatchingDataType' )
			->will( $this->returnCallback( function( PropertyId $id, $type ) {
				return $id->getSerialization() === 'P1';
			} ) );

		return new ExternalLinksDataUpdate( $matcher );
	}

	/**
	 * @param StatementList $statements
	 * @param string $string
	 * @param int $propertyId
	 */
	private function addStatement( StatementList $statements, $string, $propertyId = 1 ) {
		$statements->addNewStatement(
			new PropertyValueSnak( $propertyId, new StringValue( $string ) )
		);
	}

	/**
	 * @dataProvider externalLinksProvider
	 */
	public function testGetExternalLinks( StatementList $statements, array $expected ) {
		$instance = $this->newInstance();
		$actual = $instance->getExternalLinks( $statements );
		$this->assertSame( $expected, $actual );
	}

	/**
	 * @dataProvider externalLinksProvider
	 */
	public function testUpdateParserOutput( StatementList $statements, array $expected ) {
		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();
		$parserOutput->expects( $this->exactly( count( $expected ) ) )
			->method( 'addExternalLink' );

		$instance = $this->newInstance();
		foreach ( $statements as $statement ) {
			$instance->processStatement( $statement );
		}
		$instance->updateParserOutput( $parserOutput );
	}

	public function externalLinksProvider() {
		$set1 = new StatementList();
		$this->addStatement( $set1, 'http://1.de' );
		$this->addStatement( $set1, '' );
		$this->addStatement( $set1, 'no url property', 2 );

		$set2 = new StatementList();
		$this->addStatement( $set2, 'http://2a.de' );
		$this->addStatement( $set2, 'http://2b.de' );

		return array(
			array( new StatementList(), array() ),
			array( $set1, array( 'http://1.de' ) ),
			array( $set2, array( 'http://2a.de', 'http://2b.de' ) ),
		);
	}

}
