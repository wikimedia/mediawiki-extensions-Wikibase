<?php

namespace Wikibase\Validators\Test;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Validators\CompositeEntityValidator;

/**
 * @covers Wikibase\Validators\CompositeEntityValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CompositeEntityValidatorTest extends \PHPUnit_Framework_TestCase {

	public function validEntityProvider() {
		$success = Result::newSuccess();
		$failure = Result::newError( array( Error::newError( 'Foo!' ) ) );

		$good = $this->getMock( 'Wikibase\Validators\EntityValidator' );
		$good->expects( $this->any() )
			->method( 'validateEntity' )
			->will( $this->returnValue( $success ) );

		$bad = $this->getMock( 'Wikibase\Validators\EntityValidator' );
		$bad->expects( $this->any() )
			->method( 'validateEntity' )
			->will( $this->returnValue( $failure ) );

		return array(
			array( array( $good, $bad ), false ),
			array( array( $bad, $good ), false ),
			array( array( $good, $good ), true ),
			array( array(), true ),
		);
	}

	/**
	 * @dataProvider validEntityProvider
	 */
	public function testValidateEntity( $validators, $expected ) {
		$entity = new Item();

		$validator = new CompositeEntityValidator( $validators );
		$result = $validator->validateEntity( $entity );

		$this->assertEquals( $expected, $result->isValid(), 'isValid' );
	}

}
