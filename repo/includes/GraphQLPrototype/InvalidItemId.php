<?php declare( strict_types=1 );

namespace Wikibase\Repo\GraphQLPrototype;

use GraphQL\Error\ClientAware;

/**
 * @license GPL-2.0-or-later
 */
class InvalidItemId extends \Exception implements ClientAware {
	public function isClientSafe(): bool {
		return true;
	}
}
