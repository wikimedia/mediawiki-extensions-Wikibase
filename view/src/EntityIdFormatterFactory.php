<?php

namespace Wikibase\View;

use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\Store\LabelDescriptionLookup;

/**
 * A factory interface for generating EntityIdFormatters.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface EntityIdFormatterFactory {

	/**
	 * Returns the formatter's output format, as defined by the
	 * SnakFormatter::FORMAT_XXX constants.
	 *
	 * This allows callers to assert that the formatter returned by getEntityIdFormater()
	 * will generate text in the desired format, applying the appropriate escaping.
	 *
	 * @see SnakFormatter::FORMAT_WIKITEXT
	 * @see SnakFormatter::FORMAT_HTML
	 *
	 * @return string
	 */
	public function getOutputFormat();

	/**
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 *
	 * @return EntityIdFormatter
	 */
	public function getEntityIdFormater( LabelDescriptionLookup $labelDescriptionLookup );

}
