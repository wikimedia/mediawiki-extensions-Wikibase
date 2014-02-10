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
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @param SettingsArray $settings
	 * @param NamespaceChecker $namespaceChecker
	 */
	public function __construct( SettingsArray $settings, NamespaceChecker $namespaceChecker ) {
		$this->settings = $settings;
		$this->namespaceChecker = $namespaceChecker;
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 *
	 * @return OutputPage
	 */
	public function handle( OutputPage $out, Skin $skin ) {
		$title = $out->getTitle();

		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			return $out;
		}

		$out = $this->addModules( $out, $skin, $title );
		$out = $this->addWikibaseIdConfig( $out );

		return $out;
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 *
	 * @return array
	 */
	private function getModules( OutputPage $out, Skin $skin, Title $title ) {
		$modules = array(
			'css' => array(),
			'js' => array(),
			'config' => array()
		);

		// styles are not appropriate for cologne blue and should leave styling up to other skins
		if ( in_array( $skin->getSkinName(), array( 'vector', 'monobook', 'modern' ) ) ) {
			$modules['js'][] = 'wikibase.client.init';
		}

		if ( !$out->getLanguageLinks() ) {
			// Module with the sole purpose to hide #p-lang
			// Needed as we can't do that in the regular CSS nor in JavaScript
			// (as that only runs after the element initially appeared).
			$modules['css'][] = 'wikibase.client.nolanglinks';
		}

		$actionName = Action::getActionName( $skin->getContext() );
		$user = $skin->getContext()->getUser();

		if ( $this->hasLinkItemWidget( $user, $out, $title, $actionName ) ) {
			// Add the JavaScript which lazy-loads the link item widget
			// (needed as jquery.wikibase.linkitem has pretty heavy dependencies)
			$modules['js'][] = 'wikibase.client.linkitem.init';
		}

		return $modules;
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

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @param Title $title
	 *
	 * @return OutputPage
	 */
	private function addModules( OutputPage $out, Skin $skin, Title $title ) {
		$modules = $this->getModules( $out, $skin, $title );

		foreach( $modules['css'] as $module ) {
			$out->addModuleStyles( $module );
		}

		foreach( $modules['js'] as $module ) {
			$out->addModules( $module );
		}

		return $out;
	}

	/**
	 * @param User $user
	 * @param OutputPage $out
	 * @param Title $title
	 * @param string $actionName
	 *
	 * @return boolean
	 */
	private function hasLinkItemWidget( User $user, OutputPage $out, Title $title, $actionName ) {
		if ( !$this->settings->getSetting( 'enableSiteLinkWidget' ) ) {
			return false;
		}

		if ( !$user->isLoggedIn() ) {
			return false;
		}

		if ( $out->getLanguageLinks() ) {
			return false;
		}

		if ( $out->getProperty( 'noexternallanglinks' ) ) {
			return false;
		}

		if ( $actionName !== 'view' ) {
			return false;
		}

		if ( !$title->exists() ) {
			return false;
		}

		return true;
	}

}
