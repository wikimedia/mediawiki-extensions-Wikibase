<?php

namespace Wikibase\Api;

use OutOfBoundsException;
use Wikimedia\Assert\Assert;

/**
 * Factory for entity type specific handlers for the wbeditentity api module
 * which is based on callbacks from a definitions array.
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EditEntityHandlerFactory {

	/**
	 * @var callable
	 */
	private $factoryCallbacks;

	/**
	 * @param callable[] $factoryCallbacks
	 */
	public function __construct( array $factoryCallbacks ) {
		Assert::parameterElementType( 'callable', $factoryCallbacks, '$factoryCallbacks' );

		$this->factoryCallbacks = $factoryCallbacks;
	}

	/**
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException
	 * @return EditEntityHandler
	 */
	public function newEditEntityHandler( $entityType ) {
		if ( !isset( $this->factoryCallbacks[$entityType] ) ) {
			throw new OutOfBoundsException( "No EditEntityHandler is registered for entity type '$entityType'" );
		}

		$editEntityHandler = call_user_func( $this->factoryCallbacks[$entityType] );

		Assert::postcondition(
			$editEntityHandler instanceof EditEntityHandler,
			'Callback must return an instance of EditEntityHandler'
		);

		return $editEntityHandler;
	}

}
