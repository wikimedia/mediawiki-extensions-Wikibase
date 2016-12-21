<?php

namespace Wikibase\Lib\Interactors;

/**
 * Interface for factories creating TermSearchInteractor instances configured for the particular display language.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 */
interface TermSearchInteractorFactory {

	/**
	 * @param string $displayLanguageCode
	 * @return TermSearchInteractor
	 */
	public function getInteractor( $displayLanguageCode );

}
