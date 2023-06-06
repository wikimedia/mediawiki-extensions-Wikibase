<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyIdValidatorTest extends TestCase {

	public function testWithInvalidId(): void {
		$invalidId = 'X123';

		$error = ( new PropertyIdValidator() )->validate( $invalidId );

		$this->assertSame( PropertyIdValidator::CODE_INVALID, $error->getCode() );
		$this->assertSame( $invalidId, $error->getContext()[PropertyIdValidator::CONTEXT_VALUE] );
	}

	public function testWithValidId(): void {
		$this->assertNull(
			( new PropertyIdValidator() )->validate( 'P123' )
		);
	}

}
