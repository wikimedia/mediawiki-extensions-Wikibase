<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Summary;
use Wikibase\Validators\TermValidatorFactory;

/**
 * Class for aliases change operation
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpAliases extends ChangeOpBase {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * @since 0.4
	 *
	 * @var string[]
	 */
	protected $aliases;

	/**
	 * @since 0.4
	 *
	 * @var array
	 */
	protected $action;

	/**
	 * @since 0.5
	 *
	 * @var TermValidatorFactory
	 */
	protected $termValidatorFactory;

	/**
	 * @since 0.5
	 *
	 * @param string $language
	 * @param string[] $aliases
	 * @param string $action should be set|add|remove
	 *
	 * @param TermValidatorFactory $termValidatorFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$language,
		array $aliases,
		$action,
		TermValidatorFactory $termValidatorFactory
	) {
		if ( !is_string( $language ) ) {
			throw new InvalidArgumentException( '$language needs to be a string' );
		}

		if ( !is_string( $action ) ) {
			throw new InvalidArgumentException( '$action needs to be a string' );
		}

		$this->language = $language;
		$this->aliases = $aliases;
		$this->action = $action;

		$this->termValidatorFactory = $termValidatorFactory;
	}

	/**
	 * Applies the change to the fingerprint
	 *
	 * @param Fingerprint $fingerprint
	 *
	 * @throws ChangeOpException
	 */
	private function updateFingerprint( Fingerprint $fingerprint ) {
		try {
			$current = $fingerprint->getAliasGroup( $this->language )->getAliases();
		} catch ( \OutOfBoundsException $ex ) {
			$current = array();
		}

		if ( $this->action === "remove" ) {
			$updated = array_diff( $current, $this->aliases );
		} elseif ( $this->action === "add" ) {
			$updated = array_merge( $current, $this->aliases );
		} elseif ( $this->action === "set" || $this->action === "" ) {
			$updated = $this->aliases;
		} else {
			throw new ChangeOpException( 'Bad action: ' . $this->action );
		}

		$fingerprint->getAliasGroups()->setAliasesForLanguage( $this->language, $updated );
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$fingerprint = $entity->getFingerprint();

		$this->updateFingerprint( $fingerprint );
		$this->updateSummary( $summary, $this->action, $this->language, $this->aliases );

		$entity->setFingerprint( $fingerprint );
		return true;
	}

	/**
	 * Validates this ChangeOp
	 *
	 * @see ChangeOp::validate()
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 *
	 * @throws ChangeOpException
	 * @return Result
	 */
	public function validate( Entity $entity ) {
		$languageValidator = $this->termValidatorFactory->getLanguageValidator();
		$termValidator = $this->termValidatorFactory->getLabelValidator( $entity->getType() );

		// check that the language is valid
		$result = $languageValidator->validate( $this->language );

		if ( !$result->isValid() ) {
			return $result;
		}

		if ( $this->action === 'set' || $this->action === '' || $this->action === 'add' ) {
			// Check that the new aliases are valid
			foreach ( $this->aliases as $alias ) {
				$result = $termValidator->validate( $alias );

				if ( !$result->isValid() ) {
					return $result;
				}
			}
		} elseif ( $this->action !== 'remove' )  {
			throw new ChangeOpException( 'Bad action: ' . $this->action );
		}

		//XXX: Do we want to check the updated fingerprint, as we do for labels and descriptions?
		return Result::newSuccess();
	}
}
