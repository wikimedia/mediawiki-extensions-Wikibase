<?php

namespace Wikibase\Lib;

use DataValues\StringValue;
use Html;
use InvalidArgumentException;
use Title;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

use Wikibase\Repo\WikibaseRepo as WikibaseRepo;
use SiteSQLStore;

/**
 * Formats a StringValue as an HTML link.
 *
 * @since 0.5
 *
 * @todo Use MediaWiki renderer
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang
 */
class CommonsLinkFormatter implements ValueFormatter {

	/**
	 * @var array HTML attributes to use on the generated <a> tags.
	 */
	protected $attributes;

	public function __construct( FormatterOptions $options ) {
		// @todo configure from options
		$this->attributes = array(
			'class' => 'extiw'
		);
	}

	/**
	 * Formats the given commons file name as an HTML link
	 *
	 * @since 0.5
	 *
	 * @param StringValue $value The commons file name to turn into a link
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException
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

		// Construct URL of the image in the selected commons wiki.
		$commonsSiteId = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'commonsSiteId' );
		$commonsSitePaths = SiteSQLStore::newInstance()->getSite( $commonsSiteId )->getAllPaths();
		$href = str_replace('$1', 'File:'.$title->getPartialURL(),  $commonsSitePaths['page_path']);

		$attributes = array_merge( $this->attributes, array(
			'href' => $href
		) );
		$html = Html::element( 'a', $attributes, $title->getText() );

		return $html;
	}

}
