<?php

namespace Wikibase\Test;
use Wikibase\StatementAggregate;
use Wikibase\Statement;

/**
 * Tests for the Wikibase\StatementAggregate implementing classes.
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
 * @since 0.2
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStatement
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementAggregateTest extends \MediaWikiTestCase {

	public function statementTestProvider() {
		$statements = array();

		$statements[] = new \Wikibase\StatementObject( new \Wikibase\ClaimObject( new \Wikibase\PropertyNoValueSnak( 42 ) ) );

		$aggregates = array();

		$item = \Wikibase\ItemObject::newEmpty();
		$item->setStatements( new \Wikibase\StatementList( $statements ) );

		$aggregates[] = $item;

		$argLists = array();

		/**
		 * @var StatementAggregate $aggregate
		 */
		foreach ( $aggregates as $aggregate ) {
			foreach ( $statements as $statement ) {
				$argLists[] = array( clone $aggregate, array( $statement ) );
			}

			$argLists[] = array( clone $aggregate, $statements );
		}

		return $argLists;
	}

	/**
	 * @dataProvider statementTestProvider
	 *
	 * @param StatementAggregate $aggregate
	 * @param array $statements
	 */
	public function testAllOfTheStuff( StatementAggregate $aggregate, array $statements ) {
		$obtainedStatements = $aggregate->getStatements();
		$this->assertInstanceOf( '\Wikibase\Statements', $obtainedStatements );

		// Below code tests if the statements in the statementAggregate indeed do not get modified.

		$unmodifiedStatements = serialize( $obtainedStatements );

		/**
		 * @var Statement $statement
		 */
		foreach ( $obtainedStatements as $statement ) {
			$statement->setRank( Statement::RANK_DEPRECATED );
		}

		foreach ( $statements as $statement ) {
			$obtainedStatements->addStatement( $statement );
		}

		$freshlyObtained = $aggregate->getStatements();

		$this->assertEquals( $unmodifiedStatements, serialize( $freshlyObtained ), 'Was able to modify statements via StatementAggregate::getStatements' );
	}

}
