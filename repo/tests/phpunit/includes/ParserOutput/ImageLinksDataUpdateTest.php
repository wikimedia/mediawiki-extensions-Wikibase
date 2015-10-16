<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\StringValue;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\ParserOutput\ImageLinksDataUpdate;

/**
 * @covers Wikibase\Repo\ParserOutput\ImageLinksDataUpdate
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class ImageLinksDataUpdateTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return ImageLinksDataUpdate
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

		return new ImageLinksDataUpdate( $matcher );
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
	 * @dataProvider imageLinksProvider
	 */
	public function testUpdateParserOutput( StatementList $statements, array $expected ) {
		$actual = array();

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();
		$parserOutput->expects( $this->exactly( count( $expected ) ) )
			->method( 'addImage' )
			->will( $this->returnCallback( function( $name ) use ( &$actual ) {
				$actual[] = $name;
			} ) );

		$instance = $this->newInstance();

		foreach ( $statements as $statement ) {
			$instance->processStatement( $statement );
		}

		$instance->updateParserOutput( $parserOutput );
		$this->assertSame( $expected, $actual );
	}

	public function imageLinksProvider() {
		$set1 = new StatementList();
		$this->addStatement( $set1, '1.jpg' );
		$this->addStatement( $set1, '' );
		$this->addStatement( $set1, 'no image property', 2 );

		$set2 = new StatementList();
		$this->addStatement( $set2, '2a.jpg' );
		$this->addStatement( $set2, '2b.jpg' );

		return array(
			array( new StatementList(), array() ),
			array( $set1, array( '1.jpg' ) ),
			array( $set2, array( '2a.jpg', '2b.jpg' ) ),
		);
	}

}
