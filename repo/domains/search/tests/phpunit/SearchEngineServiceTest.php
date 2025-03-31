<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search;

use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\InLabelSearchEngine;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\SqlTermStoreSearchEngine;
use Wikibase\Repo\Domains\Search\WbSearch;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SearchEngineServiceTest extends MediaWikiIntegrationTestCase {

	public function testUsesInLabelSearchEngine(): void {
		$this->markTestSkippedIfExtensionNotLoaded( 'WikibaseCirrusSearch' );

		$this->setMwGlobals( 'wgSearchType', 'CirrusSearch' );
		$extensionRegistry = $this->createMock( ExtensionRegistry::class );
		$extensionRegistry->expects( $this->once() )->method( 'isLoaded' )
			->willReturnCallback( fn( string $extensionName ) => $extensionName === 'WikibaseCirrusSearch' );
		$this->setService( 'ExtensionRegistry', $extensionRegistry );

		$service = $this->newService( $this->getServiceContainer() );

		$this->assertInstanceOf( InLabelSearchEngine::class, $service );
	}

	public function testUsesSqlBasedSearchEngine(): void {
		$this->setMwGlobals( 'wgSearchType', 'CirrusSearch' );
		$extensionRegistry = $this->createMock( ExtensionRegistry::class );
		$extensionRegistry->expects( $this->once() )->method( 'isLoaded' )
			->willReturn( false );
		$this->setService( 'ExtensionRegistry', $extensionRegistry );

		$service = $this->newService( $this->getServiceContainer() );

		$this->assertInstanceOf( SqlTermStoreSearchEngine::class, $service );
	}

	/**
	 * @return InLabelSearchEngine|SqlTermStoreSearchEngine
	 */
	private function newService( MediaWikiServices $serviceContainer ) {
		return WbSearch::getSearchEngine( $serviceContainer );
	}

}
