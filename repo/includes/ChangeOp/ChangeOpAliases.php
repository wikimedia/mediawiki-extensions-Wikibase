<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Summary;
use ValueValidators\ValueValidator;

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
	 * @since 0.5
	 *
	 * @param string $language
	 * @param string[] $aliases
	 * @param string $action should be set|add|remove
	 *
	 * @param ValueValidator $termValidator
	 * @param ValueValidator $languageValidator
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$language,
		array $aliases,
		$action,
		ValueValidator $termValidator,
		ValueValidator $languageValidator
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

		$this->termValidator = $termValidator;
		$this->languageValidator = $languageValidator;
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$this->validateChange( $entity );

		if ( $this->action === "" || $this->action === "set" ) {
			$this->updateSummary( $summary, 'set', $this->language, $this->aliases );
			$entity->setAliases( $this->language, $this->aliases );
		} elseif ( $this->action === "add" ) {
			$this->updateSummary( $summary, 'add', $this->language, $this->aliases );
			$entity->addAliases( $this->language, $this->aliases );
		} elseif ( $this->action === "remove" ) {
			$this->updateSummary( $summary, 'remove', $this->language, $this->aliases );
			$entity->removeAliases( $this->language, $this->aliases );
		} else {
			throw new ChangeOpException( "Unknown action for change op: $this->action" );
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
		$this->languageValidator->validate( $this->language );

		if ( $this->action === 'set' || $this->action === 'add' ) {
			// Check that the new aliases are valid
			foreach ( $this->aliases as $alias ) {
				$this->termValidator->validate( $alias );
			}
		}
	}
}
