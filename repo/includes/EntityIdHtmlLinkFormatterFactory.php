<?php

namespace Wikibase\Repo;

use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleStoreLookup;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * A factory for generating EntityIdHtmlLinkFormatters.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityIdHtmlLinkFormatterFactory implements EntityIdFormatterFactory {

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $titleLookup;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @param EntityTitleStoreLookup $titleLookup
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function __construct(
		EntityTitleStoreLookup $titleLookup,
		LanguageNameLookup $languageNameLookup
	) {
		$this->titleLookup = $titleLookup;
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * @see EntityIdFormatterFactory::getOutputFormat
	 *
	 * @return string SnakFormatter::FORMAT_HTML
	 */
	public function getOutputFormat() {
		return SnakFormatter::FORMAT_HTML;
	}

	/**
	 * @see EntityIdFormatterFactory::getEntityIdFormatter
	 *
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 *
	 * @return EntityIdHtmlLinkFormatter
	 */
	public function getEntityIdFormatter( LabelDescriptionLookup $labelDescriptionLookup ) {
		return new EntityIdHtmlLinkFormatter(
			$labelDescriptionLookup,
			$this->titleLookup,
			$this->languageNameLookup
		);
	}

}
