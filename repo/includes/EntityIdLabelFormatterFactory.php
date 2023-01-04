<?php

declare( strict_types = 1 );

namespace Wikibase\Repo;

use Language;
use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * A factory for generating EntityIdHtmlLinkFormatters.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdLabelFormatterFactory implements EntityIdFormatterFactory {

	private FallbackLabelDescriptionLookupFactory $lookupFactory;

	public function __construct( FallbackLabelDescriptionLookupFactory $lookupFactory ) {
		$this->lookupFactory = $lookupFactory;
	}

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
	 * @param Language $language
	 *
	 * @return EntityIdLabelFormatter
	 */
	public function getEntityIdFormatter( Language $language ) {
		$labelDescriptionLookup = $this->lookupFactory->newLabelDescriptionLookup( $language );

		return new EntityIdLabelFormatter( $labelDescriptionLookup );
	}

}
