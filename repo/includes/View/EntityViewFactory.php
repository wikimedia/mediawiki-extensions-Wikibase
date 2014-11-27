<?php

namespace Wikibase\Repo\View;

use InvalidArgumentException;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\Repo\WikibaseRepo;

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
	 * @var string[]
	 */
	private $siteLinkGroups;

	public function __construct(
		EntityTitleLookup $entityTitleLookup,
		EntityLookup $entityLookup,
		OutputFormatSnakFormatterFactory $snakFormatterFactory,
		array $siteLinkGroups
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityLookup = $entityLookup;
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->sectionEditLinkGenerator = new SectionEditLinkGenerator();
		$this->siteLinkGroups = $siteLinkGroups;
	}

	/**
	 * Creates an EntityView suitable for rendering the entity.
	 *
	 * @param string $entityType
	 * @param string $languageCode
	 * @param LanguageFallbackChain|null $fallbackChain
	 * @param LabelLookup|null $labelLookup
	 *
	 * @throws InvalidArgumentException
	 * @return EntityView
	 */
	public function newEntityView(
		$entityType,
		$languageCode,
		LanguageFallbackChain $fallbackChain = null,
		LabelLookup $labelLookup = null
	 ) {
		$fingerprintView = $this->newFingerprintView( $languageCode );
		$claimsView = $this->newClaimsView( $languageCode, $fallbackChain, $labelLookup );

		// @fixme all that seems needed in EntityView is language code and dir.
		$language = Language::factory( $languageCode );

		// @fixme support more entity types
		if ( $entityType === 'item' ) {
			return new ItemView( $fingerprintView, $claimsView, $language, $this->siteLinkGroups );
		} elseif ( $entityType === 'property' ) {
			$displayStatementsOnProperties = WikibaseRepo::getDefaultInstance()->getSettings()
					->getSetting( 'displayStatementsOnProperties' );

			return new PropertyView( $fingerprintView, $claimsView, $language, $displayStatementsOnProperties );
		}

		throw new InvalidArgumentException( 'No EntityView for entity type: ' . $entityType );
	}

	/**
	 * @param string $languageCode
	 * @param LanguageFallbackChain|null $fallbackChain
	 * @param LabelLookup|null $labelLookup
	 *
	 * @return ClaimsView
	 */
	private function newClaimsView(
		$languageCode,
		LanguageFallbackChain $fallbackChain = null,
		LabelLookup $labelLookup = null
	) {
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$this->getSnakFormatter( $languageCode, $fallbackChain, $labelLookup ),
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
	 * @param string $languageCode
	 * @param LanguageFallbackChain|null $languageFallbackChain
	 * @param LabelLookup|null $labelLookup
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter(
		$languageCode,
		LanguageFallbackChain $languageFallbackChain = null,
		LabelLookup $labelLookup = null
	) {
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $languageCode );

		if ( $languageFallbackChain ) {
			$formatterOptions->setOption( 'languages', $languageFallbackChain );
		}

		if ( $labelLookup ) {
			$formatterOptions->setOption( 'LabelLookup', $labelLookup );
		}

		return $this->snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_HTML_WIDGET,
			$formatterOptions
		);
	}

}
