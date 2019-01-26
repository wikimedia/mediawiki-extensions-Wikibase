<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

// phpcs:disable Wikibase.Namespaces.FullQualifiedClassName.Found
/**
 * Each field is intended to be used by CirrusSearch as an
 * additional property of a page.
 *
 * The data returned by the field must match the field
 * type defined in the mapping. (e.g. nested must be array,
 * integer field must get an int, etc)
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @deprecated Use Wikibase\Repo\Search\Fields\WikibaseIndexField
 */
interface WikibaseIndexField extends \Wikibase\Repo\Search\Fields\WikibaseIndexField {
}
