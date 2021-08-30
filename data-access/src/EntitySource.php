<?php

namespace Wikibase\DataAccess;

/**
 * An EntitySource includes information needed to interact with one or more entity types at a given source.
 * EntitySource can only currently be used via direct database access.
 *
 * @see EntitySourceDefinitions for defining multiple EntitySources within a single site.
 *
 * @license GPL-2.0-or-later
 */
interface EntitySource {

	public function getSourceName();

	public function getEntityTypes();

	public function getConceptBaseUri();

	public function getRdfNodeNamespacePrefix();

	public function getRdfPredicateNamespacePrefix();

	public function getInterwikiPrefix();

	public function getType();
}
