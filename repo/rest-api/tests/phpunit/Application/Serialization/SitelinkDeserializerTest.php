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

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SitelinkDeserializerTest extends TestCase {

	private const ALLOWED_BADGES = [ 'Q987', 'Q654' ];

	public function testGivenValidSerialization_deserializeReturnsCorrectSitelink(): void {
		$siteId = 'testwiki';
		$title = 'Test Title';
		$badge = self::ALLOWED_BADGES[ 1 ];
		$serialization = [ 'title' => $title, 'badges' => [ $badge ] ];

		$this->assertEquals(
			new SiteLink( $siteId, $title, [ new ItemId( $badge ) ] ),
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

	public function newSitelinkDeserializer(): SitelinkDeserializer {
		return new SitelinkDeserializer( '/\?/', self::ALLOWED_BADGES );
	}

}
