<?php

namespace Wikibase\Lib\Interactors;

use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * Class creating DispatchingTermIndexSearchInteractor instances configured for the particular display language.
 *
 * @license GPL-2.0+
 */
class DispatchingTermSearchInteractorFactory implements TermSearchInteractorFactory {

	/**
	 * @var TermIndexSearchInteractorFactory[]
	 */
	private $interactorFactories = [];

	/**
	 * @param TermIndexSearchInteractorFactory[] $interactorFactories Associative array mapping entity types (strings)
	 *        to TermIndexSearchInteractorFactory instances providing the interactor for the given entity type.
	 *
	 * @throws ParameterAssertionException
	 */
	public function __construct( array $interactorFactories ) {
		Assert::parameterElementType( TermSearchInteractorFactory::class, $interactorFactories, '$interactorFactories' );
		Assert::parameterElementType( 'string', array_keys( $interactorFactories ), 'array_keys( $interactorFactories )' );
		$this->interactorFactories = $interactorFactories;
	}

	/**
	 * @param string $displayLanguageCode
	 * @return DispatchingTermSearchInteractor
	 */
	public function getInteractor( $displayLanguageCode ) {
		return new DispatchingTermSearchInteractor(
			array_map(
				function( TermSearchInteractorFactory $factory ) use ( $displayLanguageCode ) {
					return $factory->getInteractor( $displayLanguageCode );
				},
				$this->interactorFactories
			)
		);
	}

}
