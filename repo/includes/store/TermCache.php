<?php

namespace Wikibase;

/**
 * Interface to a cache for terms with both write and lookup methods.
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
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface TermCache {

	/**
	 * Term type enum.
	 *
	 * @since 0.1
	 */
	const TERM_TYPE_LABEL = 'label';
	const TERM_TYPE_ALIAS = 'alias';
	const TERM_TYPE_DESCRIPTION = 'description';

	/**
	 * Returns the ids for the items with the provided label in the specified language.
	 *
	 * TODO: generalize to entity
	 *
	 * @since 0.1
	 *
	 * @param string $label
	 * @param string|null $languageCode
	 * @param string|null $description
	 *
	 * @return array of integer
	 */
	public function getItemIdsForLabel( $label, $languageCode = null, $description = null );

	/**
	 * Saves the terms of the provided entity in the term cache.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function saveTermsOfEntity( Entity $entity );

	/**
	 * Deletes the terms of the provided entity from the term cache.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function deleteTermsOfEntity( Entity $entity );

	/**
	 * Returns if a term with the specified parameters exists.
	 *
	 * @since 0.1
	 *
	 * @param string $termValue
	 * @param string|null $termType
	 * @param string|null $termLanguage Language code
	 * @param string|null $entityType
	 *
	 * @return boolean
	 */
	public function termExists( $termValue, $termType = null, $termLanguage = null, $entityType = null );

	/**
	 * Returns the terms that match the provided conditions.
	 *
	 * $terms is an array of arrays where each inner array specifies a set of
	 * conditions which are joined by AND, and the inner arrays get joined by OR.
	 * These inner arrays can have the following keys:
	 *
	 * - entityType:   string
	 * - termType:     element of the TermCache::TERM_TYPE_ enum
	 * - termLanguage: string, language code
	 * - termText:     string
	 *
	 * A default can be provided for termType and entityType via the corresponding
	 * method parameters.
	 *
	 * The return value is an array in similar format where all 4 fields are set.
	 * Example:
	 *
	 * array(
	 *   array(
	 *      entityType: item,
	 *      termType: TERM_TYPE_LABEL,
	 *      termLanguage: en,
	 *      termText: foobar,
	 *   ),
	 *   array(
	 *      ...
	 *   ),
	 *   ...
	 * )
	 *
	 * @since 0.1
	 *
	 * @param array $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 *
	 * @return array
	 */
	public function getMatchingTerms( array $terms, $termType = null, $entityType = null );

}