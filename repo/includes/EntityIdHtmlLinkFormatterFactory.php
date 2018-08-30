<?php

namespace Wikibase\Repo;

use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lib\DefaultEntityIdHtmlLinkFormatter;
use Wikibase\Lib\Formatters\DispatchingEntityIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\EntityIdFormatterFactory;
use Wikimedia\Assert\Assert;

/**
 * A factory for generating EntityIdFormatter returning HTML.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdHtmlLinkFormatterFactory implements EntityIdFormatterFactory {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	private $formatterCallbacks;

	public function __construct(
		EntityTitleLookup $titleLookup,
		LanguageNameLookup $languageNameLookup,
		array $formatterCallbacks = []
	) {
		Assert::parameterElementType( 'callable', $formatterCallbacks, '$formatterCallbacks' );

		$this->formatterCallbacks = $formatterCallbacks;
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
	 * @return EntityIdFormatter
	 */
	public function getEntityIdFormatter( LabelDescriptionLookup $labelDescriptionLookup ) {
		$defaultFormatter = new DefaultEntityIdHtmlLinkFormatter(
			$labelDescriptionLookup,
			$this->titleLookup,
			$this->languageNameLookup
		);
		$formatters = $this->buildFormatters();
		return new DispatchingEntityIdHtmlLinkFormatter( $formatters, $defaultFormatter );
	}

	private function buildFormatters() {
		$formatters = [];

		foreach ( $this->formatterCallbacks as $type => $func ) {
			$formatter = call_user_func( $func );
			$formatters[ $type ] = $formatter;
		}

		return $formatters;
	}

}
