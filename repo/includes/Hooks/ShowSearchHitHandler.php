<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use Html;
use HtmlArmor;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Search\Hook\ShowSearchHitHook;
use MediaWiki\Search\Hook\ShowSearchHitTitleHook;
use RequestContext;
use SearchResult;
use SpecialSearch;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Search\ExtendedResult;
use Wikibase\Repo\WikibaseRepo;

/**
 * Handler to format entities in the search results
 *
 * @license GPL-2.0-or-later
 * @author Matěj Suchánek
 * @author Daniel Kinzler
 */
class ShowSearchHitHandler implements ShowSearchHitHook, ShowSearchHitTitleHook {

	/** @var EntityContentFactory */
	private $entityContentFactory;
	/** @var EntityIdLookup */
	private $entityIdLookup;
	/** @var EntityLookup */
	private $entityLookup;
	/** @var LanguageFallbackChainFactory */
	private $fallbackChainFactory;

	public function __construct(
		EntityContentFactory $entityContentFactory,
		EntityIdLookup $entityIdLookup,
		EntityLookup $entityLookup,
		LanguageFallbackChainFactory $fallbackChainFactory
	) {
		$this->entityContentFactory = $entityContentFactory;
		$this->entityIdLookup = $entityIdLookup;
		$this->entityLookup = $entityLookup;
		$this->fallbackChainFactory = $fallbackChainFactory;
	}

	/**
	 * Format the output when the search result contains entities
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ShowSearchHit
	 * @see showEntityResultHit
	 * @see showPlainSearchHit
	 * @param SpecialSearch $searchPage
	 * @param SearchResult $result
	 * @param string[] $terms
	 * @param string &$link
	 * @param string &$redirect
	 * @param string &$section
	 * @param string &$extract
	 * @param string &$score
	 * @param string &$size
	 * @param string &$date
	 * @param string &$related
	 * @param string &$html
	 * @return void
	 */
	public function onShowSearchHit( $searchPage, $result,
		$terms, &$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related,
		&$html
	): void {
		if ( $result instanceof ExtendedResult ) {
			return;
		}

		$languageFallbackChain = $this->fallbackChainFactory->newFromContext( $searchPage->getContext() );

		$title = $result->getTitle();

		if ( !$this->isTitleEntity( $title ) ) {
			return;
		}

		try {
			$entity = $this->getEntity( $title );
		} catch ( UnresolvedEntityRedirectException $exception ) {
			return;
		}

		if ( !( $entity instanceof DescriptionsProvider ) ) {
			return;
		}

		$extract = '';

		$entityTerms = $entity->getDescriptions()->toTextArray();
		$termData = $languageFallbackChain->extractPreferredValue( $entityTerms );
		if ( $termData !== null ) {
			// TODO: do something akin to SearchResult::getTextSnippet here?
			self::addDescription( $extract, $termData, $searchPage );
		}

		if ( $entity instanceof StatementListProvider ) {
			$statementCount = $entity->getStatements()->count();
		} else {
			$statementCount = 0;
		}
		if ( $entity instanceof Item ) {
			$linkCount = $entity->getSiteLinkList()->count();
		} else {
			$linkCount = 0;
		}

		// set $size to size metrics
		$size = $searchPage->msg(
			'wikibase-search-result-stats',
			$statementCount,
			$linkCount
		)->escaped();
	}

	private function isTitleEntity( Title $title ): bool {
		$contentModel = $title->getContentModel();
		return $this->entityContentFactory->isEntityContentModel( $contentModel );
	}

	private function getEntity( Title $title ): ?EntityDocument {
		$entityId = $this->entityIdLookup->getEntityIdForTitle( $title );
		if ( $entityId ) {
			return $this->entityLookup->getEntity( $entityId );
		}
		return null;
	}

	/**
	 * Add attributes appropriate for language of this text.
	 * @param array &$attr Link attributes, to be modified if needed
	 * @param string $displayLanguage
	 * @param array $text Text description array, with language in ['language']
	 */
	public static function addLanguageAttrs( array &$attr, string $displayLanguage, array $text ) {
		if ( $text['language'] !== $displayLanguage ) {
			$services = MediaWikiServices::getInstance();
			if ( $services->getLanguageNameUtils()->isValidCode( $text['language'] ) ) {
				$language = $services->getLanguageFactory()->getLanguage( $text['language'] );
			} else {
				// If somebody fed us broken language, ignore it
				return;
			}
			$attr += [ 'dir' => $language->getDir(), 'lang' => $language->getHtmlCode() ];
		}
	}

	/**
	 * Add HTML description to search result.
	 * @param string &$html The html of the description will be appended here.
	 * @param string[] $description Description as [language, value] array
	 * @param SpecialSearch $searchPage
	 */
	public static function addDescription( string &$html, array $description, SpecialSearch $searchPage ) {
		RequestContext::getMain()->getOutput()->addModuleStyles( [ 'wikibase.alltargets' ] );
		$displayLanguage = $searchPage->getLanguage()->getCode();
		$description = self::withLanguage( $description, $displayLanguage );
		$attr = [ 'class' => 'wb-itemlink-description' ];
		self::addLanguageAttrs( $attr, $displayLanguage, $description );
		// Wrap with searchresult div, as original code does
		$html .= Html::rawElement( 'div', [ 'class' => 'searchresult' ],
			Html::rawElement( 'span', $attr, HtmlArmor::getHtml( $description['value'] ) )
		);
	}

	/**
	 * Remove span tag placed around title search hit for entity titles
	 * to highlight matches in bold.
	 *
	 * @todo Add highlighting when Q##-id matches and not label text.
	 *
	 * @param Title &$title
	 * @param string|HtmlArmor|null &$titleSnippet
	 * @param SearchResult $result
	 * @param array $terms
	 * @param SpecialSearch $specialSearch
	 * @param string[] &$query
	 * @param string[] &$attributes
	 * @return void
	 */
	public function onShowSearchHitTitle(
		&$title,
		&$titleSnippet,
		$result,
		$terms,
		$specialSearch,
		&$query,
		&$attributes
	): void {
		if ( $result instanceof ExtendedResult ) {
			return;
		}
		if ( $this->isTitleEntity( $title ) ) {
			$titleSnippet = $title->getFullText();
		}
	}

	/**
	 * If text's language is not the same as display language, add
	 * marker with language name to the string.
	 *
	 * @param string[] $text ['language' => LANG, 'value' => TEXT]
	 * @param string $displayLanguage
	 * @return array ['language' => LANG, 'value' => TEXT]
	 */
	public static function withLanguage( $text, $displayLanguage ) {
		if ( $text['language'] == $displayLanguage || $text['value'] == '' ) {
			return $text;
		}
		try {
			$termFallback = new TermFallback( $displayLanguage, HtmlArmor::getHtml( $text['value'] ),
					$text['language'], null );
		} catch ( InvalidArgumentException $e ) {
			return $text;
		}
		$fallback = new LanguageFallbackIndicator(
			WikibaseRepo::getLanguageNameLookupFactory()->getForLanguageCode( $displayLanguage )
		);
		$markedText = HtmlArmor::getHtml( $text['value'] ) . $fallback->getHtml( $termFallback );
		return [
			'language' => $text['language'],
			'value' => new HtmlArmor( $markedText ),
		];
	}

}
