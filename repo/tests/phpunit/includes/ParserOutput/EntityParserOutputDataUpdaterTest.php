<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
use ParserOutput;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\ParserOutput\EntityParserOutputDataUpdater;
use Wikibase\Repo\ParserOutput\ParserOutputDataUpdater;
use Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater;
use Wikibase\Repo\ParserOutput\SiteLinkDataUpdater;
use Wikibase\Repo\ParserOutput\StatementDataUpdater;

/**
 * @covers Wikibase\Repo\ParserOutput\EntityParserOutputDataUpdater
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class EntityParserOutputDataUpdaterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider entitiesProvider
	 */
	public function testUpdateParserOutput( array $entities, $statements, $siteLinks ) {
		$statementDataUpdater = $this->getMock( StatementDataUpdater::class );
		$statementDataUpdater->expects( $this->exactly( $statements ) )
			->method( 'processStatement' );
		$statementDataUpdater->expects( $this->once() )
			->method( 'updateParserOutput' );

		$siteLinkDataUpdater = $this->getMock( SiteLinkDataUpdater::class );
		$siteLinkDataUpdater->expects( $this->exactly( $siteLinks ) )
			->method( 'processSiteLink' );
		$siteLinkDataUpdater->expects( $this->once() )
			->method( 'updateParserOutput' );

		$siteLinkAndStatementDataUpdater = $this->getMockBuilder(
				ReferencedEntitiesDataUpdater::class
			)
			->disableOriginalConstructor()
			->getMock();
		$siteLinkAndStatementDataUpdater->expects( $this->exactly( $statements ) )
			->method( 'processStatement' );
		$siteLinkAndStatementDataUpdater->expects( $this->exactly( $siteLinks ) )
			->method( 'processSiteLink' );
		$siteLinkAndStatementDataUpdater->expects( $this->once() )
			->method( 'updateParserOutput' );

		$instance = new EntityParserOutputDataUpdater( new ParserOutput(), [
			$statementDataUpdater,
			$siteLinkDataUpdater,
			$siteLinkAndStatementDataUpdater
		] );

		foreach ( $entities as $entity ) {
			$instance->processEntity( $entity );
		}

		$instance->finish();
	}

	public function entitiesProvider() {
		$statements = new StatementList();
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ) );
		$q1 = new Item( null, null, null, $statements );

		$siteLinks = new SiteLinkList();
		$siteLinks->addNewSiteLink( 'enwiki', 'Title' );
		$q2 = new Item( null, null, $siteLinks );

		return [
			[ [], 0, 0 ],
			[ [ $q1 ], 1, 0 ],
			[ [ $q2 ], 0, 1 ],
			[ [ $q1, $q2 ], 1, 1 ],
		];
	}

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testGivenInvalidDataUpdater_constructorThrowsException( array $argument ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new EntityParserOutputDataUpdater( new ParserOutput(), $argument );
	}

	public function invalidConstructorArgumentProvider() {
		return [
			[ [ null ] ],
			[ [ 'notAnObject' ] ],
			[ [ $this->getMock( ParserOutputDataUpdater::class ) ] ],
		];
	}

	public function testProcessEntityDoesNotTriggerGetters() {
		$entity = $this->getMock( Item::class );
		$entity->expects( $this->never() )->method( 'getStatements' );
		$entity->expects( $this->never() )->method( 'getSiteLinkList' );
		$instance = new EntityParserOutputDataUpdater( new ParserOutput(), [] );
		$instance->processEntity( $entity );
	}

}
