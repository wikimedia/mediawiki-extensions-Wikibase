<?php

namespace Wikibase\Test\Validators;

use ValueParsers\ParserOptions;
use Wikibase\Item;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Property;
use Wikibase\Validators\EntityIdValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @covers Wikibase\Validators\EntityIdValidator
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL 2+
 * @author Daniel Kinzler
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
	 * @dataProvider provideValidate
	 */
	public function testValidate( $types, $value, $expected, $message ) {
		$parser = new EntityIdParser( new ParserOptions() );

		$validator = new EntityIdValidator( $parser, $types );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );

		if ( !$expected ) {
			$errors = $result->getErrors();
			$this->assertCount( 1, $errors, $message );
			$this->assertTrue( in_array( $errors[0]->getCode(), array( 'bad-entity-id', 'bad-entity-type' ) ), $message . "\n" . $errors[0]->getCode() );

			$localizer = new ValidatorErrorLocalizer( );
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}