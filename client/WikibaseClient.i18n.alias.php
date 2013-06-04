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

/** Finnish (suomi) */
$specialPageAliases['fi'] = array(
	'UnconnectedPages' => array( 'Yhdistämättömät_sivut' ),
);

/** Japanese (日本語) */
$specialPageAliases['ja'] = array(
	'UnconnectedPages' => array( '関連付けられていないページ', '関連付けのないページ' ),
);

/** Korean (한국어) */
$specialPageAliases['ko'] = array(
	'UnconnectedPages' => array( '연결안된문서' ),
);

/** Macedonian (македонски) */
$specialPageAliases['mk'] = array(
	'UnconnectedPages' => array( 'НесврзаниСтраници' ),
);

/** Dutch (Nederlands) */
$specialPageAliases['nl'] = array(
	'UnconnectedPages' => array( 'OngekoppeldePaginas', 'OngekoppeldePagina\'s' ),
);

/** Turkish (Türkçe) */
$specialPageAliases['tr'] = array(
	'UnconnectedPages' => array( 'BağlanmamışSayfalar', 'Bağlantısız', 'SiteBağlantısız' ),
);

/** Vietnamese (Tiếng Việt) */
$specialPageAliases['vi'] = array(
	'UnconnectedPages' => array( 'Trang_không_kết_nối', 'Trang_không_có_liên_kết_site' ),
);