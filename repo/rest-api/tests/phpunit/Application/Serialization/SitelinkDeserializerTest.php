<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\RestApi\Application\Serialization\EmptySitelinkException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
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

	public function testGivenValidSerialization_deserializeReturnsCorrectSitelink(): void {
		$siteId = 'testwiki';
		$title = 'Test Title';
		$badge = 'Q123';
		$serialization = [ 'title' => $title, 'badges' => [ $badge ] ];

		$this->assertEquals(
			new SiteLink( $siteId, $title, [ new ItemId( $badge ) ] ),
			( new SitelinkDeserializer( '/\?/' ) )->deserialize( $siteId, $serialization )
		);
	}

	/**
	 * @dataProvider provideInvalidSitelinkSerialization
	 */
	public function testGivenInvalidSitelink_deserializeThrows( array $serialization, Exception $expectedError ): void {
		try {
			( new SitelinkDeserializer( '/\?/' ) )->deserialize( 'Q123', $serialization );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( Exception $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function provideInvalidSitelinkSerialization(): Generator {
		yield 'title missing' => [ [ 'badges' => [ 'Q456' ] ], new MissingFieldException( 'title' ) ];
		yield 'title empty' => [ [ 'title' => '', 'badges' => [ 'Q456' ] ], new EmptySitelinkException( 'title', '' ) ];
		yield 'title empty w/ whitespace' => [ [ 'title' => " \t" ], new EmptySitelinkException( 'title', '' ) ];
		yield 'title invalid' => [ [ 'title' => 'invalid?' ], new InvalidFieldException( 'title', 'invalid?' ) ];
	}

}
