<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\SiteLinkGlobalIdentifiersProvider;

/**
 * @license GPL-2.0-or-later
 */
class SiteIdType extends ScalarType {

	public function __construct(
		private readonly SiteLinkGlobalIdentifiersProvider $siteLinkGlobalIdentifiersProvider,
		private readonly SettingsArray $repoSettings,
	) {
		parent::__construct();
	}

	public function serialize( mixed $value ): string {
		if ( !$this->isValidSiteId( $value ) ) {
			throw new InvariantViolation( 'Could not serialize the following value as site ID: ' . Utils::printSafe( $value ) );
		}

		return $value;
	}

	public function parseValue( mixed $value ): string {
		if ( !$this->isValidSiteId( $value ) ) {
			throw new Error( 'Cannot represent the following value as site ID: ' . Utils::printSafeJson( $value ) );
		}

		return $value;
	}

	public function parseLiteral( Node $valueNode, ?array $variables = null ): string {
		if ( !$valueNode instanceof StringValueNode ) {
			throw new Error( 'Query error: Can only parse strings got: ' . $valueNode->kind, [ $valueNode ] );
		}

		if ( !$this->isValidSiteId( $valueNode->value ) ) {
			throw new Error( 'Not a valid site ID: ' . Utils::printSafeJson( $valueNode->value ), [ $valueNode ] );
		}

		return $valueNode->value;
	}

	private function isValidSiteId( mixed $id ): bool {
		$validSiteIds = $this->siteLinkGlobalIdentifiersProvider
			->getList( $this->repoSettings->getSetting( 'siteLinkGroups' ) );

		return in_array( $id, $validSiteIds );
	}
}
