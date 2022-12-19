<?php

namespace Wikibase\Lib\Formatters;

use DataValues\StringValue;
use Html;
use InvalidArgumentException;
use Title;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * Formats the StringValue from a "commonsMedia" snak as an HTML link pointing to the file
 * description page on Wikimedia Commons.
 *
 * @todo Use MediaWiki renderer
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class CommonsLinkFormatter implements ValueFormatter {

	/**
	 * @var array HTML attributes to use on the generated <a> tags.
	 */
	private $attributes;

	/**
	 * @param FormatterOptions|null $options
	 */
	public function __construct( FormatterOptions $options = null ) {
		// @todo configure from options
		$this->attributes = [
			'class' => 'extiw',
		];
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * Formats the given commons file name as an HTML link
	 *
	 * @param StringValue $value The commons file name to turn into a link
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a StringValue.' );
		}

		$fileName = $value->getValue();
		// We are using NS_MAIN only because makeTitleSafe requires a valid namespace
		// We cannot use makeTitle because it does not secureAndSplit()
		$title = Title::makeTitleSafe( NS_MAIN, $fileName );
		if ( $title === null ) {
			return htmlspecialchars( $fileName );
		}

		$attributes = array_merge( $this->attributes, [
			'href' => '//commons.wikimedia.org/wiki/File:' . $title->getPartialURL(),
		] );
		$html = Html::element( 'a', $attributes, $fileName );

		return $html;
	}

}
