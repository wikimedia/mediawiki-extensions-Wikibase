<?php

namespace Wikibase\Repo\Test\Database;

use Wikibase\Repo\Database\TableBuilder;
use Wikibase\Repo\Database\FieldDefinition;
use Wikibase\Repo\Database\TableDefinition;

/**
 * Unit test Wikibase\Repo\Database\TableBuilder class.
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
 * @since 0.4
 *
 * @ingroup WikibaseRepoTest
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseDatabase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TableBuilderTest extends \MediaWikiTestCase {

	public function tableNameProvider() {
		return $this->arrayWrap(
			array(
				'foo',
				'bar',
				'o',
				'foo_bar_baz',
				'foobarbaz',
			)
		);
	}

	/**
	 * @dataProvider tableNameProvider
	 */
	public function testCreateTableCallsTableExists( $tableName ) {
		$table = new TableDefinition(
			$tableName,
			array( new FieldDefinition( 'foo', FieldDefinition::TYPE_TEXT ) )
		);

		$reporter = new NullMessageReporter();

		$queryInterface = new ObservableQueryInterface();

		$assertEquals = array( $this, 'assertEquals' );
		$callCount = 0;

		$queryInterface->registerCallback(
			'tableExists',
			function( $tableName ) use ( $table, &$callCount, $assertEquals ) {
				call_user_func( $assertEquals, $table->getName(), $tableName );
				$callCount += 1;
			}
		);

		$builder = new TableBuilder( $queryInterface, $reporter );

		$builder->createTable( $table );
		$this->assertEquals( 1, $callCount );
	}

}

use Wikibase\Repo\Database\QueryInterface;

class ObservableQueryInterface implements QueryInterface {

	/**
	 * @var callable[]
	 */
	private $callbacks = array();

	/**
	 * @param string $method
	 * @param callable $callback
	 */
	public function registerCallback( $method, $callback ) {
		$this->callbacks[$method] = $callback;
	}

	private function runCallbacks( $method, $args ) {
		if ( array_key_exists( $method, $this->callbacks ) ) {
			call_user_func_array( $this->callbacks[$method], $args );
		}
	}

	/**
	 * @see QueryInterface::tableExists
	 *
	 * @param string $tableName
	 *
	 * @return boolean
	 */
	public function tableExists( $tableName ) {
		$this->runCallbacks( __FUNCTION__, func_get_args() );
	}

}

use MessageReporter;

class NullMessageReporter implements MessageReporter {

	/**
	 * @see MessageReporter::reportMessage
	 *
	 * @since 0.4
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		// no-op
	}

}