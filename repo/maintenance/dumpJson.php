<?php

namespace Wikibase;

use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Dumpers\DumpGenerator;
use Wikibase\Dumpers\JsonDumpGenerator;

require_once __DIR__ . '/dumpEntities.php';

class DumpJson extends DumpScript {

	public function __construct() {
		parent::__construct();

		$this->addOption(
			'snippet',
			'Output a JSON snippet without square brackets at the start and end. Allows output to'
				. ' be combined more freely.',
			false,
			false
		);
	}

	/**
	 * Create concrete dumper instance
	 * @param resource $output
	 * @return DumpGenerator
	 */
	protected function createDumper( $output ) {
		$serializerOptions = SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_OBJECTS_FOR_MAPS;
		$serializerFactory = new SerializerFactory( new DataValueSerializer(), $serializerOptions );

		$entitySerializer = $serializerFactory->newEntitySerializer();
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
