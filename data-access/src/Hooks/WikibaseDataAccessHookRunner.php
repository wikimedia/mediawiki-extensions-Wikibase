<?php

declare( strict_types = 1 );

namespace Wikibase\DataAccess\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Title\Title;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseDataAccessHookRunner implements GetEntityContentModelForTitleHook {

	private HookContainer $hookContainer;

	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	public function onGetEntityContentModelForTitle( Title $title, string &$contentModel ): void {
		$this->hookContainer->run(
			'GetEntityContentModelForTitle',
			[ $title, &$contentModel ],
			[ 'abortable' => false ]
		);
	}

}
