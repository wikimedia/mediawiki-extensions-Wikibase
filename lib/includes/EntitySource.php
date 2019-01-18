<?php

// TODO: move to DataAccess?
namespace Wikibase\Lib;

class EntitySource {

	private $sourceName;

	private $databaseName;

	private $entityTypes;
	private $entityNamespaceIds;

	// TODO: a single source can provide multiple entity types, each having a defined namespace

	public function __construct( $name, array $entityTypes, $databaseName, array $entityNamespaceIds ) {
		$this->sourceName = $name;
		$this->entityTypes = $entityTypes;
		$this->databaseName = $databaseName;
		$this->entityNamespaceIds = $entityNamespaceIds;
	}

	public function getDatabaseName() {
		return $this->databaseName;
	}

	public function getEntityTypes() {
		return $this->entityTypes;
	}

	public function getSourceName() {
		return $this->sourceName;
	}

}
