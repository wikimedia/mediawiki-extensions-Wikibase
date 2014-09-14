<?php

namespace Wikibase\Repo\View;

use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\ClaimHtmlGenerator;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Factory to create a ClaimsView
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ClaimsViewFactory {

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityInfoBuilderFactory
	 */
	private $entityInfoBuilderFactory;

	public function __construct(
		OutputFormatSnakFormatterFactory $snakFormatterFactory,
		EntityTitleLookup $entityTitleLookup,
		EntityInfoBuilderFactory $entityInfoBuilderFactory
	) {
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityInfoBuilderFactory = $entityInfoBuilderFactory;
	}

	/**
	 * Creates a claims view.
	 *
	 * @since 0.5
	 *
	 * @param string $languageCode
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @return ClaimsView
	 */
	public function createClaimsView( $languageCode, LanguageFallbackChain $languageFallbackChain ) {
		$sectionEditLinkGenerator = new SectionEditLinkGenerator();

		$formatterOptions = new FormatterOptions();

		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $languageCode );
		$formatterOptions->setOption( 'languages', $languageFallbackChain );

		$snakFormatter = $this->snakFormatterFactory->getSnakFormatter( SnakFormatter::FORMAT_HTML_WIDGET, $formatterOptions );

		$snakHtmlGenerator = new SnakHtmlGenerator(
			$snakFormatter,
			$this->entityTitleLookup
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$snakHtmlGenerator,
			$this->entityTitleLookup
		);

		return new ClaimsView(
			$this->entityInfoBuilderFactory,
			$this->entityTitleLookup,
			$sectionEditLinkGenerator,
			$claimHtmlGenerator,
			$languageCode
		);
	}

}
