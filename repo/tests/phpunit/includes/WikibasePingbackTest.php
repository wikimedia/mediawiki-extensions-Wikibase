<?php

namespace Wikibase\Repo\Tests;

use Config;
use ExtensionRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
}
