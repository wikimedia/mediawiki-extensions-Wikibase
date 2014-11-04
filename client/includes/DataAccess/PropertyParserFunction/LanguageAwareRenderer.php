<?php

namespace Wikibase\DataAccess\PropertyParserFunction;

use InvalidArgumentException;
use Language;
use Status;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\PropertyIdResolver;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Client\Usage\UsageAccumulator;

/**
 * @fixme see what code can be shared with Lua handling code.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Liangent < liangent@gmail.com >
 */
class LanguageAwareRenderer implements PropertyParserFunctionRenderer {

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
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @param Language $language
	 * @param SnaksFinder $snaksFinder
	 * @param SnakFormatter $snakFormatter
	 * @param UsageAccumulator $usageAcc
	 */
	public function __construct(
		Language $language,
		PropertyIdResolver $propertyIdResolver,
		SnaksFinder $snaksFinder,
		SnakFormatter $snakFormatter,
		UsageAccumulator $usageAcc
	) {
		$this->language = $language;
		$this->propertyIdResolver = $propertyIdResolver;
		$this->snaksFinder = $snaksFinder;
		$this->snakFormatter = $snakFormatter;
		$this->usageAccumulator = $usageAcc;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId property label or ID (pXXX)
	 *
	 * @return string
	 */
	public function render( EntityId $entityId, $propertyLabelOrId ) {
		try {
			// @todo have the $propertyId resolved before passing into here
			$propertyId = $this->propertyIdResolver->resolvePropertyId(
				$propertyLabelOrId,
				$this->language->getCode()
			);

			$status = $this->renderWithStatus( $entityId, $propertyId );
		} catch ( PropertyLabelNotResolvedException $ex ) {
			// @fixme use ExceptionLocalizer
			$status = $this->getStatusForException( $propertyLabelOrId, $ex->getMessage() );
		} catch ( InvalidArgumentException $ex ) {
			$status = $this->getStatusForException( $propertyLabelOrId, $ex->getMessage() );
		}

		if ( !$status->isGood() ) {
			$error = $status->getMessage()->inLanguage( $this->language )->text();
			return '<p class="error wikibase-error">' . $error . '</p>';
		}

		return $status->getValue();
	}

	/**
	 * @param string $propertyLabel
	 * @param string $message
	 *
	 * @return Status
	 */
	private function getStatusForException( $propertyLabel, $message ) {
		return Status::newFatal(
			'wikibase-property-render-error',
			$propertyLabel,
			$message
		);
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

	/**
	 * @param Snak[] $snaks
	 */
	private function trackUsage( array $snaks ) {
		// Note: we track any EntityIdValue as a label usage.
		// This is making assumptions about what the respective formatter actually does.
		// Ideally, the formatter itself would perform the tracking, but that seems nasty to model.

		foreach ( $snaks as $snak ) {
			if ( !( $snak instanceof PropertyValueSnak) ) {
				continue;
			}

			$value = $snak->getDataValue();

			if ( $value instanceof EntityIdValue ) {
				$this->usageAccumulator->addLabelUsage( $value->getEntityId() );
			}
		}
	}

	/**
	 * @param EntityId $entityId
	 * @param PropertyId $propertyId
	 *
	 * @return Status a status object wrapping a wikitext string
	 */
	private function renderWithStatus( EntityId $entityId, PropertyId $propertyId ) {
		wfProfileIn( __METHOD__ );

		$snaks = $this->snaksFinder->findSnaks(
			$entityId,
			$propertyId,
			$this->language->getCode()
		);

		if ( !$snaks ) {
			return Status::newGood( '' );
		}

		$this->trackUsage( $snaks );

		$text = $this->formatSnaks( $snaks );
		$status = Status::newGood( $text );

		wfProfileOut( __METHOD__ );
		return $status;
	}

}
