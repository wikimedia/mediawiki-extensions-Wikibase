<?php

namespace Wikibase\Repo\Tests\Validators;

use PHPUnit4And6Compat;
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
	use PHPUnit4And6Compat;

	public function provideValidateEntity() {
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
			[ [], true, 0 ],
			[ [ $good ], true, 0 ],
			[ [ $bad ], true, 1 ],
			[ [ $good, $bad ], true, 1 ],
			[ [ $bad, $good ], true, 1 ],
			[ [ $good, $good ], true, 0 ],
			[ [ $bad, $bad ], true, 1 ],
			[ [ $bad, $bad ], false, 2 ],
		];
	}

	/**
	 * @dataProvider provideValidateEntity
	 */
	public function testValidateEntity( $validators, $failFast, $expectedErrorCount ) {
		$entity = new Item();

		$validator = new CompositeEntityValidator( $validators, $failFast );
		$result = $validator->validateEntity( $entity );

		$this->assertSame( $expectedErrorCount === 0, $result->isValid() );
		$this->assertCount( $expectedErrorCount, $result->getErrors() );
	}

}
