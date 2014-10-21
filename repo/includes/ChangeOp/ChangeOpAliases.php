<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
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
	private $languageCode;

	/**
	 * @since 0.4
	 *
	 * @var string[]
	 */
	private $aliases;

	/**
	 * @since 0.4
	 *
	 * @var array
	 */
	private $action;

	/**
	 * @since 0.5
	 *
	 * @var TermValidatorFactory
	 */
	private $termValidatorFactory;

	/**
	 * @since 0.5
	 *
	 * @param string $languageCode
	 * @param string[] $aliases
	 * @param string $action should be set|add|remove
	 * @param TermValidatorFactory $termValidatorFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$languageCode,
		array $aliases,
		$action,
		TermValidatorFactory $termValidatorFactory
	) {
		if ( !is_string( $languageCode ) ) {
			throw new InvalidArgumentException( 'Language code needs to be a string.' );
		}

		if ( !is_string( $action ) ) {
			throw new InvalidArgumentException( 'Action needs to be a string.' );
		}

		$this->languageCode = $languageCode;
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
		if ( $fingerprint->getAliasGroups()->hasGroupForLanguage( $this->languageCode ) ) {
			$oldAliases = $fingerprint->getAliasGroup( $this->languageCode )->getAliases();
		} else {
			$oldAliases = array();
		}

		if ( $this->action === 'set' || $this->action === '' ) {
			$newAliases = $this->aliases;
		} elseif ( $this->action === 'add' ) {
			$newAliases = array_merge( $oldAliases, $this->aliases );
		} elseif ( $this->action === 'remove' ) {
			$newAliases = array_diff( $oldAliases, $this->aliases );
		} else {
			throw new ChangeOpException( 'Bad action: ' . $this->action );
		}

		$fingerprint->getAliasGroups()->setAliasesForLanguage( $this->languageCode, $newAliases );
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$fingerprint = $entity->getFingerprint();

		$this->updateSummary( $summary, $this->action, $this->languageCode, $this->aliases );

		$this->updateFingerprint( $fingerprint );
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
		$result = $languageValidator->validate( $this->languageCode );

		if ( !$result->isValid() ) {
			return $result;
		}

		// It should be possible to remove invalid aliases, but not to add/set new invalid ones
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
		return $result;
	}

}
