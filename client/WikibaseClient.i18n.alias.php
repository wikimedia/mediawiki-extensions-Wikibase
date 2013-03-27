<?php

/**
 * Aliases for the special pages of the Wikibase extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */

$specialPageAliases = array();

/** English (English) */
$specialPageAliases['en'] = array(
	'UnconnectedPages' => array( 'UnconnectedPages', 'WithoutConnection', 'WithoutSitelinks' ),
);

/** German (Deutsch) */
$specialPageAliases['de'] = array(
	'UnconnectedPages' => array( 'Nicht_verbundene_Seiten' ),
);

/** Vietnamese (Tiếng Việt) */
$specialPageAliases['vi'] = array(
	'UnconnectedPages' => array( 'Trang_không_kết_nối', 'Trang_không_có_liên_kết_site' ),
);