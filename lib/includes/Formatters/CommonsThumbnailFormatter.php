<?php

namespace Wikibase\Lib\Formatters;

use DataValues\StringValue;
use Title;
use ValueFormatters\ValueFormatter;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterTypeException;

/**
 * A rich wikitext formatter for values of the "commonsMedia" property type. It assumes
 * InstantCommons is enabled on the wiki consuming the resulting wikitext.
 *
 * @see https://www.mediawiki.org/wiki/InstantCommons
 *
 * @todo Most feature requests require this to be a SnakFormatter instead of a ValueFormatter
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class CommonsThumbnailFormatter implements ValueFormatter {

	/**
	 * @see ValueFormatter::format
	 *
	 * @param StringValue $value A MediaWiki Commons file name
	 *
	 * @throws ParameterTypeException
	 * @return string Wikitext
	 */
	public function format( $value ) {
		Assert::parameterType( StringValue::class, $value, 'value' );

		$fileName = $value->getValue();

		$title = Title::newFromText( $fileName, NS_FILE );

		if ( $title === null ) {
			return wfEscapeWikiText( $fileName );
		}

		// TODO: Add the (localized) label of the property the image came from
		// TODO: Render multiple images as <gallery>
		// TODO: Automatically add "upright" to upright images
		// TODO: Allow the user to override all image parameters
		$imageParameters = 'frameless';

		return '[[' . $title->getFullText() . '|' . $imageParameters . ']]';
	}

}
