<?php

namespace Wikibase\Database\Tests;

use DatabaseBase;
use Wikibase\Database\DBConnectionProvider;
use Wikibase\Database\MediaWikiQueryInterface;
use Wikibase\Database\QueryInterface;
use Wikibase\Database\TableDefinition;
use Wikibase\Database\FieldDefinition;

/**
 * @covers Wikibase\Database\MediaWikiQueryInterface
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseDatabaseTest
 *
 * @group Wikibase
 * @group WikibaseDatabase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MediaWikiQueryInterfaceTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return QueryInterface
	 */
	protected function newInstance() {
		$connection = $this->getMock( 'DatabaseMysql' );

		$connectionProvider = new DirectConnectionProvider( $connection );

		return new MediaWikiQueryInterface(
			$connectionProvider,
			$this->getMock( '\Wikibase\Database\MWDB\ExtendedMySQLAbstraction' )
		);
	}

	/**
	 * @dataProvider tableNameProvider
	 *
	 * @param string $tableName
	 */
	public function testTableExists( $tableName ) {
		$connection = $this->getMock( 'DatabaseMysql' );
		$extendedAbstraction = $this->getMockBuilder( '\Wikibase\Database\MWDB\ExtendedMySQLAbstraction' )
			->disableOriginalConstructor()->getMock();

		$queryInterface = new MediaWikiQueryInterface(
			new DirectConnectionProvider( $connection ),
			$extendedAbstraction
		);

		$connection->expects( $this->once() )
			->method( 'tableExists' )
			->with( $this->equalTo( $tableName ) );

		$queryInterface->tableExists( $tableName );
	}

	public function tableNameProvider() {
		$argLists = array();

		$argLists[] = array( 'user' );
		$argLists[] = array( 'xdgxftjhreyetfytj' );
		$argLists[] = array( 'a' );
		$argLists[] = array( 'foo_bar_baz_bah' );

		return $argLists;
	}

	/**
	 * @dataProvider tableProvider
	 *
	 * @param TableDefinition $table
	 */
	public function testCreateTable( TableDefinition $table ) {
		$connection = $this->getMock( 'DatabaseMysql' );
		$extendedAbstraction = $this->getMockBuilder( '\Wikibase\Database\MWDB\ExtendedMySQLAbstraction' )
			->disableOriginalConstructor()->getMock();

		$queryInterface = new MediaWikiQueryInterface(
			new DirectConnectionProvider( $connection ),
			$extendedAbstraction
		);

		$extendedAbstraction->expects( $this->once() )
			->method( 'createTable' )
			->with( $this->equalTo( $table ) )
			->will( $this->returnValue( true ) );

		$queryInterface->createTable( $table );
	}

	/**
	 * @dataProvider tableProvider
	 *
	 * @param TableDefinition $table
	 */
	public function testCreateTableFailure( TableDefinition $table ) {
		$connection = $this->getMock( 'DatabaseMysql' );
		$extendedAbstraction = $this->getMockBuilder( '\Wikibase\Database\MWDB\ExtendedMySQLAbstraction' )
			->disableOriginalConstructor()->getMock();

		$queryInterface = new MediaWikiQueryInterface(
			new DirectConnectionProvider( $connection ),
			$extendedAbstraction
		);

		$extendedAbstraction->expects( $this->once() )
			->method( 'createTable' )
			->will( $this->returnValue( false ) );

		$this->setExpectedException( 'Wikibase\Database\TableCreationFailedException' );

		$queryInterface->createTable( $table );
	}

	/**
	 * @dataProvider tableProvider
	 *
	 * @param TableDefinition $table
	 */
	public function testDropTable( TableDefinition $table ) {
		$connection = $this->getMock( 'DatabaseMysql' );
		$extendedAbstraction = $this->getMockBuilder( '\Wikibase\Database\MWDB\ExtendedMySQLAbstraction' )
			->disableOriginalConstructor()->getMock();

		$queryInterface = new MediaWikiQueryInterface(
			new DirectConnectionProvider( $connection ),
			$extendedAbstraction
		);

		$connection->expects( $this->once() )
			->method( 'dropTable' )
			->with( $this->equalTo( $table ) );

		$queryInterface->dropTable( $table );
	}

	public function tableProvider() {
		$tables = array();

		$tables[] = new TableDefinition( 'differentfieldtypes', array(
			new FieldDefinition( 'intfield', FieldDefinition::TYPE_INTEGER ),
			new FieldDefinition( 'floatfield', FieldDefinition::TYPE_FLOAT ),
			new FieldDefinition( 'textfield', FieldDefinition::TYPE_TEXT ),
			new FieldDefinition( 'boolfield', FieldDefinition::TYPE_BOOLEAN ),
		) );

		$tables[] = new TableDefinition( 'defaultfieldvalues', array(
			new FieldDefinition( 'intfield', FieldDefinition::TYPE_INTEGER, true, 42 ),
		) );

		$tables[] = new TableDefinition( 'notnullfields', array(
			new FieldDefinition( 'intfield', FieldDefinition::TYPE_INTEGER, false ),
			new FieldDefinition( 'textfield', FieldDefinition::TYPE_TEXT, false ),
		) );

		$argLists = array();

		foreach ( $tables as $table ) {
			$argLists[] = array( $table );
		}

		return $argLists;
	}

	/**
	 * @dataProvider insertProvider
	 */
	public function testInsert( $tableName, array $fieldValues ) {
		$connection = $this->getMock( 'DatabaseMysql' );
		$extendedAbstraction = $this->getMockBuilder( '\Wikibase\Database\MWDB\ExtendedMySQLAbstraction' )
			->disableOriginalConstructor()->getMock();

		$queryInterface = new MediaWikiQueryInterface(
			new DirectConnectionProvider( $connection ),
			$extendedAbstraction
		);

		$connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->equalTo( $tableName ),
				$this->equalTo( $fieldValues )
			);

		$queryInterface->insert(
			$tableName,
			$fieldValues
		);
	}

	public function insertProvider() {
		$argLists = array();

		$argLists[] = array( 'foo', array() );

		$argLists[] = array( 'bar', array(
			'intfield' => 42,
		) );

		$argLists[] = array( 'baz', array(
			'intfield' => 42,
			'textfield' => '~=[,,_,,]:3',
		) );

		return $argLists;
	}

	/**
	 * @dataProvider updateProvider
	 */
	public function testUpdate( $tableName, array $newValues, array $conditions ) {
		$connection = $this->getMock( 'DatabaseMysql' );
		$extendedAbstraction = $this->getMockBuilder( '\Wikibase\Database\MWDB\ExtendedMySQLAbstraction' )
			->disableOriginalConstructor()->getMock();

		$queryInterface = new MediaWikiQueryInterface(
			new DirectConnectionProvider( $connection ),
			$extendedAbstraction
		);

		$connection->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->equalTo( $tableName ),
				$this->equalTo( $newValues ),
				$this->equalTo( $conditions )
			)
			->will( $this->returnValue( true ) );

		$queryInterface->update(
			$tableName,
			$newValues,
			$conditions
		);
	}

	/**
	 * @dataProvider updateProvider
	 */
	public function testUpdateFailure( $tableName, array $newValues, array $conditions ) {
		$connection = $this->getMock( 'DatabaseMysql' );
		$extendedAbstraction = $this->getMockBuilder( '\Wikibase\Database\MWDB\ExtendedMySQLAbstraction' )
			->disableOriginalConstructor()->getMock();

		$queryInterface = new MediaWikiQueryInterface(
			new DirectConnectionProvider( $connection ),
			$extendedAbstraction
		);

		$connection->expects( $this->once() )
			->method( 'update' )
			->will( $this->returnValue( false ) );

		$this->setExpectedException( '\Wikibase\Database\UpdateFailedException' );

		$queryInterface->update(
			$tableName,
			$newValues,
			$conditions
		);
	}

	public function updateProvider() {
		$argLists = array();

		$argLists[] = array(
			'foo',
			array(
				'intfield' => 42,
				'textfield' => 'foobar baz',
			),
			array(
			)
		);

		$argLists[] = array(
			'foo',
			array(
				'textfield' => '~=[,,_,,]:3',
			),
			array(
				'intfield' => 0
			)
		);

		$argLists[] = array(
			'foo',
			array(
				'textfield' => '~=[,,_,,]:3',
				'intfield' => 0,
				'floatfield' => 4.2,
			),
			array(
				'textfield' => '~[,,_,,]:3',
				'floatfield' => 9000.1,
			)
		);

		return $argLists;
	}

	/**
	 * @dataProvider deleteProvider
	 */
	public function testDelete( $tableName, array $conditions ) {
		$connection = $this->getMock( 'DatabaseMysql' );
		$extendedAbstraction = $this->getMockBuilder( '\Wikibase\Database\MWDB\ExtendedMySQLAbstraction' )
			->disableOriginalConstructor()->getMock();

		$queryInterface = new MediaWikiQueryInterface(
			new DirectConnectionProvider( $connection ),
			$extendedAbstraction
		);

		$connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->equalTo( $tableName ),
				$this->equalTo( $conditions )
			)
			->will( $this->returnValue( true ) );

		$queryInterface->delete( $tableName, $conditions );
	}

	/**
	 * @dataProvider deleteProvider
	 */
	public function testDeleteFailure( $tableName, array $conditions ) {
		$connection = $this->getMock( 'DatabaseMysql' );
		$extendedAbstraction = $this->getMockBuilder( '\Wikibase\Database\MWDB\ExtendedMySQLAbstraction' )
			->disableOriginalConstructor()->getMock();

		$queryInterface = new MediaWikiQueryInterface(
			new DirectConnectionProvider( $connection ),
			$extendedAbstraction
		);

		$connection->expects( $this->once() )
			->method( 'delete' )
			->will( $this->returnValue( false ) );

		$this->setExpectedException( '\Wikibase\Database\DeleteFailedException' );

		$queryInterface->delete( $tableName, $conditions );
	}

	public function deleteProvider() {
		$argLists = array();

		$argLists[] = array( 'foo', array() );

		$argLists[] = array( 'bar', array(
			'intfield' => 42,
		) );

		$argLists[] = array( 'baz', array(
			'intfield' => 42,
			'textfield' => '~=[,,_,,]:3',
		) );

		return $argLists;
	}

	public function testGetInsertId() {
		$connection = $this->getMock( 'DatabaseMysql' );
		$extendedAbstraction = $this->getMockBuilder( '\Wikibase\Database\MWDB\ExtendedMySQLAbstraction' )
			->disableOriginalConstructor()->getMock();

		$queryInterface = new MediaWikiQueryInterface(
			new DirectConnectionProvider( $connection ),
			$extendedAbstraction
		);

		$connection->expects( $this->once() )
			->method( 'insertId' )
			->will( $this->returnValue( 42 ) );

		$this->assertEquals( 42, $queryInterface->getInsertId() );
	}

	/**
	 * @dataProvider selectProvider
	 */
	public function testSelect( $tableName, array $fields, array $conditions ) {
		$connection = $this->getMock( 'DatabaseMysql' );
		$extendedAbstraction = $this->getMockBuilder( '\Wikibase\Database\MWDB\ExtendedMySQLAbstraction' )
			->disableOriginalConstructor()->getMock();

		$queryInterface = new MediaWikiQueryInterface(
			new DirectConnectionProvider( $connection ),
			$extendedAbstraction
		);

		$resultWrapper = $this->getMockBuilder( 'ResultWrapper' )
			->disableOriginalConstructor()->getMock();

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->equalTo( $tableName ),
				$this->equalTo( $fields ),
				$this->equalTo( $conditions )
			)
			->will( $this->returnValue( $resultWrapper ) );

		$queryInterface->select( $tableName, $fields, $conditions );

		// Ideally we would have the select method result a mock ResultWrapper
		// and would assert if the data was present in the selection result.
		// It however seems somewhat impossible to create a mock of ResultWrapper.
	}

	public function selectProvider() {
		$argLists = array();

		$argLists[] = array(
			'table',
			array(
				'foo',
				'bar',
				'baz',
			),
			array(
				'intfield' => 42,
				'strfield' => 'nyan',
			)
		);

		$argLists[] = array(
			'table',
			array(
				'foo',
				'bar',
				'baz',
			),
			array(
			)
		);

		$argLists[] = array(
			'onoez',
			array(
				'foo',
			),
			array(
				'intfield' => 42,
			)
		);

		return $argLists;
	}

}

class DirectConnectionProvider implements DBConnectionProvider {

	protected $connection;

	public function __construct( DatabaseBase $connection ) {
		$this->connection = $connection;
	}

	/**
	 * @see DBConnectionProvider::getConnection
	 *
	 * @since 0.1
	 *
	 * @return DatabaseBase
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * @see DBConnectionProvider::releaseConnection
	 *
	 * @since 0.1
	 */
	public function releaseConnection() {

	}

}
