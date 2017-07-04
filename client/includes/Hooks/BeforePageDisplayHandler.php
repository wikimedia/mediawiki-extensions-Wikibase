<?php

namespace Wikibase\Client\Hooks;

use OutputPage;
use Title;
use User;
use Wikibase\Client\NamespaceChecker;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BeforePageDisplayHandler {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

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

		if ( !$title || !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
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
			!in_array( $actionName, [ 'view', 'submit' ] ) ||
			$this->allLinksAreSuppressed( $out ) ||
			!$title->exists()
		) {
			return false;
		}

		return true;
	}

	private function allLinksAreSuppressed( OutputPage $out ) {
		$noexternallanglinks = $out->getProperty( 'noexternallanglinks' );

		if ( $noexternallanglinks !== null ) {
			return in_array( '*', $noexternallanglinks );
		}

		return false;
	}

	private function hasLinkItemWidget( User $user, OutputPage $out, Title $title, $actionName ) {
		if (
			$out->getLanguageLinks() !== [] || !$user->isLoggedIn()
			|| !$this->hasEditOrAddLinks( $out, $title, $actionName )
		) {
			return false;
		}

		return true;
	}

}
