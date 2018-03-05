<?php

namespace Wikibase\Lib\Interactors;

use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * Picks a TermSearchInteractor configured for the particular entity type when searching for entities.
 *
 * @license GPL-2.0-or-later
 *
 * TODO: rename to DispatchingByEntityTypeTermSearchInteractor ?
 */
class DispatchingTermSearchInteractor implements ConfigurableTermSearchInteractor {

	/**
	 * @var TermSearchInteractor[]
	 */
	private $interactors = [];

	/**
	 * @var TermSearchOptions
	 */
	private $searchOptions;

	/**
	 * @param TermSearchInteractor[] $interactors Associative array mapping entity types (strings) to TermSearchInteractor instances
	 *
	 * @throws ParameterAssertionException
	 */
	public function __construct( array $interactors ) {
		Assert::parameterElementType( TermSearchInteractor::class, $interactors, '$interactors' );
		Assert::parameterElementType( 'string', array_keys( $interactors ), 'array_keys( $interactors )' );

		$this->interactors = $interactors;
		$this->searchOptions = new TermSearchOptions();
	}

	/**
	 * @see TermSearchInteractor::searchForEntities
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param string[] $termTypes
	 *
	 * @return TermSearchResult[] Returns an empty array also when there is no TermSearchInteractor configured for $entityType
	 */
	public function searchForEntities( $text, $languageCode, $entityType, array $termTypes ) {
		$interactor = $this->getInteractorForEntityType( $entityType );

		if ( $interactor === null ) {
			return [];
		}

		if ( $interactor instanceof ConfigurableTermSearchInteractor ) {
			$interactor->setTermSearchOptions( $this->searchOptions );
		}

		return $interactor->searchForEntities( $text, $languageCode, $entityType, $termTypes );
	}

	/**
	 * @param string $entityType
	 *
	 * @return TermSearchInteractor|null
	 */
	private function getInteractorForEntityType( $entityType ) {
		return isset( $this->interactors[$entityType] ) ? $this->interactors[$entityType] : null;
	}

	public function setTermSearchOptions( TermSearchOptions $termSearchOptions ) {
		$this->searchOptions = $termSearchOptions;
	}

}
