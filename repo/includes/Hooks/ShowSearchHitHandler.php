<?php

namespace Wikibase\Repo\Hooks;

use Html;
use HtmlArmor;
use IContextSource;
use InvalidArgumentException;
use Language;
use MWException;
use RequestContext;
use SearchResult;
use SpecialSearch;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatter;
use Wikibase\Repo\Search\ExtendedResult;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\EntityIdLookup;

/**
 * Handler to format entities in the search results
 *
 * @license GPL-2.0-or-later
 * @author Matěj Suchánek
 * @author Daniel Kinzler
 */
class ShowSearchHitHandler {

	/**
	 * @var EntityContentFactory
	 */
	private $entityContentFactory;

	/**
	 * @var LanguageFallbackChain
	 */
	private $languageFallbackChain;

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	public function __construct(
		EntityContentFactory $entityContentFactory,
		LanguageFallbackChain $languageFallbackChain,
		EntityIdLookup $entityIdLookup,
		EntityLookup $entityLookup,
		EntityLinkFormatter $linkFormatter
	) {
		$this->entityContentFactory = $entityContentFactory;
		$this->languageFallbackChain = $languageFallbackChain;
		$this->entityIdLookup = $entityIdLookup;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @param IContextSource $context
	 * @return self
	 */
	private static function newFromGlobalState( IContextSource $context ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$languageFallbackChainFactory = $wikibaseRepo->getLanguageFallbackChainFactory();

		return new self(
			$wikibaseRepo->getEntityContentFactory(),
			$languageFallbackChainFactory->newFromContext( $context ),
			$wikibaseRepo->getEntityIdLookup(),
			$wikibaseRepo->getEntityLookup(),
			new DefaultEntityLinkFormatter( $context->getLanguage() )
		);
	}

	/**
	 * Format the output when the search result contains entities
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ShowSearchHit
	 * @see showEntityResultHit
	 * @see showPlainSearchHit
	 *
	 */
	public static function onShowSearchHit( SpecialSearch $searchPage, SearchResult $result,
		array $terms, &$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related,
		&$html
	) {
		if ( $result instanceof ExtendedResult ) {
			return;
		}
		$self = self::newFromGlobalState( $searchPage->getContext() );
		$self->showPlainSearchHit( $searchPage, $result, $terms, $link, $redirect, $section, $extract,
				$score, $size, $date, $related, $html );
	}

	/**
	 * Show result hit display
	 */
	private function showPlainSearchHit( SpecialSearch $searchPage, SearchResult $result, array $terms,
		&$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related, &$html
	) {
		if ( $result instanceof ExtendedResult ) {
			return;
		}
		$title = $result->getTitle();

		if ( !$this->isTitleEntity( $title ) ) {
			return;
		}

		$entity = $this->getEntity( $title );
		if ( !( $entity instanceof DescriptionsProvider ) ) {
			return;
		}

		$extract = '';

		$entityTerms = $entity->getDescriptions()->toTextArray();
		$termData = $this->languageFallbackChain->extractPreferredValue( $entityTerms );
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

	/**
	 * Check whether the title represents entity
	 * @param Title $title
	 * @return bool
	 */
	private function isTitleEntity( Title $title ) {
		$contentModel = $title->getContentModel();
		return $this->entityContentFactory->isEntityContentModel( $contentModel );
	}

	/**
	 * Retrieve entity by title
	 * @param Title $title
	 * @return EntityDocument|null
	 */
	private function getEntity( Title $title ) {
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
	public static function addLanguageAttrs( array &$attr, $displayLanguage, array $text ) {
		if ( $text['language'] !== $displayLanguage ) {
			try {
				$language = Language::factory( $text['language'] );
			} catch ( MWException $e ) {
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
	public static function addDescription( &$html, array $description, SpecialSearch $searchPage ) {
		RequestContext::getMain()->getOutput()->addModuleStyles( [ 'wikibase.common' ] );
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
	 * @param Title $title
	 * @param string &$titleSnippet
	 * @param SearchResult $result
	 * @param string $terms
	 * @param SpecialSearch $specialSearch
	 * @param string[] &$query
	 * @param string[] $attributes
	 */
	public static function onShowSearchHitTitle(
		Title $title,
		&$titleSnippet,
		SearchResult $result,
		$terms,
		SpecialSearch $specialSearch,
		array &$query,
		array &$attributes
	) {
		if ( $result instanceof ExtendedResult ) {
			return;
		}
		$self = self::newFromGlobalState( $specialSearch->getContext() );
		$self->showPlainSearchTitle( $title, $titleSnippet );
	}

	/**
	 * Handle search result title
	 * @param Title $title
	 * @param string &$titleSnippet
	 */
	private function showPlainSearchTitle( Title $title, &$titleSnippet ) {
		if ( $this->isTitleEntity( $title ) ) {
			$titleSnippet = $title->getFullText();
		}
		// The rest of the plain title work is done in LinkBeginHookHandler
	}

	/**
	 * If text's language is not the same as display language, add
	 * marker with language name to the string.
	 *
	 * @param string[] $text ['language' => LANG, 'value' => TEXT]
	 * @param string $displayLanguage
	 * @return string[] ['language' => LANG, 'value' => TEXT]
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
		$fallback = new LanguageFallbackIndicator( new LanguageNameLookup( $displayLanguage ) );
		$markedText = HtmlArmor::getHtml( $text['value'] ) . $fallback->getHtml( $termFallback );
		return [
			'language' => $text['language'],
			'value' => new HtmlArmor( $markedText )
		];
	}

}
