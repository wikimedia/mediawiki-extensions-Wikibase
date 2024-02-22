<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\RestApi\Application\Serialization\BadgeNotAllowed;
use Wikibase\Repo\RestApi\Application\Serialization\EmptySitelinkException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidSitelinkBadgeException;
use Wikibase\Repo\RestApi\Application\Serialization\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Domain\ReadModel\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\SitelinkTargetTitleResolver;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\DummyItemRevisionMetaDataRetriever;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\SameTitleSitelinkTargetResolver;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SitelinkDeserializerTest extends TestCase {

	private const ALLOWED_BADGES = [ 'Q987', 'Q654' ];

	private SitelinkTargetTitleResolver $titleResolver;
	private ItemRevisionMetadataRetriever $revisionMetadataRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->titleResolver = new SameTitleSitelinkTargetResolver();
		$this->revisionMetadataRetriever = new DummyItemRevisionMetaDataRetriever();
	}

	public function testGivenValidSerialization_returnsCorrectSitelink(): void {
		$siteId = 'testwiki';
		$title = 'Test Title';
		$resolvedTitle = 'Test Title redirect target';
		$badge = self::ALLOWED_BADGES[ 1 ];
		$serialization = [ 'title' => $title, 'badges' => [ $badge ] ];

		$this->titleResolver = $this->createMock( SitelinkTargetTitleResolver::class );
		$this->titleResolver->expects( $this->once() )
			->method( 'resolveTitle' )
			->with( $siteId, $title, [ new ItemId( $badge ) ] )
			->willReturn( $resolvedTitle );

		$this->assertEquals(
			new SiteLink( $siteId, $resolvedTitle, [ new ItemId( $badge ) ] ),
			$this->newSitelinkDeserializer()->deserialize( $siteId, $serialization )
		);
	}

	/**
	 * @dataProvider provideInvalidSitelinkSerialization
	 */
	public function testGivenInvalidSitelink_deserializeThrows( array $serialization, Exception $expectedError ): void {
		try {
			$this->newSitelinkDeserializer()->deserialize( 'Q123', $serialization );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( Exception $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function provideInvalidSitelinkSerialization(): Generator {
		yield 'title missing' => [ [ 'badges' => self::ALLOWED_BADGES[ 0 ] ], new MissingFieldException( 'title' ) ];
		yield 'title empty' => [
			[ 'title' => '', 'badges' => self::ALLOWED_BADGES[ 1 ] ],
			new EmptySitelinkException( 'title', '' ),
		];
		yield 'title empty w/ whitespace' => [ [ 'title' => " \t" ], new EmptySitelinkException( 'title', '' ) ];
		yield 'title invalid' => [ [ 'title' => 'invalid?' ], new InvalidFieldException( 'title', 'invalid?' ) ];
		yield 'title not a string' => [
			[ 'title' => [ 'arrrays', 'not', 'allowed' ] ],
			new InvalidFieldTypeException( 'title' ),
		];

		yield 'badges not an array' => [
			[ 'title' => 'valid', 'badges' => self::ALLOWED_BADGES[ 0 ] ],
			new InvalidFieldTypeException( 'badges' ),
		];
		yield 'invalid badge' => [
			[ 'title' => 'valid', 'badges' => [ 'P999' ] ],
			new InvalidSitelinkBadgeException( 'P999' ),
		];
		yield 'badge not allowed' => [
			[ 'title' => 'valid', 'badges' => [ 'Q7' ] ],
			new BadgeNotAllowed( new ItemId( 'Q7' ) ),
		];
	}

	public function testGivenSitelinkTargetNotFound_throws(): void {
		$expectedException = new SitelinkTargetNotFound();
		$this->titleResolver = $this->createStub( SitelinkTargetTitleResolver::class );
		$this->titleResolver->method( 'resolveTitle' )->willThrowException( $expectedException );

		try {
			$this->newSitelinkDeserializer()->deserialize( 'somewiki', [ 'title' => 'Page does not exist' ] );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( SitelinkTargetNotFound $exception ) {
			$this->assertSame( $expectedException, $exception );
		}
	}

	public function testGivenBadgeItemDoesNotExist_throws(): void {
		$badge = new ItemId( self::ALLOWED_BADGES[0] );
		$serialization = [ 'title' => 'Potato', 'badges' => [ "$badge" ] ];

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::itemNotFound() );

		try {
			$this->newSitelinkDeserializer()->deserialize( 'somewiki', $serialization );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( BadgeNotAllowed $e ) {
			$this->assertEquals( $badge, $e->getBadge() );
		}
	}

	public function newSitelinkDeserializer(): SitelinkDeserializer {
		return new SitelinkDeserializer(
			'/\?/',
			self::ALLOWED_BADGES,
			$this->titleResolver,
			$this->revisionMetadataRetriever
		);
	}

}
