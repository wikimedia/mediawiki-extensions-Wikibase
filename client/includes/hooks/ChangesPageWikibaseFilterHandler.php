<?php

namespace Wikibase\Client\Hooks;

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
	 * @var string
	 */
	private $filterName;

	/**
	 * @var string
	 */
	private $optionName;

	/**
	 * @var string
	 */
	private $toggleMessageKey;

	/**
	 * @param IContextSource $context
	 * @param boolean $showExternalChanges
	 * @param string $filterName - name for Wikibase toggle in FormOptions
	 * @param string $optionName - user option name for showing Wikibase edits by default
	 * @param string $toggleMessageKey
	 */
	public function __construct(
		IContextSource $context,
		$showExternalChanges,
		$filterName,
		$optionName,
		$toggleMessageKey
	) {
		$this->context = $context;
		$this->showExternalChanges = $showExternalChanges;
		$this->filterName = $filterName;
		$this->optionName = $optionName;
		$this->toggleMessageKey = $toggleMessageKey;
	}

	/**
	 * @param array $filters
	 *
	 * @return array
	 */
	public function addFilterIfEnabled( array $filters ) {
		$user = $this->context->getUser();

		if ( !$this->shouldAddFilter( $user ) ) {
			return $filters;
		}

		$toggleDefault = $this->showWikibaseEditsByDefault( $user, $this->optionName );
		$filters = $this->addFilter( $filters, $toggleDefault );

		return $filters;
	}

	/**
	 * @param User $user
	 *
	 * @return boolean
	 */
	private function shouldAddFilter( User $user ) {
		if ( $this->showExternalChanges && !$this->isEnhancedChangesEnabled( $user ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param array $filters
	 * @param boolean $toggleDefault
	 *
	 * @return array
	 */
	private function addFilter( array $filters, $toggleDefault ) {
		$filters["{$this->filterName}"] = array(
			'msg' => $this->toggleMessageKey,
			'default' => $toggleDefault
		);

		return $filters;
	}

	/**
	 * @param User $user
	 *
	 * @return boolean
	 */
	private function showWikibaseEditsByDefault( User $user ) {
		return !$user->getOption( $this->optionName );
	}

	/**
	 * @param User $user
	 *
	 * @return boolean
	 */
	private function isEnhancedChangesEnabled( User $user ) {
		$enhancedChangesUserOption = $user->getOption( 'usenewrc' );

		$isEnabled = $this->context->getRequest()->getBool( 'enhanced', $enhancedChangesUserOption );

		return $isEnabled;
	}

}
