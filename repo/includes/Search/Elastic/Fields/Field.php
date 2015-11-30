<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
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
