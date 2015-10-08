<?php

namespace Wikibase\Repo\Tests\DataUpdates;

use DataValues\StringValue;
use PHPUnit_Framework_MockObject_Matcher_Invocation;
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
	public function testGetImageLinks(
		StatementList $statements,
		PHPUnit_Framework_MockObject_Matcher_Invocation $matcher,
		array $expected
	) {
		$instance = new ImageLinksDataUpdate();
		$imageLinks = $instance->getImageLinks( $statements );
		$this->assertSame( $expected, $imageLinks );
	}

	/**
	 * @dataProvider imageLinksProvider
	 */
	public function testUpdateParserOutput(
		StatementList $statements,
		PHPUnit_Framework_MockObject_Matcher_Invocation $matcher,
		array $expected
	) {
		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();
		$parserOutput->expects( $matcher )
			->method( 'addImage' );

		$instance = new ImageLinksDataUpdate();
		foreach ( $statements as $statement ) {
			$instance->processStatement( $statement );
		}
		$instance->updateParserOutput( $parserOutput );
	}

	public function imageLinksProvider() {
		$statements = new StatementList();
		$this->addStatement( $statements, 'A.jpg' );

		return array(
			array( new StatementList(), $this->never(), array() ),
			array( $statements, $this->once(), array( 'A.jpg' ) ),
		);
	}

}
