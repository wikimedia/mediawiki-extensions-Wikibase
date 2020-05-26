<?php

namespace Wikibase\Repo\Hooks\Formatters;

use Language;
use Wikibase\Lib\Store\EntityTitleTextLookup;
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
	public function __construct( Language $language, EntityTitleTextLookup $entityTitleTextLookup, array $callbacks ) {
		Assert::parameterElementType( 'callable', $callbacks, '$callbacks' );

		$this->language = $language;
		$this->callbacks = array_merge( $callbacks, [ 'default' => function ( $language ) use ( $entityTitleTextLookup ) {
			return new DefaultEntityLinkFormatter( $language, $entityTitleTextLookup );
		} ] );
	}

	/**
	 * @param string $type entity type
	 * @return EntityLinkFormatter
	 */
	public function getLinkFormatter( $type ): EntityLinkFormatter {
		Assert::parameterType( 'string', $type, '$type' );

		if ( !isset( $this->callbacks[$type] ) ) {
			return $this->getDefaultLinkFormatter();
		}

		return $this->getOrCreateLinkFormatter( $type );
	}

	/**
	 * @return EntityLinkFormatter
	 */
	public function getDefaultLinkFormatter() {
		return $this->getOrCreateLinkFormatter( 'default' );
	}

	/**
	 * @param string $type
	 * @return EntityLinkFormatter
	 */
	private function getOrCreateLinkFormatter( $type ) {
		return $this->cachedLinkFormatters[$type] ?? $this->createAndCacheLinkFormatter( $type );
	}

	/**
	 * @param string $type
	 * @return EntityLinkFormatter
	 */
	private function createAndCacheLinkFormatter( $type ) {
		$this->cachedLinkFormatters[$type] = $this->callbacks[$type]( $this->language );

		return $this->cachedLinkFormatters[$type];
	}

}
