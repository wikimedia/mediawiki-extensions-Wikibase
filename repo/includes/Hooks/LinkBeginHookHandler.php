<?php

namespace Wikibase\Repo\Hooks;

use Action;
use DummyLinker;
use HtmlArmor;
use Language;
use MediaWiki\Interwiki\InterwikiLookup;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use RequestContext;
use SpecialPageFactory;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\EntityIdLookup;

/**
 * Handler for the LinkBegin hook, used to change the default link text of links to wikibase Entity
 * pages to the respective entity's label. This is used mainly for listings on special pages or for
 * edit summaries, where it is useful to see pages listed by label rather than their entity ID.
 *
 * Label lookups are relatively expensive if done repeatedly for individual labels. If possible,
 * labels should be pre-loaded and buffered for later use via the LinkBegin hook.
 *
 * @see LabelPrefetchHookHandlers
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LinkBeginHookHandler {

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var LanguageFallbackChain
	 */
	private $languageFallback;

	/**
	 * @var Language
	 */
	private $pageLanguage;

	/**
	 * @var LinkRenderer
	 */
	private $linkRenderer;

	/**
	 * @var InterwikiLookup
	 */
	private $interwikiLookup;

	/**
	 * @return self
	 */
	private static function newFromGlobalState() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$languageFallbackChainFactory = $wikibaseRepo->getLanguageFallbackChainFactory();
		// NOTE: keep in sync with fallback chain construction in LabelPrefetchHookHandler::newFromGlobalState
		$context = RequestContext::getMain();
		$languageFallbackChain = $languageFallbackChainFactory->newFromContext( $context );

		return new self(
			$wikibaseRepo->getEntityIdLookup(),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getTermLookup(),
			$wikibaseRepo->getEntityNamespaceLookup(),
			$languageFallbackChain,
			$context->getLanguage(),
			MediaWikiServices::getInstance()->getLinkRenderer(),
			MediaWikiServices::getInstance()->getInterwikiLookup()
		);
	}

	/**
	 * Special page handling where we want to display meaningful link labels instead of just the items ID.
	 * This is only handling special pages right now and gets disabled in normal pages.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinkBegin
	 *
	 * @param DummyLinker $dummy Used to be a skin, but that eliminated.
	 * @param Title $target
	 * @param string $html
	 * @param array $customAttribs
	 * @param array $query
	 * @param array $options
	 * @param mixed $ret
	 *
	 * @return bool true
	 */
	public static function onLinkBegin(
		$dummy,
		Title $target,
		&$html,
		array &$customAttribs,
		array &$query,
		&$options,
		&$ret
	) {
		$context = RequestContext::getMain();
		if ( !$context->hasTitle() ) {
			// Short-circuit this hook if no title is
			// set in the main context (T131176)
			return true;
		}

		$handler = self::newFromGlobalState();
		$handler->doOnLinkBegin( $target, $html, $customAttribs, $context );

		return true;
	}

	/**
	 * @param EntityIdLookup $entityIdLookup
	 * @param EntityIdParser $entityIdParser
	 * @param TermLookup $termLookup
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param LanguageFallbackChain $languageFallback
	 * @param Language $pageLanguage
	 * @param LinkRenderer $linkRenderer
	 * @param InterwikiLookup $interwikiLookup
	 *
	 * @todo: Would be nicer to take a LabelDescriptionLookup instead of TermLookup + FallbackChain.
	 */
	public function __construct(
		EntityIdLookup $entityIdLookup,
		EntityIdParser $entityIdParser,
		TermLookup $termLookup,
		EntityNamespaceLookup $entityNamespaceLookup,
		LanguageFallbackChain $languageFallback,
		Language $pageLanguage,
		LinkRenderer $linkRenderer,
		InterwikiLookup $interwikiLookup
	) {
		$this->entityIdLookup = $entityIdLookup;
		$this->entityIdParser = $entityIdParser;
		$this->termLookup = $termLookup;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->languageFallback = $languageFallback;
		$this->pageLanguage = $pageLanguage;
		$this->linkRenderer = $linkRenderer;
		$this->interwikiLookup = $interwikiLookup;
	}

	/**
	 * @param Title $target
	 * @param string &$html
	 * @param array &$customAttribs
	 * @param RequestContext $context
	 */
	public function doOnLinkBegin( Title $target, &$html, array &$customAttribs, RequestContext $context ) {
		$out = $context->getOutput();
		$outTitle = $out->getTitle();

		// For good measure: Don't do anything in case the OutputPage has no Title set.
		if ( !$outTitle ) {
			return;
		}

		// if custom link text is given, there is no point in overwriting it
		// but not if it is similar to the plain title
		if ( $html !== null && $target->getFullText() !== $html ) {
			return;
		}

		$foreignEntityId = $this->parseForeignEntityId( $target );
		$isLocal = !$foreignEntityId;

		if ( $isLocal
			&& !$this->entityNamespaceLookup->isEntityNamespace( $target->getNamespace() )
		) {
			return;
		}

		// Only continue on pages with edit summaries (histories / diffs) or on special pages.
		// Don't run this code when accessing it through the api (eg. for parsing) as the title is
		// set to a special page dummy in api.php, see https://phabricator.wikimedia.org/T111346
		if ( defined( 'MW_API' ) || !$this->shouldConvert( $outTitle, $context ) ) {
			return;
		}

		$targetText = $target->getText();

		// Handle "fake" titles for new entities as generated by
		// EditEntity::getContextForEditFilter(). For instance, a link to Property:NewProperty
		// would be replaced by a link to Special:NewProperty. This is useful in logs,
		// to indicate that the logged action occurred while creating an entity.
		if ( SpecialPageFactory::exists( $targetText ) ) {
			$target = Title::makeTitle( NS_SPECIAL, $targetText );
			$html = $this->linkRenderer->makeKnownLink( $target );
			return;
		}

		if ( $isLocal && !$target->exists() ) {
			// The link points to a non-existing item.
			return;
		}

		$entityId = $foreignEntityId ?: $this->entityIdLookup->getEntityIdForTitle( $target );

		if ( !$entityId ) {
			return;
		}

		// @todo: this re-implements the logic in LanguageFallbackLabelDescriptionLookup,
		// as that didn't support descriptions back when this code was written.

		// NOTE: keep in sync with with fallback languages in LabelPrefetchHookHandler::newFromGlobalState

		try {
			$labels = $this->termLookup->getLabels( $entityId, $this->languageFallback->getFetchLanguageCodes() );
			$descriptions = $this->termLookup->getDescriptions( $entityId, $this->languageFallback->getFetchLanguageCodes() );
		} catch ( StorageException $ex ) {
			// This shouldn't happen if $target->exists() return true!
			return;
		}

		$labelData = $this->getPreferredTerm( $labels );
		$descriptionData = $this->getPreferredTerm( $descriptions );

		$html = $this->getHtml( $entityId->getSerialization(), $labelData );

		$customAttribs['title'] = $this->getTitleAttribute(
			$target,
			$labelData,
			$descriptionData
		);

		// add wikibase styles in all cases, so we can format the link properly:
		$out->addModuleStyles( [ 'wikibase.common' ] );
	}

	/**
	 * @param LinkTarget $target
	 *
	 * @return EntityId|null
	 */
	private function parseForeignEntityId( LinkTarget $target ) {
		$interwiki = $target->getInterwiki();

		if ( $interwiki === '' || !$this->interwikiLookup->isValidInterwiki( $interwiki ) ) {
			return null;
		}

		$idPart = $this->extractForeignIdString( $target->getText() );

		if ( $idPart !== null ) {
			try {
				// FIXME: This assumes repository name is equal to interwiki. This assumption might
				// become invalid
				return $this->entityIdParser->parse(
					EntityId::joinSerialization( [ $interwiki, '', $idPart ] )
				);
			} catch ( EntityIdParsingException $ex ) {
			}
		}

		return null;
	}

	/**
	 * @param string $pageName
	 *
	 * @return string|null
	 */
	private function extractForeignIdString( $pageName ) {
		// FIXME: This encodes knowledge from EntityContentFactory::getTitleForId
		$prefix = 'Special:EntityPage/';
		$prefixLength = 19;

		if ( strncmp( $pageName, $prefix, $prefixLength ) === 0 ) {
			return substr( $pageName, $prefixLength );
		}

		return null;
	}

	/**
	 * @param array $termsByLanguage
	 *
	 * @return string[]|null
	 */
	private function getPreferredTerm( array $termsByLanguage ) {
		if ( empty( $termsByLanguage ) ) {
			return null;
		}

		return $this->languageFallback->extractPreferredValueOrAny(
			$termsByLanguage
		);
	}

	/**
	 * Whether we should try to convert links on this page.
	 * This caches that result within a static variable,
	 * thus it can't change (except in phpunit tests).
	 *
	 * @param Title|null $currentTitle
	 * @param RequestContext $context
	 *
	 * @return bool
	 */
	private function shouldConvert( Title $currentTitle = null, RequestContext $context ) {
		static $shouldConvert = null;
		if ( $shouldConvert !== null && !defined( 'MW_PHPUNIT_TEST' ) ) {
			return $shouldConvert;
		}

		$actionName = Action::getActionName( $context );
		 // This is how Article detects diffs
		$isDiff = $actionName === 'view' && $context->getRequest()->getCheck( 'diff' );

		// Only continue on pages with edit summaries (histories / diffs) or on special pages.
		if (
			( $currentTitle === null || !$currentTitle->isSpecialPage() )
			&& $actionName !== 'history'
			&& !$isDiff
		) {
			// Note: this may not work right with special page transclusion. If $out->getTitle()
			// doesn't return the transcluded special page's title, the transcluded text will
			// not have entity IDs resolved to labels.
			$shouldConvert = false;
			return false;
		}

		$shouldConvert = true;
		return true;
	}

	/**
	 * @param string[]|null $termData A term record as returned by
	 * LanguageFallbackChain::extractPreferredValueOrAny(),
	 * containing the 'value' and 'language' fields, or null
	 * or an empty array.
	 *
	 * @see LanguageFallbackChain::extractPreferredValueOrAny
	 *
	 * @return array list( string $text, Language $language )
	 */
	private function extractTextAndLanguage( array $termData = null ) {
		if ( $termData ) {
			return [
				$termData['value'],
				Language::factory( $termData['language'] )
			];
		} else {
			return [
				'',
				$this->pageLanguage
			];
		}
	}

	/**
	 * @param string $entityIdSerialization
	 * @param string[]|null $labelData
	 *
	 * @return string
	 */
	private function getHtml( $entityIdSerialization, array $labelData = null ) {
		/** @var Language $labelLang */
		list( $labelText, $labelLang ) = $this->extractTextAndLanguage( $labelData );

		$idHtml = '<span class="wb-itemlink-id">'
			. wfMessage(
				'wikibase-itemlink-id-wrapper',
				$entityIdSerialization
			)->inContentLanguage()->escaped()
			. '</span>';

		$labelHtml = '<span class="wb-itemlink-label"'
				. ' lang="' . htmlspecialchars( $labelLang->getHtmlCode() ) . '"'
				. ' dir="' . htmlspecialchars( $labelLang->getDir() ) . '">'
			. HtmlArmor::getHtml( $labelText )
			. '</span>';

		return '<span class="wb-itemlink">'
			. wfMessage( 'wikibase-itemlink' )->rawParams(
				$labelHtml,
				$idHtml
			)->inContentLanguage()->escaped()
			. '</span>';
	}

	/**
	 * @param Title $title
	 * @param string[]|null $labelData
	 * @param string[]|null $descriptionData
	 *
	 * @return string The plain, unescaped title="…" attribute for the link.
	 */
	private function getTitleAttribute( Title $title, array $labelData = null, array $descriptionData = null ) {
		/** @var Language $labelLang */
		/** @var Language $descriptionLang */

		list( $labelText, $labelLang ) = $this->extractTextAndLanguage( $labelData );
		list( $descriptionText, $descriptionLang ) = $this->extractTextAndLanguage( $descriptionData );

		// Set title attribute for constructed link, and make tricks with the directionality to get it right
		$titleText = ( $labelText !== '' )
			? $labelLang->getDirMark() . $labelText
				. $this->pageLanguage->getDirMark()
			: $title->getPrefixedText();

		if ( $descriptionText !== '' ) {
			$descriptionText = $descriptionLang->getDirMark() . $descriptionText
				. $this->pageLanguage->getDirMark();
			return wfMessage(
				'wikibase-itemlink-title',
				$titleText,
				$descriptionText
			)->inContentLanguage()->text();
		} else {
			return $titleText; // no description, just display the title then
		}
	}

}
