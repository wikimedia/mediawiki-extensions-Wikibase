<?php

namespace Wikibase\Tests\Repo;

use Wikibase\Repo\Store\Sql\DatabaseSchemaUpdater;

/**
 * @covers Wikibase\Repo\Store\Sql\DatabaseSchemaUpdater
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseChange
 *
 * @license GNU GPL v2+
 * @author Marius Hoch
 */
class DatabaseSchemaUpdaterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Extremely simple test making sure this isn't going to blow up.
	 */
	public function testDoSchemaUpdate() {
		$store = $this->getMock( 'Wikibase\Store' );

		$db = $this->getMockBuilder( 'DatabaseMysql' )
			->disableOriginalConstructor()
			->getMock();

		$db->expects( $this->atLeastOnce() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$updater = $this->getMockBuilder( 'MysqlUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$updater->expects( $this->atLeastOnce() )
			->method( 'getDB' )
			->will( $this->returnValue( $db ) );

		$databaseSchemaUpdater = new DatabaseSchemaUpdater( $store );
		$databaseSchemaUpdater->doSchemaUpdate( $updater );
	}

}
