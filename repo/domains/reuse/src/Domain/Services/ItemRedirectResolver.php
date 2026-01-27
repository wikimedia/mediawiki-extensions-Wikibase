<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
interface ItemRedirectResolver {

	/**
	 * Returns the ID of the redirect target if there is a redirect, or the original ID otherwise.
	 * It also returns the original ID if the Item does not exist.
	 */
	public function resolveRedirect( ItemId $id ): ItemId;
}
