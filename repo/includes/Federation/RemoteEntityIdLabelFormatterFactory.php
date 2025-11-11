<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Federation;

use MediaWiki\Language\Language;
use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;
use Wikibase\Repo\EntityIdLabelFormatterFactory;

/**
 * Factory that produces label formatters aware of RemoteEntityId.
 *
 * It wraps the existing EntityIdLabelFormatterFactory and replaces
 * the produced formatters with RemoteEntityIdLabelFormatter decorators
 * that can fetch remote entity labels from other Wikibase repositories.
 */
class RemoteEntityIdLabelFormatterFactory extends EntityIdLabelFormatterFactory {

	private EntityIdLabelFormatterFactory $innerFactory;
	private RemoteEntityLookup $remoteLookup;
	/** @var string[] */
	private array $fallbackLanguages;

	/**
	 * @param EntityIdLabelFormatterFactory $innerFactory
	 *   The base factory used for local entities.
	 * @param RemoteEntityLookup $remoteLookup
	 *   The federation-aware remote entity lookup.
	 * @param string[] $fallbackLanguages
	 *   Extra fallback language codes (e.g. site language, 'en').
	 */
	public function __construct(
		EntityIdLabelFormatterFactory $innerFactory,
		RemoteEntityLookup $remoteLookup,
		array $fallbackLanguages
	) {
		$this->innerFactory = $innerFactory;
		$this->remoteLookup = $remoteLookup;
		$this->fallbackLanguages = $fallbackLanguages;
	}

	/**
	 * Signature must match the parent:
	 *   getEntityIdFormatter( Language $language ): EntityIdLabelFormatter
	 */
	public function getEntityIdFormatter( Language $language ): EntityIdLabelFormatter {
		// Base formatter for local entities
		$baseFormatter = $this->innerFactory->getEntityIdFormatter( $language );

		// Build the language preference list (UI/content language first, then fallbacks)
		$langCodes = array_merge(
			[ $language->getCode() ],
			$this->fallbackLanguages
		);

		return new RemoteEntityIdLabelFormatter(
			$baseFormatter,
			$this->remoteLookup,
			$langCodes
		);
	}
}
