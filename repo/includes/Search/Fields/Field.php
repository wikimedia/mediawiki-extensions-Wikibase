<?php

namespace Wikibase\Repo\Search\Fields;

use Wikibase\DataModel\Entity\EntityDocument;

interface Field {

	/**
	 * @return array
	 */
	public function getMapping();

	/**
	 * @param EntityDocument $entity
	 *
	 * @return mixed Either an array with nested data, or
	 *               an int or string for simple field types.
	 */
	public function buildData( EntityDocument $entity );

}
