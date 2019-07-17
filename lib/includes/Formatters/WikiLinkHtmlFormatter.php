<?php

namespace Wikibase\Lib\Formatters;

use DataValues\StringValue;
use Html;
use InvalidArgumentException;
use Title;
use ValueFormatters\ValueFormatter;

/**
 * Formats the StringValue from a snak as an HTML link.
 *
 * @license GPL-2.0-or-later
 */
class WikiLinkHtmlFormatter implements ValueFormatter {

	/**
	 * @var int
	 */
	private $namespace;

	/**
	 * @param int $namespace namespace to be used to format the link
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * Formats the given page title as an HTML link
	 *
	 * @param StringValue $value The page title to  be turned into a link
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a StringValue.' );
		}

		$title = Title::makeTitle( $this->namespace, $value->getValue() );

		if ( !$title ) {
			throw new InvalidArgumentException(
				"Failed to make a Title for text `{$value->getValue()}` in namespace {$this->namespace}."
			);
		}

		return Html::element( 'a', [
			'href' => $title->getInternalURL(),
		], $value->getValue() );
	}

}
