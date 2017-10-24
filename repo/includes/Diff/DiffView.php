<?php

namespace Wikibase\Repo\Diff;

/**
 * Interface for generating views of DiffOp objects.
 *
 * @license GPL-2.0+
 */
interface DiffView {

	/**
	 * Builds and returns the HTML to represent the Diff.
	 *
	 * @return string
	 */
	public function getHtml();

}