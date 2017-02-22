<?php

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Validators\NullValidator;

/**
 * @covers Wikibase\Repo\Validators\NullValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class NullValidatorTest extends \PHPUnit_Framework_TestCase {

	public function provideValidate() {
		$itemId = new ItemId( 'Q8' );

		return [
			'entity' => [ new EntityIdValue( $itemId )],
			'itemId' => [ $itemId ],
			'propertyId' => [ new PropertyId( 'P8' ) ],
			'string' => 'Hey!',
			'int' => 12345
		];
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $value ) {
		$validator = new NullValidator();
		$result = $validator->validate( $value );

		$this->assertTrue( $result->isValid() );
	}

}
