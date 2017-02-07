<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use Language;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Actual implementations of the functions to access Wikibase through the Scribunto extension
 *
 * @license GPL-2.0+
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
	 * @param StatementTransclusionInteractor $plainTextTransclusionInteractor
	 * @param StatementTransclusionInteractor $richWikitextTransclusionInteractor
	 * @param EntityIdParser $entityIdParser
	 * @param Language $language
	 * @param UsageAccumulator $usageAccumulator
	 * @param string $siteId
	 */
	public function __construct(
		StatementTransclusionInteractor $plainTextTransclusionInteractor,
		StatementTransclusionInteractor $richWikitextTransclusionInteractor,
		EntityIdParser $entityIdParser,
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
	}

	/**
	 * Format the main Snaks belonging to a Statement (which is identified by a PropertyId
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
	 * Format the main Snaks belonging to a Statement (which is identified by a PropertyId
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
	 * @param bool $propertyExists
	 */
	public function addStatementUsage( $entityId, $propertyId ) {
		$entityId = $this->entityIdParser->parse( $entityId );
		$propertyId = $this->entityIdParser->parse( $propertyId );

		$this->usageAccumulator->addStatementUsage( $entityId, $propertyId );
	}

	/**
	 * Get global site ID (e.g. "enwiki")
	 * This is basically a helper function.
	 * @TODO: Make this part of mw.site in the Scribunto extension.
	 *
	 * @return string
	 */
	public function getGlobalSiteId() {
		return $this->siteId;
	}

	/**
	 * Get the language we are currently working with.
	 * @TODO: Once T114640 has been implemented, this should probably be
	 * generally exposed in Scribunto as parser target language.
	 *
	 * @return string
	 */
	public function getLanguageCode() {
		return $this->language->getCode();
	}

}
