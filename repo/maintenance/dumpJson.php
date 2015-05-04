<?php

namespace Wikibase;

use Wikibase\Dumpers\DumpGenerator;
use Wikibase\Dumpers\JsonDumpGenerator;
use Wikibase\Lib\Serializers\DispatchingEntitySerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;

require_once __DIR__ . '/dumpEntities.php';

class DumpJson extends DumpScript {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'snippet', "Output a JSON snippet without square brackets at the start and end. Allows output to be combined more freely.", false, false );
	}

	/**
	 * Create concrete dumper instance
	 * @param resource $output
	 * @return DumpGenerator
	 */
	protected function createDumper( $output ) {
		$entityFactory = $this->wikibaseRepo->getEntityFactory();
		$serializerOptions = new SerializationOptions();

		$serializerFactory = new SerializerFactory(
			$serializerOptions,
			$this->wikibaseRepo->getPropertyDataTypeLookup(),
			$entityFactory
		);

		$entitySerializer = new DispatchingEntitySerializer( $serializerFactory, $serializerOptions );
		$entityPrefetcher = $this->wikibaseRepo->getStore()->getEntityPrefetcher();

		$dumper = new JsonDumpGenerator(
			$output,
			$this->entityLookup,
			$entitySerializer,
			$entityPrefetcher
		);

		$dumper->setUseSnippets( (bool)$this->getOption( 'snippet', false ) );
		return $dumper;
	}
}

$maintClass = 'Wikibase\DumpJson';
require_once RUN_MAINTENANCE_IF_MAIN;
