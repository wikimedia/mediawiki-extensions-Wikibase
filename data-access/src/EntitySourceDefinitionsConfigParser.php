<?php

namespace Wikibase\DataAccess;

use InvalidArgumentException;
use MWNamespace;
use Wikimedia\Assert\Assert;

/**
 * TODO: alternatively, the logic could go to the "static constructor" of EntitySourceDefinitions class?
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceDefinitionsConfigParser {

	public function newDefinitionsFromConfigArray( array $sourceConfig ) {
		$this->assertConfigArrayWellFormed( $sourceConfig );

		$sources = [];

		foreach ( $sourceConfig as $sourceName => $sourceData ) {
			$namespaceSlotData = [];
			foreach ( $sourceData['entityNamespaces'] as $entityType => $namespaceSlot ) {

				list( $namespaceId, $slot ) = self::splitNamespaceAndSlot( $namespaceSlot );
				$namespaceSlotData[$entityType] = [
					'namespaceId' => $namespaceId,
					'slot' => $slot,
				];

			}
			$sources[] = new EntitySource(
				$sourceName,
				$sourceData['repoDatabase'],
				$namespaceSlotData,
				$sourceData['baseUri'],
				$sourceData['rdfNodeNamespacePrefix'],
				$sourceData['rdfPredicateNamespacePrefix'],
				$sourceData['interwikiPrefix']
			);
		}

		return new EntitySourceDefinitions( $sources );
	}

	private function assertConfigArrayWellFormed( array $sourceConfig ) {
		Assert::parameterElementType( 'array', $sourceConfig, '$sourceConfig' );

		foreach ( $sourceConfig as $sourceName => $sourceData ) {
			if ( !is_string( $sourceName ) ) {
				throw new InvalidArgumentException( 'Source name should be a string. Given: "' . $sourceName . '"' );
			}

			if ( !array_key_exists( 'entityNamespaces', $sourceData ) ) {
				throw new InvalidArgumentException( 'Source data should include "entityNamespace" element' );
			}

			if ( !is_array( $sourceData['entityNamespaces'] ) ) {
				throw new InvalidArgumentException(
					'Entity namespace definition of entity source "' . $sourceName . '" should be an associative array'
				);
			}

			foreach ( $sourceData['entityNamespaces'] as $entityType => $namespaceSlot ) {
				if ( !is_string( $entityType ) ) {
					throw new InvalidArgumentException(
						'Entity namespace definition of entity source "' . $sourceName . '" should be indexed by strings'
					);
				}
				if ( !is_string( $namespaceSlot ) && !is_int( $namespaceSlot ) ) {
					throw new InvalidArgumentException(
						'Entity namespaces of entity source "' . $sourceName . '" should be either a string or an integer'
					);
				}
			}

			if ( !array_key_exists( 'repoDatabase', $sourceData ) ) {
				throw new InvalidArgumentException( 'Source data should include "repoDatabase" element' );
			}

			if ( !is_string( $sourceData['repoDatabase'] ) && $sourceData['repoDatabase'] !== false ) {
				throw new InvalidArgumentException( 'Symbolic database name of entity source "' . $sourceName . '" should be a string or false.' );
			}

			if ( !array_key_exists( 'baseUri', $sourceData ) ) {
				throw new InvalidArgumentException( 'Source data should include "baseUri" element' );
			}

			if ( !is_string( $sourceData['baseUri'] ) ) {
				throw new InvalidArgumentException( 'URI base of entities from entity source "' . $sourceName . '" should be a string.' );
			}

			if ( !array_key_exists( 'interwikiPrefix', $sourceData ) ) {
				throw new InvalidArgumentException( 'Source data should include "interwikiPrefix" element' );
			}

			if ( !is_string( $sourceData['interwikiPrefix'] ) ) {
				throw new InvalidArgumentException( 'Interwiki prefix of entity source "' . $sourceName . '" should be a string.' );
			}
		}
	}

	private static function splitNamespaceAndSlot( $namespaceAndSlot ) {
		if ( is_int( $namespaceAndSlot ) ) {
			return [ $namespaceAndSlot, 'main' ];
		}

		if ( !preg_match( '!^(\w*)(/(\w+))?!', $namespaceAndSlot, $m ) ) {
			throw new InvalidArgumentException(
				'Bad namespace/slot specification: an integer namespace index, or a canonical'
				. ' namespace name, or have the form <namespace>/<slot-name>.'
				. ' Found ' . $namespaceAndSlot
			);
		}

		if ( is_numeric( $m[1] ) ) {
			$ns = intval( $m[1] );
		} else {
			// TODO: this is evil, can we get around this without binding to MediaWiki?
			// Or should this class go to some other place, where coupling is not an issue?
			$ns = MWNamespace::getCanonicalIndex( strtolower( $m[1] ) );
		}

		if ( !is_int( $ns ) ) {
			throw new InvalidArgumentException(
				'Bad namespace specification: must be either an integer or a canonical'
				. ' namespace name. Found ' . $m[1]
			);
		}

		return [
			$ns,
			$m[3] ?? 'main'
		];
	}

}
