<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Federation;

use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdValue;

/**
 * ValueFormatter decorator for EntityIdValue that knows how to format
 * RemoteEntityId using RemoteEntityLookup.
 *
 * It is created via the ValueFormatter callbacks in ServiceWiring,
 * so it participates in the normal snak/value formatting pipeline.
 */
class RemoteEntityIdValueFormatter implements ValueFormatter {

	private ValueFormatter $inner;
	private RemoteEntityLookup $remoteLookup;
	/** @var string[] */
	private array $languages;

	/**
	 * @param ValueFormatter $inner Base formatter (usually EntityIdValueFormatter)
	 * @param RemoteEntityLookup $remoteLookup Remote entity fetcher + cache
	 * @param string[] $languages Preferred language codes for labels (in order)
	 */
	public function __construct(
		ValueFormatter $inner,
		RemoteEntityLookup $remoteLookup,
		array $languages
	) {
		$this->inner = $inner;
		$this->remoteLookup = $remoteLookup;
		$this->languages = $languages;
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function format( $value ) {
		if ( !( $value instanceof EntityIdValue ) ) {
			// Not an entity-id value → just delegate.
			return $this->inner->format( $value );
		}

		$entityId = $value->getEntityId();

		// Only treat remote entity IDs specially.
		if ( !( $entityId instanceof RemoteEntityId ) ) {
			return $this->inner->format( $value );
		}

		$repo = $entityId->getRepositoryName();
		$localId = $entityId->getLocalEntityId()->getSerialization();

		$entityData = $this->remoteLookup->getEntity( $repo, $localId );

		if ( !is_array( $entityData ) ) {
			return $this->inner->format( $value );
		}

		$label = $this->pickLabel( $entityData );

		if ( $label === '' ) {
			return $this->inner->format( $value );
		}

		// For now return plain text; HTML/linking is handled elsewhere.
		return $label;
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
