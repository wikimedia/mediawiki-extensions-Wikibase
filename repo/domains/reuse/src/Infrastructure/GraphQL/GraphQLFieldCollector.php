<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Visitor;

/**
 * @license GPL-2.0-or-later
 */
class GraphQLFieldCollector {

	private array $fragments;
	private array $currentPath;
	private array $currentPathWithAliases;
	private array $pathsWithAliasesMap;

	public function getRequestedFieldPaths( string $query, ?string $operationName ): array {
		$doc = Parser::parse( $query );

		$operation = $this->getQueryOperation( $doc, $operationName );
		if ( !$operation ) {
			return [];
		}

		// using class properties for these to make them more easily accessible in callback functions
		$this->fragments = $this->getFragments( $doc );
		$this->currentPath = [];
		$this->currentPathWithAliases = [];
		$this->pathsWithAliasesMap = [];

		$this->collectFieldNames( $operation );

		return array_values( $this->pathsWithAliasesMap );
	}

	private function collectFieldNames( Node $node ): void {
		Visitor::visit( $node, [
			NodeKind::FIELD => [
				'enter' => function( FieldNode $node ): void {
					$this->currentPath[] = $node->name->value;
					$this->currentPathWithAliases[] = $node->alias?->value ?? $node->name->value;
					$this->pathsWithAliasesMap[implode( '_', $this->currentPathWithAliases )] = implode( '_', $this->currentPath );
				},
				'leave' => function(): void {
					array_pop( $this->currentPath );
					array_pop( $this->currentPathWithAliases );
				},
			],
			NodeKind::FRAGMENT_SPREAD => [
				'enter' => function( FragmentSpreadNode $node ) {
					if ( isset( $this->fragments[ $node->name->value ] ) ) {
						$this->collectFieldNames( $this->fragments[ $node->name->value ] );
					}
					return Visitor::skipNode();
				},
			],
		] );
	}

	private function getQueryOperation( DocumentNode $doc, ?string $operationName ): ?OperationDefinitionNode {
		$operations = [];

		Visitor::visit( $doc, [
			NodeKind::OPERATION_DEFINITION => function( OperationDefinitionNode $node ) use ( &$operations ): void {
				$operations[] = $node;
			},
		] );

		if ( $operationName === null && count( $operations ) === 1 ) {
			return $operations[ 0 ];
		}

		foreach ( $operations as $operation ) {
			if ( $operation->name && $operation->name->value === $operationName ) {
				return $operation;
			}
		}

		return null;
	}

	private function getFragments( DocumentNode $doc ): array {
		$fragments = [];
		Visitor::visit( $doc, [
			NodeKind::FRAGMENT_DEFINITION => function( $node ) use ( &$fragments ) {
				$fragments[ $node->name->value ] = $node;
				return null;
			},
		] );

		return $fragments;
	}

}
