<?php

/**
 * Aliases for the special pages of the Wikibase Client extension.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 */
// @codingStandardsIgnoreFile

$specialPageAliases = [];

/** English (English) */
$specialPageAliases['en'] = array(
	'PagesWithBadges' => array( 'PagesWithBadges', 'QueryBadges' ),
	'UnconnectedPages' => array( 'UnconnectedPages', 'WithoutConnection', 'WithoutSitelinks' ),
);
/** Arabic (العربية) */
$specialPageAliases['ar'] = array(
	'UnconnectedPages' => array( 'صفحات_غير_متصلة', 'بدون_اتصال', 'بدون_وصلات_موقع' ),
);

/** Egyptian Arabic (مصرى) */
$specialPageAliases['arz'] = array(
	'UnconnectedPages' => array( 'صفحات_مش_متوصله', 'من_غير_وصله', 'من_غير_وصلات_موقع' ),
);

/** Western Balochi (بلوچی رخشانی) */
$specialPageAliases['bgn'] = array(
	'UnconnectedPages' => array( 'وسل_نه_بوته_ئین_تاکدیمان' ),
);

/** Czech (čeština) */
$specialPageAliases['cs'] = array(
	'PagesWithBadges' => array( 'Stránky_s_odznaky' ),
	'UnconnectedPages' => array( 'Nepropojené_stránky' ),
);

/** German (Deutsch) */
$specialPageAliases['de'] = array(
	'UnconnectedPages' => array( 'Nicht_verbundene_Seiten' ),
);

/** Spanish (español) */
$specialPageAliases['es'] = array(
	'UnconnectedPages' => array( 'PáginasSinConexión' ),
);

/** Estonian (eesti) */
$specialPageAliases['et'] = array(
	'UnconnectedPages' => array( 'Ühendamata_leheküljed' ),
);

/** Persian (فارسی) */
$specialPageAliases['fa'] = array(
	'UnconnectedPages' => array( 'صفخات_متصل‌نشده' ),
);

/** Finnish (suomi) */
$specialPageAliases['fi'] = array(
	'UnconnectedPages' => array( 'Yhdistämättömät_sivut' ),
);

/** Hebrew (עברית) */
$specialPageAliases['he'] = array(
	'UnconnectedPages' => array( 'דפים_שאינם_מקושרים_לפריטים', 'דפים_שלא_מקושרים_לפריטים' ),
);

/** Italian (italiano) */
$specialPageAliases['it'] = array(
	'PagesWithBadges' => array( 'PagineConDistintivi' ),
	'UnconnectedPages' => array( 'PagineNonConnesse' ),
);

/** Japanese (日本語) */
$specialPageAliases['ja'] = array(
	'UnconnectedPages' => array( '関連付けられていないページ', '関連付けのないページ' ),
);

/** Korean (한국어) */
$specialPageAliases['ko'] = array(
	'UnconnectedPages' => array( '연결안된문서' ),
);

/** Luxembourgish (Lëtzebuergesch) */
$specialPageAliases['lb'] = array(
	'UnconnectedPages' => array( 'Net_verbonne_Säiten', 'Ouni_Verbindung', 'Ouni_Linken_op_aner_Säiten' ),
);

/** Macedonian (македонски) */
$specialPageAliases['mk'] = array(
	'UnconnectedPages' => array( 'НесврзаниСтраници' ),
);

/** Dutch (Nederlands) */
$specialPageAliases['nl'] = array(
	'UnconnectedPages' => array( 'OngekoppeldePaginas', 'OngekoppeldePagina\'s' ),
	'PagesWithBadges' => array( 'PaginasMetBadges', 'Pagina\'sMetBadges' ),
);

/** Portuguese (português) */
$specialPageAliases['pt'] = array(
	'UnconnectedPages' => array( 'Páginas_sem_conexões' ),
);

/** Turkish (Türkçe) */
$specialPageAliases['tr'] = array(
	'UnconnectedPages' => array( 'BağlanmamışSayfalar', 'Bağlantısız', 'SiteBağlantısız' ),
);

/** Vietnamese (Tiếng Việt) */
$specialPageAliases['vi'] = array(
	'UnconnectedPages' => array( 'Trang_không_kết_nối', 'Trang_không_có_liên_kết_site' ),
);

/** Simplified Chinese (中文（简体）‎) */
$specialPageAliases['zh-hans'] = array(
	'UnconnectedPages' => array( '未链接页面', '丢失链接页面' ),
);

/** Traditional Chinese (中文（繁體）‎) */
$specialPageAliases['zh-hant'] = array(
	'UnconnectedPages' => array( '無連接頁面', '失去連接', '失去站點連接' ),
);
