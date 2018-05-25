<?php

namespace Wikibase\Repo\Hooks\Formatters;

use Language;
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
	 * @var EntityLinkFormatter[] map of entity types to EntityLinkFormatter
	 */
	private $cachedLinkFormatters = [];

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

		$this->language = $language;
		$this->callbacks = array_merge( $callbacks, [ 'default' => function ( $language ) {
			return new DefaultEntityLinkFormatter( $language );
		} ] );
	}

	/**
	 * @param $type string entity type
	 * @return mixed
	 */
	public function getLinkFormatter( $type ) {
		Assert::parameterType( 'string', $type, '$type' );

		if ( !isset( $this->callbacks[$type] ) ) {
			return $this->getDefaultLinkFormatter();
		}

		return $this->getOrCreateLinkFormatter( $type );
	}

	private function getDefaultLinkFormatter() {
		return $this->getOrCreateLinkFormatter( 'default' );
	}

	private function getOrCreateLinkFormatter( $type ) {
		if ( isset( $this->cachedLinkFormatters[$type] ) ) {
			return $this->cachedLinkFormatters[$type];
		}

		return $this->createAndCacheLinkFormatter( $type );
	}

	private function createAndCacheLinkFormatter( $type ) {
		if ( !isset( $this->cachedLinkFormatters[$type] ) ) {
			$this->cachedLinkFormatters[$type] = [];
		}

		$this->cachedLinkFormatters[$type] = $this->callbacks[$type]( $this->language );

		return $this->cachedLinkFormatters[$type];
	}

}
