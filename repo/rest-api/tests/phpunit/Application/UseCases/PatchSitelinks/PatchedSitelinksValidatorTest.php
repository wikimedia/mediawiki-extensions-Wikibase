<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchSitelinks;

use Generator;
use Monolog\Test\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchedSitelinksValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinksValidator;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;
use Wikibase\Repo\RestApi\Domain\Services\SitelinkTargetTitleResolver;
use Wikibase\Repo\RestApi\Infrastructure\SiteLinkLookupSitelinkValidator;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\DummyItemRevisionMetaDataRetriever;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\SameTitleSitelinkTargetResolver;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchedSitelinksValidator
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

		yield 'invalid site id' => [
			[ 'bad-site-id' => [ 'title' => 'test_title' ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_INVALID_SITE_ID,
				"Not a valid site ID 'bad-site-id' in patched sitelinks",
				[ UseCaseError::CONTEXT_SITE_ID => 'bad-site-id' ]
			),
		];

		yield 'missing title' => [
			[ $validSiteId => [ 'badges' => [ $badgeItemId ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_MISSING_TITLE,
				"No sitelink title provided for site '$validSiteId' in patched sitelinks",
				[ UseCaseError::CONTEXT_SITE_ID => $validSiteId ]
			),
		];

		yield 'empty title' => [
			[ $validSiteId => [ 'title' => '' ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_TITLE_EMPTY,
				"Sitelink cannot be empty for site '$validSiteId' in patched sitelinks",
				[ UseCaseError::CONTEXT_SITE_ID => $validSiteId ]
			),
		];

		$invalidTitle = 'invalid??%00';
		yield 'invalid title' => [
			[ $validSiteId => [ 'title' => $invalidTitle ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_INVALID_TITLE,
				"Invalid sitelink title '$invalidTitle' for site '$validSiteId' in patched sitelinks",
				[
					UseCaseError::CONTEXT_SITE_ID => $validSiteId,
					UseCaseError::CONTEXT_TITLE => $invalidTitle,
				]
			),
		];

		yield 'invalid badges format' => [
			[ $validSiteId => [ 'title' => 'test_title', 'badges' => $badgeItemId ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_BADGES_FORMAT,
				"Badges value for site '$validSiteId' is not a list in patched sitelinks",
				[
					UseCaseError::CONTEXT_SITE_ID => $validSiteId,
					UseCaseError::CONTEXT_BADGES => $badgeItemId,
				]
			),
		];

		$invalidBadge = 'not-an-item-id';
		yield 'invalid badge' => [
			[ $validSiteId => [ 'title' => 'test_title', 'badges' => [ $invalidBadge ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_INVALID_BADGE,
				"Incorrect patched sitelinks. Badge value '$invalidBadge' for site '$validSiteId' is not an item ID",
				[
					UseCaseError::CONTEXT_SITE_ID => $validSiteId,
					UseCaseError::CONTEXT_BADGE => $invalidBadge,
				]
			),
		];

		$itemIdNotBadge = new ItemId( 'Q99' );
		yield 'item is not a badge' => [
			[ $validSiteId => [ 'title' => 'test_title', 'badges' => [ "$itemIdNotBadge" ] ] ],
			new UseCaseError(
				UseCaseError::PATCHED_SITELINK_ITEM_NOT_A_BADGE,
				"Incorrect patched sitelinks. Item 'Q99' used for site '$validSiteId' is not allowed as a badge",
				[
					UseCaseError::CONTEXT_SITE_ID => $validSiteId,
					UseCaseError::CONTEXT_BADGE => 'Q99',
				]
			),
		];
	}

	public function testTitleDoesNotExist_throws(): void {
		$validSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];

		$sitelinkTargetTitleResolver = $this->createStub( SitelinkTargetTitleResolver::class );
		$sitelinkTargetTitleResolver->method( 'resolveTitle' )->willThrowException(
			$this->createStub( SitelinkTargetNotFound::class )
		);

		$expectedUseCaseError = new UseCaseError(
			UseCaseError::PATCHED_SITELINK_TITLE_DOES_NOT_EXIST,
			"Incorrect patched sitelinks. Page with title 'non-existing-title' does not exist on site '$validSiteId'",
			[
				UseCaseError::CONTEXT_SITE_ID => $validSiteId,
				UseCaseError::CONTEXT_TITLE => 'non-existing-title',
			]
		);

		try {
			$this->newValidator( $sitelinkTargetTitleResolver )->validateAndDeserialize(
				self::SITELINK_ITEM_ID,
				[],
				[ $validSiteId => [ 'title' => 'non-existing-title' ] ]
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedUseCaseError, $e );
		}
	}

	public function testSitelinkConflict_throws(): void {
		$validSiteId = TestValidatingRequestDeserializer::ALLOWED_SITE_IDS[0];
		$matchingItemId = 'Q987';
		$pageTitle = 'test-title';

		$this->siteLinkLookup = $this->createStub( SiteLinkLookup::class );
		$this->siteLinkLookup->method( 'getItemIdForSiteLink' )->willReturn( new ItemId( $matchingItemId ) );

		$expectedUseCaseError = new UseCaseError(
			UseCaseError::PATCHED_SITELINK_CONFLICT,
			"Site '$validSiteId' is already being used on '$matchingItemId'",
			[
				UseCaseError::CONTEXT_MATCHING_ITEM_ID => $matchingItemId,
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

		$expectedError = new UseCaseError(
			UseCaseError::PATCHED_SITELINK_URL_NOT_MODIFIABLE,
			'URL of sitelink cannot be modified',
			[ UseCaseError::CONTEXT_SITE_ID => $validSiteId ]
		);

		try {
			$this->newValidator( new SameTitleSitelinkTargetResolver() )->validateAndDeserialize(
				self::SITELINK_ITEM_ID,
				[ $validSiteId => [ 'title' => $title, 'url' => 'https://en.wikipedia.org/wiki/.example', 'badges' => [] ] ],
				[ $validSiteId => [ 'title' => $title, 'url' => 'https://en.wikipedia.org/wiki/Example.com' ] ]
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertEquals( $expectedError, $error );
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

	public function modifiedSitelinksProvider(): Generator {
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
