<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Summary;
use Wikibase\Validators\TermChangeValidationHelper;

/**
 * Class for description change operation
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpDescription extends ChangeOpBase {

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
	protected $description;

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
	 * @param string|null $description
	 *
	 * @param TermChangeValidationHelper $validationHelper
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $language, $description, TermChangeValidationHelper $validationHelper ) {
		if ( !is_string( $language ) ) {
			throw new InvalidArgumentException( '$language needs to be a string' );
		}

		$this->language = $language;
		$this->description = $description;
		$this->validationHelper = $validationHelper;
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$this->validateChange( $entity );

		if ( $this->description === null ) {
			$this->updateSummary( $summary, 'remove', $this->language, $entity->getDescription( $this->language ) );
			$entity->removeDescription( $this->language );
		} else {
			$entity->getDescription( $this->language ) === false ? $action = 'add' : $action = 'set';
			$this->updateSummary( $summary, $action, $this->language, $this->description );
			$entity->setDescription( $this->language, $this->description );
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

		if ( $this->description !== null ) {
			// Check that the new label is valid
			$this->validationHelper->validateDescription( $this->language, $this->description );
		}

		if ( $entity->getId() !== null && $this->description !== null ) {
			// Check that the new combination of label and description is not used for another
			// entity (of the same entity type).
			//XXX: allow the EntityId to be null? We need it at least for the entity type...
			$this->validationHelper->validateUniqueness(
				$entity->getId(),
				$this->language,
				$entity->getLabel( $this->language ),
				$this->description
			);
		}
	}
}
