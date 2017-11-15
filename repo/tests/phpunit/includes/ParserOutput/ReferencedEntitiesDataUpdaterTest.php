<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\QuantityValue;
use DataValues\UnboundedQuantityValue;
use MediaWikiTestCase;
use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class ReferencedEntitiesDataUpdaterTest extends MediaWikiTestCase {

	const UNIT_PREFIX = 'unit:';

	public function addDBData() {
		foreach ( [ 'P1', 'Q1', 'Q20', 'Q21', 'Q22' ] as $pageName ) {
			if ( $pageName[0] === 'P' ) {
				$entityType = 'property';
				$text = '{ "type": "property", "datatype": "string", "id": "P1" }';
			} else {
				$entityType = 'item';
				$text = '{ "type": "item", "id": "Q1" }';
			}

			$this->insertPage( $pageName, $text, $this->getEntityNamespace( $entityType ) );
		}
	}

	/**
	 * @param int $count
	 *
	 * @return ReferencedEntitiesDataUpdater
	 */
	private function newInstance( $count = 0 ) {
		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->exactly( $count ) )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$namespace = $this->getEntityNamespace( $id->getEntityType() );
				return Title::makeTitle( $namespace, $id->getSerialization() );
			} ) );

		$entityIdParser = $this->getMock( EntityIdParser::class );
		$entityIdParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnCallback( function( $id ) {
				return new ItemId(
					substr( $id, strlen( self::UNIT_PREFIX ) )
				);
			} ) );

		return new ReferencedEntitiesDataUpdater( $entityTitleLookup, $entityIdParser );
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
		$actual = [];

		$parserOutput = $this->getMockBuilder( ParserOutput::class )
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
		$statementList1 = new StatementList();
		$this->addStatement( $statementList1, 'Q1' );

		$statementList2 = new StatementList();
		$this->addStatement( $statementList2, 'Q20' );
		$statementList2->addNewStatement( new PropertyValueSnak(
			1,
			UnboundedQuantityValue::newFromNumber( 1, self::UNIT_PREFIX . 'Q21' )
		) );
		$statementList2->addNewStatement( new PropertyValueSnak(
			1,
			QuantityValue::newFromNumber( 1, self::UNIT_PREFIX . 'Q22' )
		) );

		$siteLinks = new SiteLinkList();
		$siteLinks->addNewSiteLink( 'siteId', 'pageName', [ new ItemId( 'Q1' ) ] );

		return [
			[ new StatementList(), null, [] ],
			[ $statementList1, null, [ 'P1', 'Q1' ] ],
			[ new StatementList(), $siteLinks, [ 'Q1' ] ],
			[ $statementList1, $siteLinks, [ 'P1', 'Q1' ] ],
			[ $statementList2, null, [ 'P1', 'Q20', 'Q21', 'Q22' ] ],
			[ $statementList2, $siteLinks, [ 'P1', 'Q20', 'Q21', 'Q22', 'Q1' ] ]
		];
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
	 * @param string $entityType
	 * @return int|null
	 */
	private function getEntityNamespace( $entityType ) {
		$entityNamespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		return $entityNamespaceLookup->getEntityNamespace( $entityType );
	}

}
