<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchSitelinks;

use Generator;
use Monolog\Test\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\PatchedSitelinksValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;
use Wikibase\Repo\RestApi\Domain\Services\SitelinkTargetTitleResolver;
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

	/**
	 * @dataProvider validSitelinksProvider
	 */
	public function testWithValidSitelinks( array $sitelinksSerialization, SiteLinkList $expectedResult ): void {
		$this->assertEquals(
			$expectedResult,
			$this->newValidator( new SameTitleSitelinkTargetResolver() )->validateAndDeserialize( $sitelinksSerialization )
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
			$this->newValidator( new SameTitleSitelinkTargetResolver() )->validateAndDeserialize( $serialization );

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
				"Badges for site '$validSiteId' is not a list in patched sitelinks",
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
				[ $validSiteId => [ 'title' => 'non-existing-title' ] ]
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedUseCaseError, $e );
		}
	}

	private function newValidator( SitelinkTargetTitleResolver $sitelinkTargetTitleResolver ): PatchedSitelinksValidator {
		return new PatchedSitelinksValidator(
			new SiteIdValidator( TestValidatingRequestDeserializer::ALLOWED_SITE_IDS ),
			new SitelinkDeserializer(
				'/\?/',
				self::ALLOWED_BADGES,
				$sitelinkTargetTitleResolver,
				new DummyItemRevisionMetaDataRetriever()
			)
		);
	}

}
