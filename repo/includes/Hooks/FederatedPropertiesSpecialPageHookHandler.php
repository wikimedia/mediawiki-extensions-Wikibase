<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use MediaWiki\SpecialPage\Hook\SpecialPage_initListHook;
use Wikibase\Lib\SettingsArray;

/**
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesSpecialPageHookHandler implements SpecialPage_initListHook {

	/** @var bool */
	private $isFederatedPropertiesEnabled;

	public function __construct( SettingsArray $settings ) {
		$this->isFederatedPropertiesEnabled = $settings
			->getSetting( 'federatedPropertiesEnabled' );
	}

	/**
	 * Unset 'Special:NewProperty' special page from the list of special pages.
	 *
	 * @param array &$list
	 */
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	public function onSpecialPage_initList( &$list ): void {
		if ( $this->isFederatedPropertiesEnabled && isset( $list[ 'NewProperty' ] ) ) {
			unset( $list['NewProperty'] );
		}
	}
}
