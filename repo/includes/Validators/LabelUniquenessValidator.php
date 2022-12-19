<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\Store\TermsCollisionDetector;

/**
 * @license GPL-2.0-or-later
 */
class LabelUniquenessValidator implements EntityValidator {

	/** @var TermsCollisionDetector */
	private $collisionDetector;

	public function __construct(
		TermsCollisionDetector $collisionDetector
	) {
		$this->collisionDetector = $collisionDetector;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 */
	public function validateEntity( EntityDocument $entity ) {
		$entityId = $entity->getId()->getSerialization();
		$entityType = $entity->getType();

		if ( $entity instanceof LabelsProvider ) {
			$errors = [];

			$entityCollisions = $this->collisionDetector->detectLabelsCollision( $entity->getLabels() );

			foreach ( $entityCollisions as $idString => $collisions ) {
				// allow colliding with self
				if ( $entityId === $idString ) {
					continue;
				}

				/** @var Term $collision */
				foreach ( $collisions as $collision ) {

					$collidingEntityId = $entityType === Item::ENTITY_TYPE
						? new ItemId( $idString )
						: new NumericPropertyId( $idString );

					$errors[] = $this->collisionToError(
						'label-conflict',
						$collidingEntityId,
						$collision->getLanguageCode(),
						$collision->getText()
					);
				}

			}

			if ( !empty( $errors ) ) {
				return Result::newError( $errors );
			}
		}

		return Result::newSuccess();
	}

	private function collisionToError(
		string $code,
		EntityId $collidingEntityId,
		string $lang,
		string $label
	): UniquenessViolation {
		return new UniquenessViolation(
			$collidingEntityId,
			'found conflicting terms',
			$code,
			[
				$label,
				$lang,
				$collidingEntityId,
			]
		);
	}

}
