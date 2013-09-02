<?php
namespace Wikibase\Lib;
use Wikibase\Snak;

/**
 * SnakFormatter is an interface for services that render Snaks to a specific
 * output format. A SnakFormatter may be able to work on any kind of Snak, or
 * may be specialized on a single kind of snak.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface SnakFormatter {

	const FORMAT_PLAIN = 'text/plain';
	const FORMAT_WIKI = 'text/x-wiki';
	const FORMAT_HTML = 'text/html';
	const FORMAT_HTML_WIDGET = 'text/html; disposition=widget';
	const FORMAT_JSON = 'application/json';

	/**
	 * Formats a snak.
	 *
	 * @param Snak $snak
	 *
	 * @return string
	 */
	public function formatSnak( Snak $snak );

	/**
	 * Checks whether this SnakFormatter can format the given snak.
	 *
	 * @param Snak $snak
	 *
	 * @return bool
	 */
	public function canFormatSnak( Snak $snak );

	/**
	 * Returns the format ID of the format this formatter generates.
	 * This uses the FORMAT_XXX constants defined in SnakFormatterFactory.
	 *
	 * @see SnakFormatter::FORMAT_PLAIN
	 * @see SnakFormatter::FORMAT_WIKI
	 * @see SnakFormatter::FORMAT_HTML
	 * @see SnakFormatter::FORMAT_HTML_WIDGET
	 *
	 * @return string
	 */
	public function getFormat();

}