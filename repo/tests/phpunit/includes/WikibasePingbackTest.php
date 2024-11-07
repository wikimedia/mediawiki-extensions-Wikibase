<?php

declare( strict_types=1 );
namespace Wikibase\Repo\Tests;

use MediaWiki\Config\HashConfig;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\MainConfigNames;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\SiteStats\SiteStats;
use MediaWiki\SiteStats\SiteStatsInit;
use MediaWiki\Utils\MWTimestamp;
use MediaWikiIntegrationTestCase;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
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

		$settings = $this->getConfVar( 'WBRepoSettings' );
		$settings['wikibasePingback'] = true;
		$settings['pingbackHost'] = 'http://localhost/event/beacon';
		$this->overrideConfigValue( 'WBRepoSettings', $settings );
		SiteStatsInit::doPlaceholderInit();
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
		return $this->getDb()->newSelectQueryBuilder()
			->select( 'ul_value' )
			->from( 'updatelog' )
			->where( [ 'ul_key' => self::TEST_KEY ] )
			->caller( __METHOD__ )->fetchField();
	}

	public function getPingbackWithRequestExpectation( $expectation, $logger ) {
		$requestFactory = $this->createMock( HttpRequestFactory::class );
		$requestFactory->expects( $expectation )
			->method( 'post' )
			->willReturn( true );
		$lbFactory = new FakeLBFactory( [ 'lb' => new FakeLoadBalancer( [ 'dbr' => $this->getDb() ] ) ] );

		return new WikibasePingback(
			null,
			$logger,
			null,
			null,
			$requestFactory,
			null,
			new RepoDomainDb( $lbFactory, $lbFactory->getLocalDomainID() ),
			self::TEST_KEY
		);
	}

	private function getPingback( ?HttpRequestFactory $requestFactory = null ): WikibasePingback {
		$extensions = $this->createMock( ExtensionRegistry::class );
		$wikibaseRepoSettings = $this->createMock( SettingsArray::class );
		$requestFactory ??= $this->createMock( HttpRequestFactory::class );
		$lbFactory = new FakeLBFactory( [ 'lb' => new FakeLoadBalancer( [ 'dbr' => $this->getDb() ] ) ] );

		$wikibaseRepoSettings
			->method( 'getSetting' )
			->willReturnMap( [
				[ 'pingbackHost', 'http://localhost' ],
				[ 'federatedPropertiesEnabled', true ],
				[ 'termboxEnabled', true ],
			] );

		$extensions->method( 'getAllThings' )
			->willReturn( [
				'Babel' => [],
				'VisualEditor' => [],
			] );

		return new WikibasePingback(
			new HashConfig( [ MainConfigNames::DBtype => '' ] ),
			new NullLogger(),
			$extensions,
			$wikibaseRepoSettings,
			$requestFactory,
			null,
			new RepoDomainDb( $lbFactory, $lbFactory->getLocalDomainID() )
		);
	}

	private function populateSiteStats() {
		$this->getDb()->newUpdateQueryBuilder()
			->update( 'site_stats' )
			->set( [
				'ss_total_pages' => 11,
				// Specify edits as well to make sure that the row is considered sensible
				'ss_total_edits' => 11,
			] )
			->where( [ 'ss_row_id' => 1 ] )
			->caller( __METHOD__ )->execute();
		// Clear stale cache
		SiteStats::unload();
	}
}
