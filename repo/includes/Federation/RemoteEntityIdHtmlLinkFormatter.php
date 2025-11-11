<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Federation;

use Html;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;

/**
 * HTML link formatter decorator that knows how to render RemoteEntityId.
 *
 * For local entities it delegates to the inner formatter.
 * For RemoteEntityId it fetches a label via RemoteEntityLookup and
 * renders a span with the remote label.
 */
class RemoteEntityIdHtmlLinkFormatter implements EntityIdFormatter {

	private EntityIdFormatter $inner;
	private RemoteEntityLookup $remoteLookup;
	/** @var string[] */
	private array $languages;

	/**
	 * @param EntityIdFormatter $inner Base HTML formatter for local ids.
	 * @param RemoteEntityLookup $remoteLookup Remote entity fetcher + cache.
	 * @param string[] $languages Preferred language codes for labels.
	 */
	public function __construct(
		EntityIdFormatter $inner,
		RemoteEntityLookup $remoteLookup,
		array $languages
	) {
		$this->inner = $inner;
		$this->remoteLookup = $remoteLookup;
		$this->languages = $languages;
	}

	public function formatEntityId( EntityId $entityId ): string {
		// Local entities: behave exactly as before.
		if ( !( $entityId instanceof RemoteEntityId ) ) {
			return $this->inner->formatEntityId( $entityId );
		}

		$repository = $entityId->getRepositoryName();
		$localId = $entityId->getLocalEntityId()->getSerialization();

		// Fetch (and cache) the remote entity blob.
		$entityData = $this->remoteLookup->getEntity( $repository, $localId );
		if ( !is_array( $entityData ) ) {
			// If remote fetch fails, fall back to the inner formatter.
			return $this->inner->formatEntityId( $entityId );
		}

		// Pick a label based on preferred languages.
		$label = $this->pickLabel( $entityData );
		if ( $label === '' ) {
			$label = $entityId->getSerialization();
		}

		return Html::element(
			'span',
			[
				'class' => 'wb-remote-entity',
				'data-repository' => $repository,
			],
			$label
		);
	}

	/**
	 * @param array $entityData wbgetentities-style entity blob
	 */
	private function pickLabel( array $entityData ): string {
		if ( !isset( $entityData['labels'] ) || !is_array( $entityData['labels'] ) ) {
			return '';
		}

		foreach ( $this->languages as $code ) {
			if ( isset( $entityData['labels'][$code]['value'] ) ) {
				return (string)$entityData['labels'][$code]['value'];
			}
		}

		return '';
	}
}
