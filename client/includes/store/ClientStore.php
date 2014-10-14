<?php

namespace Wikibase;

use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\TermLookup;
use Wikibase\Lib\Store\TermsLookup;

/**
 * Client store interface.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
interface ClientStore {

	/**
	 * @since 0.4
	 *
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkTable();

	/**
	 * @since 0.4
	 *
	 * @return ItemUsageIndex
	 */
	public function getItemUsageIndex();

	/**
	 * @since 0.4
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup();

	/**
	 * @since 0.5
	 *
	 * @return EntityLookup
	 */
	public function getEntityRevisionLookup();

	/**
	 * @since 0.4
	 *
	 * @return PropertyLabelResolver
	 */
	public function getPropertyLabelResolver();

	/**
	 * @since 0.4
	 *
	 * @return TermIndex
	 */
	public function getTermIndex();

	/**
	 * @since 0.4
	 *
	 * @return ChangesTable
	 *
	 * @throws \MWException if no changes table can be supplied.
	 */
	public function newChangesTable();

	/**
	 * @since 0.4
	 *
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore();

	/**
	 * Removes all data from the store.
	 *
	 * @since 0.2
	 */
	public function clear();

	/**
	 * Rebuilds all data in the store.
	 *
	 * @since 0.2
	 */
	public function rebuild();

	/**
	 * @returns TermsLookup
	 */
	public function getTermsLookup();

	/**
	 * @return TermLookup
	 */
	public function getTermLookup();

}
