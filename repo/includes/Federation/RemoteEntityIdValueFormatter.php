<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Federation;

use MediaWiki\Html\Html;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;

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
	 * @return string HTML for the value
	 */
	public function format( $value ) {
		if ( !( $value instanceof EntityIdValue ) ) {
			// Not an entity-id value → just delegate.
			return $this->inner->format( $value );
		}

		$entityId = $value->getEntityId();

		// Only treat *remote* entity IDs specially.
		if ( !( $entityId instanceof RemoteEntityId ) ) {
			return $this->inner->format( $value );
		}

		$repo = $entityId->getRepositoryName();
		$localId = $entityId->getLocalEntityId()->getSerialization();

		$entityData = $this->remoteLookup->getEntity( $repo, $localId );

		if ( !is_array( $entityData ) ) {
			// Remote lookup failed → fall back to normal behavior
			// (which will show “Deleted Item”, etc.).
			return $this->inner->format( $value );
		}

		$label = $this->pickLabel( $entityData );
		if ( $label === '' ) {
			$label = $entityId->getSerialization();
		}

		$href = $this->buildEntityUrl( $repo, $localId );

		$linkAttrs = [
			'class' => 'wb-remote-entity-link',
		];

		if ( $href !== null ) {
			$linkAttrs['href'] = $href;
			$linkAttrs['target'] = '_blank';
			$linkAttrs['rel'] = 'noopener';
		}

		$linkHtml = Html::element(
			'a',
			$linkAttrs,
			$label
		);

		$badgeHtml = Html::element(
			'span',
			[
				'class' => 'wb-remote-entity-badge',
				'data-repository' => $repo,
			],
			$repo
		);

		return Html::rawElement(
			'span',
			[
				'class' => 'wb-remote-entity-wrapper',
				'data-repository' => $repo,
			],
			$linkHtml . $badgeHtml
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

	private function buildEntityUrl( string $repository, string $localId ): ?string {
		// MVP: static wiring. Later we can use federationRepositories.
		if ( $repository === 'wikidata' ) {
			return 'https://www.wikidata.org/wiki/' . $localId;
		}

		// Unknown repo → just show label with no link.
		return null;
	}
}
