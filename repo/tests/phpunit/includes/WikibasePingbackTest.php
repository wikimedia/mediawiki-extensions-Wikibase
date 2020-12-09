<?php

namespace Wikibase\Repo\Tests;

use Config;
use ExtensionRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\WikibasePingback;

/**
 *
 * @covers WikibasePingback
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibasePingbackTest extends TestCase {

	public function testGetSystemInfo_getsListOfExtenstions() {
		$config = $this->createMock( Config::class );
		$logger = $this->createMock( LoggerInterface::class );
		$extensions = $this->createMock( ExtensionRegistry::class );

		$extensions->method( 'getAllThings' )
			->willReturn( [
				'Babel' => [],
				'VisualEditor' => []
			] );

		$pingback = new WikibasePingback( $config, $logger, $extensions );
		$actual = $pingback->getSystemInfo()['extensions'];

		$this->assertEquals( [ 'BBL', 'VE' ], $actual );
	}

	public function testGetSystemInfo_getsRepoSettings() {
		$config = $this->createMock( Config::class );
		$logger = $this->createMock( LoggerInterface::class );
		$wikibaseRepoSettings = $this->createMock( SettingsArray::class );

		$wikibaseRepoSettings
			->method( 'getSetting' )
			->withConsecutive( [ 'federatedPropertiesEnabled' ], [ 'termboxEnabled' ] )
			->willReturn( true );

		$pingback = new WikibasePingback( $config, $logger, null, $wikibaseRepoSettings );
		$federationActual = $pingback->getSystemInfo()['federation'];
		$termboxActual = $pingback->getSystemInfo()['termbox'];

		$this->assertTrue( $federationActual );
		$this->assertTrue( $termboxActual );
	}
}
