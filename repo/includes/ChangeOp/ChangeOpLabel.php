<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Summary;
use Wikibase\Validators\TermChangeValidationHelper;

/**
 * Class for label change operation
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpLabel extends ChangeOpBase {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * @since 0.4
	 *
	 * @var string|null
	 */
	protected $label;

	/**
	 * @since 0.5
	 *
	 * @var TermChangeValidationHelper
	 */
	protected $validationHelper;

	/**
	 * @since 0.4
	 *
	 * @param string $language
	 * @param string|null $label
	 *
	 * @param TermChangeValidationHelper $validationHelper
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $language, $label, TermChangeValidationHelper $validationHelper ) {
		if ( !is_string( $language ) ) {
			throw new InvalidArgumentException( '$language needs to be a string' );
		}

		$this->language = $language;
		$this->label = $label;
		$this->validationHelper = $validationHelper;
	}

	/**
	 * @see ChangeOp::apply()
	 *
	 * @param Entity $entity
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 * @return bool
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$this->validateChange( $entity );

		if ( $this->label === null ) {
			$this->updateSummary( $summary, 'remove', $this->language, $entity->getLabel( $this->language ) );
			$entity->removeLabel( $this->language );
		} else {
			$entity->getLabel( $this->language ) === false ? $action = 'add' : $action = 'set';
			$this->updateSummary( $summary, $action, $this->language, $this->label );
			$entity->setLabel( $this->language, $this->label );
		}
		return true;
	}

	/**
	 * @param Entity $entity
	 *
	 * @throws ChangeOpException
	 */
	protected function validateChange( Entity $entity ) {
		// check that the language is valid
		$this->validationHelper->validateLanguage( $this->language );

		if ( $this->label !== null ) {
			// Check that the new label is valid
			$this->validationHelper->validateLabel( $this->language, $this->label );
		}

		if ( $entity->getId() !== null && $this->label !== null ) {
			// Check that the new combination of label and description is not used for another
			// entity (of the same entity type).
			//XXX: allow the EntityId to be null? We need it at least for the entity type...
			$this->validationHelper->validateUniqueness(
				$entity->getId(),
				$this->language,
				$this->label,
				$entity->getDescription( $this->language )
			);
		}
	}
}
