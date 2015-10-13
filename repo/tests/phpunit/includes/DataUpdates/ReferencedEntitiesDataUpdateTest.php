<?php

namespace Wikibase\Repo\Tests\DataUpdates;

use DataValues\QuantityValue;
use MediaWikiTestCase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\DataUpdates\ReferencedEntitiesDataUpdate;

/**
 * @covers Wikibase\Repo\DataUpdates\ReferencedEntitiesDataUpdate
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class ReferencedEntitiesDataUpdateTest extends MediaWikiTestCase {

	const UNIT_PREFIX = 'unit:';

	protected function setUp() {
		parent::setUp();

		foreach ( array( 'P1', 'Q1', 'Q20', 'Q21', 'Q22' ) as $pageName ) {
			$this->insertPage( $pageName );
		}
	}

	/**
	 * @param int $count
	 *
	 * @return ReferencedEntitiesDataUpdate
	 */
	private function newInstance( $count = 0 ) {
		$entityTitleLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\EntityTitleLookup' )
			->disableOriginalConstructor()
			->getMock();
		$entityTitleLookup->expects( $this->exactly( $count ) )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::newFromText( $id->getSerialization() );
			} ) );

		$entityidParser = $this->getMockBuilder( 'Wikibase\DataModel\Entity\EntityIdParser' )
			->disableOriginalConstructor()
			->getMock();
		$entityidParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnCallback( function( $id ) {
				return new ItemId(
					substr( $id, strlen( ReferencedEntitiesDataUpdateTest::UNIT_PREFIX ) )
				);
			} ) );

		return new ReferencedEntitiesDataUpdate( $entityTitleLookup, $entityidParser );
	}

	/**
	 * @param StatementList $statements
	 * @param string $itemId
	 */
	private function addStatement( StatementList $statements, $itemId ) {
		$statements->addNewStatement(
			new PropertyValueSnak( 1, new EntityIdValue( new ItemId( $itemId ) ) )
		);
	}

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testGetEntityIds(
		StatementList $statements,
		SiteLinkList $siteLinks = null,
		array $expected
	) {
		$instance = $this->newInstance();
		$actual = $instance->getEntityIds( $statements, $siteLinks );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testUpdateParserOutput(
		StatementList $statements,
		SiteLinkList $siteLinks = null,
		array $expected
	) {
		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();
		$parserOutput->expects( $this->exactly( count( $expected ) ) )
			->method( 'addLink' );

		$instance = $this->newInstance( count( $expected ) );

		foreach ( $statements as $statement ) {
			$instance->processStatement( $statement );
		}

		if ( $siteLinks !== null ) {
			foreach ( $siteLinks as $siteLink ) {
				$instance->processSiteLink( $siteLink );
			}
		}

		$instance->updateParserOutput( $parserOutput );
	}

	public function entityIdProvider() {
		$set1 = new StatementList();
		$this->addStatement( $set1, 'Q1' );

		$set2 = new StatementList();
		$this->addStatement( $set2, 'Q20' );
		$this->addStatement( $set2, 'Q21' );
		$set2->addNewStatement(
			new PropertyValueSnak( 1, QuantityValue::newFromNumber( 1, self::UNIT_PREFIX . 'Q22' ) )
		);

		$siteLinks = new SiteLinkList();
		$siteLinks->addNewSiteLink( 'siteId', 'pageName', array( new ItemId( 'Q1' ) ) );

		return array(
			array( new StatementList(), null, array(
			) ),
			array( $set1, null, array(
				new PropertyId( 'P1' ),
				new ItemId( 'Q1' ),
			) ),
			array( new StatementList(), $siteLinks, array(
				new ItemId( 'Q1' ),
			) ),
			array( $set1, $siteLinks, array(
				new PropertyId( 'P1' ),
				new ItemId( 'Q1' ),
			) ),
			array( $set2, null, array(
				new PropertyId( 'P1' ),
				new ItemId( 'Q20' ),
				new ItemId( 'Q21' ),
				new ItemId( 'Q22' ),
			) ),
			array( $set2, $siteLinks, array(
				new PropertyId( 'P1' ),
				new ItemId( 'Q20' ),
				new ItemId( 'Q21' ),
				new ItemId( 'Q22' ),
				new ItemId( 'Q1' ),
			) ),
		);
	}

}
