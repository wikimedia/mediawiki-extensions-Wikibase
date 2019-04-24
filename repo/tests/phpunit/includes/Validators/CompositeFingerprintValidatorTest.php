<?php

namespace Wikibase\Repo\Tests\Validators;

use PHPUnit4And6Compat;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Validators\CompositeFingerprintValidator;
use Wikibase\Repo\Validators\FingerprintValidator;

/**
 * @covers \Wikibase\Repo\Validators\CompositeFingerprintValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseContent
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class CompositeFingerprintValidatorTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function validFingerprintProvider() {
		$success = Result::newSuccess();
		$failure = Result::newError( [ Error::newError( 'Foo!' ) ] );

		$good = $this->getMock( FingerprintValidator::class );
		$good->expects( $this->any() )
			->method( 'validateFingerprint' )
			->will( $this->returnValue( $success ) );

		$bad = $this->getMock( FingerprintValidator::class );
		$bad->expects( $this->any() )
			->method( 'validateFingerprint' )
			->will( $this->returnValue( $failure ) );

		return [
			[ [ $good, $bad ], false ],
			[ [ $bad, $good ], false ],
			[ [ $good, $good ], true ],
			[ [], true ],
		];
	}

	/**
	 * @dataProvider validFingerprintProvider
	 */
	public function testValidateFingerprint( $validators, $expected ) {
		$terms = new TermList();
		$entityId = new ItemId( 'Q1' );

		$validator = new CompositeFingerprintValidator( $validators );
		$result = $validator->validateFingerprint( $terms, $terms, $entityId );

		$this->assertEquals( $expected, $result->isValid(), 'isValid' );
	}

}
