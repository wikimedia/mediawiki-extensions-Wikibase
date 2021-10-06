<?php

namespace Wikibase\Client\Hooks;

use MediaWiki\HookContainer\HookContainer;

/**
 * Handle Changes' hooks
 * @author dang
 * @license GPL-2.0-or-later
 */
class WikibaseClientHookRunner implements WikibaseHandleChangeHook, WikibaseHandleChangesHook {

	/** @var HookContainer */
	private $hookContainer;

	/**
	 * @param HookContainer $hookContainer
	 */
	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * Hook runner for the 'WikibaseHandleChange' hook
	 *
	 * @param $change
	 * @param array $rootJobParams
	 * @return bool
	 */
	public function onWikibaseHandleChange( $change, array $rootJobParams = [] ) {
		return $this->hookContainer->run(
			'WikibaseHandleChange',
			[ $change, $rootJobParams ]
		);
	}

	/**
	 * Hook runner for the 'WikibaseHandleChanges' hook
	 *
	 * @param array $changes
	 * @param array $rootJobParams
	 * @return bool
	 */
	public function onWikibaseHandleChanges( array $changes, array $rootJobParams = [] ) {
		return $this->hookContainer->run(
			'WikibaseHandleChanges',
			[ $changes, $rootJobParams ]
		);
	}

}
