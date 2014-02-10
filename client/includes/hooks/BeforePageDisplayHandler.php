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
class BeforePageDisplayHandler {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var boolean
	 */
	private $widgetEnabled;

	/**
	 * @param NamespaceChecker $namespaceChecker
	 * @param boolean $widgetEnabled
	 */
	public function __construct( NamespaceChecker $namespaceChecker, $widgetEnabled ) {
		$this->namespaceChecker = $namespaceChecker;
		$this->widgetEnabled = $widgetEnabled;
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 *
	 * @return OutputPage
	 */
	public function addModules( OutputPage $out, Skin $skin, $actionName ) {
		$title = $out->getTitle();

		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			return $out;
		}

		$out = $this->addStyleModules( $out, $title, $actionName );
		$out = $this->addJsModules( $out, $skin, $title, $actionName );

		return $out;
	}

	private function addStyleModules( OutputPage $out, Title $title, $actionName ) {
		// styles are not appropriate for cologne blue and should leave styling up to other skins
		if ( $this->hasEditOrAddLinks( $out, $title, $actionName ) ) {
			$out->addModuleStyles( 'wikibase.client.init' );
		}

		if ( $out->getLanguageLinks() === array() ) {
			// Module with the sole purpose to hide #p-lang
			// Needed as we can't do that in the regular CSS nor in JavaScript
			// (as that only runs after the element initially appeared).
			$out->addModuleStyles( 'wikibase.client.nolanglinks' );
		}

		return $out;
	}

	private function addJsModules( OutputPage $out, Skin $skin, Title $title, $actionName ) {
		$user = $skin->getContext()->getUser();

		if ( $this->hasLinkItemWidget( $user, $out, $title, $actionName ) ) {
			// Add the JavaScript which lazy-loads the link item widget
			// (needed as jquery.wikibase.linkitem has pretty heavy dependencies)
			$out->addModules( 'wikibase.client.linkitem.init' );
		}

		return $out;
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
			!$this->widgetEnabled || !$user->isLoggedIn() || $out->getLanguageLinks() !== array() ||
			!$this->hasEditOrAddLinks( $out, $title, $actionName )
		) {
			return false;
		}

		return true;
	}

}
