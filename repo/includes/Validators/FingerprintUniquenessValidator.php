<?php

namespace Wikibase\Repo\Validators;

use Generator;
use InvalidArgumentException;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Repo\ChangeOp\ChangeOpDescriptionResult;
use Wikibase\Repo\ChangeOp\ChangeOpFingerprintResult;
use Wikibase\Repo\ChangeOp\ChangeOpLabelResult;
use Wikibase\Repo\ChangeOp\ChangeOpResultTraversal;
use Wikibase\Repo\Store\TermsCollisionDetector;

/**
 * Validates the uniqueness of changing parts in a {@link ChangeOpFingerprintResult}
 * across entities in store
 *
 * Business logic in here is as following:
 * Given an item Q1 in language L
 * When L label or description of Q1 are being modified
 * Then there should be no other Q2 in the store with L label and/or description
 * Items are unique on their label and description in a language. This means, given a language, no two items should
 * have same label and same description in that language.
 *
 * For properties, label uniqueness is instead validated by the LabelUniquenessValidator
 * added by EntityConstraintProvider.
 *
 * @see EntityConstraintProvider
 * @see LabelUniquenessValidator
 *
 * @license GPL-2.0-or-later
 */
class FingerprintUniquenessValidator implements ValueValidator {

	use ChangeOpResultTraversal;

	/** @var TermsCollisionDetector */
	private $termsCollisionDetector;

	/** @var TermLookup */
	private $termLookup;

	public function __construct(
		TermsCollisionDetector $termsCollisionDetector,
		TermLookup $termLookup
	) {
		$this->termsCollisionDetector = $termsCollisionDetector;
		$this->termLookup = $termLookup;
	}

	public function setOptions( array $options ) {
		// noop
	}

	public function validate( $value ) {
		if ( !$value instanceof ChangeOpFingerprintResult ) {
			throw new InvalidArgumentException( '$value can only be of type ChangeOpFingerprintResult' );
		}

		$entityId = $value->getEntityId();
		return $entityId->getEntityType() === Item::ENTITY_TYPE
			? $this->validateItem( $value )
			: Result::newSuccess();
	}

	private function validateItem( ChangeOpFingerprintResult $fingerprintChangeOpResult ): Result {
		$errors = [];

		foreach ( $this->getChangedLabelsAndDescriptionsPerLanguage( $fingerprintChangeOpResult ) as $lang => $terms ) {
			$collidingEntityId = $this->termsCollisionDetector->detectLabelAndDescriptionCollision(
				$lang,
				$terms['label'],
				$terms['description']
			);

			if ( $collidingEntityId !== null ) {
				$errors[] = $this->collisionToError(
					'label-with-description-conflict',
					$collidingEntityId,
					$lang,
					$terms['label']
				);
			}
		}

		if ( !empty( $errors ) ) {
			return Result::newError( $errors );
		}

		return Result::newSuccess();
	}

	/**
	 * @return Generator yielding entries of the shape
	 *  [ language code => [ 'label' => label text, 'descripition' => description text ] ]
	 */
	private function getChangedLabelsAndDescriptionsPerLanguage( ChangeOpFingerprintResult $changeOpsResult ): Generator {
		list( $newTerms, $oldTerms ) = $this->collectNewAndOldTerms( $changeOpsResult );

		$labelDescriptionPairsPerLanguage = $this->generateLabelDescriptionPairs(
			$newTerms,
			$oldTerms,
			$changeOpsResult->getEntityId()
		);

		yield from $labelDescriptionPairsPerLanguage;
	}

	/**
	 * @return array containing two entries [ 0 => new terms, 1 => old terms ]
	 *  new terms will contain new term values per language per term type that appear in $changeOpsResult as changing
	 *  the entity, while old terms will contain old term values per language per term type that appear in
	 *  $changeOpsResult as not changing the entity
	 *
	 *  old terms might not contain complementary data to those entries in new terms, as that depends on whether
	 *  the ChangeOpsResult contains results of things that are not being changed or not (which in turn depends
	 *  on ChangeOpFingerprint that produced the ChangeOpFingerprintResult). Example scenario is an api call that is
	 *  sending to server only the terms that need to change. Counter example scenario is a frontend (e.g. termbox)
	 *  sending back to server all terms, whether changed or not.
	 */
	private function collectNewAndOldTerms( ChangeOpFingerprintResult $changeOpsResult ): array {
		$traversable = $this->makeRecursiveTraversable( $changeOpsResult );

		$newTerms = [];
		$oldTerms = [];
		foreach ( $traversable as $changeOp ) {
			$lang = null;

			if ( $changeOp instanceof ChangeOpLabelResult ) {
				$lang = $changeOp->getLanguageCode();

				if ( $changeOp->isEntityChanged() ) {
					$newTerms[$lang]['label'] = $changeOp->getNewLabel();
				} else {
					$oldTerms[$lang]['label'] = $changeOp->getOldLabel();
				}
			} elseif ( $changeOp instanceof ChangeOpDescriptionResult ) {
				$lang = $changeOp->getLanguageCode();

				if ( $changeOp->isEntityChanged() ) {
					$newTerms[$lang]['description'] = $changeOp->getNewDescription();
				} else {
					$oldTerms[$lang]['description'] = $changeOp->getOldDescription();
				}
			} else {
				continue;
			}
		}

		return [ $newTerms, $oldTerms ];
	}

	/**
	 * In order to check label and decsription uniqueness, this validator need to know both the label and the description
	 * in a language, where one or both of them are going to change.
	 *
	 * This method purpose is take those terms that are about to change ($newTerms) and make sure to yield pairs
	 * of label and description, filling in those missing labels or descriptions from either $oldTerms or from
	 * term store directly.
	 *
	 * @return Generator yielding entries of the shape
	 *  [ language code => [ 'label' => label text, 'descripition' => description text ] ]
	 */
	private function generateLabelDescriptionPairs( array $newTerms, array $oldTerms, EntityId $entityId ) {
		foreach ( $newTerms as $lang => $terms ) {
			$missingTerms = array_diff( [ 'label', 'description' ], array_keys( $terms ) );

			if ( count( $missingTerms ) > 1 ) {
				// This should never happen as long as newTerms contains entries per language that each has exactly one or two
				// entries with 'label' or 'description' as keys. Left here for completeness.
				continue;
			} elseif ( count( $missingTerms ) === 1 ) {
				$missingTerm = reset( $missingTerms );
				// Todo: we might want to batch looking up entity terms through TermLookup, which will change this implementation
				// enough to not be suitable for Generator use-case, as those need to be collected and batch fetched before
				// yielding them
				$terms[$missingTerm] = $oldTerms[$lang][$missingTerm] ?? $this->getEntityTerm( $entityId, $lang, $missingTerm );
			}

			yield $lang => $terms;
		}
	}

	private function getEntityTerm( EntityId $entityId, $lang, $termType ): string {
		if ( $termType === 'label' ) {
			return $this->termLookup->getLabel( $entityId, $lang ) ?? '';
		} elseif ( $termType === 'description' ) {
			return $this->termLookup->getDescription( $entityId, $lang ) ?? '';
		}

		throw new InvalidArgumentException( "\$termType can only be 'label' or 'property'. '{$termType}' was given" );
	}

	private function collisionToError( $code, $collidingEntityId, $lang, $label ) {
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
