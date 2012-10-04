<?php

namespace Wikibase\Test;
use \Wikibase\StatementAggregate;
use \Wikibase\Statements;

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

		$aggregates[] = \Wikibase\ItemObject::newEmpty();
		$aggregates[] = new \Wikibase\StatementList();

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
		$this->assertInstanceOf( '\Wikibase\Statements', $aggregate->getStatements() );

		foreach ( $statements as $statement ) {
			$aggregate->addStatement( $statement );
		}

		$obtainedStatements = $aggregate->getStatements();

		$this->assertInstanceOf( '\Wikibase\Statements', $obtainedStatements );

		foreach ( $statements as $statement ) {
			$this->assertTrue( $aggregate->hasStatement( $statement ) );
		}
	}

}
