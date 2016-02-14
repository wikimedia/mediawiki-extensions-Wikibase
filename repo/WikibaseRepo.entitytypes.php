<?php
/**
 * Definition of data types for use with Wikibase.
 * The array returned by the code below is supposed to be merged into $wgWBRepoEntityTypes.
 *
 * @note: Keep in sync with lib/WikibaseLib.entitytypes.php
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @see ValidatorsBuilders
 * @see WikibaseValueFormatterBuilders
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */

return array(
	'item' => array(
		'content-model' => CONTENT_MODEL_WIKIBASE_ITEM,
		'some-constraint/validation-factory-callback' => function() {

		}
	),
	'property' => array(
		'content-model' => CONTENT_MODEL_WIKIBASE_PROPERTY,
		'some-constraint/validation-factory-callback' => function() {

		}
	)
);
