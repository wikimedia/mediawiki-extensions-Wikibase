<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\RemoteEntity;

use MediaWiki\Html\Html;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\Lib\Formatters\SnakFormatter;

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
	private string $format;

	/**
	 * @param ValueFormatter $inner Base formatter (usually EntityIdValueFormatter)
	 * @param RemoteEntityLookup $remoteLookup Remote entity fetcher + cache
	 * @param string[] $languages Preferred language codes for labels (in order)
	 * @param string $format Output format (see SnakFormatter::FORMAT_XXX)
	 */
	public function __construct(
		ValueFormatter $inner,
		RemoteEntityLookup $remoteLookup,
		array $languages,
		string $format = SnakFormatter::FORMAT_HTML
	) {
		$this->inner = $inner;
		$this->remoteLookup = $remoteLookup;
		$this->languages = $languages;
		$this->format = $format;
	}

	/**
	 * @param mixed $value
	 * @return string Formatted value (HTML or plain text depending on format)
	 */
	public function format( $value ) {
		if ( !( $value instanceof EntityIdValue ) ) {
			return $this->inner->format( $value );
		}

		$entityId = $value->getEntityId();

		// Only treat *remote* entity IDs specially.
		if ( !( $entityId instanceof RemoteEntityId ) ) {
			return $this->inner->format( $value );
		}

		$conceptUri = $entityId->getSerialization();
		$localId = $entityId->getLocalEntityId()->getSerialization();

		// Use fetchEntity() to avoid storing in DB during display/formatting.
		// Storage happens separately when the statement is actually saved.
		$entityData = $this->remoteLookup->fetchEntity( $conceptUri );

		if ( !is_array( $entityData ) ) {
			// Remote lookup failed → fall back to normal behavior
			return $this->inner->format( $value );
		}

		$label = $this->pickLabel( $entityData );
		if ( $label === '' ) {
			$label = $localId;
		}

		// For plain text format, just return the label
		if ( $this->format === SnakFormatter::FORMAT_PLAIN ) {
			return $label;
		}

		// For HTML formats, return full markup with link and badge
		return $this->formatHtml( $label, $conceptUri );
	}

	/**
	 * Format the remote entity as HTML with link and source badge.
	 */
	private function formatHtml( string $label, string $conceptUri ): string {
		$href = $this->buildEntityUrl( $conceptUri );

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
				'data-host' => (string)parse_url( $conceptUri, PHP_URL_HOST ),
			],
			(string)parse_url( $conceptUri, PHP_URL_HOST )
		);

		return Html::rawElement(
			'span',
			[
				'class' => 'wb-remote-entity-wrapper',
				'data-concepturi' => $conceptUri,
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

	private function buildEntityUrl( string $conceptUri ): ?string {
		// Link to the concept URI by default; /entity/ is widely dereferenceable.
		return $conceptUri;
	}
}
