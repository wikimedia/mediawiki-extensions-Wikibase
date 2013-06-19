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


namespace Wikibase\Test;


use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\GeoCoordinateValue;
use DataValues\NumberValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use ValueParsers\ParserOptions;
use ValueValidators\Result;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Lib\WikibaseDataTypeBuilders;
use Wikibase\Property;

/**
 * Class WikibaseDataTypeBuildersTest
 * @package Wikibase\Test
 */
class WikibaseDataTypeBuildersTest extends \PHPUnit_Framework_TestCase {

	protected function newTypeFactory() {
		$entityIdParser = new EntityIdParser(
			new ParserOptions( array(
				EntityIdParser::OPT_PREFIX_MAP => array(
					'p' => Property::ENTITY_TYPE,
					'q' => Item::ENTITY_TYPE,
				)
			) )
		);

		$q8 = Item::newEmpty();
		$q8->setId( 8 );

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $q8 );

		$builders = new WikibaseDataTypeBuilders( $entityLookup, $entityIdParser );
		$dataTypeFactory = new DataTypeFactory( $builders->getDataTypeBuilders() );

		return $dataTypeFactory;
	}

	public function provideDataTypeValidation() {
		return array(
			//wikibase-item
			array( 'wikibase-item', 'q8', false, 'Expected EntityId, string supplied' ),
			array( 'wikibase-item', new StringValue( 'q8' ), false, 'Expected EntityId, StringValue supplied' ),
			array( 'wikibase-item', new EntityId( Item::ENTITY_TYPE, 8 ), true, 'existing entity' ),
			array( 'wikibase-item', new EntityId( Item::ENTITY_TYPE, 3 ), false, 'missing entity' ),

			//commonsMedia
			array( 'commonsMedia', 'Foo.jpg', false, 'StringValue expected, string supplied' ),
			array( 'commonsMedia', new NumberValue( 7 ), false, 'StringValue expected' ),
			array( 'commonsMedia', new StringValue( '' ), false, 'empty string should be invalid' ),
			array( 'commonsMedia', new StringValue( str_repeat('x', 250) . '.jpg' ), false, 'name too long' ),
			array( 'commonsMedia', new StringValue( 'Foo' ), false, 'no file extension' ),
			array( 'commonsMedia', new StringValue( 'Foo.jpg' ), true, 'this should be good' ),
			array( 'commonsMedia', new StringValue( 'Foo#bar.jpg' ), false, 'illegal character: hash' ),
			array( 'commonsMedia', new StringValue( 'Foo:bar.jpg' ), false, 'illegal character: colon' ),
			array( 'commonsMedia', new StringValue( 'Foo/bar.jpg' ), false, 'illegal character: slash' ),
			array( 'commonsMedia', new StringValue( 'Foo\bar.jpg' ), false, 'illegal character: backslash' ),
			array( 'commonsMedia', new StringValue( 'Äöü.jpg' ), true, 'Unicode support' ),
			array( 'commonsMedia', new StringValue( ' Foo.jpg ' ), false, 'Untrimmed input is forbidden' ),

			//string
			array( 'string', 'Foo', false, 'StringValue expected, string supplied' ),
			array( 'string', new NumberValue( 7 ), false, 'StringValue expected' ),
			array( 'string', new StringValue( '' ), false, 'empty string should be invalid' ),
			array( 'string', new StringValue( 'Foo' ), true, 'simple string' ),
			array( 'string', new StringValue( 'Äöü' ), true, 'Unicode support' ),
			array( 'string', new StringValue( str_repeat('x', 390) ), true, 'long, but not too long' ),
			array( 'string', new StringValue( str_repeat('x', 401) ), false, 'too long' ),
			array( 'string', new StringValue( ' Foo ' ), false, 'Untrimmed' ),

			//time
			array( 'time', 'Foo', false, 'TimeValue expected, string supplied' ),
			array( 'time', new NumberValue( 7 ), false, 'TimeValue expected' ),

			//time['calendar-model']
			array( 'time', new TimeValue( '+0000000000002013-06-06T11:22:33Z', 0, 0, 0, 0, '' ), false, 'calendar: empty string should be invalid' ),
			array( 'time', new TimeValue( '+0000000000002013-06-06T11:22:33Z', 0, 0, 0, 0, 'http://' . str_repeat('x', 256) ), false, 'calendar: too long' ),
			array( 'time', new TimeValue( '+0000000000002013-06-06T11:22:33Z', 0, 0, 0, 0, 'http://acme.com/calendar' ), true, 'calendar: URL' ),
			array( 'time', new TimeValue( '+0000000000002013-06-06T11:22:33Z', 0, 0, 0, 0, ' http://acme.com/calendar ' ), false, 'calendar: untrimmed' ),

			//time['time']
			//NOTE: The below will fail with a IllevalValueExcpetion once the TimeValue constructor enforces the time format.
			//      Once that is done, this test and the respective validator can and should both be removed.
			array( 'string', new TimeValue( '2013-06-06 11:22:33', 0, 0, 0, 0, 'http://acme.com/calendar' ), false, 'time: not ISO 8601' ),

			//TODO: must be an item reference
			//TODO: must be from a list of configured values

			//globe-coordinate
			array( 'globe-coordinate', 'Foo', false, 'GeoCoordinateValue expected, string supplied' ),
			array( 'globe-coordinate', new NumberValue( 7 ), false, 'GeoCoordinateValue expected' ),

			//globe-coordinate[globe]
			array( 'globe-coordinate', new GeoCoordinateValue( 0, 0, 0, 0, '' ), false, 'globe: empty string should be invalid' ),
			array( 'globe-coordinate', new GeoCoordinateValue( 0, 0, 0, 0, 'http://' . str_repeat('x', 256) ), false, 'globe: too long' ),
			array( 'globe-coordinate', new GeoCoordinateValue( 0, 0, 0, 0, 'http://acme.com/globe' ), true, 'globe: URL' ),
			array( 'globe-coordinate', new GeoCoordinateValue( 0, 0, 0, 0, ' http://acme.com/globe ' ), false, 'globe: untrimmed' ),
			//TODO: must be an item reference
			//TODO: must be from a list of configured values
		);
	}

	/**
	 * @dataProvider provideDataTypeValidation
	 */
	public function testDataTypeValidation( $typeId, $value, $expected, $message ) {
		$typeFactory = $this->newTypeFactory();
		$type = $typeFactory->getType( $typeId );

		$this->assertValidation( $expected, $type, $value, $message );
	}

	protected function assertValidation( $expected, DataType $type, $value, $message ) {
		$validators = $type->getValidators(); //TODO: there should probably be a DataType::validate() method.

		$result = Result::newSuccess();
		foreach ( $validators as $validator ) {
			$result = $validator->validate( $value );

			if ( !$result->isValid() ) {
				break;
			}
		}

		if ( $expected ) {
			$errors = $result->getErrors();
			if ( !empty( $errors ) ) {
				$this->fail( $message . "\n" . $errors[0]->getText() );
			}

			$this->assertEquals( $expected, $result->isValid(), $message );
		} else {
			$this->assertEquals( $expected, $result->isValid(), $message );
		}
	}
}