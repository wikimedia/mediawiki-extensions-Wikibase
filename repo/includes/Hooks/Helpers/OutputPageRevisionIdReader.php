<?php

namespace Wikibase\Repo\Hooks\Helpers;

use OutputPage;

/**
 * Determines the revision id shown on an OutputPage by inspecting this god object's properties.
 *
 * @license GPL-2.0-or-later
 */
class OutputPageRevisionIdReader {

	/**
	 * @param OutputPage $out
	 * @return int|null
	 */
	public function getRevisionFromOutputPage( OutputPage $out ) {
		// apparently the revision id can be null on a ParserCache hit, but only for the latest revision
		return $out->getRevisionId() ?: $out->getTitle()->getLatestRevID() ?: null;
	}

}
