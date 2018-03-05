<?php

namespace Wikibase\View;

use Wikibase\DataModel\Term\Term;

/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
interface HtmlTermRenderer {

	/**
	 * @param Term $term
	 * @return string HTML representing the term; This will be used in an HTML language and directionality context
	 *   that corresponds to $term->getLanguageCode().
	 */
	public function renderTerm( Term $term );

}
