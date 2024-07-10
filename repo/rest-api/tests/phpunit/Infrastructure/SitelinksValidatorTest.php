<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\Store\HashSiteLinkStore;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinksValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\SiteLinkLookupSitelinkValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\SitelinksValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SitelinksValidatorTest extends TestCase {

	private const VALID_SITES = [ 'enwiki', 'wikidatawiki' ];

	private SitelinkValidator $sitelinkValidator;

	protected function setUp(): void {
		parent::setUp();

		$sitelinkDeserializer = $this->createStub( SitelinkDeserializer::class );
		$sitelinkDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( $siteId, $serialization ) => new SiteLink( $siteId, $serialization['title'] )
		);
		$this->sitelinkValidator = new SiteLinkLookupSitelinkValidator(
			$sitelinkDeserializer,
			new HashSiteLinkStore()
		);
	}

	/**
	 * @dataProvider validSerializationProvider
	 */
	public function testGivenValidSitelinks_returnsNull( array $serialization, SiteLinkList $expectedSitelinks ): void {
		$validator = $this->newValidator();

		$this->assertNull( $validator->validate( 'Q123', $serialization ) );
		$this->assertEquals( $expectedSitelinks, $validator->getValidatedSitelinks() );
	}

	public static function validSerializationProvider(): Generator {
		yield 'empty' => [ [], new SiteLinkList() ];
		yield 'two sitelinks' => [
			[
				self::VALID_SITES[0] => [ 'title' => 'Potato' ],
				self::VALID_SITES[1] => [ 'title' => 'Q10998' ],
			],
			new SiteLinkList( [
				new SiteLink( self::VALID_SITES[0], 'Potato' ),
				new SiteLink( self::VALID_SITES[1], 'Q10998' ),
			] ),
		];
	}

	/**
	 * @dataProvider invalidSiteIdProvider
	 * @param string|int $site
	 */
	public function testGivenInvalidSiteId_returnsValidationError( $site ): void {
		$error = $this->newValidator()->validate( 'Q123', [
			$site => [ 'title' => 'Whatever' ],
		] );
		$this->assertSame( SiteIdValidator::CODE_INVALID_SITE_ID, $error->getCode() );
	}

	public static function invalidSiteIdProvider(): array {
		return [ [ 'invalidwiki' ], [ 123 ] ];
	}

	public function testGivenInvalidSitelink_returnsValidationError(): void {
		$siteId = self::VALID_SITES[0];
		$sitelink = [ 'title' => 'Invalid' ];
		$itemId = 'Q123';

		$expectedError = $this->createStub( ValidationError::class );
		$this->sitelinkValidator = $this->createMock( SitelinkValidator::class );
		$this->sitelinkValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $itemId, $siteId, $sitelink )
			->willReturn( $expectedError );

		$this->assertSame(
			$expectedError,
			$this->newValidator()->validate( $itemId, [ $siteId => $sitelink ] )
		);
	}

	public function testGivenSitelinkNotAnArray_returnsValidationError(): void {
		$siteId = self::VALID_SITES[0];
		$error = $this->newValidator()->validate( 'Q123', [ $siteId => 'not-an-array' ] );

		$this->assertSame( SitelinksValidator::CODE_INVALID_SITELINK, $error->getCode() );
		$this->assertEquals(
			[ SitelinksValidator::CONTEXT_SITE_ID => $siteId ],
			$error->getContext()
		);
	}

	public function testGivenSitelinksNotAnAssociativeArray_returnsValidationError(): void {
		$error = $this->newValidator()->validate( 'Q123', [ [ 'title' => 'Whatever' ] ] );

		$this->assertSame( SitelinksValidator::CODE_SITELINKS_NOT_ASSOCIATIVE, $error->getCode() );
	}

	public function testPartialValidation(): void {
		$itemId = 'Q123';
		$siteToValidate = self::VALID_SITES[0];
		$sitelinksSerialization = [
			self::VALID_SITES[0] => [ 'title' => 'Potato' ],
			self::VALID_SITES[1] => [ 'title' => 'Q10998' ],
		];
		$validatedSitelink = new SiteLink( self::VALID_SITES[0], 'Potato' );

		$this->sitelinkValidator = $this->createMock( SitelinkValidator::class );
		$this->sitelinkValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $itemId, $siteToValidate, $sitelinksSerialization[$siteToValidate] );
		$this->sitelinkValidator->method( 'getValidatedSitelink' )->willReturn( $validatedSitelink );

		$sitelinksValidator = $this->newValidator();
		$this->assertNull( $sitelinksValidator->validate( $itemId, $sitelinksSerialization, [ $siteToValidate ] ) );
		$this->assertEquals(
			new SiteLinkList( [ $validatedSitelink, new SiteLink( self::VALID_SITES[1], 'Q10998' ) ] ),
			$sitelinksValidator->getValidatedSitelinks()
		);
	}

	private function newValidator(): SitelinksValidator {
		return new SitelinksValidator( new SiteIdValidator( self::VALID_SITES ), $this->sitelinkValidator );
	}
}
