<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use Language;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Actual implementations of the functions to access Wikibase through the Scribunto extension
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLuaEntityBindings {

	/**
	 * @var StatementTransclusionInteractor
	 */
	private $statementTransclusionInteractor;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @param StatementTransclusionInteractor $statementTransclusionInteractor
	 * @param EntityIdParser $entityIdParser
	 * @param Language $language
	 * @param string $siteId
	 */
	public function __construct(
		StatementTransclusionInteractor $statementTransclusionInteractor,
		EntityIdParser $entityIdParser,
		Language $language,
		$siteId
	) {
		$this->statementTransclusionInteractor = $statementTransclusionInteractor;
		$this->entityIdParser = $entityIdParser;
		$this->language = $language;
		$this->siteId = $siteId;
	}

	/**
	 * Render the main Snaks belonging to a Statement (which is identified by a PropertyId
	 * or the label of a Property).
	 *
	 * @since 0.5
	 *
	 * @param string $entityId
	 * @param string $propertyLabelOrId
	 * @param int[]|null $acceptableRanks
	 *
	 * @return string
	 */
	public function formatPropertyValues( $entityId, $propertyLabelOrId, array $acceptableRanks = null ) {
		$entityId = $this->entityIdParser->parse( $entityId );

		return $this->statementTransclusionInteractor->render(
			$entityId,
			$propertyLabelOrId,
			$acceptableRanks
		);
	}

	/**
	 * Get global site ID (e.g. "enwiki")
	 * This is basically a helper function.
	 * @TODO: Make this part of mw.site in the Scribunto extension.
	 *
	 * @since 0.5
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
	 * @since 0.5
	 *
	 * @return string
	 */
	public function getLanguageCode() {
		return $this->language->getCode();
	}

}
