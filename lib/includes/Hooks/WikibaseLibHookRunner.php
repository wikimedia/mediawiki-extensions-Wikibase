<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Hooks;

use MediaWiki\HookContainer\HookContainer;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseLibHookRunner implements
	WikibaseContentLanguagesHook
{

	private HookContainer $hookContainer;

	public function __construct( HookContainer $container ) {
		$this->hookContainer = $container;
	}

	public function onWikibaseContentLanguages( array &$contentLanguages ): void {
		$this->hookContainer->run(
			'WikibaseContentLanguages',
			[ &$contentLanguages ],
			[ 'abortable' => false ]
		);
	}

}
