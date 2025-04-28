<?php

namespace Wikibase\Lib\Tests;

use MediaWiki\Http\HttpRequestFactory;
use MediaWikiIntegrationTestCase;
use ObjectCacheFactory;
use Psr\Log\NullLogger;
use Wikibase\Lib\StatslibRecordingSimpleCache;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheServiceFactory;
use Wikibase\Lib\TermFallbackCacheFactory;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Lib\WikibaseSettings;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\Stats\StatsFactory;

/**
 * Test to assert that factory methods of hook service classes (and similar services)
 * don't access the database or do http requests (which would be a performance issue).
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 * @coversNothing
 */
class GlobalStateFactoryMethodsResourceTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Factory methods should never access the database or do http requests
		// https://phabricator.wikimedia.org/T243729
		$this->disallowDBAccess();
		$this->disallowHttpAccess();
	}

	public function testWikibaseContentLanguages(): void {
		WikibaseContentLanguages::getDefaultInstance();
		WikibaseContentLanguages::getDefaultMonolingualTextLanguages();
		WikibaseContentLanguages::getDefaultTermsLanguages();
		$this->assertTrue( true );
	}

	public function testWikibaseSettings_clientSettings(): void {
		if ( !WikibaseSettings::isClientEnabled() ) {
			$this->markTestSkipped(
				'Can only get client settings, if client is enabled'
			);
		}
		WikibaseSettings::getClientSettings();
		$this->assertTrue( true );
	}

	public function testWikibaseSettings_repoSettings(): void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped(
				'Can only get repo settings, if repo is enabled'
			);
		}
		WikibaseSettings::getRepoSettings();
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider cacheTypeProvider
	 */
	public function testTermFallbackCacheFactory( $sharedCacheType ): void {
		$statsFactory = $this->createMock( StatsFactory::class );

		$factory = new TermFallbackCacheFactory(
			$sharedCacheType,
			new NullLogger(),
			$statsFactory,
			'secret',
			new TermFallbackCacheServiceFactory(),
			null,
			$this->createMock( ObjectCacheFactory::class )
		);
		$this->assertInstanceOf( StatslibRecordingSimpleCache::class, $factory->getTermFallbackCache() );
	}

	public static function cacheTypeProvider(): array {
		return [
			[ CACHE_ANYTHING ],
			[ CACHE_NONE ],
			[ CACHE_DB ],
			[ CACHE_MEMCACHED ],
			[ CACHE_ACCEL ],
		];
	}

	private function disallowDBAccess() {
		$this->setService(
			'DBLoadBalancerFactory',
			function() {
				$lb = $this->createMock( ILoadBalancer::class );
				$lb->expects( $this->never() )
					->method( 'getConnection' );
				$lb->expects( $this->never() )
					->method( 'getMaintenanceConnectionRef' );
				$lb->method( 'getLocalDomainID' )
					->willReturn( 'banana' );

				$lbFactory = $this->createMock( LBFactory::class );
				$lbFactory->method( 'getMainLB' )
					->willReturn( $lb );

				return $lbFactory;
			}
		);
	}

	private function disallowHttpAccess() {
		$this->setService(
			'HttpRequestFactory',
			function() {
				$factory = $this->createMock( HttpRequestFactory::class );
				$factory->expects( $this->never() )
					->method( 'create' );
				$factory->expects( $this->never() )
					->method( 'request' );
				$factory->expects( $this->never() )
					->method( 'get' );
				$factory->expects( $this->never() )
					->method( 'post' );
				return $factory;
			}
		);
	}

}
