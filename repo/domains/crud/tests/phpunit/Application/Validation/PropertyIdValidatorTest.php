<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Validation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyIdValidator;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Validation\PropertyIdValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyIdValidatorTest extends TestCase {

	public function testWithInvalidId(): void {
		$error = ( new PropertyIdValidator() )->validate( 'X123' );

		$this->assertSame( PropertyIdValidator::CODE_INVALID, $error->getCode() );
	}

	public function testWithValidId(): void {
		$this->assertNull(
			( new PropertyIdValidator() )->validate( 'P123' )
		);
	}

}
