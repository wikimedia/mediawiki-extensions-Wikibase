<?php

namespace Wikibase\Lib;

use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Snak\Snak;

/**
 * SnakFormatter is an interface for services that render Snaks to a specific
 * output format. A SnakFormatter may be able to work on any kind of Snak, or
 * may be specialized on a single kind of snak.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
interface SnakFormatter {

	/**
	 * Options key for controlling the output language.
	 */
	const OPT_LANG = ValueFormatter::OPT_LANG;

	const FORMAT_PLAIN = 'text/plain';
	const FORMAT_WIKI = 'text/x-wiki';
	const FORMAT_HTML = 'text/html';
	const FORMAT_HTML_DIFF = 'text/html; disposition=diff';

	/**
	 * Options key for controlling error handling.
	 */
	const OPT_ON_ERROR = 'on-error';

	/**
	 * Value for the OPT_ON_ERROR option indicating that recoverable
	 * errors should cause a warning to be show to the user.
	 */
	const ON_ERROR_WARN = 'warn';

	/**
	 * Value for the OPT_ON_ERROR option indicating that recoverable
	 * errors should cause the formatting to fail with an exception
	 */
	const ON_ERROR_FAIL = 'fail';

	/**
	 * @param Snak $snak
	 *
	 * @return string
	 */
	public function formatSnak( Snak $snak );

	/**
	 * Returns the format ID of the format this formatter generates.
	 * This uses the FORMAT_XXX constants defined in OutputFormatSnakFormatterFactory.
	 *
	 * @return string One of the self::FORMAT_... constants.
	 */
	public function getFormat();

}
