<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Extension\Scribunto\Hooks\ScribuntoExternalLibrariesHook;
use Wikibase\Client\DataAccess\Scribunto\WikibaseEntityLibrary;
use Wikibase\Client\DataAccess\Scribunto\WikibaseLibrary;
use Wikibase\Lib\SettingsArray;

/**
 * @license GPL-2.0-or-later
 */
class ScribuntoExternalLibrariesHandler implements ScribuntoExternalLibrariesHook {

	private bool $allowDataTransclusion;

	public function __construct( bool $allowDataTransclusion ) {
		$this->allowDataTransclusion = $allowDataTransclusion;
	}

	public static function factory(
		SettingsArray $settings
	): self {
		return new self(
			$settings->getSetting( 'allowDataTransclusion' )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onScribuntoExternalLibraries( string $engine, array &$extraLibraries ): void {
		if ( $engine === 'lua' && $this->allowDataTransclusion ) {
			$extraLibraries['mw.wikibase'] = WikibaseLibrary::class;
			$extraLibraries['mw.wikibase.entity'] = WikibaseEntityLibrary::class;
		}
	}

}
