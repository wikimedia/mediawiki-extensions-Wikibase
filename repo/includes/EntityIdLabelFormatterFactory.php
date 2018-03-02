<?php

namespace Wikibase\Repo;

use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * A factory for generating EntityIdHtmlLinkFormatters.
 *
 * @license GPL-2.0-or-later
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
	 * @see EntityIdFormatterFactory::getEntityIdFormatter
	 *
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 *
	 * @return EntityIdLabelFormatter
	 */
	public function getEntityIdFormatter( LabelDescriptionLookup $labelDescriptionLookup ) {
		return new EntityIdLabelFormatter( $labelDescriptionLookup );
	}

}
