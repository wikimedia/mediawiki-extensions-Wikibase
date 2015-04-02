<?php

namespace Wikibase\Repo;

use Wikibase\Lib\EntityIdLabelFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\LabelDescriptionLookup;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * A factory for generating EntityIdHtmlLinkFormatters.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityIdLabelFormatterFactory implements EntityIdFormatterFactory {

	/**
	 * @see EntityIdFormatterFactory::getOutputFormat
	 *
	 * @return string SnakFormatter::FORMAT_HTML
	 */
	public function getOutputFormat() {
		return SnakFormatter::FORMAT_PLAIN;
	}

	/**
	 * @see EntityIdFormatterFactory::getEntityIdFormater
	 *
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 *
	 * @return EntityIdLabelFormatter
	 */
	public function getEntityIdFormater( LabelDescriptionLookup $labelDescriptionLookup ) {
		return new EntityIdLabelFormatter( $labelDescriptionLookup );
	}

}
