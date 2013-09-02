<?php
namespace Wikibase\Lib;
use Wikibase\Snak;

/**
 * SnakFormatter is an interface for services that render Snaks to a specific
 * output format.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface SnakFormatter {

	/**
	 * Formats a snak.
	 *
	 * @param Snak $snak
	 *
	 * @return string
	 */
	public function formatSnak( Snak $snak );

	/**
	 * Returns the format ID of the format this formatter generates.
	 * This uses the FORMAT_XXX constants defined in SnakFormatterFactory.
	 *
	 * @see SnakFormatterFactory::FORMAT_PLAIN
	 * @see SnakFormatterFactory::FORMAT_WIKI
	 * @see SnakFormatterFactory::FORMAT_HTML
	 * @see SnakFormatterFactory::FORMAT_HTML_WIDGET
	 *
	 * @return string
	 */
	public function getFormat();

}