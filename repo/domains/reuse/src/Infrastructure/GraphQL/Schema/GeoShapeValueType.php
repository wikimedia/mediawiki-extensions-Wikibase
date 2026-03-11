<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\ObjectType;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValuePair;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statement;

/**
 * @license GPL-2.0-or-later
 */
class GeoShapeValueType extends ObjectType {
	public function __construct(
		private readonly string $baseUrl,
		Types $types
	) {
		$contentProviderType = $types->getStringContentProviderType();
		$contentField = clone $contentProviderType->getField( 'content' );
		$contentField->resolveFn = fn( Statement|PropertyValuePair $valueProvider ) => $valueProvider->value->getValue();

		$urlProviderType = $types->getUrlProviderType();
		$urlField = clone $urlProviderType->getField( 'url' );
		$urlField->resolveFn = fn( Statement|PropertyValuePair $valueProvider ) => $this->formatUrl( $valueProvider->value->getValue() );

		parent::__construct( [
			'name' => 'GeoShapeValue',
			'interfaces' => [ $contentProviderType, $urlProviderType ],
			'fields' => [ $contentField, $urlField ],
		] );
	}

	private function formatUrl( string $value ): string {
		return $this->baseUrl . str_replace( ' ', '_', $value );
	}
}
