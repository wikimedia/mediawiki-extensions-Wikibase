<?php

declare( strict_types=1 );
namespace Wikibase\Repo\Tests;

use Config;
use ExtensionRegistry;
use MediaWiki\Http\HttpRequestFactory;
use MediaWikiIntegrationTestCase;
use MWTimestamp;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use TestLogger;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\FakeLBFactory;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\FakeLoadBalancer;
use Wikibase\Repo\WikibasePingback;

/**
 *
 * @covers \Wikibase\Repo\WikibasePingback
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class WikibasePingbackTest extends MediaWikiIntegrationTestCase {

	private const TEST_KEY = 'TEST_KEY';

	protected function setUp(): void {
		parent::setUp();

		global $wgWBRepoSettings;

		$settings = $wgWBRepoSettings;
		$settings['wikibasePingback'] = true;
		$settings['pingbackHost'] = 'http://localhost/event/beacon';
		$this->setMwGlobals( 'wgWBRepoSettings', $settings );
		$this->tablesUsed[] = 'updatelog';
	}

	public function testGetSystemInfo() {
		$systemInfo = $this->getPingback()->getSystemInfo();
		$this->assertIsArray( $systemInfo );
	}

	public function testSendPingback() {
		$requestFactory = $this->createMock( HTTPRequestFactory::class );
		$requestFactory->expects( $this->once() )
			->method( 'post' )
			->willReturn( true );

		$pingback = $this->getPingback( $requestFactory );
		$result = $pingback->sendPingback();

		$this->assertTrue( $result );
	}

	public function testGetSystemInfo_getsListOfExtenstions() {
		$pingback = $this->getPingback();
		$actual = $pingback->getSystemInfo()['extensions'];

		$this->assertEquals( [ 'BBL', 'VE' ], $actual );
	}

	public function testGetSystemInfo_getsRepoSettings() {
		$pingback = $this->getPingback();
		$systemInfo = $pingback->getSystemInfo();
		$federationActual = $systemInfo['federation'];
		$termboxActual = $systemInfo['termbox'];

		$this->assertTrue( $federationActual );
		$this->assertTrue( $termboxActual );
	}

	public function testGetSystemInfo_determinesIfWikibaseHasEntities() {
		$this->populateSiteStats();
		$pingback = $this->getPingback();
		$hasEntities = $pingback->getSystemInfo()['hasEntities'];

		$this->assertTrue( $hasEntities );
	}

	public function testWikibasePingbackSchedules() {
		MWTimestamp::setFakeTime( '20000101010000' );
		$logger = new TestLogger( true );

		// disable throttle
		$this->setMainCache( CACHE_NONE );

		$currentTime = $this->getPingbackTime();
		$this->assertFalse( $currentTime );

		// first time there no row - it should pingback as soon as this code is run
		WikibasePingback::doSchedule( $this->getPingbackWithRequestExpectation( $this->once(), $logger ) );

		$currentTime = $this->getPingbackTime();
		$this->assertIsNumeric( $currentTime );

		// this won't trigger it
		WikibasePingback::doSchedule( $this->getPingbackWithRequestExpectation( $this->never(), $logger ) );
		$this->assertSame( $currentTime, $this->getPingbackTime() );

		// move forward one month
		MWTimestamp::setFakeTime( '20000201010000' );

		// should trigger
		WikibasePingback::doSchedule( $this->getPingbackWithRequestExpectation( $this->once(), $logger ) );

		$buffer = $logger->getBuffer();
		$this->assertCount( 2, $buffer );
		$this->assertSame(
			[
				LogLevel::DEBUG,
				'Wikibase\Repo\WikibasePingback::sendPingback: pingback sent OK (' . self::TEST_KEY . ')',
			],
			$buffer[0]
		);
		$this->assertSame(
			[
				LogLevel::DEBUG,
				'Wikibase\Repo\WikibasePingback::sendPingback: pingback sent OK (' . self::TEST_KEY . ')',
			],
			$buffer[1]
		);
		MWTimestamp::setFakeTime( false );
		$logger->clearBuffer();
	}

	private function getPingbackTime() {
		return $this->db->selectField(
			'updatelog',
			'ul_value',
			[ 'ul_key' => self::TEST_KEY ],
			__METHOD__
		);
	}

	public function getPingbackWithRequestExpectation( $expectation, $logger ) {
		$requestFactory = $this->createMock( HttpRequestFactory::class );
		$requestFactory->expects( $expectation )
			->method( 'post' )
			->willReturn( true );
		$lbFactory = new FakeLBFactory( [ 'lb' => new FakeLoadBalancer( [ 'dbr' => $this->db ] ) ] );

		return new WikibasePingback(
			null,
			$logger,
			null,
			null,
			$requestFactory,
			new RepoDomainDb( $lbFactory, $lbFactory->getLocalDomainID() ),
			self::TEST_KEY
		);
	}

	private function getPingback(
		HttpRequestFactory $requestFactory = null,
		Config $config = null,
		LoggerInterface $logger = null,
		ExtensionRegistry $extensions = null,
		SettingsArray $wikibaseRepoSettings = null
	): WikibasePingback {
		$config = $config ?: $this->createMock( Config::class );
		$logger = $logger ?: $this->createMock( LoggerInterface::class );
		$extensions = $extensions ?: $this->createMock( ExtensionRegistry::class );
		$wikibaseRepoSettings = $wikibaseRepoSettings ?: $this->createMock( SettingsArray::class );
		$requestFactory = $requestFactory ?: $this->createMock( HTTPRequestFactory::class );
		$lbFactory = new FakeLBFactory( [ 'lb' => new FakeLoadBalancer( [ 'dbr' => $this->db ] ) ] );

		$wikibaseRepoSettings
			->method( 'getSetting' )
			->withConsecutive( [ 'pingbackHost' ], [ 'federatedPropertiesEnabled' ], [ 'termboxEnabled' ] )
			->willReturn( 'http://localhost', true, true );

		$extensions->method( 'getAllThings' )
			->willReturn( [
				'Babel' => [],
				'VisualEditor' => [],
			] );

		return new WikibasePingback(
			$config,
			$logger,
			$extensions,
			$wikibaseRepoSettings,
			$requestFactory,
			new RepoDomainDb( $lbFactory, $lbFactory->getLocalDomainID() )
		);
	}

	private function populateSiteStats() {
		$this->db->update( 'site_stats', [ 'ss_total_pages' => 11 ], [ 'ss_row_id' => 1 ], __METHOD__ );
	}
}
