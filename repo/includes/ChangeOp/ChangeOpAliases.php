<?php

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * Class for aliases change operation
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpAliases extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var string[]
	 */
	private $aliases;

	/**
	 * @var string
	 */
	private $action;

	/**
	 * @var TermValidatorFactory
	 */
	private $termValidatorFactory;

	/**
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
	 * Applies the change to the aliases
	 *
	 * @param AliasGroupList $aliases
	 *
	 * @throws ChangeOpException
	 */
	private function updateAliases( AliasGroupList $aliases ) {
		if ( $aliases->hasGroupForLanguage( $this->languageCode ) ) {
			$oldAliases = $aliases->getByLanguage( $this->languageCode )->getAliases();
		} else {
			$oldAliases = [];
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

		$aliases->setAliasesForLanguage( $this->languageCode, $newAliases );
	}

	/**
	 * @see ChangeOp::apply()
	 *
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 * @throws ChangeOpException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		if ( !( $entity instanceof AliasesProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a AliasesProvider' );
		}

		$aliases = $entity->getAliasGroups();
		if ( $aliases->hasGroupForLanguage( $this->languageCode ) ) {
			$oldAliases = $aliases->getByLanguage( $this->languageCode )->getAliases();
		} else {
			$oldAliases = [];
		}

		$this->updateSummary( $summary, $this->action, $this->languageCode, $this->aliases );

		$this->updateAliases( $entity->getAliasGroups() );

		if ( $aliases->hasGroupForLanguage( $this->languageCode ) ) {
			$newAliases = $aliases->getByLanguage( $this->languageCode )->getAliases();
		} else {
			$newAliases = [];
		}

		return new ChangeOpAliasesResult(
			$entity->getId(),
			$this->languageCode,
			$oldAliases,
			$newAliases,
			$oldAliases !== $newAliases
		);
	}

	/**
	 * @see ChangeOp::validate
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws ChangeOpException
	 * @return Result
	 */
	public function validate( EntityDocument $entity ) {
		$languageValidator = $this->termValidatorFactory->getAliasLanguageValidator();
		$termValidator = $this->termValidatorFactory->getAliasValidator();

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
		} elseif ( $this->action !== 'remove' ) {
			throw new ChangeOpException( 'Bad action: ' . $this->action );
		}

		//XXX: Do we want to check the updated fingerprint, as we do for labels and descriptions?
		return $result;
	}

	/**
	 * @see ChangeOp::getActions
	 *
	 * @return string[]
	 */
	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT_TERMS ];
	}

}
