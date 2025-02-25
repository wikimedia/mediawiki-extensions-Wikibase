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
	public function __construct( array $valueBuilders, ?LoggerInterface $logger = null ) {
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
	 * @param string $snakNamespace
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
		$builder = $this->getValueBuilder( $dataType, $valueType, $propertyValueNamespace, $propertyValueLName, $snak );

		if ( $builder ) {
			$builder->addValue( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $snakNamespace, $snak );
		} else {
			// emit a fake predicate+object just to ensure the writer stays in a sane state when we return (T384625)
			$writer->say( 'rdfs', 'comment' )->text(
				"broken $propertyValueNamespace:$propertyValueLName triple, please ignore",
				'en'
			);
		}
	}

	private function getValueBuilder(
		?string $dataTypeId,
		string $dataValueType,
		string $propertyValueNamespace,
		string $propertyValueLName,
		PropertyValueSnak $snak
	): ?ValueSnakRdfBuilder {
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
				'{dataTypeId} nor for value type {dataValueType} ' .
				'(for predicate {propertyValueNamespace}:{propertyValueLName}).',
				[
					'dataTypeId' => $dataTypeId,
					'dataValueType' => $dataValueType,
					'propertyValueNamespace' => $propertyValueNamespace,
					'propertyValueLName' => $propertyValueLName,
					'snak' => $snak,
				]
			);
		} else {
			$this->logger->warning(
				__METHOD__ . ': No RDF builder defined for value type {dataValueType} ' .
				'(for predicate {propertyValueNamespace}:{propertyValueLName}).',
				[
					'dataValueType' => $dataValueType,
					'propertyValueNamespace' => $propertyValueNamespace,
					'propertyValueLName' => $propertyValueLName,
					'snak' => $snak,
				]
			);
		}

		return null;
	}

}
