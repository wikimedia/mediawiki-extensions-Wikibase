<?php

namespace Wikibase\Lib\Formatters;

use DataValues\StringValue;
use InvalidArgumentException;
use Title;
use ValueFormatters\ValueFormatter;
use Wikimedia\Assert\Assert;

/**
 * A rich wikitext formatter for values of the "commonsMedia" property type. It assumes
 * InstantCommons is enabled on the wiki consuming the resulting wikitext.
 *
 * @see https://www.mediawiki.org/wiki/InstantCommons
 *
 * @todo Most feature requests require this to be a SnakFormatter instead of a ValueFormatter
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class CommonsThumbnailFormatter implements ValueFormatter {

	/**
	 * @see ValueFormatter::format
	 *
	 * @since 0.5
	 *
	 * @param StringValue $value A MediaWiki Commons file name
	 *
	 * @throws InvalidArgumentException
	 * @return string Wikitext
	 */
	public function format( $value ) {
		Assert::parameterType( StringValue::class, $value, 'value' );

		$fileName = $value->getValue();

		// We cannot use makeTitle because it does not call secureAndSplit
		$title = Title::makeTitleSafe( NS_FILE, $fileName );

		if ( $title === null ) {
			return wfEscapeWikiText( $fileName );
		}

		// TODO: Automatically add "upright" to upright images
		// TODO: Add the label of the property the image came from
		// TODO: Allow the user to override all image parameters
		$imageParameters = 'frameless';

		return '[[' . $title->getFullText() . '|' . $imageParameters . ']]';
	}

}
