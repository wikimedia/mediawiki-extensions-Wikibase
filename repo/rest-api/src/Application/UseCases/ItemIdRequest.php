<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

/**
 * @license GPL-2.0-or-later
 */
interface ItemIdRequest {
	public function getItemId(): string;
}
