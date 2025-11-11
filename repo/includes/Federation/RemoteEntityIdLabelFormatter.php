<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Federation;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;

/**
 * Decorator that can format labels for RemoteEntityId by fetching from
 * a remote Wikibase and caching via RemoteEntityLookup.
 *
 * It extends the core EntityIdLabelFormatter class so that it can be
 * returned from EntityIdLabelFormatterFactory without breaking type hints.
 */
class RemoteEntityIdLabelFormatter extends EntityIdLabelFormatter {

	private EntityIdLabelFormatter $inner;
	private RemoteEntityLookup $remoteLookup;
	/** @var string[] */
	private array $languages;

	/**
	 * @param EntityIdLabelFormatter $inner Existing (local) label formatter
	 * @param RemoteEntityLookup $remoteLookup Federation-aware remote lookup
	 * @param string[] $languages Language codes ordered by preference
	 */
	public function __construct(
		EntityIdLabelFormatter $inner,
		RemoteEntityLookup $remoteLookup,
		array $languages
	) {
		// We deliberately do NOT call parent::__construct(), because we
		// don't need the parent's LabelLookup; we always delegate to $inner.
		$this->inner = $inner;
		$this->remoteLookup = $remoteLookup;
		$this->languages = $languages;
	}

	/**
	 * Override the core formatting behavior to special-case RemoteEntityId.
	 *
	 * @param EntityId $entityId
	 * @return string
	 */
	public function formatEntityId( EntityId $entityId ) {
		// Local entities: just delegate to the wrapped formatter.
		if ( !( $entityId instanceof RemoteEntityId ) ) {
			return $this->inner->formatEntityId( $entityId );
		}

		$repository = $entityId->getRepositoryName();
		$localId = $entityId->getLocalEntityId()->getSerialization();

		// Try to fetch (and implicitly cache) the remote entity JSON.
		$entityData = $this->remoteLookup->getEntity( $repository, $localId );
		if ( !is_array( $entityData ) ) {
			// No remote data – fall back to default behavior.
			return $this->inner->formatEntityId( $entityId );
		}

		// Try language list in order of preference.
		foreach ( $this->languages as $langCode ) {
			if ( isset( $entityData['labels'][ $langCode ]['value'] ) ) {
				return (string)$entityData['labels'][ $langCode ]['value'];
			}
		}

		// No usable remote label → fall back to core behavior.
		return $this->inner->formatEntityId( $entityId );
	}
}
