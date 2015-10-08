<?php

namespace Wikibase\Repo\Tests\DataUpdates;

use DataValues\StringValue;
use PHPUnit_Framework_TestCase;
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
	 * @param StatementList $statements
	 * @param string $string
	 */
	private function addStatement( StatementList $statements, $string ) {
		$statements->addNewStatement( new PropertyValueSnak( 1, new StringValue( $string ) ) );
	}

	/**
	 * @dataProvider externalLinksProvider
	 */
	public function testGetExternalLinks( StatementList $statements, array $expected ) {
		$instance = new ExternalLinksDataUpdate();
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

		$instance = new ExternalLinksDataUpdate();
		foreach ( $statements as $statement ) {
			$instance->processStatement( $statement );
		}
		$instance->updateParserOutput( $parserOutput );
	}

	public function externalLinksProvider() {
		$set1 = new StatementList();
		$this->addStatement( $set1, 'http://1.de' );

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
