<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Hook\BeforePageDisplayHook;
use OutputPage;
use Skin;
use Title;
use User;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Lib\SettingsArray;

/**
 * Adds CSS for the edit links sidebar link or JS to create a new item
 * or to link with an existing one.
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BeforePageDisplayHandler implements BeforePageDisplayHook {

	private NamespaceChecker $namespaceChecker;

	private bool $dataBridgeEnabled;

	public function __construct( NamespaceChecker $namespaceChecker, bool $dataBridgeEnabled ) {
		$this->namespaceChecker = $namespaceChecker;
		$this->dataBridgeEnabled = $dataBridgeEnabled;
	}

	public static function factory(
		NamespaceChecker $namespaceChecker,
		SettingsArray $clientSettings
	): self {
		return new self(
			$namespaceChecker,
			$clientSettings->getSetting( 'dataBridgeEnabled' )
		);
	}

	/**
	 * @param OutputPage $outputPage
	 * @param Skin $skin
	 */
	public function onBeforePageDisplay( $outputPage, $skin ): void {
		$actionName = $outputPage->getActionName();
		$this->addModules( $outputPage, $actionName, $skin );
	}

	public function addModules( OutputPage $outputPage, string $actionName, Skin $skin ): void {
		$title = $outputPage->getTitle();

		if ( !$title || !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			return;
		}

		$this->addStyleModules( $outputPage, $title, $actionName );
		$this->addJsModules( $outputPage, $title, $actionName, $skin );
	}

	private function addStyleModules( OutputPage $outputPage, Title $title, string $actionName ): void {
		// styles are not appropriate for cologne blue and should leave styling up to other skins
		if ( $this->hasEditOrAddLinks( $outputPage, $title, $actionName ) ) {
			$outputPage->addModuleStyles( 'wikibase.client.init' );
		}

		if ( $this->dataBridgeEnabled ) {
			$outputPage->addModuleStyles( 'wikibase.client.data-bridge.externalModifiers' );
		}
	}

	private function addJsModules( OutputPage $outputPage, Title $title, $actionName, Skin $skin ): void {
		$user = $outputPage->getUser();

		if ( $this->hasLinkItemWidget( $user, $outputPage, $title, $actionName ) ) {
			// Add the JavaScript which lazy-loads the link item widget
			// (needed as jquery.wikibase.linkitem has pretty heavy dependencies)
			$outputPage->addModules( 'wikibase.client.linkitem.init' );
		}

		if ( $skin->getSkinName() === 'vector-2022' && $outputPage->getProperty( 'wikibase_item' ) !== null ) {
			$outputPage->addModules( 'wikibase.client.vector-2022' );
		}

		if ( $this->dataBridgeEnabled ) {
			$outputPage->addModules( 'wikibase.client.data-bridge.init' );
		}
	}

	private function hasEditOrAddLinks( OutputPage $outputPage, Title $title, string $actionName ): bool {
		if (
			!in_array( $actionName, [ 'view', 'submit' ] ) ||
			$this->allLinksAreSuppressed( $outputPage ) ||
			!$title->exists()
		) {
			return false;
		}

		return true;
	}

	private function allLinksAreSuppressed( OutputPage $outputPage ): bool {
		$noexternallanglinks = $outputPage->getProperty( 'noexternallanglinks' );

		if ( $noexternallanglinks !== null ) {
			return in_array( '*', $noexternallanglinks );
		}

		return false;
	}

	private function hasLinkItemWidget( User $user, OutputPage $outputPage, Title $title, string $actionName ): bool {
		if (
			$outputPage->getLanguageLinks() !== [] || !$user->isRegistered()
			|| !$this->hasEditOrAddLinks( $outputPage, $title, $actionName )
		) {
			return false;
		}

		return true;
	}

}
