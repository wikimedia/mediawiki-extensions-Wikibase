<?php

namespace Wikibase\Repo\View;

use InvalidArgumentException;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\ClaimHtmlGenerator;
use Wikibase\ItemView;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityRetrievingTermLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageLabelLookup;
use Wikibase\Lib\Store\TermLookup;
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

	/**
	 * @var WikibaseValueFormatterBuilders
	 */
	private $valueFormatterBuilders = null;

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
	 * @param TermLookup $termLookup
	 * @param LanguageFallbackChain $fallbackChain
	 * @param string $languageCode
	 * @param string $entityType
	 *
	 * @return EntityView
	 */
	public function newEntityView(
		TermLookup $termLookup,
		LanguageFallbackChain $fallbackChain,
		$languageCode,
		$entityType
	 ) {
		$valueFormatterBuilders = $this->getValueFormatterBuilders( $termLookup, $languageCode );
		$entityIdHtmlLinkFormatter = '';

		$fingerprintView = $this->newFingerprintView( $languageCode );
		$claimsView = $this->newClaimsView( $termLookup, $fallbackChain, $languageCode );

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
	 * @param TermLookup $termLookup
	 * @param LanguageFallbackChain $fallbackChain
	 * @param string $languageCode
	 *
	 * @return ClaimsView
	 */
	private function newClaimsView(
		TermLookup $termLookup,
		LanguageFallbackChain $fallbackChain,
		$languageCode
	) {
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$this->newEntityIdHtmlLinkFormatter( $termLookup, $fallbackChain, $languageCode ),
			$this->getSnakFormatter( $termLookup, $fallbackChain, $languageCode ),
			$this->entityTitleLookup
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$snakHtmlGenerator,
			$this->entityTitleLookup
		);

		return new ClaimsView(
			$this->newEntityIdHtmlLinkFormatter( $termLookup, $fallbackChain, $languageCode ),
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
	 * @param TermLookup $termLookup
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param string $languageCode
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter(
		TermLookup $termLookup,
		LanguageFallbackChain $languageFallbackChain,
		$languageCode
	) {
		// @fixme use language fallback here
		return $this->snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_HTML_WIDGET,
			$this->getValueFormatterBuilders( $termLookup, $languageCode ),
			$this->getFormatterOptions( $languageFallbackChain, $languageCode )
		);
	}

	private function getFormatterOptions(
		LanguageFallbackChain $languageFallbackChain,
		$languageCode
	) {
        $formatterOptions = new FormatterOptions();
        $formatterOptions->setOption( ValueFormatter::OPT_LANG, $languageCode );
        $formatterOptions->setOption( 'languages', $languageFallbackChain );

		return $formatterOptions;
	}

	/**
	 * @param TermLookup $termLookup
	 * @param string $languageCode
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	private function getValueFormatterBuilders( TermLookup $termLookup, $languageCode ) {
		return new WikibaseValueFormatterBuilders(
			$this->entityLookup,
			Language::factory( $languageCode ),
			$this->getLabelLookup( $termLookup, $languageCode ),
			$this->entityTitleLookup
		);
	}

	private function getLabelLookup( TermLookup $termLookup, $languageCode ) {
		return new LanguageLabelLookup( $termLookup, $languageCode );
	}

	private function newEntityIdHtmlLinkFormatter(
		TermLookup $termLookup,
		LanguageFallbackChain $languageFallbackChain,
		$languageCode
	) {
		return new EntityIdHtmlLinkFormatter(
			$this->getFormatterOptions( $languageFallbackChain, $languageCode ),
			$this->getLabelLookup( $termLookup, $languageCode ),
			$this->entityTitleLookup
		);
	}

}
