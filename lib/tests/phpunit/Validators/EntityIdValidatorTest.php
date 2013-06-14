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
use Wikibase\Item;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Property;
use Wikibase\Validators\EntityIdValidator;

/**
 * Class EntityIdValidatorTest
 * @covers Wikibase\Validators\EntityIdValidator
 * @package Wikibase\Test\Validators
 */
class EntityIdValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		return array(
			array( null, 'q3', true, "matching all types" ),
			array( null, 'q', false, "malformed id" ),
			array( null, 'x3', false, "bad prefix" ),
			array( null, 'q3...', false, "extra stuff" ),
			array( array(), 'q3', false, "matching no type" ),
			array( array( Item::ENTITY_TYPE ), 'q3', true, "an item id" ),
			array( array( Item::ENTITY_TYPE ), 'p3', false, "not an item id" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $types, $value, $expected, $message ) {
		//XXX: the ParserOptions stuff seems cumbersome, what is it good for?
		$parser = new EntityIdParser( new ParserOptions( array(
			EntityIdParser::OPT_PREFIX_MAP => array(
				'q' => Item::ENTITY_TYPE,
				'p' => Property::ENTITY_TYPE,
			)
		) ) );

		$validator = new EntityIdValidator( $parser, $types );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );
	}

}