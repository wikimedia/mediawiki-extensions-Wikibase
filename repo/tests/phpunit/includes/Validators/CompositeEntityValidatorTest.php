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

	public function validEntityProvider() {
		$success = Result::newSuccess();
		$failure = Result::newError( [ Error::newError( 'Foo!' ) ] );

		$good = $this->createMock( EntityValidator::class );
		$good->method( 'validateEntity' )
			->willReturn( $success );

		$bad = $this->createMock( EntityValidator::class );
		$bad->method( 'validateEntity' )
			->willReturn( $failure );

		return [
			[ [ $good, $bad ], false ],
			[ [ $bad, $good ], false ],
			[ [ $good, $good ], true ],
			[ [], true ],
		];
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
