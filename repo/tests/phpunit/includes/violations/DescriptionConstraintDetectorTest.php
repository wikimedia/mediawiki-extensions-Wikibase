<?php

namespace Wikibase\Test;
use Status;
use Wikibase\Item;

/**
 * Tests Wikibase\DescriptionConstraintDetector.
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
 * @group DescriptionConstraintDetector
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class DescriptionConstraintDetectorTest extends MultiLangConstraintDetectorBase {

	/**
	 * Create a new detector for this specific type of test
	 */
	static function newDetector() {
		return new \Wikibase\DescriptionConstraintDetector();
	}

	/**
	 * The class we are perusing
	 */
	static function detectorClass() {
		return '\Wikibase\DescriptionConstraintDetector';
	}

	/**
	 * @dataProvider mlStringProvider
	 */
	public function testGetStringLengthConstraintViolations( $data, $expected, $fatal ) {
		static::doGetStringLengthConstraintViolations( $data, $expected, $fatal );
	}

	/**
	 * @dataProvider mlStringProvider
	 */
	public function testCheckStringConstraints( $data, $expected, $fatal ) {
		$status = Status::newGood();
		$detector = static::newDetector();

		$extData = array(
			'da' => self::$short,
			'de' => self::$long,
		);

		$baseEntity = new Item( array( 'description' => $extData ) );
		$newEntity = new Item( array( 'description' => array_merge( $extData, $data ) ) );
		$diff = $baseEntity->getDiff( $newEntity );
		$detector->checkConstraints( $newEntity, $status, $diff, array( 'length' => self::$limit ) );

		$this->assertEquals( $fatal, !$status->isOk() );
	}
}