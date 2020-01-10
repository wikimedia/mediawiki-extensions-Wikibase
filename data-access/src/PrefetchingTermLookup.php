<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;

/**
 * Interface for implementations of both TermLookup and TermBuffer
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
