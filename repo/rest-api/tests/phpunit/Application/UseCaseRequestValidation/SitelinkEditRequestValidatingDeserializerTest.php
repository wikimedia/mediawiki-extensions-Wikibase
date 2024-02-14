<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\SitelinkEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\SitelinkEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\SitelinkEditRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SitelinkEditRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsSitelink(): void {
		$request = $this->createStub( SitelinkEditRequest::class );
		$request->method( 'getSiteId' )->willReturn( 'enwiki' );
		$request->method( 'getSitelink' )->willReturn( [
			'title' => 'Potato',
			'badges' => [
				'Q1234',
			],
		] );

		$this->assertEquals(
			new SiteLink( 'enwiki', 'Potato', [ new ItemId( 'Q1234' ) ] ),
			$this->newValidatingDeserializer( $this->createStub( SitelinkValidator::class ) )
				->validateAndDeserialize( $request )
		);
	}

	/**
	 * @dataProvider sitelinkValidationErrorProvider
	 */
	public function testGivenInvalidRequest_throws(
		array $siteLink,
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage
	): void {
		$request = $this->createStub( SitelinkEditRequest::class );
		$request->method( 'getSiteId' )->willReturn( 'enwiki' );
		$request->method( 'getSitelink' )->willReturn( $siteLink );

		$sitelinkValidator = $this->createStub( SitelinkValidator::class );
		$sitelinkValidator->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newValidatingDeserializer( $sitelinkValidator )->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( $expectedErrorCode, $useCaseEx->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $useCaseEx->getErrorMessage() );
		}
	}

	public function sitelinkValidationErrorProvider(): \Generator {
		yield 'missing title' => [
			[ 'badges' => [ 'Q1234' ] ],
			new ValidationError( SitelinkValidator::CODE_TITLE_MISSING ),
			UseCaseError::SITELINK_DATA_MISSING_TITLE,
			'Mandatory sitelink title missing',
		];
		yield 'title is empty' => [
			[ 'title' => '', 'badges' => [ 'Q1234' ] ],
			new ValidationError( SitelinkValidator::CODE_EMPTY_TITLE ),
			UseCaseError::TITLE_FIELD_EMPTY,
			'Title must not be empty',
		];
		yield 'invalid title' => [
			[ 'title' => 'test-title?', 'badges' => [ 'Q1234' ] ],
			new ValidationError( SitelinkValidator::CODE_INVALID_TITLE ),
			UseCaseError::INVALID_TITLE_FIELD,
			'Not a valid input for title field',
		];
	}

	private function newValidatingDeserializer( SitelinkValidator $sitelinkValidator ): SitelinkEditRequestValidatingDeserializer {
		return new SitelinkEditRequestValidatingDeserializer(
			$sitelinkValidator,
			new SitelinkDeserializer()
		);
	}
}
