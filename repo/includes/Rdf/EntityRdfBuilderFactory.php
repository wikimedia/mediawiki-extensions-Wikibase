<?php

namespace Wikibase\Rdf;

/**
 * Interface for EntityRdfBuilderFactories
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
interface EntityRdfBuilderFactory {

	/**
	 * Return an array of Rdf builders for parts of the entity
	 *
	 * @param EntityDocument $entity Entity to do santiy check and don't run Rdf builder where it's not supposed to
	 * @param RdfWriter $writer
	 * @param RdfVocabulary $vocabulary
	 * @param int $flavorFlags
	 * @return EntityRdfBuilder[]
	 */
	public function getBuilders(
		EntityDocument $entity,
		RdfWriter $writer,
		RdfVocabulary $vocabulary,
		$flavorFlags
	);

}
