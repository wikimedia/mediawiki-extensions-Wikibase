<?php

namespace Wikibase\Client\Scribunto;

use Wikibase\DataAccess\EntityStatementsRenderer;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Actual implementations of the functions to access Wikibase through the Scribunto extension
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLuaEntityBindings {

	/**
	 * @var EntityStatementsRenderer
	 */
	private $entityStatementsRenderer;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @param EntityStatementsRenderer $entityStatementsRenderer
	 * @param EntityIdParser $entityIdParser
	 * @param string $siteId
	 */
	public function __construct(
		EntityStatementsRenderer $entityStatementsRenderer,
		EntityIdParser $entityIdParser,
		$siteId
	) {
		$this->entityStatementsRenderer = $entityStatementsRenderer;
		$this->entityIdParser = $entityIdParser;
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
	 * @throws PropertyLabelNotResolvedException
	 *
	 * @return string
	 */
	public function formatPropertyValues( $entityId, $propertyLabelOrId, array $acceptableRanks = null ) {
		$entityId = $this->entityIdParser->parse( $entityId );

		return $this->entityStatementsRenderer->render( $entityId, $propertyLabelOrId, $acceptableRanks );
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

}
