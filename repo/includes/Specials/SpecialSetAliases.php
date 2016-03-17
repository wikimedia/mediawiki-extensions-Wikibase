<?php

namespace Wikibase\Repo\Specials;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\Summary;

/**
 * Special page for setting the aliases of a Wikibase entity.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetAliases extends SpecialModifyTerm {

	/**
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'SetAliases' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialModifyTerm::validateInput
	 *
	 * @return bool
	 */
	protected function validateInput() {
		if ( !parent::validateInput() ) {
			return false;
		}

		return $this->entityRevision->getEntity() instanceof AliasesProvider;
	}

	/**
	 * @see SpecialSetEntity::getPostedValue()
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getPostedValue() {
		return $this->getRequest()->getVal( 'aliases' );
	}

	/**
	 * @see SpecialSetEntity::getValue()
	 *
	 * @since 0.4
	 *
	 * @param EntityDocument $entity
	 * @param string $languageCode
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	protected function getValue( EntityDocument $entity, $languageCode ) {
		if ( !( $entity instanceof AliasesProvider ) ) {
			throw new InvalidArgumentException( '$entity must be an AliasesProvider' );
		}

		$aliases = $entity->getAliasGroups();

		if ( $aliases->hasGroupForLanguage( $languageCode ) ) {
			return implode( '|', $aliases->getByLanguage( $languageCode )->getAliases() );
		}

		return '';
	}

	/**
	 * @see SpecialSetEntity::setValue()
	 *
	 * @since 0.4
	 *
	 * @param EntityDocument $entity
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @throws InvalidArgumentException
	 * @return Summary
	 */
	protected function setValue( EntityDocument $entity, $languageCode, $value ) {
		if ( !( $entity instanceof AliasesProvider ) ) {
			throw new InvalidArgumentException( '$entity must be an AliasesProvider' );
		}

		$summary = new Summary( 'wbsetaliases' );

		if ( $value === '' ) {
			$aliases = $entity->getAliasGroups()->getByLanguage( $languageCode )->getAliases();
			$changeOp = $this->termChangeOpFactory->newRemoveAliasesOp( $languageCode, $aliases );
		} else {
			$changeOp = $this->termChangeOpFactory->newSetAliasesOp( $languageCode, explode( '|', $value ) );
		}

		$this->applyChangeOp( $changeOp, $entity, $summary );

		return $summary;
	}

}
