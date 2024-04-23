<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Exception;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\RestApi\Application\Serialization\EmptySitelinkException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;
use Wikibase\Repo\RestApi\Infrastructure\SiteLinkLookupSitelinkValidator;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\SiteLinkLookupSitelinkValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkLookupSitelinkValidatorTest extends TestCase {

	private SitelinkDeserializer $sitelinkDeserializer;
	private SiteLinkLookup $siteLinkLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->sitelinkDeserializer = $this->createStub( SitelinkDeserializer::class );
		$this->siteLinkLookup = $this->createStub( SiteLinkLookup::class );
	}

	public function testGivenValidSitelink_returnsNull(): void {
		$this->assertNull(
			$this->newSitelinkValidator()->validate( 'Q444', 'enwiki', [ 'title' => 'test-title', 'badges' => [ 'Q123' ] ] )
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

		$siteId = 'enwiki';
		$validationError = $this->newSitelinkValidator()->validate( 'Q444', $siteId, [] );
		$this->assertSame( $validationErrorCode, $validationError->getCode() );
		$this->assertEquals( [ SitelinkValidator::CONTEXT_SITE_ID => $siteId ], $validationError->getContext() );
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
		$this->assertNull( $sitelinkValidator->validate( 'Q444', 'enwiki', [ 'title' => 'test-title' ] ) );
		$this->assertSame( $deserializedSitelink, $sitelinkValidator->getValidatedSitelink() );
	}

	public function testGivenSitelinkConflict_throws(): void {
		$siteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		$pageTitle = 'test-title';
		$conflictItemId = 'Q20';

		$this->siteLinkLookup = $this->createStub( SiteLinkLookup::class );
		$this->siteLinkLookup->method( 'getItemIdForSiteLink' )->willReturn( new ItemId( $conflictItemId ) );

		$validationError = $this->newSitelinkValidator()->validate(
			'Q444',
			$siteId,
			[ 'title' => $pageTitle ]
		);

		$this->assertSame( SitelinkValidator::CODE_SITELINK_CONFLICT, $validationError->getCode() );
		$this->assertSame(
			$conflictItemId,
			$validationError->getContext()[SitelinkValidator::CONTEXT_CONFLICT_ITEM_ID]
		);
	}

	public function testGivenSelfConflict_returnsNull(): void {
		$siteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		$pageTitle = 'test-title';
		$inputItemId = 'Q20';
		$conflictItemId = 'Q20';

		$this->siteLinkLookup = $this->createStub( SiteLinkLookup::class );
		$this->siteLinkLookup->method( 'getItemIdForSiteLink' )->willReturn( new ItemId( $conflictItemId ) );

		$this->assertNull(
			$this->newSitelinkValidator()->validate(
				$inputItemId,
				$siteId,
				[ 'title' => $pageTitle ]
			)
		);
	}

	private function newSitelinkValidator(): SiteLinkLookupSitelinkValidator {
		return new SiteLinkLookupSitelinkValidator(
			$this->sitelinkDeserializer,
			$this->siteLinkLookup
		);
	}

}
