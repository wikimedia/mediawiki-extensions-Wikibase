<?php

namespace Wikibase\Lib\Store;

/**
 * TODO: Rename to TermLookupForBatch ?
 */
interface LabelDescriptionLookupForBatch {

	public function getLabels( array $ids, array $languageCodes );

	public function getDescriptions( array $ids, array $languageCodes );

}