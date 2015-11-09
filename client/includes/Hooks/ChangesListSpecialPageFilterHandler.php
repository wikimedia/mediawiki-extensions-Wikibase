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
	 * @var boolean
	 */
	private $showExternalChanges;

	/**
	 * @param WebRequest $request
	 * @param User $user
	 * @param string $pageName
	 * @param boolean $showExternalChanges
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
	public static function newFromGlobalState(
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
	public function onChangesListSpecialPageFilters(
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
			$toggleDefault = $this->getShowWikibaseEditsByDefaultSetting();
			$this->addFilter( $filters, $toggleDefault );
		}
	}

	/**
	 * @return boolean
	 */
	private function shouldAddFilter() {
		return $this->showExternalChanges && !$this->isEnhancedChangesEnabled();
	}

	/**
	 * @param array &$filters
	 * @param boolean $toggleDefault
	 */
	private function addFilter( array &$filters, $toggleDefault ) {
		$filterName = $this->getFilterName();

		$filters[$filterName] = array(
			'msg' => 'wikibase-rc-hide-wikidata',
			'default' => $toggleDefault
		);

		return $filters;
	}

	/**
	 * @return boolean
	 */
	private function getShowWikibaseEditsByDefaultSetting() {
		return !$this->user->getOption( $this->getOptionName() );
	}

	/**
	 * @return boolean
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
