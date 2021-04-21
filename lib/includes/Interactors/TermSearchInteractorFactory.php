<?php

namespace Wikibase\Lib\Interactors;

/**
 * Interface for factories creating TermSearchInteractor instances configured for the particular display language.
 *
 * @license GPL-2.0-or-later
 */
interface TermSearchInteractorFactory {

	/**
	 * @param string $displayLanguageCode
	 *
	 * @return ConfigurableTermSearchInteractor
	 */
	public function newInteractor( $displayLanguageCode );

}
