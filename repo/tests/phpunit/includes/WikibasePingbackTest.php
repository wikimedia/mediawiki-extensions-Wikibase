<?php

namespace Wikibase\Repo\Tests;

use Config;
use ExtensionRegistry;
use MediaWiki\Http\HttpRequestFactory;
use MediaWikiIntegrationTestCase;
use Psr\Log\LoggerInterface;
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
		$federationActual = $pingback->getSystemInfo()['federation'];
		$termboxActual = $pingback->getSystemInfo()['termbox'];

		$this->assertTrue( $federationActual );
		$this->assertTrue( $termboxActual );
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
		$loadBalancerFactory = new FakeLBFactory( [ 'lb' => new FakeLoadBalancer( [ 'dbr' => $this->db ] ) ] );

		$wikibaseRepoSettings
			->method( 'getSetting' )
			->withConsecutive( [ 'federatedPropertiesEnabled' ], [ 'termboxEnabled' ] )
			->willReturn( true );

		$extensions->method( 'getAllThings' )
			->willReturn( [
				'Babel' => [],
				'VisualEditor' => []
			] );

		return new WikibasePingback(
			$config,
			$logger,
			$extensions,
			$wikibaseRepoSettings,
			$requestFactory,
			$loadBalancerFactory
		);
	}
}
