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
		$source = 'some-source';

		$error = ( new ItemIdValidator() )->validate( $invalidId, $source );

		$this->assertSame( $source, $error->getSource() );
		$this->assertSame( $invalidId, $error->getValue() );
	}

	public function testWithValidId(): void {
		$this->assertNull(
			( new ItemIdValidator() )->validate( 'Q123', 'some-source' )
		);
	}

}
