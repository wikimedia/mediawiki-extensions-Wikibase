<?php

namespace Wikibase\Client\Hooks;

use ChangesListSpecialPage;
use User;
use WebRequest;
use Wikibase\Client\WikibaseClient;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangesListSpecialPageFilterHandler {

	/**
	 * @var WebRequest
	 */
	private $request;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var string
	 */
	private $pageName;

	/**
	 * @var bool
	 */
	private $showExternalChanges;

	/**
	 * @param WebRequest $request
	 * @param User $user
	 * @param string $pageName
	 * @param bool $showExternalChanges
	 */
	public function __construct(
		WebRequest $request,
		User $user,
		$pageName,
		$showExternalChanges
	) {
		$this->request = $request;
		$this->user = $user;
		$this->pageName = $pageName;
		$this->showExternalChanges = $showExternalChanges;
	}

	/**
	 * @param ChangesListSpecialPage $specialPage
	 *
	 * @return ChangesListSpecialPageFilterHandler
	 */
	private static function newFromGlobalState(
		ChangesListSpecialPage $specialPage
	) {
		$context = $specialPage->getContext();
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		return new self(
			$context->getRequest(),
			$context->getUser(),
			$specialPage->getName(),
			$settings->getSetting( 'showExternalRecentChanges' )
		);
	}

	/**
	 * Modifies recent changes and watchlist options to show a toggle for Wikibase changes
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ChangesListSpecialPageFilters
	 *
	 * @param ChangesListSpecialPage $specialPage
	 * @param array &$filters
	 *
	 * @return bool
	 */
	public static function onChangesListSpecialPageFilters(
		ChangesListSpecialPage $specialPage,
		array &$filters
	) {
		$hookHandler = self::newFromGlobalState( $specialPage );
		$hookHandler->addFilterIfEnabled( $filters );

		return true;
	}

	/**
	 * @param array &$filters
	 */
	public function addFilterIfEnabled( array &$filters ) {
		if ( $this->shouldAddFilter() ) {
			$filterName = $this->getFilterName();

			$filters[$filterName] = array(
				'msg' => 'wikibase-rc-hide-wikidata',
				'default' => $this->getToggleDefault()
			);
		}
	}

	/**
	 * @return bool
	 */
	private function shouldAddFilter() {
		return $this->showExternalChanges && !$this->isEnhancedChangesEnabled();
	}

	/**
	 * @return bool
	 */
	private function getToggleDefault() {
		if ( $this->request->getVal( 'action' ) === 'submit' ) {
			return $this->request->getBool( $this->getFilterName() );
		}

		// if preference enabled, then Wikibase edits are included by default and
		// the toggle default value needs to be the inverse to hide them, and vice versa.
		return !$this->hasShowWikibaseEditsPrefEnabled();
	}

	/**
	 * @return bool
	 */
	private function hasShowWikibaseEditsPrefEnabled() {
		return (bool)$this->user->getOption( $this->getOptionName() );
	}

	/**
	 * @return bool
	 */
	private function isEnhancedChangesEnabled() {
		$enhancedChangesUserOption = $this->user->getOption( 'usenewrc' );

		return $this->request->getBool( 'enhanced', $enhancedChangesUserOption );
	}

	/**
	 * @return string
	 */
	private function getFilterName() {
		if ( $this->pageName === 'Watchlist' ) {
			return 'hideWikibase';
		}

		return 'hidewikidata';
	}

	/**
	 * @return string
	 */
	private function getOptionName() {
		if ( $this->pageName === 'Watchlist' ) {
			return 'wlshowwikibase';
		}

		return 'rcshowwikidata';
	}

}
