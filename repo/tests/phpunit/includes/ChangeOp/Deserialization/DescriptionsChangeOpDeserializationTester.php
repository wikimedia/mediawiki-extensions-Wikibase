<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

/**
 * Set of test methods that can be reused in DescriptionsChangeOpDeserializerTest and tests for
 * ChangeOpDeserializers of entities that have descriptions.
 *
 * @license GPL-2.0-or-later
 */
trait DescriptionsChangeOpDeserializationTester {

	public function testGivenChangeRequestWithDescription_addsDescription() {
		$entity = $this->getEntity();
		$description = 'foo';
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[
				'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => $description ] ],
			]
		);

		$changeOp->apply( $entity, new Summary() );
		$this->assertSame( $description, $entity->getDescriptions()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithNewDescription_overridesExistingDescription() {
		$entity = $this->getEntityWithEnDescription();
		$newDescription = 'foo';
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => $newDescription ] ] ]
		);

		$changeOp->apply( $entity, new Summary() );
		$this->assertSame( $newDescription, $entity->getDescriptions()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithRemove_removesDescription() {
		$entity = $this->getEntityWithEnDescription();
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ]
		);

		$changeOp->apply( $entity, new Summary() );
		$this->assertFalse( $entity->getDescriptions()->hasTermForLanguage( 'en' ) );
	}

	public function testGivenChangeRequestWithEmptyValue_removesDescription() {
		$entity = $this->getEntityWithEnDescription();
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => '' ] ] ]
		);

		$changeOp->apply( $entity, new Summary() );
		$this->assertFalse( $entity->getDescriptions()->hasTermForLanguage( 'en' ) );
	}

	private function getEntityWithEnDescription() {
		$entity = $this->getEntity();
		$entity->getDescriptions()->setTextForLanguage( 'en', 'en-description' );

		return $entity;
	}

	/**
	 * @return DescriptionsProvider|EntityDocument
	 */
	abstract protected function getEntity();

	/**
	 * @return ChangeOpDeserializer
	 */
	abstract protected function getChangeOpDeserializer();

}
