<?php

namespace Wikibase\Repo\Specials;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\Summary;

/**
 * Special page for setting the aliases of a Wikibase entity.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetAliases extends SpecialModifyTerm {

	/**
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'SetAliases' );
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
	 * @param Entity $entity
	 * @param string $languageCode
	 *
	 * @return string
	 */
	protected function getValue( $entity, $languageCode ) {
		return $entity === null ? '' : implode( '|', $entity->getAliases( $languageCode ) );
	}

	/**
	 * @see SpecialSetEntity::setValue()
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @return Summary
	 */
	protected function setValue( $entity, $languageCode, $value ) {
		$summary = $this->getSummary( 'wbsetaliases' );
		if ( $value === '' ) {
			$changeOp = $this->termChangeOpFactory->newRemoveAliasesOp( $languageCode, $entity->getAliases( $languageCode ) );
		} else {
			$changeOp = $this->termChangeOpFactory->newSetAliasesOp( $languageCode, explode( '|', $value ) );
		}

		$this->applyChangeOp( $changeOp, $entity, $summary );

		return $summary;
	}

}
