<?php

namespace Wikibase\Repo\Rdf\Values;

use DataValues\DataValue;
use Wikibase\Repo\Rdf\DedupeBag;
use Wikibase\Repo\Rdf\HashDedupeBag;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * Helper object for mapping DataValues to complex RDF structures (value nodes).
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class ComplexValueRdfHelper {

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var DedupeBag
	 */
	private $dedupeBag;

	/**
	 * @var RdfWriter
	 */
	private $valueNodeWriter;

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $valueNodeWriter
	 * @param DedupeBag|null $dedupeBag
	 */
	public function __construct( RdfVocabulary $vocabulary, RdfWriter $valueNodeWriter, DedupeBag $dedupeBag = null ) {
		$this->valueNodeWriter = $valueNodeWriter;
		$this->vocabulary = $vocabulary;
		$this->dedupeBag = $dedupeBag ?: new HashDedupeBag();
	}

	/**
	 * @return RdfWriter
	 */
	public function getValueNodeWriter() {
		return $this->valueNodeWriter;
	}

	/**
	 * Creates a value node for $value, and attaches it to the current subject of $writer.
	 * If a value node for $value was already created, null is returned. Otherwise, the
	 * value node's lname is returned, which should be used to generate detailed about the
	 * value into the writer returned by getValueNodeWriter().
	 *
	 * When this method returns a non-null lname, the current subject of the RdfWriter returned by
	 * getValueNodeWriter() will the be value node with that lname.
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type (unused, passed here for symmetry
	 *        with the signature of ValueSnakRdfBuilder::addValue).
	 * @param DataValue $value
	 * @param bool $normalized Is this a normalized value?
	 *
	 * @return string|null The LName of the value node (in the RdfVocabulary::NS_VALUE namespace),
	 *  or null if the value node should not be processed (generally, because it already has
	 *  been processed).
	 */
	public function attachValueNode(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		$snakNamespace,
		DataValue $value,
		$normalized = false
	) {
		// @phan-suppress-next-line PhanUndeclaredMethod
		$valueLName = $value->getHash();

		$namespaces =
			$normalized ? $this->vocabulary->claimToValueNormalized : $this->vocabulary->claimToValue;

		$writer->say( $namespaces[$propertyValueNamespace], $propertyValueLName )
			->is( $snakNamespace, $valueLName );

		if ( $this->dedupeBag->alreadySeen( $valueLName, 'V' ) !== false ) {
			return null;
		}

		$this->valueNodeWriter->about( $snakNamespace, $valueLName )
			->a( RdfVocabulary::NS_ONTOLOGY, $this->vocabulary->getValueTypeName( $value ) );

		return $valueLName;
	}

}
