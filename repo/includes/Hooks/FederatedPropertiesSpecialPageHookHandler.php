<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Hooks;

use MediaWiki\SpecialPage\Hook\SpecialPage_initListHook;
use Wikibase\Lib\WikibaseSettings;

/**
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesSpecialPageHookHandler implements SpecialPage_initListHook {

	private $isFederatedPropertiesEnabled;

	public function __construct( bool $isFederatedpropertiesEnabled ) {
		$this->isFederatedPropertiesEnabled = $isFederatedpropertiesEnabled;
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

	public static function newFromGlobalState(): self {
		return new self( WikibaseSettings::getRepoSettings()->getSetting( 'federatedPropertiesEnabled' ) );
	}
}
