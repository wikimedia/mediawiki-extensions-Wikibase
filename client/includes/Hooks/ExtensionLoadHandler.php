<?php

namespace Wikibase\Client\Hooks;

use ExtensionRegistry;

/**
 * Do special hook registrations. These are affected by ordering issues and/or
 * conditional on another extension being registered.
 *
 * Strictly speaking, this isn’t a hook handler
 * (it’s called through $wgExtensionFunctions, not $wgHooks),
 * but it behaves very similarly so it’s grouped with the real hook handlers.
 *
 * @see https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:$wgExtensionFunctions
 * @license GPL-2.0-or-later
 */
class ExtensionLoadHandler {

	/** @var ExtensionRegistry */
	private $extensionRegistry;

	public function __construct(
		ExtensionRegistry $extensionRegistry
	) {
		$this->extensionRegistry = $extensionRegistry;
	}

	public static function newFromGlobalState(): self {
		return new self(
			ExtensionRegistry::getInstance()
		);
	}

	public static function onExtensionLoad() {
		global $wgHooks;

		$handler = self::newFromGlobalState();

		$wgHooks = array_merge_recursive( $wgHooks, $handler->getHooks() );
	}

	public function getHooks(): array {
		$hooks = [];

		// These hooks should only be run if we use the Echo extension
		if ( $this->extensionRegistry->isLoaded( 'Echo' ) ) {
			$hooks['LocalUserCreated'][] = EchoNotificationsHandlers::class . '::onLocalUserCreated';
			$hooks['WikibaseHandleChange'][] = EchoNotificationsHandlers::class . '::onWikibaseHandleChange';
		}

		// This is in onExtensionLoad to ensure we register our
		// ChangesListSpecialPageStructuredFilters after ORES's.
		//
		// However, ORES is not required.
		//
		// recent changes / watchlist hooks
		$hooks['ChangesListSpecialPageStructuredFilters'][] =
			ChangesListSpecialPageHookHandlers::class . '::onChangesListSpecialPageStructuredFilters';

		return $hooks;
	}

}
