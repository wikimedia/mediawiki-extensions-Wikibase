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
	 * @return EntityRdfBuilder[]
	 */
	public function getBuilders();

}
