<?php

namespace Wikibase\Rdf\Values;

use DataValues\DataValue;
use Wikibase\Rdf\DataValueRdfBuilder;
use Wikimedia\Assert\Assert;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for DataValues that map to a literal.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class LiteralValueRdfBuilder implements DataValueRdfBuilder {

	/**
	 * @var string|null the base URI of the type
	 */
	private $typeBase;

	/**
	 * @var string|null the local name of the type
	 */
	private $typeLocal;

	/**
	 * @param string|null $typeBase
	 * @param string|null $typeLocal
	 */
	function __construct( $typeBase, $typeLocal ) {
		Assert::parameterType( 'string|null', $typeBase, '$typeBase' );
		Assert::parameterType( 'string|null', $typeLocal, '$typeLocal' );

		$this->typeBase = $typeBase;
		$this->typeLocal = $typeLocal;
	}


	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param DataValue $value
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		DataValue $value
	) {
		$literalValue = $this->getLiteralValue( $value );
		$nsType = $this->typeBase ?: ( $this->typeLocal === null ? null : 'xsd' );

		$writer->say( $propertyValueNamespace, $propertyValueLName )
			->value( $literalValue, $nsType, $this->typeLocal );
	}

	/**
	 * @param DataValue $value
	 *
	 * @return string
	 */
	protected function getLiteralValue( DataValue $value ) {
		return $value->getValue();
	}
}
