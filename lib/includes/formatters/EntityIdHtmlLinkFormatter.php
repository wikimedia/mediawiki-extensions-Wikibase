<?php

namespace Wikibase\Lib;

use Html;
use InvalidArgumentException;
use OutOfBoundsException;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;

/**
 * Formats entity IDs by generating an html link to the corresponding page title.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang
 */
class EntityIdHtmlLinkFormatter extends EntityIdLabelFormatter {

	/**
	 * Format an EntityId data value
	 *
	 * @param EntityId|EntityIdValue $value The value to format
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException
	 */
	public function format( $value ) {
		$value = $this->unwrapEntityId( $value );

		$title = Title::newFromText( $value->getPrefixedId() );
		$attributes = array(
			'title' => $title->getPrefixedText(),
			'href' => $title->getLocalURL()
		);

		$label = $value->getPrefixedId();

		if ( $this->getOption( self::OPT_RESOLVE_ID ) ) {
			try {
				$itemLabel = $this->lookupItemLabel( $value );
				if ( is_string( $itemLabel ) ) {
					$label = $itemLabel;
				}
			} catch ( OutOfBoundsException $ex ) {
				$attributes['class'] = 'new';
			}
		}

		$html = Html::element( 'a', $attributes, $label );

		return $html;
	}

}
