<?php

namespace Wikibase\View;

use Wikibase\DataModel\Term\Term;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
interface HtmlTermRenderer {

	/**
	 * @param Term $term
	 * @return string HTML
	 */
	public function renderTerm( Term $term );

}
