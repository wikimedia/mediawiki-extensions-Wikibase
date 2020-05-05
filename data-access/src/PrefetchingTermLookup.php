<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;

/**
 * Interface for implementations of both TermLookup and TermBuffer
 *
 * Lookup methods should try to retrieve terms from TermBuffer::getPrefetchedTerm.
 * Implementations may choose to fallback to another lookup if terms have not been prefeteched.
 * Most implementations do not fallback and require terms to be prefetched in order to be returned by the lookups.
 *
 * @todo PrefetchingTermLookup probably wants an implementation that allows composing a service
 * from multiple different parts.
 * This would for example allow MediaInfo to use a default null AliasTermBuffer (as aliases do not
 * exist in that context), while using a LabelLookup etc that looks up from the correct place.
 *
 * @license GPL-2.0-or-later
 */
interface PrefetchingTermLookup extends TermBuffer, TermLookup, AliasTermBuffer {
}
