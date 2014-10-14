<?php

namespace Wikibase\Validators\Test;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Validators\CompositeFingerprintValidator;

/**
 * @covers Wikibase\Validators\CompositeFingerprintValidator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CompositeFingerprintValidatorTest extends \PHPUnit_Framework_TestCase {

	public function validFingerprintProvider() {
		$success = Result::newSuccess();
		$failure = Result::newError( array( Error::newError( 'Foo!' ) ) );

		$good = $this->getMock( 'Wikibase\Validators\FingerprintValidator' );
		$good->expects( $this->any() )
			->method( 'validateFingerprint' )
			->will( $this->returnValue( $success ) );

		$bad = $this->getMock( 'Wikibase\Validators\FingerprintValidator' );
		$bad->expects( $this->any() )
			->method( 'validateFingerprint' )
			->will( $this->returnValue( $failure ) );

		return array(
			array( array( $good, $bad ), false ),
			array( array( $bad, $good ), false ),
			array( array( $good, $good ), true ),
			array( array(), true ),
		);
	}

	/**
	 * @dataProvider validFingerprintProvider
	 */
	public function testValidateFingerprint( $validators, $expected ) {
		$fingerprint = new Fingerprint(
			new TermList( array() ),
			new TermList( array() ),
			new AliasGroupList( array() )
		);

		$validator = new CompositeFingerprintValidator( $validators );
		$result = $validator->validateFingerprint( $fingerprint, new ItemId( 'Q99' ) );

		$this->assertEquals( $expected, $result->isValid(), 'isValid' );
	}

}
