<?php

namespace Wikibase\Repo\Diff;

/**
 * Interface for generating views of DiffOp objects.
 *
 * @license GPL-2.0-or-later
 */
interface DiffView {

	/**
	 * Builds and returns the HTML to represent the Diff.
	 *
	 * The HTML returned here is expected to be a set of <tr> elements without
	 * any <table> or <tbody>. Each <tr> is expected to contain exactly four <td> cells.
	 * The first two cells represent marker (either a "+" or a "-") and content
	 * from the old revision, the last two cells represent marker and content
	 * from the new revision. Either pair of cells can be omitted and replaced
	 * with an empty <td colspan="2"> if there is no relevant content on this side.
	 * <th> cells are not expected.
	 *
	 * @return string HTML
	 */
	public function getHtml();

}
