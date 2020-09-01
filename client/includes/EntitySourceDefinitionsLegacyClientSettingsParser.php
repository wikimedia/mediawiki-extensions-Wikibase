<?php

namespace Wikibase\Client;

use InvalidArgumentException;
use MWNamespace;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;

/**
 * TODO: alternatively, the logic could go to the "static constructor" of EntitySourceDefinitions class?
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceDefinitionsLegacyClientSettingsParser {

	public function newDefinitionsFromSettings( SettingsArray $settings, EntityTypeDefinitions $entityTypeDefinitions ) {
		$repoSettingsArray = $settings->hasSetting( 'foreignRepositories' )
			? $settings->getSetting( 'foreignRepositories' )
			: $settings->getSetting( 'repositories' );

		if ( $settings->hasSetting( 'repoDatabase' )
			&& $settings->hasSetting( 'entityNamespaces' )
			&& $settings->hasSetting( 'repoConceptBaseUri' )
		) {
			$localEntityNamespaces = $settings->getSetting( 'entityNamespaces' );
			$localDatabaseName = $settings->getSetting( 'repoDatabase' );
			$localConceptBaseUri = $settings->getSetting( 'repoConceptBaseUri' );
			unset( $repoSettingsArray[''] );
		}

		if ( array_key_exists( '', $repoSettingsArray ) ) {
			$localEntityNamespaces = $repoSettingsArray['']['entityNamespaces'];
			$localDatabaseName = $repoSettingsArray['']['repoDatabase'];
			$localConceptBaseUri = $repoSettingsArray['']['baseUri'];
			unset( $repoSettingsArray[''] );
		}

		if ( !isset( $localEntityNamespaces ) || !isset( $localDatabaseName ) || !isset( $localConceptBaseUri ) ) {
			throw new InvalidArgumentException( 'local entity source not defined!' );
		}

		$sources = [];

		$localEntityNamespaceSlotData = [];
		foreach ( $localEntityNamespaces as $entityType => $namespaceSlot ) {
			list( $namespaceId, $slot ) = $this->splitNamespaceAndSlot( $namespaceSlot );
			$localEntityNamespaceSlotData[$entityType] = [
				'namespaceId' => $namespaceId,
				'slot' => $slot,
			];
		}

		$sources[] = new EntitySource(
			'local',
			$localDatabaseName,
			$localEntityNamespaceSlotData,
			$localConceptBaseUri,
			'wd', // TODO: make configurable
			'', // TODO: make configurable
			''
		);

		foreach ( $repoSettingsArray as $repository => $repositorySettings ) {
			$namespaceSlotData = [];
			foreach ( $repositorySettings['entityNamespaces'] as $entityType => $namespaceSlot ) {
				list( $namespaceId, $slot ) = $this->splitNamespaceAndSlot( $namespaceSlot );
				$namespaceSlotData[$entityType] = [
					'namespaceId' => $namespaceId,
					'slot' => $slot,
				];
			}
			$sources[] = new EntitySource(
				$repository,
				$repositorySettings['repoDatabase'],
				$namespaceSlotData,
				$repositorySettings['baseUri'],
				$repository, // TODO: make configurable
				$repository, // TODO: make configurable
				$repository // TODO: this is a "magic" default/assumption
			);
		}

		return new EntitySourceDefinitions( $sources, $entityTypeDefinitions );
	}

	private function splitNamespaceAndSlot( $namespaceAndSlot ) {
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
