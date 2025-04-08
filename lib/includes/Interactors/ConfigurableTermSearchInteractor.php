<?php declare( strict_types = 1 );

namespace Wikibase\Lib\Interactors;

/**
 * Interface for TermSearchInteractors that can be configured using TermSearchOptions.
 *
 * @license GPL-2.0-or-later
 */
interface ConfigurableTermSearchInteractor extends TermSearchInteractor {

	public function setTermSearchOptions( TermSearchOptions $termSearchOptions ): void;

}
