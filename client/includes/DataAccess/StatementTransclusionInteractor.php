<?php

namespace Wikibase\DataAccess;

use Language;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\StatementListProvider;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;

/**
 * Renders the main Snaks associated with a given Property on an Entity.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 *
 * @author Marius Hoch < hoo@online.de >
 */
class StatementTransclusionInteractor {

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var PropertyIdResolver
	 */
	private $propertyIdResolver;

	/**
	 * @var SnaksFinder
	 */
	private $snaksFinder;

	/**
	 * @var SnakFormatter
	 */
	private $snakFormatter;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @param Language $language
	 * @param PropertyIdResolver $propertyIdResolver
	 * @param SnaksFinder $snaksFinder
	 * @param SnakFormatter $snakFormatter
	 * @param EntityLookup $entityLookup
	 */
	public function __construct(
		Language $language,
		PropertyIdResolver $propertyIdResolver,
		SnaksFinder $snaksFinder,
		SnakFormatter $snakFormatter,
		EntityLookup $entityLookup
	) {
		$this->language = $language;
		$this->propertyIdResolver = $propertyIdResolver;
		$this->snaksFinder = $snaksFinder;
		$this->snakFormatter = $snakFormatter;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId property label or ID (pXXX)
	 * @param int[]|null $acceptableRanks
	 *
	 * @throws PropertyLabelNotResolvedException
	 * @return string
	 */
	public function render(
		EntityId $entityId,
		$propertyLabelOrId,
		$acceptableRanks = null
	) {
		$entity = $this->entityLookup->getEntity( $entityId );

		if ( !$entity instanceof StatementListProvider ) {
			return '';
		}

		$propertyId = $this->propertyIdResolver->resolvePropertyId(
			$propertyLabelOrId,
			$this->language->getCode()
		);

		$snaks = $this->snaksFinder->findSnaks(
			$entity,
			$propertyId,
			$acceptableRanks
		);

		return $this->formatSnaks( $snaks );
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string wikitext
	 */
	private function formatSnaks( array $snaks ) {
		$formattedValues = array();

		foreach ( $snaks as $snak ) {
			$formattedValues[] = $this->snakFormatter->formatSnak( $snak );
		}

		return $this->language->commaList( $formattedValues );
	}

}
