<?php

namespace Wikibase\Repo;

use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lib\Formatters\DispatchingEntityIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\NonExistingEntityIdHtmlFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\UnknownTypeEntityIdHtmlLinkFormatter;
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
	 * TODO Point of the interface? Should it not - at least - lose its param?
	 *
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 *
	 * @return EntityIdFormatter
	 */
	public function getEntityIdFormatter( LabelDescriptionLookup $labelDescriptionLookup ) {
		return new DispatchingEntityIdHtmlLinkFormatter(
			$this->buildFormatters(),
			new UnknownTypeEntityIdHtmlLinkFormatter(
				$this->titleLookup,
				new NonExistingEntityIdHtmlFormatter( 'wikibase-deletedentity-' )
			)
		);
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
