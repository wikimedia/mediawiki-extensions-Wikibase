<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MysqlUpdater;
use PHPUnit4And6Compat;
use Wikibase\Repo\Store\Sql\DatabaseSchemaUpdater;
use Wikibase\Store;
use Wikimedia\Rdbms\IMaintainableDatabase;

/**
 * @covers \Wikibase\Repo\Store\Sql\DatabaseSchemaUpdater
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class DatabaseSchemaUpdaterTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * Extremely simple test making sure this isn't going to blow up.
	 */
	public function testDoSchemaUpdate() {
		$store = $this->getMock( Store::class );

		$db = $this->getMock( IMaintainableDatabase::class );

		$db->expects( $this->atLeastOnce() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$updater = $this->getMockBuilder( MysqlUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$updater->expects( $this->atLeastOnce() )
			->method( 'getDB' )
			->will( $this->returnValue( $db ) );

		$databaseSchemaUpdater = new DatabaseSchemaUpdater( $store );
		$databaseSchemaUpdater->doSchemaUpdate( $updater );
	}

}
