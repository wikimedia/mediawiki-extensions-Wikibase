<?php

namespace Wikibase\Repo\Rdf\Values;

use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Repo\Rdf\EntityMentionListener;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for wikibase-entity DataValues.
 *
 * @license GPL-2.0-or-later
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
		$snakNamespace,
		PropertyValueSnak $snak
	) {
		$value = $snak->getDataValue();
		$entityId = $value->getValue()->getEntityId();
		$entityLName = $this->vocabulary->getEntityLName( $entityId );
		$entityRepoName = $this->vocabulary->getEntityRepositoryName( $entityId );

		$writer->say( $propertyValueNamespace, $propertyValueLName )
			->is( $this->vocabulary->entityNamespaceNames[$entityRepoName], $entityLName );

		$this->mentionedEntityTracker->entityReferenceMentioned( $entityId );
	}

}
