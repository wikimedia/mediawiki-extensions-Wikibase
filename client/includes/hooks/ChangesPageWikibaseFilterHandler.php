<?php

namespace Wikibase\Client\Hooks;

use ChangesListSpecialPage;
use IContextSource;
use User;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangesPageWikibaseFilterHandler {

	/**
	 * @var IContextSource
	 */
	private $context;

	/**
	 * @var boolean
	 */
	private $showExternalChanges;

	/**
	 * @param ChangesListSpecialPage $special
	 * @param boolean $showExternalChanges
	 */
	public function __construct( ChangesListSpecialPage $special, $showExternalChanges ) {
		$this->context = $special;
		$this->showExternalChanges = $showExternalChanges;
	}

	/**
	 * @param array $filters
	 * @param string $optionName
	 * @param string $toggleMessageKey
	 *
	 * @return array
	 */
	public function handleHook( array $filters, $filterName, $optionName, $toggleMessageKey ) {
		if ( !$this->showExternalChanges ) {
			return $filters;
		}

		$user = $this->context->getUser();

		if ( !$this->isEnhancedChangesEnabled( $user ) ) {
			$toggleDefault = $this->showWikibaseEditsByDefault( $user, $optionName );
			$filters = $this->addFilter( $filters, $filterName, $toggleDefault, $toggleMessageKey );
		}

		return $filters;
	}

	/**
	 * @param array $filters
	 * @param string $filterName
	 * @param boolean $toggleDefault
	 * @param string $toggleMessageKey
	 *
	 * @return array
	 */
	private function addFilter( array $filters, $filterName, $toggleDefault, $toggleMessageKey ) {
		$filters[$filterName] = array(
			'msg' => $toggleMessageKey,
			'default' => $toggleDefault
		);

		return $filters;
	}

	/**
	 * @param User $user
	 * @param string $optionName
	 *
	 * @return boolean
	 */
	private function showWikibaseEditsByDefault( User $user, $optionName ) {
		return !$user->getOption( $optionName );
	}

	/**
	 * @return boolean
	 */
	private function isEnhancedChangesEnabled( User $user ) {
		$enhancedChangesUserOption = $user->getOption( 'usenewrc' );

		$isEnabled = $this->context->getRequest()->getBool( 'enhanced', $enhancedChangesUserOption );

		return $isEnabled;
	}

}
