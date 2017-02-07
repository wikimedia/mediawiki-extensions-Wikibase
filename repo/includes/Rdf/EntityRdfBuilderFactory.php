<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikimedia\Purtle\RdfWriter;

/**
 * Interface for EntityRdfBuilderFactories
 * This helps RdfBuilder to know the proper builders based on the entities.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
interface EntityRdfBuilderFactory {

	/**
	 * Return an array of Rdf builders for parts of the entity
	 *
	 * @param EntityDocument $entity Entity to do santiy check and don't return Rdf builders where it's not supposed to
	 * @param RdfWriter $writer
	 * @param RdfVocabulary $vocabulary
	 * @param int $flavorFlags Flavor flags to use for builders
	 * @return EntityRdfBuilder[]
	 */
	public function getBuilders(
		EntityDocument $entity,
		RdfWriter $writer,
		RdfVocabulary $vocabulary,
		$flavorFlags
	);

}
