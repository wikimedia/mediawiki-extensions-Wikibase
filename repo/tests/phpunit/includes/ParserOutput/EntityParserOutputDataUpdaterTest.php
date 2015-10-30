<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use ParserOutput;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\ParserOutput\EntityParserOutputDataUpdater;

/**
 * @covers Wikibase\Repo\ParserOutput\EntityParserOutputDataUpdater
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class EntityParserOutputDataUpdaterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider entitiesProvider
	 */
	public function testUpdateParserOutput( array $entities, $statements, $siteLinks ) {
		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$statementDataUpdate = $this->getMock( 'Wikibase\Repo\ParserOutput\StatementDataUpdater' );
		$statementDataUpdate->expects( $this->exactly( $statements ) )
			->method( 'processStatement' );
		$statementDataUpdate->expects( $this->once() )
			->method( 'updateParserOutput' );

		$siteLinkDataUpdate = $this->getMock( 'Wikibase\Repo\ParserOutput\SiteLinkDataUpdater' );
		$siteLinkDataUpdate->expects( $this->exactly( $siteLinks ) )
			->method( 'processSiteLink' );
		$siteLinkDataUpdate->expects( $this->once() )
			->method( 'updateParserOutput' );

		$instance = new EntityParserOutputDataUpdater( $parserOutput, array(
			$statementDataUpdate,
			$siteLinkDataUpdate,
		) );
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

		return array(
			array( array(), 0, 0 ),
			array( array( $q1 ), 1, 0 ),
			array( array( $q2 ), 0, 1 ),
			array( array( $q1, $q2 ), 1, 1 ),
		);
	}

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testGivenInvalidDataUpdate_constructorThrowsException( array $argument ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new EntityParserOutputDataUpdater( new ParserOutput(), $argument );
	}

	public function invalidConstructorArgumentProvider() {
		return array(
			array( array( null ) ),
			array( array( 'notAnObject' ) ),
			array( array( $this->getMock( 'Wikibase\Repo\ParserOutput\ParserOutputDataUpdater' ) ) ),
		);
	}

}
