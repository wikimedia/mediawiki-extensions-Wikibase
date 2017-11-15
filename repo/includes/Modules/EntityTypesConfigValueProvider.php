<?php

namespace Wikibase\Repo\Modules;

use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Kreuz
 * @author Jonas Kress
 */
class EntityTypesConfigValueProvider implements MediaWikiConfigValueProvider {

	/**
	 * @var EntityTypeDefinitions
	 */
	private $entityTypeDefinitions;

	public function __construct( EntityTypeDefinitions $entityTypeDefinitions ) {
		$this->entityTypeDefinitions = $entityTypeDefinitions;
	}

	/**
	 * @see MediaWikiJsConfigProvider::getKey
	 *
	 * @return string
	 */
	public function getKey() {
		return 'wbEntityTypes';
	}

	/**
	 * @see MediaWikiJsConfigProvider::getValue
	 *
	 * @return array[]
	 */
	public function getValue() {
		return [
			'types' => $this->entityTypeDefinitions->getEntityTypes(),
			'deserializer-factory-functions'
				=> $this->entityTypeDefinitions->getJsDeserializerFactoryFunctions()
		];
	}

}
