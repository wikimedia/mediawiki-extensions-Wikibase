<?php

namespace Wikibase\Client\Hooks;

use OutputPage;
use Title;
use User;
use Wikibase\NamespaceChecker;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BeforePageDisplayHandler {

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
	 * @note in php5, $out is by passed by reference (by default, so &$out is not needed)
	 *
	 * @param OutputPage $out
	 * @param string $actionName
	 *
	 * @return bool
	 */
	public function addModules( OutputPage $out, $actionName ) {
		$title = $out->getTitle();

		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			return true;
		}

		$this->addStyleModules( $out, $title, $actionName );
		$this->addJsModules( $out, $title, $actionName );

		return true;
	}

	private function addStyleModules( OutputPage $out, Title $title, $actionName ) {
		// styles are not appropriate for cologne blue and should leave styling up to other skins
		if ( $this->hasEditOrAddLinks( $out, $title, $actionName ) ) {
			$out->addModuleStyles( 'wikibase.client.init' );
		}

		$user = $out->getUser();
		if ( $out->getLanguageLinks() === array() ) {
			if ( !$this->hasLinkItemWidget( $user, $out, $title, $actionName ) ) {
				// Module with the sole purpose to hide #p-lang
				// Needed as we can't do that in the regular CSS nor in JavaScript
				// (as that only runs after the element initially appeared).
				$out->addModuleStyles( 'wikibase.client.nolanglinks' );
			} else {
				$out->addModuleStyles( 'wikibase.client.linkitem.init' );
			}
		}
	}

	private function addJsModules( OutputPage $out, Title $title, $actionName ) {
		$user = $out->getUser();

		if ( $this->hasLinkItemWidget( $user, $out, $title, $actionName ) ) {
			// Add the JavaScript which lazy-loads the link item widget
			// (needed as jquery.wikibase.linkitem has pretty heavy dependencies)
			$out->addModules( 'wikibase.client.linkitem.init' );
		}
	}

	private function hasEditOrAddLinks( OutputPage $out, Title $title, $actionName ) {
		if (
			$out->getProperty( 'noexternallanglinks' ) ||
			$actionName !== 'view' ||
			!$title->exists()
		) {
			return false;
		}

		return true;
	}

	private function hasLinkItemWidget( User $user, OutputPage $out, Title $title, $actionName ) {
		if (
			$out->getLanguageLinks() !== array() || !$user->isLoggedIn()
			|| !$this->hasEditOrAddLinks( $out, $title, $actionName )
		) {
			return false;
		}

		return true;
	}

}
