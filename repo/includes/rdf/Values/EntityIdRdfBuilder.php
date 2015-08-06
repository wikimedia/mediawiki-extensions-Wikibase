<?php

namespace Wikibase\Rdf\Values;

use DataValues\DataValue;
use Wikibase\Rdf\DataValueRdfBuilder;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for wikibase-entity DataValues.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class EntityIdRdfBuilder implements DataValueRdfBuilder {

	/**
	 * @var EntityMentionListener
	 */
	private $mentionedEntityTracker;

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param EntityMentionListener $mentionedEntityTracker
	 */
	public function __construct( RdfVocabulary $vocabulary, EntityMentionListener $mentionedEntityTracker ) {
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
	 * @param DataValue $value
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		DataValue $value
	) {
		$entityId = $value->getValue()->getEntityId();
		$entityLName = $this->vocabulary->getEntityLName( $entityId );
		$writer->say( $propertyValueNamespace, $propertyValueLName )->is( RdfVocabulary::NS_ENTITY, $entityLName );

		$this->mentionedEntityTracker->entityReferenceMentioned( $entityId );
	}

}
