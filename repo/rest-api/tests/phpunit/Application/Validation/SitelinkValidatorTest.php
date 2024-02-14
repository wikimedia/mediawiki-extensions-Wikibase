<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SitelinkValidatorTest extends TestCase {

	public function testGivenValidSitelink_returnsNull(): void {
		$this->assertNull(
			( new SitelinkValidator( '/\?/' ) )->validate( [ 'title' => 'test-title', 'badges' => [ 'Q123' ] ] )
		);
	}

	/**
	 * @dataProvider provideInvalidSitelink
	 */
	public function testGivenInvalidSitelink_returnsValidationErrors( array $sitelink, string $errorCode ): void {
		$validationError = ( new SitelinkValidator( '/\?/' ) )->validate( $sitelink );
		$this->assertSame( $errorCode, $validationError->getCode() );
	}

	public function provideInvalidSitelink(): \Generator {
		yield 'missing title' => [ [ 'badges' => [ 'Q789' ] ], SitelinkValidator::CODE_TITLE_MISSING ];

		yield 'title is empty' => [ [ 'title' => '', 'badges' => [ 'Q789' ] ], SitelinkValidator::CODE_EMPTY_TITLE ];

		yield 'invalid title' => [ [ 'title' => 'invalid title?', 'badges' => [ 'Q789' ] ], SitelinkValidator::CODE_INVALID_TITLE ];
	}
}
