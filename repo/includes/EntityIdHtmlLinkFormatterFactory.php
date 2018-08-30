<?php

namespace Wikibase\Repo;

use Language;
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

	/**
	 * @var callable[]
	 */
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
	 * @param Language|LabelDescriptionLookup $language
	 *
	 * @return EntityIdFormatter
	 */
	public function getEntityIdFormatter( $language ) {
		// TODO accept only Language
		if ( $language instanceof LabelDescriptionLookup ) {
			$labelDescriptionLookup = $language;
		} else {
			$labelDescriptionLookup = WikibaseRepo::getDefaultInstance()
				->getLanguageFallbackLabelDescriptionLookupFactory()
				->newLabelDescriptionLookup( $language );
		}

		return new DispatchingEntityIdHtmlLinkFormatter(
			$this->buildFormatters( $language ),
			// TODO switch for a simple, true fallback implementation
			new DefaultEntityIdHtmlLinkFormatter(
				$labelDescriptionLookup,
				$this->titleLookup,
				$this->languageNameLookup
			)
		);
	}

	/**
	 * @param Language $language
	 *
	 * @return EntityIdFormatter[]
	 */
	private function buildFormatters( Language $language ) {
		$formatters = [];

		foreach ( $this->formatterCallbacks as $type => $func ) {
			$formatters[ $type ] = call_user_func( $func, $language );
		}

		return $formatters;
	}

}
