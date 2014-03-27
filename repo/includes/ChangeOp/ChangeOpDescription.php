<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Summary;
use ValueValidators\ValueValidator;

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
	 * @var ValueValidator
	 */
	protected $languageValidator;

	/**
	 * @since 0.5
	 *
	 * @var ValueValidator
	 */
	protected $termValidator;

	/**
	 * @since 0.4
	 *
	 * @param string $language
	 * @param string|null $description
	 *
	 * @param ValueValidator $termValidator
	 * @param ValueValidator $languageValidator
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$language,
		$description,
		ValueValidator $termValidator,
		ValueValidator $languageValidator
	) {
		if ( !is_string( $language ) ) {
			throw new InvalidArgumentException( '$language needs to be a string' );
		}

		$this->language = $language;
		$this->description = $description;

		$this->termValidator = $termValidator;
		$this->languageValidator = $languageValidator;
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
		if ( $this->description !== null ) {
			// check that the language is valid (note that it is fine to remove bad languages)
			$this->applyValidator( $this->languageValidator, $this->language );

			// Check that the new label is valid
			$this->applyValidator( $this->termValidator, $this->description );
		}
	}
}
