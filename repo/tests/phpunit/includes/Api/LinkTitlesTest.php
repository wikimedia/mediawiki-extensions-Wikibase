<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;

/**
 * @covers \Wikibase\Repo\Api\LinkTitles
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Addshore
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group BreakingTheSlownessBarrier
 *
 * @group Database
 * @group medium
 */
class LinkTitlesTest extends WikibaseApiTestCase {

	private static $hasSetup;

	protected function setUp(): void {
		parent::setUp();

		if ( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( [ 'StringProp', 'Oslo', 'Berlin' ] );
		}
		self::$hasSetup = true;
	}

	public function provideLinkTitles(): iterable {
		return [
			[ //0 add nowiki as fromsite
				'p' => [ 'tosite' => 'nnwiki', 'totitle' => 'Oslo', 'fromsite' => 'nowiki', 'fromtitle' => 'Oslo' ],
				'e' => [ 'inresult' => 1 ] ],
			[ //1 add svwiki as tosite
				'p' => [ 'tosite' => 'svwiki', 'totitle' => 'Oslo', 'fromsite' => 'nowiki', 'fromtitle' => 'Oslo' ],
				'e' => [ 'inresult' => 1 ] ],
			[ //2 Create a link between 2 new pages
				'p' => [ 'tosite' => 'svwiki', 'totitle' => 'NewTitle', 'fromsite' => 'nowiki', 'fromtitle' => 'NewTitle' ],
				'e' => [ 'inresult' => 2 ] ],
			[ //4 Create a link between 2 new pages
				'p' => [ 'tosite' => 'svwiki', 'totitle' => 'ATitle', 'fromsite' => 'nowiki', 'fromtitle' => 'ATitle' ],
				'e' => [ 'inresult' => 2 ] ],
		];
	}

	/**
	 * @dataProvider provideLinkTitles
	 */
	public function testLinkTitles( array $params, array $expected ): void {
		// -- set any defaults ------------------------------------
		$params['action'] = 'wblinktitles';

		// -- do the request --------------------------------------------------
		list( $result,, ) = $this->doApiRequestWithToken( $params );

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $result );
		$this->assertArrayHasKey( 'entity', $result, "Missing 'entity' section in response." );
		$this->assertArrayHasKey( 'lastrevid', $result['entity'], 'entity should contain lastrevid key' );

		$this->assertCount( $expected['inresult'], $result['entity']['sitelinks'], "Result has wrong number of sitelinks" );
		foreach ( $result['entity']['sitelinks'] as $link ) {
			$this->assertTrue( $params['fromsite'] === $link['site'] || $params['tosite'] === $link['site'] );
			$this->assertTrue( $params['fromtitle'] === $link['title'] || $params['totitle'] === $link['title'] );
		}

		// check the item in the database -------------------------------
		if ( array_key_exists( 'id', $result['entity'] ) ) {
			$item = $this->loadEntity( $result['entity']['id'] );
			$links = $this->flattenArray( $item['sitelinks'], 'site', 'title' );
			$this->assertEquals( $params['fromtitle'], $links[ $params['fromsite'] ], 'wrong link target' );
			$this->assertEquals( $params['totitle'], $links[ $params['tosite'] ], 'wrong link target' );
		}

		// -- check the edit summary --------------------------------------------
		if ( array_key_exists( 'summary', $params ) ) {
			$this->assertRevisionSummary( '/' . $params['summary'] . '/', $result['entity']['lastrevid'] );
		}
	}

	public function provideLinkTitleExceptions(): iterable {
		return [
			'notoken' => [
				'p' => [
					'tosite' => 'nnwiki',
					'totitle' => 'Oslo',
					'fromsite' => 'nowiki',
					'fromtitle' => 'AnotherPage',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'notoken' ),
						$this->equalTo( 'missingparam' )
					),
					'message' => 'The "token" parameter must be set',
				] ],
				'token' => false,
			],
			'badtoken' => [
				'p' => [
					'tosite' => 'nnwiki',
					'totitle' => 'Oslo',
					'fromsite' => 'nowiki',
					'fromtitle' => 'AnotherPage',
					'token' => '88888888888888888888888888888888+\\',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'badtoken',
					'message' => 'Invalid CSRF token.',
				] ],
				'token' => false,
			],
			'add two links already exist together' => [
				'p' => [
					'tosite' => 'nnwiki',
					'totitle' => 'Oslo',
					'fromsite' => 'nowiki',
					'fromtitle' => 'Oslo',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'common-item',
				] ],
			],
			'no common item' => [
				'p' => [
					'tosite' => 'dewiki',
					'totitle' => 'Berlin',
					'fromsite' => 'nlwiki',
					'fromtitle' => 'Oslo',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'no-common-item',
				] ],
			],
			'add two links from the same site' => [
				'p' => [
					'tosite' => 'nnwiki',
					'totitle' => 'Hammerfest',
					'fromsite' => 'nnwiki',
					'fromtitle' => 'Hammerfest',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'param-illegal',
				] ],
			],
			'missing title' => [
				'p' => [
					'tosite' => 'nnwiki',
					'totitle' => '',
					'fromsite' => 'dewiki',
					'fromtitle' => 'Hammerfest',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'nototitle' ),
						$this->equalTo( 'missingparam' )
					),
				] ],
			],
			'bad tosite' => [
				'p' => [
					'tosite' => 'qwerty',
					'totitle' => 'Hammerfest',
					'fromsite' => 'nnwiki',
					'fromtitle' => 'Hammerfest',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'unknown_tosite' ),
						$this->equalTo( 'badvalue' )
					),
				] ],
			],
			'bad fromsite' => [
				'p' => [
					'tosite' => 'nnwiki',
					'totitle' => 'Hammerfest',
					'fromsite' => 'qwerty',
					'fromtitle' => 'Hammerfest',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'unknown_fromsite' ),
						$this->equalTo( 'badvalue' )
					),
				] ],
			],
			'missing site' => [
				'p' => [
					'tosite' => 'nnwiki',
					'totitle' => 'APage',
					'fromsite' => '',
					'fromtitle' => 'Hammerfest',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'unknown_fromsite' ),
						$this->equalTo( 'badvalue' )
					),
				] ],
			],
		];
	}

	/**
	 * @dataProvider provideLinkTitleExceptions
	 */
	public function testLinkTitlesExceptions( array $params, array $expected, bool $token = true ) {
		// -- set any defaults ------------------------------------
		$params['action'] = 'wblinktitles';
		$this->doTestQueryExceptions( $params, $expected['exception'], null, $token );
	}

}
