<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PredicateProperty;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\PropertyLabelsResolver;

/**
 * @license GPL-2.0-or-later
 */
class PredicatePropertyType extends ObjectType {

	public function __construct(
		PropertyLabelsResolver $labelsResolver,
		InterfaceType $labelProviderType
	) {
		$labelField = clone $labelProviderType->getField( 'label' ); // cloned to not override the resolver in other places
		$labelField->resolveFn = fn( PredicateProperty $property, array $args ) => $labelsResolver->resolve(
			$property->id,
			$args['languageCode']
		);

		parent::__construct( [
			'fields' => [
				'id' => [
					'type' => Type::nonNull( Type::string() ),
					'resolve' => fn( PredicateProperty $rootValue ) => $rootValue->id,
				],
				'dataType' => [
					'type' => Type::string(),
					'resolve' => fn( PredicateProperty $rootValue ) => $rootValue->dataType,
				],
				$labelField,
			],
			'interfaces' => [ $labelProviderType ],
		] );
	}

}
