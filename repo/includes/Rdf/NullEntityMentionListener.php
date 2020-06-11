<?php

namespace Wikibase\Repo\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Null implementation of EntityMentionListener
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 *
 * @codeCoverageIgnore
 */
class NullEntityMentionListener implements EntityMentionListener {

	/**
	 * Should be called when an entity reference (an EntityIdValue object) is encountered.
	 *
	 * @param EntityId $id
	 */
	public function entityReferenceMentioned( EntityId $id ) {
	}

	/**
	 * Should be called when a property is used in a PropertySnak.
	 *
	 * @param PropertyId $id
	 */
	public function propertyMentioned( PropertyId $id ) {
	}

	/**
	 * Should be called when a sub entity is encountered.
	 * For example, in WikibaseLexeme, when a Form or a Sense is encountered when serializing a Lexeme.
	 *
	 * @param EntityDocument $entity
	 */
	public function subEntityMentioned( EntityDocument $entity ) {
	}

}
