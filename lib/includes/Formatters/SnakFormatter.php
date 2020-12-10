<?php

namespace Wikibase\Lib\Formatters;

use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Snak\Snak;

/**
 * SnakFormatter is an interface for services that render Snaks to a specific
 * output format. A SnakFormatter may be able to work on any kind of Snak, or
 * may be specialized on a single kind of snak.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface SnakFormatter {

	/**
	 * Options key for controlling the output language.
	 */
	public const OPT_LANG = ValueFormatter::OPT_LANG;

	/**
	 * Output format types.
	 *
	 * Use SnakFormat::getBaseFormat or SnakFormat::isPossibleFormat when dispatching
	 * to a concrete formatter based on any of these (in order to support more nuanced
	 * formats in the future).
	 */
	public const FORMAT_PLAIN = 'text/plain';
	public const FORMAT_WIKI = 'text/x-wiki';
	public const FORMAT_HTML = 'text/html';
	public const FORMAT_HTML_DIFF = 'text/html; disposition=diff';
	public const FORMAT_HTML_VERBOSE = 'text/html; disposition=verbose';
	public const FORMAT_HTML_VERBOSE_PREVIEW = 'text/html; disposition=verbose-preview';

	/**
	 * Options key for controlling error handling.
	 */
	public const OPT_ON_ERROR = 'on-error';

	/**
	 * Value for the OPT_ON_ERROR option indicating that recoverable
	 * errors should cause a warning to be show to the user.
	 */
	public const ON_ERROR_WARN = 'warn';

	/**
	 * Value for the OPT_ON_ERROR option indicating that recoverable
	 * errors should cause the formatting to fail with an exception
	 */
	public const ON_ERROR_FAIL = 'fail';

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
