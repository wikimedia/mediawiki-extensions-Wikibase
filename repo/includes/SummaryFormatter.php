<?php

namespace Wikibase;

use Wikibase\Lib\FormatableSummary;

/**
 * A formatter to turn a {@link FormatableSummary} into a comment text.
 *
 * @license GPL-2.0-or-later
 */
interface SummaryFormatter {

	/**
	 * @param FormatableSummary $summary
	 * @return string
	 */
	public function formatSummary( FormatableSummary $summary );

}
