<?php

namespace Wikibase\DataModel\Entity;

use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * Interface for EntityDocument objects that are also StatementListProviders
 *
 * @since 7.6
 *
 * @license GPL-2.0-or-later
 */
interface StatementListProvidingEntity extends EntityDocument, StatementListProvider {
}
