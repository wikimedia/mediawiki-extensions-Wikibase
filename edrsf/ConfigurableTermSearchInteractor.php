<?php

namespace Wikibase\Edrsf;

/**
 * Interface for TermSearchInteractors that can be configured using TermSearchOptions.
 *
 * @license GPL-2.0+
 */
interface ConfigurableTermSearchInteractor extends TermSearchInteractor {

	public function setTermSearchOptions( TermSearchOptions $termSearchOptions );

}
