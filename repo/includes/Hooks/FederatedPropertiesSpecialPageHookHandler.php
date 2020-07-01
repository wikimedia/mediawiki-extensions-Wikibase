<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Hooks;

use Wikibase\Lib\WikibaseSettings;

/**
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesSpecialPageHookHandler {

	private $isFederatedPropertiesEnabled;

	public function __construct( bool $isFederatedpropertiesEnabled ) {
		$this->isFederatedPropertiesEnabled = $isFederatedpropertiesEnabled;
	}

	/**
	 * Unset 'Special:NewProperty' special page from the list of special pages.
	 *
	 * @param array $list
	 */
	public static function onSpecialPageInitList( array &$list ) {
		self::newFromGlobalState()->doSpecialPageUnset( $list );
	}

	public function doSpecialPageUnset( array &$list ) {
		if ( $this->isFederatedPropertiesEnabled && isset( $list[ 'NewProperty' ] ) ) {
			unset( $list['NewProperty'] );
		}
	}

	private static function newFromGlobalState(): self {
		return new self( WikibaseSettings::getRepoSettings()->getSetting( 'federatedPropertiesEnabled' ) );
	}
}
