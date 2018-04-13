<?php

namespace Wikibase\Lib\Formatters;

use DataValues\StringValue;
use ImageGalleryBase;
use InvalidArgumentException;
use Title;
use ValueFormatters\ValueFormatter;

/**
 * Formats the StringValue from a "commonsMedia" snak as an HTML link pointing to the file
 * description page on Wikimedia Commons.
 *
 * @todo Use MediaWiki renderer
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Marius Hoch
 */
class CommonsInlineImageFormatter implements ValueFormatter {

	/**
	 * @see ValueFormatter::format
	 *
	 * Formats the given commons file name as an HTML image gallery.
	 *
	 * @param StringValue $value The commons file name
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a StringValue.' );
		}

		$fileName = $value->getValue();
		// We cannot use makeTitle because it does not secureAndSplit()
		$title = Title::makeTitleSafe( NS_FILE, $fileName );
		if ( $title === null ) {
			return htmlspecialchars( $fileName );
		}

		/** @var CommonsMediaImageGallery $imageGallery */
		$imageGallery = ImageGalleryBase::factory( 'wikibase-commons-media' );
		$imageGallery->add( $title );

		return $imageGallery->toHTML();
	}

}
