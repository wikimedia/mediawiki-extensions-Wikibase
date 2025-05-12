<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use Wikibase\Lib\Hooks\WikibaseContentLanguagesHook;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\UnionContentLanguages;
use Wikibase\Lib\WikibaseContentLanguages;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseContentLanguagesHandler implements WikibaseContentLanguagesHook {

	private SettingsArray $clientSettings;

	public function __construct( SettingsArray $clientSettings ) {
		$this->clientSettings = $clientSettings;
	}

	public function onWikibaseContentLanguages( array &$contentLanguages ): void {
		if ( !$this->clientSettings->getSetting( 'enableMulLanguageCode' ) ) {
			return;
		}

		if ( $contentLanguages[WikibaseContentLanguages::CONTEXT_TERM]->hasLanguage( 'mul' ) ) {
			return;
		}

		$contentLanguages[WikibaseContentLanguages::CONTEXT_TERM] = new UnionContentLanguages(
			$contentLanguages[WikibaseContentLanguages::CONTEXT_TERM],
			new StaticContentLanguages( [ 'mul' ] )
		);
	}

}
