<?php

declare( strict_types = 1 );

namespace Wikibase\Client\DataAccess\Scribunto;

use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\ContentLanguages;

/**
 * Actual implementations of the functions to access Wikibase through the Scribunto extension
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLuaEntityBindings {

	private StatementTransclusionInteractor $plainTextTransclusionInteractor;
	private StatementTransclusionInteractor $richWikitextTransclusionInteractor;
	private EntityIdParser $entityIdParser;
	private UsageAccumulator $usageAccumulator;
	private ContentLanguages $termsLanguages;
	private string $globalSiteId;

	public function __construct(
		StatementTransclusionInteractor $plainTextTransclusionInteractor,
		StatementTransclusionInteractor $richWikitextTransclusionInteractor,
		EntityIdParser $entityIdParser,
		ContentLanguages $termsLanguages,
		UsageAccumulator $usageAccumulator,
		string $globalSiteId
	) {
		$this->plainTextTransclusionInteractor = $plainTextTransclusionInteractor;
		$this->richWikitextTransclusionInteractor = $richWikitextTransclusionInteractor;
		$this->entityIdParser = $entityIdParser;
		$this->usageAccumulator = $usageAccumulator;
		$this->termsLanguages = $termsLanguages;
		$this->globalSiteId = $globalSiteId;
	}

	/**
	 * Format the main Snaks belonging to a Statement (which is identified by a NumericPropertyId
	 * or the label of a Property) as wikitext escaped plain text.
	 *
	 * @param string $entityId
	 * @param string $propertyLabelOrId
	 * @param int[]|null $acceptableRanks
	 *
	 * @return string Wikitext
	 */
	public function formatPropertyValues(
		string $entityId,
		string $propertyLabelOrId,
		?array $acceptableRanks = null
	): string {
		$entityId = $this->entityIdParser->parse( $entityId );

		return $this->plainTextTransclusionInteractor->render(
			$entityId,
			$propertyLabelOrId,
			$acceptableRanks
		);
	}

	/**
	 * Format the main Snaks belonging to a Statement (which is identified by a NumericPropertyId
	 * or the label of a Property) as rich wikitext.
	 *
	 * @param string $entityId
	 * @param string $propertyLabelOrId
	 * @param int[]|null $acceptableRanks
	 *
	 * @return string Wikitext
	 */
	public function formatStatements(
		string $entityId,
		string $propertyLabelOrId,
		?array $acceptableRanks = null
	): string {
		$entityId = $this->entityIdParser->parse( $entityId );

		return $this->richWikitextTransclusionInteractor->render(
			$entityId,
			$propertyLabelOrId,
			$acceptableRanks
		);
	}

	/**
	 * Add a statement usage (called once specific statements are accessed).
	 *
	 * @param string $entityId The Entity from which the statements were accessed.
	 * @param string $propertyId Property id of the statements accessed.
	 */
	public function addStatementUsage( string $entityId, string $propertyId ): void {
		$entityId = $this->entityIdParser->parse( $entityId );
		$propertyId = new NumericPropertyId( $propertyId );

		$this->usageAccumulator->addStatementUsage( $entityId, $propertyId );
	}

	/**
	 * Add a label usage (called once specific labels are accessed).
	 *
	 * @param string $entityId The Entity from which the labels were accessed.
	 * @param string|null $langCode Language code the labels accessed.
	 */
	public function addLabelUsage( string $entityId, ?string $langCode ): void {
		$entityId = $this->entityIdParser->parse( $entityId );
		if ( !$this->termsLanguages->hasLanguage( $langCode ) ) {
			$langCode = null;
		}
		$this->usageAccumulator->addLabelUsage( $entityId, $langCode );
	}

	/**
	 * Add a description usage (called once specific descriptions are accessed).
	 *
	 * @param string $entityId The Entity from which the descriptions were accessed.
	 * @param string|null $langCode Language code the descriptions accessed.
	 */
	public function addDescriptionUsage( string $entityId, ?string $langCode ): void {
		$entityId = $this->entityIdParser->parse( $entityId );
		if ( !$this->termsLanguages->hasLanguage( $langCode ) ) {
			$langCode = null;
		}
		$this->usageAccumulator->addDescriptionUsage( $entityId, $langCode );
	}

	/**
	 * Add an alias usage (called once specific aliases are accessed).
	 *
	 * @param string $entityId The Entity from which the aliases were accessed.
	 * @param string|null $langCode Language code the aliases accessed.
	 */
	public function addAliasUsage( string $entityId, ?string $langCode ): void {
		$entityId = $this->entityIdParser->parse( $entityId );
		if ( !$this->termsLanguages->hasLanguage( $langCode ) ) {
			$langCode = null;
		}
		$this->usageAccumulator->addAliasUsage( $entityId, $langCode );
	}

	/**
	 * Add a other usage.
	 *
	 * @param string $entityId The Entity from which something was accessed.
	 */
	public function addOtherUsage( string $entityId ): void {
		$entityId = $this->entityIdParser->parse( $entityId );
		$this->usageAccumulator->addOtherUsage( $entityId );
	}

	/**
	 * Add a sitelink usage (called once any sitelink is accessed).
	 *
	 * @param string $entityId The Entity from which the sitelinks were accessed.
	 * @param string|null $requestedSiteId The site for which a specific sitelink is requested.
	 */
	public function addTitleOrSiteLinksUsage( string $entityId, ?string $requestedSiteId ): void {
		$entityId = $this->entityIdParser->parse( $entityId );
		$requestedSiteId = $requestedSiteId ?: $this->globalSiteId;

		if ( $requestedSiteId === $this->globalSiteId ) {
			$this->usageAccumulator->addTitleUsage( $entityId );
		} else {
			$this->usageAccumulator->addSiteLinksUsage( $entityId );
		}
	}
}
