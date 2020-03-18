<?php

namespace Wikibase\Lib\Formatters\Reference;

use Wikibase\DataModel\Reference;

/**
 * A service to format a {@link Reference} into a block of Wikitext.
 *
 * @license GPL-2.0-or-later
 */
interface ReferenceFormatter {

	/**
	 * @param Reference $reference
	 * @return string Wikitext
	 */
	public function formatReference( Reference $reference ): string;

}
