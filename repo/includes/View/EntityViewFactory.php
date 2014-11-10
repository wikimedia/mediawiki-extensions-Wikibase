<?php

namespace Wikibase\Repo\View;

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
use Wikibase\Lib\Store\LanguageLabelLookup;
use Wikibase\Lib\WikibaseValueFormatterBuilders;
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
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @var SectionEditLinkGenerator
	 */
	private $sectionEditLinkGenerator;

	public function __construct(
		EntityTitleLookup $entityTitleLookup,
		EntityLookup $entityLookup,
		OutputFormatSnakFormatterFactory $snakFormatterFactory
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityLookup = $entityLookup;
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->sectionEditLinkGenerator = new SectionEditLinkGenerator();
	}

	/**
	 * Creates an EntityView suitable for rendering the entity.
	 *
	 * @param LanguageFallbackChain $fallbackChain
	 * @param string $languageCode
	 * @param string $entityType
	 *
	 * @return EntityView
	 */
	public function newEntityView(
		LanguageFallbackChain $fallbackChain,
		$languageCode,
		$entityType
	 ) {
		$fingerprintView = $this->newFingerprintView( $languageCode );
		$claimsView = $this->newClaimsView( $fallbackChain, $languageCode );

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
	 *
	 * @return ClaimsView
	 */
	private function newClaimsView(
		LanguageFallbackChain $fallbackChain,
		$languageCode
	) {
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$this->getSnakFormatter( $fallbackChain, $languageCode ),
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
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter(
		LanguageFallbackChain $languageFallbackChain,
		$languageCode
	) {
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $languageCode );
		$formatterOptions->setOption( 'languages', $languageFallbackChain );

		// @fixme use language fallback here
		return $this->snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_HTML_WIDGET,
			$this->getValueFormatterBuilders( $languageCode ),
			$formatterOptions
		);
	}

	/**
	 * @param string $languageCode
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	private function getValueFormatterBuilders( $languageCode ) {
		$termLookup = new EntityRetrievingTermLookup( $this->entityLookup );

		return new WikibaseValueFormatterBuilders(
			$this->entityLookup,
			Language::factory( $languageCode ),
			new LanguageLabelLookup( $termLookup, $languageCode ),
			$this->entityTitleLookup
		);
	}

}
