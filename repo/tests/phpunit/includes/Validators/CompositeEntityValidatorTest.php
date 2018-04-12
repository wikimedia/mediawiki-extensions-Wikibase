<?php

namespace Wikibase\Repo\Tests\Validators;

use PHPUnit4And6Compat;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\Validators\CompositeEntityValidator;
use Wikibase\Repo\Validators\EntityValidator;

/**
 * @covers Wikibase\Repo\Validators\CompositeEntityValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseContent
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class CompositeEntityValidatorTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function validEntityProvider() {
		$success = Result::newSuccess();
		$failure = Result::newError( [ Error::newError( 'Foo!' ) ] );

		$good = $this->getMock( EntityValidator::class );
		$good->expects( $this->any() )
			->method( 'validateEntity' )
			->will( $this->returnValue( $success ) );

		$bad = $this->getMock( EntityValidator::class );
		$bad->expects( $this->any() )
			->method( 'validateEntity' )
			->will( $this->returnValue( $failure ) );

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
