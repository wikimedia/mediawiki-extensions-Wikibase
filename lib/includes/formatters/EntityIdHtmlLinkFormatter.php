<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use Html;
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

		$title = parent::format( $value );

		$attributes = array(
			'href' => Title::newFromText( $value->getPrefixedId() )->getLocalUrl()
		);

		$html = Html::element( 'a', $attributes, $title );

		return $html;
	}
}
