<?php

namespace Wikibase\Repo\Specials;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Special page for setting the aliases of a Wikibase entity.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetAliases extends SpecialModifyTerm {

	public function __construct(
		SpecialPageCopyrightView $copyrightView,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		EditEntityFactory $editEntityFactory,
		EntityPermissionChecker $entityPermissionChecker
	) {
		parent::__construct(
			'SetAliases',
			$copyrightView,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory,
			$entityPermissionChecker
		);
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

		return $this->getBaseRevision()->getEntity() instanceof AliasesProvider;
	}

	/**
	 * @see SpecialModifyTerm::getPostedValue()
	 *
	 * @return string|null
	 */
	protected function getPostedValue() {
		return $this->getRequest()->getVal( 'aliases' );
	}

	/**
	 * @see SpecialModifyTerm::getValue()
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
	 * @see SpecialModifyTerm::setValue()
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
