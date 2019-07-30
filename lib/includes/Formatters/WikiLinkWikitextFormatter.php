<?php

namespace Wikibase\Lib\Formatters;

use DataValues\StringValue;
use InvalidArgumentException;
use Title;
use ValueFormatters\ValueFormatter;

/**
 * Formats the StringValue from a snak as an Wikitext link.
 *
 * @license GPL-2.0-or-later
 */
class WikiLinkWikitextFormatter implements ValueFormatter {

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
	 * Formats the given page title as an Wikitext link
	 *
	 * @param StringValue $value The page title to  be turned into a link
	 *
	 * @throws InvalidArgumentException
	 * @return string Wikitext external link
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a StringValue.' );
		}

		$title = Title::makeTitleSafe( $this->namespace, $value->getValue() );

		if ( !$title ) {
			throw new InvalidArgumentException(
				"Failed to make a Title for text `{$value->getValue()}` in namespace {$this->namespace}."
			);
		}

		return '[[:' . $title->getFullText() . '|' . $value->getValue() . ']]';
	}

}
