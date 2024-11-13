<?php

namespace Wikibase\Repo\Tests\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\Validators\CompositeEntityValidator;
use Wikibase\Repo\Validators\EntityValidator;

/**
 * @covers \Wikibase\Repo\Validators\CompositeEntityValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseContent
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class CompositeEntityValidatorTest extends \PHPUnit\Framework\TestCase {

	public static function validEntityProvider() {
		$success = Result::newSuccess();
		$failure = Result::newError( [ Error::newError( 'Foo!' ) ] );

		return [
			[ [ $success, $failure ], false ],
			[ [ $failure, $success ], false ],
			[ [ $success, $success ], true ],
			[ [], true ],
		];
	}

	/**
	 * @dataProvider validEntityProvider
	 */
	public function testValidateEntity( $validatorReturns, $expected ) {
		$validators = array_map( function ( $validatorReturn ) {
			$validator = $this->createMock( EntityValidator::class );
			$validator->method( 'validateEntity' )
				->willReturn( $validatorReturn );
			return $validator;
		}, $validatorReturns );

		$entity = new Item();

		$validator = new CompositeEntityValidator( $validators );
		$result = $validator->validateEntity( $entity );

		$this->assertEquals( $expected, $result->isValid(), 'isValid' );
	}

}
