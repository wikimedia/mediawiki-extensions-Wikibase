<?php

namespace Wikibase\Rdf\Values;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Rdf\ValueSnakRdfBuilder;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for wikibase-entity DataValues.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class EntityIdRdfBuilder implements ValueSnakRdfBuilder {

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var EntityMentionListener
	 */
	private $mentionedEntityTracker;

	public function __construct(
		RdfVocabulary $vocabulary,
		EntityMentionListener $mentionedEntityTracker
	) {
		$this->vocabulary = $vocabulary;
		$this->mentionedEntityTracker = $mentionedEntityTracker;
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
		PropertyValueSnak $snak
	) {
		$value = $snak->getDataValue();
		$entityId = $value->getValue()->getEntityId();
		$entityLName = $this->vocabulary->getEntityLName( $entityId );

		$writer->say( $propertyValueNamespace, $propertyValueLName )
			->is( $this->vocabulary->entityNamespaceNames[$entityId->getRepositoryName()], $entityLName );

		$this->mentionedEntityTracker->entityReferenceMentioned( $entityId );
	}

}
