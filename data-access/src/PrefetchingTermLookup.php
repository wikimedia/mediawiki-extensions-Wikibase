<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;

/**
 * Interface for implementations of both TermLookup and TermBuffer
 * @license GPL-2.0-or-later
 */
interface PrefetchingTermLookup extends TermBuffer, TermLookup {
}
