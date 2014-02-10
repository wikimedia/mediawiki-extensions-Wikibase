<?php

namespace Wikibase\Client\Hooks;

use Action;
use OutputPage;
use Skin;
use Title;
use User;
use Wikibase\NamespaceChecker;
use Wikibase\SettingsArray;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BeforePageDisplayJsConfigHandler {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @param NamespaceChecker $namespaceChecker
	 */
	public function __construct( NamespaceChecker $namespaceChecker ) {
		$this->namespaceChecker = $namespaceChecker;
	}

	/**
	 * @param OutputPage $out
	 *
	 * @return OutputPage
	 */
	public function handleAddConfig( OutputPage $out ) {
		$title = $out->getTitle();

		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			return $out;
		}

		$out = $this->addWikibaseIdConfig( $out );

		return $out;
	}

	/**
	 * @param OutputPage $out
	 *
	 * @return OutputPage
	 */
	private function addWikibaseIdConfig( OutputPage $out ) {
		$prefixedId = $out->getProperty( 'wikibase_item' );

		if ( $prefixedId !== null ) {
			$out->addJsConfigVars( 'wgWikibaseItemId', $prefixedId );
		}

		return $out;
	}

}
