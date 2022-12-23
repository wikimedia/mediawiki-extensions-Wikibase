<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Validators;

use MediaWiki\Languages\LanguageNameUtils;
use Wikibase\Repo\Validators\NotMulValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\NotMulValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch <mail@mariushoch.de>
 */
class NotMulValidatorTest extends \PHPUnit\Framework\TestCase {

	private function newLanguageNameUtils(): LanguageNameUtils {
		$languageNameUtils = $this->createMock( LanguageNameUtils::class );
		$languageNameUtils->method( 'getLanguageName' )
			->with( 'mul', $this->isType( 'string' ) )
			->willReturn( 'Mul name' );

		return $languageNameUtils;
	}

	public function testValidate() {
		$validator = new NotMulValidator( $this->newLanguageNameUtils() );
		$result = $validator->validate( 'not-mul' );
		$this->assertTrue( $result->isValid() );
	}

	public function testValidate_error() {
		$validator = new NotMulValidator( $this->newLanguageNameUtils() );
		$result = $validator->validate( 'mul' );
		$this->assertFalse( $result->isValid() );

		$errors = $result->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertSame( 'no-mul-descriptions', $errors[0]->getCode() );
		$this->assertSame( [ 'Mul name' ], $errors[0]->getParameters() );

		$localizer = new ValidatorErrorLocalizer();
		$msg = $localizer->getErrorMessage( $errors[0] );
		$this->assertTrue( $msg->exists(), $msg->getKey() );
	}

}
