<?php

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use NamespaceInfo;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class NamespaceCheckerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'excludeNamespaces' => [ 1, 2 ],
				'namespaces' => [ 2, 3 ],
			] ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getNamespaceInfo' );

		/** @var NamespaceChecker $namespaceChecker */
		$namespaceChecker = $this->getService( 'WikibaseClient.NamespaceChecker' );

		$this->assertInstanceOf( NamespaceChecker::class, $namespaceChecker );
		$this->assertFalse( $namespaceChecker->isWikibaseEnabled( 1 ) );
		$this->assertFalse( $namespaceChecker->isWikibaseEnabled( 2 ) );
		$this->assertTrue( $namespaceChecker->isWikibaseEnabled( 3 ) );
	}

	public function testFallbackToNamespaceInfo() {
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'excludeNamespaces' => [ 1, 2 ],
				'namespaces' => [ /* all */ ],
			] ) );
		$namespaceInfo = $this->createMock( NamespaceInfo::class );
		$namespaceInfo->expects( $this->once() )
			->method( 'getValidNamespaces' )
			->willReturn( [ 1, 2, 3, 4 ] );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getNamespaceInfo' )
			->willReturn( $namespaceInfo );

		/** @var NamespaceChecker $namespaceChecker */
		$namespaceChecker = $this->getService( 'WikibaseClient.NamespaceChecker' );

		$this->assertInstanceOf( NamespaceChecker::class, $namespaceChecker );
		$this->assertEqualsCanonicalizing( [ 3, 4 ], $namespaceChecker->getWikibaseNamespaces() );
	}

}
