<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Summary;

/**
 * Set of test methods that can be reused in LabelsChangeOpDeserializerTest and tests for
 * ChangeOpDeserializers of entities that have labels.
 *
 * @license GPL-2.0+
 */
trait LabelsChangeOpDeserializationTester {

	public function testGivenChangeRequestWithLabel_addsLabel() {
		$entity = $this->getEntity();
		$label = 'foo';
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => $label ] ] ]
		);

		$changeOp->apply( $entity, new Summary() );
		$this->assertSame( $label, $entity->getLabels()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithNewLabel_overridesExistingLabel() {
		$entity = $this->getEntityWithEnLabel();
		$newLabel = 'foo';
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => $newLabel ] ] ]
		);

		$changeOp->apply( $entity, new Summary() );
		$this->assertSame( $newLabel, $entity->getLabels()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithRemove_removesLabel() {
		$entity = $this->getEntityWithEnLabel();
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ]
		);

		$changeOp->apply( $entity, new Summary() );
		$this->assertFalse( $entity->getLabels()->hasTermForLanguage( 'en' ) );
	}

	public function testGivenChangeRequestWithEmptyLabel_removesLabel() {
		$entity = $this->getEntityWithEnLabel();
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => '' ] ] ]
		);

		$changeOp->apply( $entity, new Summary() );
		$this->assertFalse( $entity->getLabels()->hasTermForLanguage( 'en' ) );
	}

	private function getEntityWithEnLabel() {
		$entity = $this->getEntity();
		$entity->getLabels()->setTextForLanguage( 'en', 'en-label' );

		return $entity;
	}

	/**
	 * @return LabelsProvider|EntityDocument
	 */
	protected abstract function getEntity();

	/**
	 * @return ChangeOpDeserializer
	 */
	protected abstract function getChangeOpDeserializer();

}
