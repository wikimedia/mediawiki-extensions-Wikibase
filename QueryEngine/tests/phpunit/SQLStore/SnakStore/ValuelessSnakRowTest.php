<?php

namespace Wikibase\QueryEngine\Tests\SQLStore\SnakStore;

use Wikibase\QueryEngine\SQLStore\SnakStore\ValuelessSnakRow;
use Wikibase\SnakRole;

/**
 * @covers Wikibase\QueryEngine\SQLStore\ValuelessSnakRow
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
 * @ingroup WikibaseQueryEngineTest
 *
 * @group Wikibase
 * @group WikibaseQueryEngine
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ValuelessSnakRowTest extends \PHPUnit_Framework_TestCase {

	public function constructorProvider() {
		$argLists = array();

		$argLists[] = array(
			ValuelessSnakRow::TYPE_NO_VALUE,
			9001,
			31337,
			SnakRole::MAIN_SNAK,
			321
		);

		$argLists[] = array(
			ValuelessSnakRow::TYPE_SOME_VALUE,
			9002,
			1337,
			SnakRole::QUALIFIER,
			123
		);

		return $argLists;
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( $internalSnakType, $internalPropertyId, $internalClaimId, $snakRole, $internalSubjectId ) {
		$snakRow = new ValuelessSnakRow( $internalSnakType, $internalPropertyId, $internalClaimId, $snakRole, $internalSubjectId );

		$this->assertEquals( $internalPropertyId, $snakRow->getInternalPropertyId() );
		$this->assertEquals( $internalClaimId, $snakRow->getInternalClaimId() );
		$this->assertEquals( $snakRole, $snakRow->getSnakRole() );
		$this->assertEquals( $internalSnakType, $snakRow->getInternalSnakType() );
		$this->assertEquals( $internalSubjectId, $snakRow->getInternalSubjectId() );
	}

}
