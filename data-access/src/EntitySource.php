<?php

namespace Wikibase\DataAccess;

/**
 * @license GPL-2.0-or-later
 */
class EntitySource {

	private $sourceName;

	private $databaseName;

	private $entityTypes;
	private $entityNamespaceIds;

	public function __construct( $name, array $entityTypes, $databaseName, array $entityNamespaceIds ) {
		$this->sourceName = $name;
		$this->entityTypes = $entityTypes;
		$this->databaseName = $databaseName;
		$this->entityNamespaceIds = $entityNamespaceIds;
		// TODO: also slots would be needed most likely
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
