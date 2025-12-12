<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use ExtensionRegistry;

/**
 * @license GPL-2.0-or-later
 */
trait SearchEnabledTestTrait {

	private function simulateSearchEnabled( bool $enabled = true ): void {
		$currentExtensionRegistry  = $this->getServiceContainer()->getExtensionRegistry();
		$extensionRegistry = $this->createStub( ExtensionRegistry::class );

		$extensionRegistry->method( 'isLoaded' )->willReturnCallback(
			static fn( string $extension ) => match ( $extension ) {
				'WikibaseCirrusSearch' => $enabled,
				default => $currentExtensionRegistry->isLoaded( $extension )
			}
		);

		$this->setMwGlobals( 'wgSearchType', $enabled ? 'CirrusSearch' : null );
		$this->setService( 'ExtensionRegistry', $extensionRegistry );
	}

}
