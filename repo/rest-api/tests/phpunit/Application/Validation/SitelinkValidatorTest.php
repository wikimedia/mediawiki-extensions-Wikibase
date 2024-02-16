<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use Exception;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\RestApi\Application\Serialization\EmptySitelinkException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SitelinkValidatorTest extends TestCase {

	private SitelinkDeserializer $sitelinkDeserializer;

	protected function setUp(): void {
		parent::setUp();
		$this->sitelinkDeserializer = $this->createStub( SitelinkDeserializer::class );
	}

	public function testGivenValidSitelink_returnsNull(): void {
		$this->assertNull(
			$this->newSitelinkValidator()->validate( 'Q123', [ 'title' => 'test-title', 'badges' => [ 'Q123' ] ] )
		);
	}

	/**
	 * @dataProvider provideInvalidSitelink
	 */
	public function testGivenSitelinkDeserializerThrows_returnsValidationErrors(
		Exception $deserializerException,
		string $validationErrorCode
	): void {
		$this->sitelinkDeserializer = $this->createStub( SitelinkDeserializer::class );
		$this->sitelinkDeserializer->method( 'deserialize' )->willThrowException( $deserializerException );

		$validationError = $this->newSitelinkValidator()->validate( 'Q123', [] );
		$this->assertSame( $validationErrorCode, $validationError->getCode() );
	}

	public function provideInvalidSitelink(): \Generator {
		yield 'missing title' => [ new MissingFieldException( 'title' ), SitelinkValidator::CODE_TITLE_MISSING ];

		yield 'title is empty' => [ new EmptySitelinkException( 'title', '' ), SitelinkValidator::CODE_EMPTY_TITLE ];

		yield 'invalid title' => [ new InvalidFieldException( 'title', 'invalid?' ), SitelinkValidator::CODE_INVALID_TITLE ];

		yield 'invalid title type' => [ new InvalidFieldTypeException( 'title' ), SitelinkValidator::CODE_INVALID_TITLE_TYPE ];

		yield 'title not found' => [ new SitelinkTargetNotFound(), SitelinkValidator::CODE_TITLE_NOT_FOUND ];
	}

	public function testGivenGetValidatedSitelinkCalledBeforeValidate_throws(): void {
		$this->expectException( LogicException::class );

		$this->newSitelinkValidator()->getValidatedSitelink();
	}

	public function testGivenGetValidatedSitelinkCalledAfterValidate_returnsSitelink(): void {
		$deserializedSitelink = $this->createStub( SiteLink::class );
		$this->sitelinkDeserializer = $this->createStub( SitelinkDeserializer::class );
		$this->sitelinkDeserializer->method( 'deserialize' )->willReturn( $deserializedSitelink );

		$sitelinkValidator = $this->newSitelinkValidator();
		$this->assertNull( $sitelinkValidator->validate( 'Q123', [] ) );
		$this->assertSame( $deserializedSitelink, $sitelinkValidator->getValidatedSitelink() );
	}

	private function newSitelinkValidator(): SitelinkValidator {
		return new SitelinkValidator( $this->sitelinkDeserializer );
	}

}
