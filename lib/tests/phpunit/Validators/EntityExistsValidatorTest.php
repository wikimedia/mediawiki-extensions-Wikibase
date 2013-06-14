<?php
 /**
 *
 * Copyright © 14.06.13 by the authors listed below.
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
 * @license GPL 2+
 * @file
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @author Daniel Kinzler
 */


namespace Wikibase\Test\Validators;

use ValueParsers\ParserOptions;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\Test\MockRepository;
use Wikibase\Validators\EntityExistsValidator;

/**
 * Class EntityExistsValidatorTest
 * @covers Wikibase\Validators\EntityExistsValidator
 * @package Wikibase\Test\Validators
 */
class EntityExistsValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		return array(
			array( 'q3', false, 'InvalidArgumentException', "Expect an EntityId" ),
			array( new EntityId( Item::ENTITY_TYPE, 3 ), false, null, "missing entity" ),
			array( new EntityId( Item::ENTITY_TYPE, 8 ), true, null, "existing entity" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $value, $expected, $exception, $message ) {
		if ( $exception !== null ) {
			$this->setExpectedException( $exception );
		}

		$q8 = Item::newEmpty();
		$q8->setId( 8 );

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $q8 );

		$validator = new EntityExistsValidator( $entityLookup );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );
	}

}