<?php

namespace Wikibase\QueryEngine;

use MessageReporter;

/**
 * Interface for query stores providing access to all needed sub components
 * such as updaters, query engines and setup/teardown operations.
 *
 * This interface somewhat acts as facade to the query component.
 * All access to a specific store should typically happen via this interface.
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
 * @ingroup WikibaseQueryStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface QueryStore {

	// TODO: create store factory and figure out how to inject dependencies
	// for the typical Wikibase repo use case.

	/**
	 * Returns the name of the query store. This name can be configuration dependent
	 * and is thus not always the same for a certain store type. For instance, you can
	 * have "Wikibase SQL store" and "Wikibase SQL store for update to new config".
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns the query engine for this store.
	 * The query engine allows running queries against the store.
	 *
	 * @since 0.1
	 *
	 * @return QueryEngine
	 */
	public function getQueryEngine();

	/**
	 * Returns the updater for this store.
	 * The updater allows for updating the data in the store.
	 *
	 * @since 0.1
	 *
	 * @return QueryStoreWriter
	 */
	public function getUpdater();

	/**
	 * Sets up the store.
	 * This means creating and initializing the storage structures
	 * required for storing data in the store.
	 *
	 * @since 0.1
	 *
	 * @param MessageReporter $messageReporter
	 *
	 * @return boolean Success indicator
	 */
	public function getSetup( MessageReporter $messageReporter );

}
