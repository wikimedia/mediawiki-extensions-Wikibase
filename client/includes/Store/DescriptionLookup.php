<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Store;

use InvalidArgumentException;
use PageProps;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\TermIndexEntry;

/**
 * Retrieves up page descriptions.
 * A description is an explanation of what the page is about, in the content language of the page,
 * short enough that it can be used in interface elements such as dropdowns to contextualize or
 * disambiguate pages.
 * @license GPL-2.0-or-later
 */
class DescriptionLookup {

	/**
	 * Local description, in the form of a {{SHORTDESC:...}} parser function.
	 */
	public const SOURCE_LOCAL = 'local';

	/**
	 * Central description, from a associated Wikibase repo installation.
	 */
	public const SOURCE_CENTRAL = 'central';

	/**
	 * page_props key.
	 */
	public const LOCAL_PROPERTY_NAME = 'wikibase-shortdesc';

	/**
	 * @var EntityIdLookup
	 */
	private $idLookup;

	/**
	 * @var TermBuffer
	 */
	private $termLookup;

	/** @var PageProps */
	private $pageProps;

	public function __construct(
		EntityIdLookup $idLookup,
		TermBuffer $termLookup,
		PageProps $pageProps
	) {
		$this->idLookup = $idLookup;
		$this->termLookup = $termLookup;
		$this->pageProps = $pageProps;
	}

	/**
	 * Look up descriptions for a set of pages.
	 * @param Title[] $titles Titles to look up (will be loaded).
	 * @param array|string $sources One or both of the DescriptionLookup::SOURCE_* constants.
	 *   When an array is provided, the second element will be used as fallback.
	 * @param null $actualSources Will be set to an associative array of page ID => SOURCE_*,
	 *   indicating where each description came from, or null if no description was found.
	 * @return string[] Associative array of page ID => description. Pages with no description
	 *   will be omitted.
	 */
	public function getDescriptions( array $titles, $sources, &$actualSources = null ) {
		$pageIds = array_map( function ( Title $title ) {
			return $title->getArticleID();
		}, $titles );
		$titlesByPageId = array_combine( $pageIds, $titles );

		$sources = (array)$sources;
		$descriptions = $actualSources = [];
		foreach ( $sources as $source ) {
			if ( $source === self::SOURCE_LOCAL ) {
				$descriptions += $this->getLocalDescriptions( $titlesByPageId );
			} elseif ( $source === self::SOURCE_CENTRAL ) {
				$descriptions += $this->getCentralDescriptions( $titlesByPageId );
			} else {
				throw new InvalidArgumentException( "Unknown source $source" );
			}
			$actualSources += array_fill_keys( array_keys( $descriptions ), $source );
		}

		// Restore original sort order.
		$pageIds = array_intersect( $pageIds, array_keys( $descriptions ) );
		$descriptions = array_replace( array_fill_keys( $pageIds, null ), $descriptions );
		$actualSources = array_replace( array_fill_keys( $pageIds, null ), $actualSources );

		return $descriptions;
	}

	/**
	 * Look up description of a page.
	 * Convenience wrapper for getDescriptions().
	 * @param Title $title Title to look up (will be loaded).
	 * @param array|string $sources One or both of the DescriptionLookup::SOURCE_* constants.
	 *   When an array is provided, the second element will be used as fallback.
	 * @param null $actualSource Will be set to one of the DescriptionLookup::SOURCE_* constants,
	 *   indicating where the description came from, or null if no description was found.
	 * @return string|null The description, or null if none was found.
	 */
	public function getDescription( Title $title, $sources, &$actualSource = null ) {
		$actualSources = null;
		$descriptions = $this->getDescriptions( [ $title ], $sources, $actualSources );

		$pageId = $title->getArticleID();
		if ( array_key_exists( $pageId, $descriptions ) ) {
			// @phan-suppress-next-line PhanTypeArraySuspiciousNullable has the same keys as $descriptions after getDescriptions() call
			$actualSource = $actualSources[$pageId];
			return $descriptions[$pageId];
		} else {
			$actualSource = null;
			return null;
		}
	}

	/**
	 * Look up local descriptions (stored in the page wikitext via parser function) for a set of pages.
	 * @param Title[] $titlesByPageId Associative array of page ID => Title object.
	 * @return string[] Associative array of page ID => description.
	 */
	private function getLocalDescriptions( array $titlesByPageId ) {
		if ( !$titlesByPageId ) {
			return [];
		}
		return $this->pageProps->getProperties( $titlesByPageId, self::LOCAL_PROPERTY_NAME );
	}

	/**
	 * Look up central descriptions (stored in a linked Wikibase instance) for a set of pages.
	 * @param Title[] $titlesByPageId Associative array of page ID => Title object.
	 * @return string[] Associative array of page ID => description.
	 */
	private function getCentralDescriptions( array $titlesByPageId ) {
		if ( !$titlesByPageId ) {
			return [];
		}

		$languages = array_unique( array_map( function ( Title $title ) {
			return $title->getPageLanguage()->getCode();
		}, $titlesByPageId ) );

		$entityIdsByPageId = $this->idLookup->getEntityIds( $titlesByPageId );
		$this->termLookup->prefetchTerms(
			$entityIdsByPageId,
			[ TermIndexEntry::TYPE_DESCRIPTION ],
			$languages
		);

		$pageIdsByEntityId = array_flip( array_map( function ( EntityId $entityId ) {
			return $entityId->getSerialization();
		}, $entityIdsByPageId ) );
		$descriptionsByPageId = [];
		foreach ( $entityIdsByPageId as $entityId ) {
			$pageId = $pageIdsByEntityId[$entityId->getSerialization()];
			$pageLanguage = $titlesByPageId[$pageId]->getPageLanguage()->getCode();
			$term = $this->termLookup->getPrefetchedTerm( $entityId, TermIndexEntry::TYPE_DESCRIPTION, $pageLanguage );

			if ( $term === false ) {
				continue;
			}
			$descriptionsByPageId[$pageId] = $term;
		}
		return $descriptionsByPageId;
	}

}
