<?php

namespace Wikibase\Repo\Hooks;

use Html;
use HtmlArmor;
use Language;
use RequestContext;
use SearchResult;
use SpecialSearch;
use Title;
use Wikibase\Repo\Search\Elastic\EntityResult;

/**
 * Handler to format entities in the search results
 *
 * @license GPL-2.0+
 * @author Matěj Suchánek
 * @author Daniel Kinzler
 */
class ShowSearchHitHandler {

	/**
	 * Format the output when the search result contains entities
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ShowSearchHit
	 * @see doShowSearchHit
	 *
	 * @param SpecialSearch $searchPage
	 * @param SearchResult $result
	 * @param array $terms
	 * @param string &$link
	 * @param string &$redirect
	 * @param string &$section
	 * @param string &$extract
	 * @param string &$score
	 * @param string &$size
	 * @param string &$date
	 * @param string &$related
	 * @param string &$html
	 */
	public static function onShowSearchHit( SpecialSearch $searchPage, SearchResult $result, array $terms,
		&$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related, &$html
	) {
		if ( !( $result instanceof EntityResult ) ) {
			return;
		}

		$extract = '';
		$displayLanguage = $searchPage->getLanguage()->getCode();
		// Put highlighted description of the item as the extract
		self::addDescription( $extract, $result, $searchPage );
		RequestContext::getMain()->getOutput()->addModuleStyles( [ 'wikibase.common' ] );
		// Add extra data
		$extra = $result->getExtraDisplay();
		if ( $extra ) {
			$attr = [ 'class' => 'wb-itemlink-description' ];
			$extra = self::withLanguage( $extra, $displayLanguage );
			self::addLanguageAttrs( $attr, $displayLanguage, $extra );
			$section = $searchPage->msg( 'colon-separator' )->escaped();
			$section .= Html::rawElement( 'span', $attr, HtmlArmor::getHtml( $extra['value'] ) );
		}
		// set $size to size metrics
		$size = $searchPage->msg(
			'wikibase-search-result-stats',
			$result->getStatementCount(),
			$result->getSitelinkCount()
		)->escaped();
	}

	/**
	 * Add attributes appropriate for language of this text.
	 * @param array $attr
	 * @param string $displayLanguage
	 * @param array $text
	 */
	private static function addLanguageAttrs( array &$attr, $displayLanguage, array $text ) {
		if ( $text['language'] !== $displayLanguage ) {
			$language = Language::factory( $text['language'] );
			$attr += [ 'dir' => $language->getDir(), 'lang' => $language->getHtmlCode() ];
		}
	}

	/**
	 * Add HTML description to search result.
	 * @param string &$html The html of the description will be appended here.
	 * @param EntityResult $result
	 * @param SpecialSearch $searchPage
	 */
	private static function addDescription( &$html, EntityResult $result, SpecialSearch $searchPage ) {
		$displayLanguage = $searchPage->getLanguage()->getCode();
		$description = self::withLanguage( $result->getDescriptionHighlightedData(), $displayLanguage );
		$attr = [ 'class' => 'wb-itemlink-description' ];
		self::addLanguageAttrs( $attr, $displayLanguage, $description );
		// Wrap with searchresult div, as original code does
		$html .= Html::rawElement( 'div', [ 'class' => 'searchresult' ],
			Html::rawElement( 'span', $attr, HtmlArmor::getHtml( $description['value'] ) )
		);
	}

	/**
	 * Remove span tag (added by Cirrus) placed around title search hit for entity titles
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
		if ( !( $result instanceof EntityResult ) ) {
			return;
		}

		self::getLink( $result, $title, $titleSnippet, $attributes, $specialSearch->getLanguage()->getCode() );
	}

	/**
	 * Generate link text for Title link in search hit.
	 * This reuses code from LinkBeginHookHandler to do actual work.
	 * @param EntityResult $result
	 * @param Title $title
	 * @param string|HtmlArmor &$html Variable where HTML will be placed
	 * @param array &$attributes Link tag attributes, can add more
	 * @param $displayLanguage
	 */
	private static function getLink( EntityResult $result, Title $title, &$html, &$attributes, $displayLanguage ) {
		$linkHandler = LinkBeginHookHandler::newFromGlobalState();
		// TODO: can we assume the title is always local?
		$entityId = $linkHandler->lookupLocalId( $title );
		if ( !$entityId ) {
			return;
		}

		// Highlighter already encodes and marks up the HTML
		$html = new HtmlArmor(
			$linkHandler->getHtml( $entityId,
				self::withLanguage( $result->getLabelHighlightedData(), $displayLanguage )
			)
		);

		$attributes['title'] = $linkHandler->getTitleAttribute(
			$title,
			$result->getLabelData(),
			$result->getDescriptionData()
		);
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
		if ( $text['language'] == $displayLanguage ) {
			return $text;
		}
		$markedText = HtmlArmor::getHtml( $text['value'] ) .
			Html::rawElement( 'sup', [ 'class' => 'wb-language-name' ], $text['language'] );
		return [
			'language' => $text['language'],
			'value' => new HtmlArmor( $markedText )
		];
	}

}
