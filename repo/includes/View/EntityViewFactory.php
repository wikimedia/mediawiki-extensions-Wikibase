<?php

namespace Wikibase\Repo\View;

use InvalidArgumentException;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\ClaimHtmlGenerator;
use Wikibase\EntityView;
use Wikibase\ItemView;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\TermLookup;
use Wikibase\Lib\Store\LanguageLabelLookup;
use Wikibase\PropertyView;

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
	 * @var {callable}
	 */
	private $getSnakFormatterForTermLookup;

	/**
	 * @var SectionEditLinkGenerator
	 */
	private $sectionEditLinkGenerator;

	public function __construct(
		EntityTitleLookup $entityTitleLookup,
		EntityLookup $entityLookup,
		$getSnakFormatterForTermLookup
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityLookup = $entityLookup;
		// TODO: replace with callable type hint when we can use PHP 5.4
		if( !is_callable( $getSnakFormatterForTermLookup ) ) {
			throw new InvalidArgumentException( 'getSnakFormatter has to be callable' );
		}
		$this->getSnakFormatterForTermLookup = $getSnakFormatterForTermLookup;
		$this->sectionEditLinkGenerator = new SectionEditLinkGenerator();
	}

	/**
	 * Creates an EntityView suitable for rendering the entity.
	 *
	 * @param LanguageFallbackChain $fallbackChain
	 * @param string $languageCode
	 * @param string $entityType
	 * @param TermLookup $termLookup
	 *
	 * @throws InvalidArgumentException
	 * @return EntityView
	 */
	public function newEntityView(
		LanguageFallbackChain $fallbackChain,
		$languageCode,
		$entityType,
		TermLookup $termLookup
	 ) {
		$fingerprintView = $this->newFingerprintView( $languageCode );
		$claimsView = $this->newClaimsView( $fallbackChain, $languageCode, $termLookup );

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
	 * @param TermLookup $termLookup
	 *
	 * @return ClaimsView
	 */
	private function newClaimsView(
		LanguageFallbackChain $fallbackChain,
		$languageCode,
		TermLookup $termLookup
	) {
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$this->getSnakFormatter( $fallbackChain, $languageCode, $termLookup ),
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
	 * @param TermLookup $termLookup
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter(
		LanguageFallbackChain $languageFallbackChain,
		$languageCode,
		TermLookup $termLookup
	) {
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $languageCode );
		$formatterOptions->setOption( 'languages', $languageFallbackChain );

		return call_user_func(
			$this->getSnakFormatterForTermLookup,
			$termLookup,
			SnakFormatter::FORMAT_HTML_WIDGET,
			$formatterOptions
		);
	}

}
