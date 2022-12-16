<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Validation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Validation\ItemIdValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemIdValidatorTest extends TestCase {

	public function testWithInvalidId(): void {
		$invalidId = 'X123';

		$error = ( new ItemIdValidator() )->validate( $invalidId );

		$this->assertSame( ItemIdValidator::CODE_INVALID, $error->getCode() );
		$this->assertSame( $invalidId, $error->getContext()[ItemIdValidator::CONTEXT_VALUE] );
	}

	public function testWithValidId(): void {
		$this->assertNull(
			( new ItemIdValidator() )->validate( 'Q123' )
		);
	}

}
