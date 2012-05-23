<?php

/**
 * Aliases for the special pages of the Wikibase extension.
 *
 * @since 0.1
 *
 * @file Wikibase.alias.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

$specialPageAliases = array();

/** English (English) */
$specialPageAliases['en'] = array(
	'CreateItem' => array( 'CreateItem' ),
	'ItemByTitle' => array( 'ItemByTitle' ),
	'ItemByLabel' => array( 'ItemByLabel' ),
);

/** Macedonian (Македонски) */
$specialPageAliases['mk'] = array(
	'CreateItem' => array( 'СоздајСтавка' ),
	'ItemByTitle' => array( 'СтавкаПоНаслов' ),
	'ItemByLabel' => array( 'СтавкаПоНатпис' ),
);

/** Dutch (Nederlands) */
$specialPageAliases['nl'] = array(
	'CreateItem' => array( 'ItemAanmaken' ),
	'ItemByTitle' => array( 'ItemPerTitel' ),
	'ItemByLabel' => array( 'ItemPerLabel' ),
);