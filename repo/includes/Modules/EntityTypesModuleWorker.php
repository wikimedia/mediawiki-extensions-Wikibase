<?php

namespace Wikibase\Repo\Modules;

use ResourceLoaderContext;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class EntityTypesModuleWorker implements MwConfigModuleWorker {

	/**
	 * @var EntityTypeDefinitions
	 */
	private $entityTypeDefinitions;

	public function __construct( EntityTypeDefinitions $entityTypeDefinitions ) {
		$this->entityTypeDefinitions = $entityTypeDefinitions;
	}

	/**
	 * @since 0.5
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return mixed
	 */
	public function getValue( ResourceLoaderContext $context ) {
		return [
			"types" => $this->entityTypeDefinitions->getEntityTypes(),
			"deserializer-factory-functions" => $this->entityTypeDefinitions->getJsDeserializerFactoryFunctions()
		];
	}

}
