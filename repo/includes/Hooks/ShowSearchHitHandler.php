<?php

namespace Wikibase\Repo\Hooks;

use Html;
use SearchResult;
use SpecialSearch;
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

		$extract = ''; // TODO: set this to something useful.

		self::addDescription( $extract, $result, $searchPage );
	}

	/**
	 * Add HTML description to link
	 * @param $link
	 * @param EntityResult $result
	 * @param SpecialSearch $searchPage
	 */
	private static function addDescription( &$link, EntityResult $result, SpecialSearch $searchPage ) {
		$description = $result->getTextSnippet( [] );
		$attr = [ 'class' => 'wb-itemlink-description' ];
		if ( $result->getTextLanguage() !== $searchPage->getLanguage()->getCode() ) {
			$attr += [ 'dir' => 'auto', 'lang' => wfBCP47( $result->getTextLanguage() ) ];
		}
		$link .= $searchPage->msg( 'colon-separator' )->escaped();
		$link .= Html::element( 'span', $attr, $description );
	}

}
