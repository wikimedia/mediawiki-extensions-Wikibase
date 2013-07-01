<?php
 /**
 *
 * Copyright © 24.04.13 by the authors listed below.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @license GPL 2+
 * @file
 *
 * @author daniel
 */


namespace Wikibase;


/**
 * Resolves property labels (which are unique per language) into entity IDs
 * using a TermIndex.
 *
 * @package Wikibase
 */

class TermPropertyLabelResolver implements PropertyLabelResolver {

	/**
	 * The language to use for looking up labels
	 *
	 * @var string
	 */
	protected $lang;

	/**
	 * @var TermIndex
	 */
	protected $termIndex;

	/**
	 * @var \BagOStuff
	 */
	protected $cache;

	/**
	 * @var int
	 */
	protected $cacheDuration;

	/**
	 * @var string
	 */
	protected $cacheKey;

	/**
	 * Maps labels to property IDs.
	 *
	 * @var EntityId[]
	 */
	protected $propertiesByLabel = null;

	/**
	 * @param string      $lang             The language of the labels to look up (typically,
	 *                                      the wiki's content language)
	 * @param TermIndex   $termIndex        The TermIndex service to look up labels with
	 * @param \BagOStuff  $cache            The cache to use for labels (typically from wfGetMainCache())
	 * @param int         $cacheDuration    Number of seconds to keep the cached version for.
	 *                                      Defaults to 3600 seconds = 1 hour.
	 * @param string|null $cacheKey         The cache key to use, auto-generated based on $lang per default.
	 *                                      Should be set to something including the wiki name
	 *                                      of the wiki that maintains the properties.
	 */
	public function __construct(
		$lang,
		TermIndex $termIndex,
		\BagOStuff $cache,
		$cacheDuration = 3600,
		$cacheKey = null
	) {
		$this->lang = $lang;
		$this->cache = $cache;
		$this->termIndex = $termIndex;
		$this->cacheDuration = $cacheDuration;

		if ( $cacheKey === null ) {
			// share cached data between wikis, only vary on language code.
			$cacheKey = __CLASS__ . '/' . $lang;
		}

		$this->cacheKey = $cacheKey;
	}

	/**
	 * @param string[] $labels the labels
	 * @param string   $recache Flag, set to 'recache' to discard cached data and fetch fresh data
	 *                 from the database.
	 *
	 * @return EntityId[] a map of strings from $lables to the corresponding entity ID.
	 */
	public function getPropertyIdsForLabels( array $labels, $recache = '' ) {
		$props = $this->getLabelMap( $recache );

		$keys = array_flip( $labels );
		$idsForLabels = array_intersect_key( $props, $keys );

		return $idsForLabels;
	}

	/**
	 * Returns a map of labels to EntityIds for all Properties currently defined.
	 * The information is taking from the cache if possible, and loaded from a TermIndex
	 * of not.
	 *
	 * @param string   $recache Flag, set to 'recache' to discard cached data and fetch fresh data
	 *                 from the database.
	 *
	 * @return EntityId[]
	 */
	protected function getLabelMap( $recache = '' ) {
		if ( $this->propertiesByLabel !== null ) {
			// in-process cache
			return $this->propertiesByLabel;
		}

		wfProfileIn( __METHOD__ );

		if ( $recache === 'recache' ) {
			$cached = false;
		} else {
			$cached = $this->cache->get( $this->cacheKey );
		}

		if ( $cached !== false && $cached !== null ) {
			$this->propertiesByLabel = $cached;

			wfProfileIn( __METHOD__ );
			return $this->propertiesByLabel;
		}

		wfProfileIn( __METHOD__ . '#load' );

		$termTemplate = new Term( array(
			'termType' => 'label',
			'termLanguage' => $this->lang,
			'entityType' => Property::ENTITY_TYPE
		) );

		$terms = $this->termIndex->getMatchingTerms(
			array( $termTemplate ),
			'label',
			Property::ENTITY_TYPE,
			array(
				'caseSensitive' => true,
				'prefixSearch' => false,
				'LIMIT' => false,
			)
		);

		$this->propertiesByLabel = array();

		foreach ( $terms as $term ) {
			$label = $term->getText();
			$id = new EntityId( $term->getEntityType(), $term->getEntityId() );

			$this->propertiesByLabel[$label] = $id;
		}

		$this->cache->set( $this->cacheKey, $this->propertiesByLabel, $this->cacheDuration );

		wfProfileOut( __METHOD__ . '#load' );
		wfProfileOut( __METHOD__ );
		return $this->propertiesByLabel;
	}

}