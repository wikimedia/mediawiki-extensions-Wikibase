<?php
namespace Wikibase\Lib;

use ValueFormatters\FormatterOptions;

/**
 * A factory interface for generating EntityIdFormatters.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface EntityIdFormatterFactory {

	/**
	 * Returns the formatter's output format.
	 *
	 * This allows callers to assert that the formatter returned by getEntityIdFormater()
	 * will generate text in the desired format, applying the appropriate escaping.
	 *
	 * @return string
	 */
	public function getOutputFormat();

	/**
	 * @param FormatterOptions $options
	 *
	 * @return EntityIdFormatter
	 */
	public function getEntityIdFormater( FormatterOptions $options );

}
