<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use Language;
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

	/**
	 * @var StatementTransclusionInteractor
	 */
	private $plainTextTransclusionInteractor;

	/**
	 * @var StatementTransclusionInteractor
	 */
	private $richWikitextTransclusionInteractor;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	public function __construct(
		StatementTransclusionInteractor $plainTextTransclusionInteractor,
		StatementTransclusionInteractor $richWikitextTransclusionInteractor,
		EntityIdParser $entityIdParser,
		ContentLanguages $termsLanguages,
		Language $language,
		UsageAccumulator $usageAccumulator,
		$siteId
	) {
		$this->plainTextTransclusionInteractor = $plainTextTransclusionInteractor;
		$this->richWikitextTransclusionInteractor = $richWikitextTransclusionInteractor;
		$this->entityIdParser = $entityIdParser;
		$this->language = $language;
		$this->usageAccumulator = $usageAccumulator;
		$this->siteId = $siteId;
		$this->termsLanguages = $termsLanguages;
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
	public function formatPropertyValues( $entityId, $propertyLabelOrId, array $acceptableRanks = null ) {
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
	public function formatStatements( $entityId, $propertyLabelOrId, array $acceptableRanks = null ) {
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
	public function addStatementUsage( $entityId, $propertyId ) {
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
	public function addLabelUsage( $entityId, $langCode ) {
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
	public function addDescriptionUsage( $entityId, $langCode ) {
		$entityId = $this->entityIdParser->parse( $entityId );
		if ( !$this->termsLanguages->hasLanguage( $langCode ) ) {
			$langCode = null;
		}
		$this->usageAccumulator->addDescriptionUsage( $entityId, $langCode );
	}

	/**
	 * Add a other usage.
	 *
	 * @param string $entityId The Entity from which something was accessed.
	 */
	public function addOtherUsage( $entityId ) {
		$entityId = $this->entityIdParser->parse( $entityId );
		$this->usageAccumulator->addOtherUsage( $entityId );
	}

	/**
	 * Add a sitelink usage (called once any sitelink is accessed).
	 *
	 * @param string $entityId The Entity from which the sitelinks were accessed.
	 */
	public function addSiteLinksUsage( $entityId ) {
		$entityId = $this->entityIdParser->parse( $entityId );
		$this->usageAccumulator->addSiteLinksUsage( $entityId );
	}

	/**
	 * Get global site ID (e.g. "enwiki")
	 * This is basically a helper function.
	 * @todo Make this part of mw.site in the Scribunto extension.
	 *
	 * @return string
	 */
	public function getGlobalSiteId() {
		return $this->siteId;
	}

	/**
	 * Get the language we are currently working with.
	 * @todo Once T114640 has been implemented, this should probably be
	 * generally exposed in Scribunto as parser target language.
	 *
	 * @return string
	 */
	public function getLanguageCode() {
		return $this->language->getCode();
	}

}
