<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\StringValue;
use PHPUnit4And6Compat;
use ParserOutput;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\ParserOutput\ImageLinksDataUpdater;

/**
 * @covers Wikibase\Repo\ParserOutput\ImageLinksDataUpdater
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ImageLinksDataUpdaterTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @return ImageLinksDataUpdater
	 */
	private function newInstance() {
		$matcher = $this->getMockBuilder( PropertyDataTypeMatcher::class )
			->disableOriginalConstructor()
			->getMock();
		$matcher->expects( $this->any() )
			->method( 'isMatchingDataType' )
			->will( $this->returnCallback( function( PropertyId $id, $type ) {
				return $id->getSerialization() === 'P1';
			} ) );

		return new ImageLinksDataUpdater( $matcher );
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
		$actual = [];

		$parserOutput = $this->getMockBuilder( ParserOutput::class )
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

		return [
			[ new StatementList(), [] ],
			[ $set1, [ '1.jpg' ] ],
			[ $set2, [ '2a.jpg', '2b.jpg' ] ],
		];
	}

}
