<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services\Exceptions;

/**
 * @license GPL-2.0-or-later
 */
class EntityUpdatePrevented extends EntityUpdateFailed {
	// Don't consider prevented edits unexpected
	//
	// This patch should only be considered a temporary solution to stop the
	// error log spam.
	//
	// TODO: think of a better way to handle it.
}
