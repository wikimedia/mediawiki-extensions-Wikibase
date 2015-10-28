<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\QuantityValue;
use MediaWikiTestCase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater;

/**
 * @covers Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class ReferencedEntitiesDataUpdaterTest extends MediaWikiTestCase {

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
	 * @return ReferencedEntitiesDataUpdater
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
					substr( $id, strlen( ReferencedEntitiesDataUpdaterTest::UNIT_PREFIX ) )
				);
			} ) );

		return new ReferencedEntitiesDataUpdater( $entityTitleLookup, $entityidParser );
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

		foreach ( $statements as $statement ) {
			$instance->processStatement( $statement );
		}

		if ( $siteLinks !== null ) {
			foreach ( $siteLinks as $siteLink ) {
				$instance->processSiteLink( $siteLink );
			}
		}

		$actual = array_map( function( EntityId $id ) {
			return $id->getSerialization();
		}, $instance->getEntityIds() );
		$this->assertSame( $expected, $actual );
	}

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testUpdateParserOutput(
		StatementList $statements,
		SiteLinkList $siteLinks = null,
		array $expected
	) {
		$actual = array();

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();
		$parserOutput->expects( $this->exactly( count( $expected ) ) )
			->method( 'addLink' )
			->will( $this->returnCallback( function( Title $title ) use ( &$actual ) {
				$actual[] = $title->getText();
			} ) );

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
		$this->assertArrayEquals( $expected, $actual );
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
				'P1',
				'Q1',
			) ),
			array( new StatementList(), $siteLinks, array(
				'Q1',
			) ),
			array( $set1, $siteLinks, array(
				'P1',
				'Q1',
			) ),
			array( $set2, null, array(
				'P1',
				'Q20',
				'Q21',
				'Q22',
			) ),
			array( $set2, $siteLinks, array(
				'P1',
				'Q20',
				'Q21',
				'Q22',
				'Q1',
			) ),
		);
	}

}
