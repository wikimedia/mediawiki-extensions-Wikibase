<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;

/**
 * Interface for implementations of both TermLookup and TermBuffer
 * @license GPL-2.0+
 */
interface PrefetchingTermLookup extends TermBuffer, TermLookup {
}
