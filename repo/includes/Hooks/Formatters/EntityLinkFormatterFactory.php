<?php

namespace Wikibase\Repo\Hooks\Formatters;

use Language;
use MediaWiki\Languages\LanguageFactory;
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
	 * @var EntityLinkFormatter[][] map of entity types and language codes to EntityLinkFormatter
	 */
	private $cachedLinkFormatters = [];

	/**
	 * @param EntityTitleTextLookup $entityTitleTextLookup
	 * @param callable[] $callbacks maps entity type strings to callbacks returning LinkFormatter
	 */
	public function __construct(
		EntityTitleTextLookup $entityTitleTextLookup,
		LanguageFactory $languageFactory,
		array $callbacks
	) {
		Assert::parameterElementType( 'callable', $callbacks, '$callbacks' );

		$this->callbacks = array_merge(
			$callbacks,
			[ 'default' => function ( Language $language ) use ( $entityTitleTextLookup, $languageFactory ): EntityLinkFormatter {
				return new DefaultEntityLinkFormatter( $language, $entityTitleTextLookup, $languageFactory );
			} ]
		);
	}

	public function getLinkFormatter( string $entityType, Language $language ): EntityLinkFormatter {
		if ( !isset( $this->callbacks[$entityType] ) ) {
			return $this->getDefaultLinkFormatter( $language );
		}

		return $this->getOrCreateLinkFormatter( $entityType, $language );
	}

	public function getDefaultLinkFormatter( Language $language ): EntityLinkFormatter {
		return $this->getOrCreateLinkFormatter( 'default', $language );
	}

	private function getOrCreateLinkFormatter( string $type, Language $language ): EntityLinkFormatter {
		return $this->cachedLinkFormatters[$type][$language->getCode()]
			?? $this->createAndCacheLinkFormatter( $type, $language );
	}

	private function createAndCacheLinkFormatter( string $type, Language $language ): EntityLinkFormatter {
		$langCode = $language->getCode();
		$this->cachedLinkFormatters[$type][$langCode] = $this->callbacks[$type]( $language );

		return $this->cachedLinkFormatters[$type][$langCode];
	}

}
