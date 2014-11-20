<?php

namespace Wikibase\Repo\View;

use Closure;
use InvalidArgumentException;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\ClaimHtmlGenerator;
use Wikibase\ItemView;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityRetrievingTermLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LabelLookupFactory;
use Wikibase\Lib\Store\LanguageLabelLookup;
use Wikibase\PropertyView;
use Wikibase\Repo\View\ClaimsView;
use Wikibase\Repo\View\FingerprintView;
use Wikibase\Repo\View\SectionEditLinkGenerator;
use Wikibase\Repo\View\SnakHtmlGenerator;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityViewFactory {

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var Closure
	 */
	private $snakFormatterFactoryBuilder;

	/**
	 * @var SectionEditLinkGenerator
	 */
	private $sectionEditLinkGenerator;

	public function __construct(
		EntityTitleLookup $entityTitleLookup,
		EntityLookup $entityLookup,
		Closure $snakFormatterFactoryForLabelLookupFactoryBuilder
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityLookup = $entityLookup;
		$this->snakFormatterFactoryBuilder = $snakFormatterFactoryForLabelLookupFactoryBuilder;
		$this->sectionEditLinkGenerator = new SectionEditLinkGenerator();
	}

	/**
	 * Creates an EntityView suitable for rendering the entity.
	 *
	 * @param LanguageFallbackChain $fallbackChain
	 * @param string $languageCode
	 * @param string $entityType
	 * @param LabelLookupFactory $labelLookupFactory
	 *
	 * @return EntityView
	 */
	public function newEntityView(
		LanguageFallbackChain $fallbackChain,
		$languageCode,
		$entityType,
		LabelLookupFactory $labelLookupFactory
	 ) {
		$fingerprintView = $this->newFingerprintView( $languageCode );
		$claimsView = $this->newClaimsView( $fallbackChain, $languageCode, $labelLookupFactory );

		// @fixme all that seems needed in EntityView is language code and dir.
		$language = Language::factory( $languageCode );

		// @fixme support more entity types
		if ( $entityType === 'item' ) {
			return new ItemView( $fingerprintView, $claimsView, $language );
		} elseif ( $entityType === 'property' ) {
			return new PropertyView( $fingerprintView, $claimsView, $language );
		}

		throw new InvalidArgumentException( 'No EntityView for entity type: ' . $entityType );
	}

	/**
	 * @param LanguageFallbackChain $fallbackChain
	 * @param string $languageCode
	 * @param LabelLookupFactory $labelLookupFactory
	 *
	 * @return ClaimsView
	 */
	private function newClaimsView(
		LanguageFallbackChain $fallbackChain,
		$languageCode,
		LabelLookupFactory $labelLookupFactory
	) {
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$this->getSnakFormatter( $fallbackChain, $languageCode, $labelLookupFactory ),
			$this->entityTitleLookup
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$snakHtmlGenerator,
			$this->entityTitleLookup
		);

		return new ClaimsView(
			$this->entityTitleLookup,
			$this->sectionEditLinkGenerator,
			$claimHtmlGenerator,
			$languageCode
		);
	}

	/**
	 * @param string $languageCode
	 *
	 * @return FingerprintView
	 */
	private function newFingerprintView( $languageCode ) {
		return new FingerprintView(
			$this->sectionEditLinkGenerator,
			$languageCode
		);
	}

	/**
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param string $languageCode
	 * @param LabelLookupFactory $labelLookupFactory
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter(
		LanguageFallbackChain $languageFallbackChain,
		$languageCode,
		LabelLookupFactory $labelLookupFactory
	) {
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $languageCode );
		$formatterOptions->setOption( 'languages', $languageFallbackChain );

		$snakFormatterFactory = call_user_func( $this->snakFormatterFactoryBuilder, $labelLookupFactory );
		return $snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_HTML_WIDGET,
			$formatterOptions
		);
	}

}
