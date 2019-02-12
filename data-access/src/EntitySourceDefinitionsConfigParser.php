<?php

namespace Wikibase\DataAccess;

use InvalidArgumentException;
use MWNamespace;

/**
 * TODO: alternatively, the logic could go to the "static constructor" of EntitySourceDefinitions class?
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceDefinitionsConfigParser {

	public function newDefinitionsFromConfigArray( array $sourceConfig ) {
		// TODO: input validation?

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
				$sourceData['interwikiPrefix']
			);
		}

		return new EntitySourceDefinitions( $sources );
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
			// TODO: this is eval, can we get around this without binding to MediaWiki?
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
