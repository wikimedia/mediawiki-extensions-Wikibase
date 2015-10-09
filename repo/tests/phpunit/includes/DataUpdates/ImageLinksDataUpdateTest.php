<?php

namespace Wikibase\Repo\Tests\DataUpdates;

use DataValues\StringValue;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\DataUpdates\ImageLinksDataUpdate;

/**
 * @covers Wikibase\Repo\DataUpdates\ImageLinksDataUpdate
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class ImageLinksDataUpdateTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param StatementList $statements
	 * @param string $string
	 */
	private function addStatement( StatementList $statements, $string ) {
		$statements->addNewStatement( new PropertyValueSnak( 1, new StringValue( $string ) ) );
	}

	/**
	 * @dataProvider imageLinksProvider
	 */
	public function testGetImageLinks( StatementList $statements, array $expected ) {
		$instance = new ImageLinksDataUpdate();
		$actual = $instance->getImageLinks( $statements );
		$this->assertSame( $expected, $actual );
	}

	/**
	 * @dataProvider imageLinksProvider
	 */
	public function testUpdateParserOutput( StatementList $statements, array $expected ) {
		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();
		$parserOutput->expects( $this->exactly( count( $expected ) ) )
			->method( 'addImage' );

		$instance = new ImageLinksDataUpdate();
		foreach ( $statements as $statement ) {
			$instance->processStatement( $statement );
		}
		$instance->updateParserOutput( $parserOutput );
	}

	public function imageLinksProvider() {
		$set1 = new StatementList();
		$this->addStatement( $set1, '1.jpg' );
		$this->addStatement( $set1, '' );

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
