<?php

namespace Wikibase\Repo\Hooks\Formatters;

use Language;
use OutOfBoundsException;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class EntityLinkFormatterFactory {

	/**
	 * @var callable[] map of entity type strings to callbacks
	 */
	private $callbacks;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @param Language $language
	 * @param callable[] $callbacks maps entity type strings to callbacks returning LinkFormatter
	 */
	public function __construct( Language $language, array $callbacks ) {
		Assert::parameterElementType( 'callable', $callbacks, '$callbacks' );
		$this->callbacks = $callbacks;
		$this->language = $language;
	}

	/**
	 * @param $type string entity type
	 * @return mixed
	 * @throws OutOfBoundsException
	 */
	public function getLinkFormatter( $type ) {
		Assert::parameterType( 'string', $type, '$type' );

		if ( !isset( $this->callbacks[$type] ) ) {
			throw new OutOfBoundsException( "No link formatter defined for entity type $type" );
		}

		return $this->callbacks[$type]( $this->language );
	}

}
