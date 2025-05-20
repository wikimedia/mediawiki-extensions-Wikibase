<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases\PatchSitelinks;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchSitelinks\PatchedSitelinksValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\SiteIdValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\SitelinksValidator;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\SitelinkTargetNotFound;
use Wikibase\Repo\Domains\Crud\Domain\Services\SitelinkTargetTitleResolver;
use Wikibase\Repo\Domains\Crud\Infrastructure\SiteLinkLookupSitelinkValidator;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\DummyItemRevisionMetaDataRetriever;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\SameTitleSitelinkTargetResolver;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\PatchSitelinks\PatchedSitelinksValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedSitelinksValidatorTest extends TestCase {

	private const ALLOWED_BADGES = [ 'Q432' ];
	private const SITELINK_ITEM_ID = 'Q123';

	private SiteLinkLookup $siteLinkLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->siteLinkLookup = $this->createStub( SiteLinkLookup::class );
	}

	/**
	 * @dataProvider validSitelinksProvider
	 */
	public function testWithValidSitelinks( array $sitelinksSerialization, SiteLinkList $expectedResult ): void {
		$this->assertEquals(
			$expectedResult,
			$this->newValidator( new SameTitleSitelinkTargetResolver() )->validateAndDeserialize(
				self::SITELINK_ITEM_ID,
				[],
				$sitelinksSerialization
			)
		);
	}

	public static function validSitelinksProvider(): Generator {
		yield 'no sitelinks' => [
			[],
			new SiteLinkList(),
		];

		$validBadgeItemId = new ItemId( self::ALLOWED_BADGES[0] );
		yield 'valid sitelinks' => [
			[
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] => [
					'title' => 'test-title',
					'badges' => [ "$validBadgeItemId" ],
				],
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1] => [
					'title' => 'another-test-title',
					'badges' => [ "$validBadgeItemId" ],
				],
			],
			new SiteLinkList( [
				new SiteLink(
					TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0],
					'test-title',
					[ $validBadgeItemId ]
				),
				new SiteLink(
					TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1],
					'another-test-title',
					[ $validBadgeItemId ]
				),
			] ),
		];
	}

	/**
	 * @dataProvider invalidSitelinksProvider
	 */
	public function testWithInvalidSitelinks( array $serialization, UseCaseError $expectedError ): void {
		try {
			$this->newValidator( new SameTitleSitelinkTargetResolver() )->validateAndDeserialize(
				self::SITELINK_ITEM_ID,
				[],
				$serialization
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertEquals( $expectedError, $error );
		}
	}

	public static function invalidSitelinksProvider(): Generator {
		$validSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		$badgeItemId = new ItemId( self::ALLOWED_BADGES[ 0 ] );

		$invalidSitelinks = [ 'invalid-sitelinks' ];
		yield 'sitelinks not associative' => [
			$invalidSitelinks,
			UseCaseError::newPatchResultInvalidValue( '', $invalidSitelinks ),
		];

		yield 'invalid sitelink type' => [
			[ $validSiteId => 'invalid-sitelink' ],
			UseCaseError::newPatchResultInvalidValue( "/$validSiteId", 'invalid-sitelink' ),
		];

		yield 'invalid site id' => [
			[ 'bad-site-id' => [ 'title' => 'test_title' ] ],
			UseCaseError::newPatchResultInvalidKey( '', 'bad-site-id' ),
		];

		yield 'missing title' => [
			[ $validSiteId => [ 'badges' => [ $badgeItemId ] ] ],
			UseCaseError::newMissingFieldInPatchResult( "/{$validSiteId}", 'title' ),
		];

		yield 'empty title' => [
			[ $validSiteId => [ 'title' => '' ] ],
			UseCaseError::newPatchResultInvalidValue( "/$validSiteId/title", '' ),
		];

		$invalidTitle = 'invalid??%00';
		yield 'invalid title' => [
			[ $validSiteId => [ 'title' => $invalidTitle ] ],
			UseCaseError::newPatchResultInvalidValue( "/$validSiteId/title", $invalidTitle ),
		];

		yield 'invalid badges format' => [
			[ $validSiteId => [ 'title' => 'test_title', 'badges' => $badgeItemId ] ],
			UseCaseError::newPatchResultInvalidValue( "/$validSiteId/badges", $badgeItemId ),
		];

		$invalidBadge = 'not-an-item-id';
		yield 'invalid badge' => [
			[ $validSiteId => [ 'title' => 'test_title', 'badges' => [ $invalidBadge ] ] ],
			UseCaseError::newPatchResultInvalidValue( "/$validSiteId/badges/0", $invalidBadge ),
		];

		$itemIdNotBadge = 'Q99';
		yield 'item is not a badge' => [
			[ $validSiteId => [ 'title' => 'test_title', 'badges' => [ $itemIdNotBadge ] ] ],
			UseCaseError::newPatchResultInvalidValue( "/$validSiteId/badges/0", $itemIdNotBadge ),
		];
	}

	public function testTitleDoesNotExist_throws(): void {
		$validSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];

		$sitelinkTargetTitleResolver = $this->createStub( SitelinkTargetTitleResolver::class );
		$sitelinkTargetTitleResolver->method( 'resolveTitle' )->willThrowException(
			$this->createStub( SitelinkTargetNotFound::class )
		);

		$nonExistingTitle = 'non-existing-title';
		try {
			$this->newValidator( $sitelinkTargetTitleResolver )->validateAndDeserialize(
				self::SITELINK_ITEM_ID,
				[],
				[ $validSiteId => [ 'title' => $nonExistingTitle ] ]
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newPatchResultReferencedResourceNotFound( "/$validSiteId/title", $nonExistingTitle ), $e );
		}
	}

	public function testSitelinkConflict_throws(): void {
		$validSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		$conflictingItemId = 'Q987';
		$pageTitle = 'test-title';

		$this->siteLinkLookup = $this->createStub( SiteLinkLookup::class );
		$this->siteLinkLookup->method( 'getItemIdForSiteLink' )->willReturn( new ItemId( $conflictingItemId ) );

		$expectedUseCaseError = UseCaseError::newDataPolicyViolation(
			UseCaseError::POLICY_VIOLATION_SITELINK_CONFLICT,
			[
				UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => $conflictingItemId,
				UseCaseError::CONTEXT_SITE_ID => $validSiteId,
			]
		);

		try {
			$this->newValidator( new SameTitleSitelinkTargetResolver() )->validateAndDeserialize(
				self::SITELINK_ITEM_ID,
				[],
				[ $validSiteId => [ 'title' => $pageTitle ] ]
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedUseCaseError, $e );
		}
	}

	public function testSitelinkUrlModification_throws(): void {
		$validSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		$title = 'test_title';

		try {
			$this->newValidator( new SameTitleSitelinkTargetResolver() )->validateAndDeserialize(
				self::SITELINK_ITEM_ID,
				[ $validSiteId => [ 'title' => $title, 'url' => 'https://en.wikipedia.org/wiki/.example', 'badges' => [] ] ],
				[ $validSiteId => [ 'title' => $title, 'url' => 'https://en.wikipedia.org/wiki/Example.com' ] ]
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertEquals( UseCaseError::newPatchResultModifiedReadOnlyValue( "/$validSiteId/url" ), $error );
		}
	}

	/**
	 * @dataProvider modifiedSitelinksProvider
	 */
	public function testValidatesOnlyModifiedSitelinks(
		array $originalSitelinks,
		array $patchedSitelinks,
		array $expectedValidatedSitelinkSites
	): void {
		$this->siteLinkLookup = $this->createMock( SiteLinkLookup::class );
		$this->siteLinkLookup->expects( $this->exactly( count( $expectedValidatedSitelinkSites ) ) )
			->method( 'getItemIdForSiteLink' )
			->willReturnCallback( function ( SiteLink $sitelink ) use ( $expectedValidatedSitelinkSites ): void {
				$this->assertContains( $sitelink->getSiteId(), $expectedValidatedSitelinkSites );
			} );

		$this->assertInstanceOf(
			SiteLinkList::class,
			$this->newValidator( new SameTitleSitelinkTargetResolver() )
				->validateAndDeserialize( 'Q13', $originalSitelinks, $patchedSitelinks )
		);
	}

	public static function modifiedSitelinksProvider(): Generator {
		$originalSitelinks = [
			TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] => [ 'title' => 'Potato', 'badges' => [] ],
			TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1] => [ 'title' => 'Kartoffel', 'badges' => [] ],
		];

		yield 'new sitelink' => [
			$originalSitelinks,
			[
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] => [ 'title' => 'Potato' ],
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1] => [ 'title' => 'Kartoffel' ],
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[2] => [ 'title' => 'بطاطا' ],
			],
			[ TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[2] ],
		];

		yield 'modified sitelink title' => [
			$originalSitelinks,
			[
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] => [ 'title' => 'Potato' ],
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1] => [ 'title' => 'Erdapfel' ],
			],
			[ TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1] ],
		];

		yield 'modified sitelink badges' => [
			$originalSitelinks,
			[
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] => [ 'title' => 'Potato', 'badges' => self::ALLOWED_BADGES ],
				TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[1] => [ 'title' => 'Kartoffel' ],
			],
			[ TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0] ],
		];
	}

	private function newValidator( SitelinkTargetTitleResolver $sitelinkTargetTitleResolver ): PatchedSitelinksValidator {
		return new PatchedSitelinksValidator(
			new SitelinksValidator(
				new SiteIdValidator( TestValidatingRequestDeserializer::ALLOWED_SITE_IDS ),
				new SiteLinkLookupSitelinkValidator(
					new SitelinkDeserializer(
						'/\?/',
						self::ALLOWED_BADGES,
						$sitelinkTargetTitleResolver,
						new DummyItemRevisionMetaDataRetriever()
					),
					$this->siteLinkLookup
				)
			)
		);
	}

}
