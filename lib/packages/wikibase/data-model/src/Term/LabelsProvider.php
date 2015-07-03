<?php

namespace Wikibase\DataModel\Term;

/**
 * @since 4.0
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface LabelsProvider {

	/**
	 * @return TermList
	 */
	public function getLabels();

}
