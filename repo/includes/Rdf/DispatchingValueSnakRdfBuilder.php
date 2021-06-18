<?php

namespace Wikibase\Repo\Rdf;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikimedia\Assert\Assert;
use Wikimedia\Purtle\RdfWriter;

/**
 * Dispatching implementation of ValueSnakRdfBuilder. This allows extensions to register
 * ValueSnakRdfBuilders for custom data types.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DispatchingValueSnakRdfBuilder implements ValueSnakRdfBuilder {

	/**
	 * @var ValueSnakRdfBuilder[]
	 */
	private $valueBuilders;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param ValueSnakRdfBuilder[] $valueBuilders ValueSnakRdfBuilder objects keyed by data type
	 * (with prefix "PT:") or value type (with prefix "VT:").
	 * @param LoggerInterface|null $logger Used to log a warning
	 * when encountering a value without a builder in $valueBuilders.
	 */
	public function __construct( array $valueBuilders, LoggerInterface $logger = null ) {
		Assert::parameterElementType( ValueSnakRdfBuilder::class, $valueBuilders, '$valueBuilders' );

		$this->valueBuilders = $valueBuilders;
		$this->logger = $logger ?: new NullLogger();
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param PropertyValueSnak $snak
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		$snakNamespace,
		PropertyValueSnak $snak
	) {
		$valueType = $snak->getDataValue()->getType();
		$builder = $this->getValueBuilder( $dataType, $valueType );

		if ( $builder ) {
			$builder->addValue( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $snakNamespace, $snak );
		}
	}

	/**
	 * @param string|null $dataTypeId
	 * @param string $dataValueType
	 *
	 * @return null|ValueSnakRdfBuilder
	 */
	private function getValueBuilder( $dataTypeId, $dataValueType ) {
		if ( $dataTypeId !== null ) {
			if ( isset( $this->valueBuilders["PT:$dataTypeId"] ) ) {
				return $this->valueBuilders["PT:$dataTypeId"];
			}
		}

		if ( isset( $this->valueBuilders["VT:$dataValueType"] ) ) {
			return $this->valueBuilders["VT:$dataValueType"];
		}

		if ( $dataTypeId !== null ) {
			$this->logger->warning(
				__METHOD__ . ': No RDF builder defined for data type ' .
				'{dataTypeId} nor for value type {dataValueType}.',
				[
					'dataTypeId' => $dataTypeId,
					'dataValueType' => $dataValueType,
				]
			);
		} else {
			$this->logger->warning(
				__METHOD__ . ': No RDF builder defined for value type {dataValueType}.',
				[
					'dataValueType' => $dataValueType,
				]
			);
		}

		return null;
	}

}
