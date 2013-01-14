<?php

namespace Wikibase\Test;
use Status;
use Wikibase\Item;

/**
 * Tests Wikibase\AliasConstraintDetector.
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
 * @ingroup Test
 *
 * @group Wikibase
 * @group MultiLangConstraintDetector
 * @group AliasConstraintDetector
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class AliasConstraintDetectorTest extends MultiLangConstraintDetectorBase {

	/**
	 * Create a new detector for this specific type of test
	 */
	static function newDetector() {
		return new \Wikibase\AliasConstraintDetector();
	}

	/**
	 * The class we are perusing
	 */
	static function detectorClass() {
		return '\Wikibase\AliasConstraintDetector';
	}

	/**
	 * Note that the following provider is reused for arrays even if it is generates strings
	 * @dataProvider mlStringProvider
	 */
	public function testGetArrayLengthConstraintViolations( $data, $expected, $fatal ) {
		static::doGetArrayLengthConstraintViolations( $data, $expected, $fatal );
	}

	/**
	 * @dataProvider mlStringProvider
	 */
	public function testAddArrayConstraintChecks( $data, $expected, $fatal ) {
		$status = Status::newGood();
		$detector = new \Wikibase\AliasConstraintDetector();

		$data = array_map( function($v) { return array($v); }, $data );

		$extData = array(
			'da' => array( self::$short ),
			'de' => array( self::$long ),
		);

		$baseEntity = new Item( array( 'aliases' => $extData ) );
		$newEntity = new Item( array( 'aliases' => array_merge_recursive( $extData, $data ) ) );
		$diff = $baseEntity->getDiff( $newEntity );
		$detector->checkConstraints( $newEntity, $status, $diff, array( 'length' => self::$limit ) );

		$this->assertEquals( $fatal, !$status->isOk() );

	}
}