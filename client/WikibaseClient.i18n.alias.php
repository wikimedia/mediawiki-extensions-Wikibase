<?php

/**
 * Aliases for the special pages of the Wikibase Client extension.
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 */

$specialPageAliases = [];

/** English (English) */
$specialPageAliases['en'] = [
	'EntityUsage' => [ 'EntityUsage', 'EntityUsageData' ],
	'PagesWithBadges' => [ 'PagesWithBadges', 'QueryBadges' ],
	'UnconnectedPages' => [ 'UnconnectedPages', 'WithoutConnection', 'WithoutSitelinks' ],
];
/** Arabic (العربية) */
$specialPageAliases['ar'] = [
	'UnconnectedPages' => [ 'صفحات_غير_متصلة', 'بدون_اتصال', 'بدون_وصلات_موقع' ],
];

/** Egyptian Arabic (مصرى) */
$specialPageAliases['arz'] = [
	'UnconnectedPages' => [ 'صفحات_مش_متوصله', 'من_غير_وصله', 'من_غير_وصلات_موقع' ],
];

/** Western Balochi (بلوچی رخشانی) */
$specialPageAliases['bgn'] = [
	'UnconnectedPages' => [ 'وسل_نه_بوته_ئین_تاکدیمان' ],
];

/** Bengali (বাংলা) */
$specialPageAliases['bn'] = [
	'EntityUsage' => [ 'সত্তার_ব্যবহার', 'সত্তার_ব্যবহারের_উপাত্ত' ],
	'PagesWithBadges' => [ 'ব্যাজসহ_পাতা' ],
	'UnconnectedPages' => [ 'অসংযুক্ত_পাতা', 'সংযোগহীন', 'সাইট_সংযোগহীন', 'অসংযুক্ত_পৃষ্ঠা', 'সংযোগবিহীন_পৃষ্ঠা' ],
];

/** Czech (čeština) */
$specialPageAliases['cs'] = [
	'EntityUsage' => [ 'Použití_entity' ],
	'PagesWithBadges' => [ 'Stránky_s_odznaky' ],
	'UnconnectedPages' => [ 'Nepropojené_stránky' ],
];

/** German (Deutsch) */
$specialPageAliases['de'] = [
	'UnconnectedPages' => [ 'Nicht_verbundene_Seiten' ],
];

/** Spanish (español) */
$specialPageAliases['es'] = [
	'EntityUsage' => [ 'Uso_de_entidades', 'Datos_de_uso_de_entidades' ],
	'PagesWithBadges' => [ 'Páginas_con_insignias', 'Buscar_insignias' ],
	'UnconnectedPages' => [ 'PáginasSinConexión', 'Páginas_sin_conexión' ],
];

/** Estonian (eesti) */
$specialPageAliases['et'] = [
	'UnconnectedPages' => [ 'Ühendamata_leheküljed' ],
];

/** Persian (فارسی) */
$specialPageAliases['fa'] = [
	'UnconnectedPages' => [ 'صفخات_متصل‌نشده' ],
];

/** Finnish (suomi) */
$specialPageAliases['fi'] = [
	'UnconnectedPages' => [ 'Yhdistämättömät_sivut' ],
];

/** Hebrew (עברית) */
$specialPageAliases['he'] = [
	'UnconnectedPages' => [ 'דפים_שאינם_מקושרים_לפריטים', 'דפים_שלא_מקושרים_לפריטים' ],
];

/** Italian (italiano) */
$specialPageAliases['it'] = [
	'PagesWithBadges' => [ 'PagineConDistintivi' ],
	'UnconnectedPages' => [ 'PagineNonConnesse' ],
];

/** Japanese (日本語) */
$specialPageAliases['ja'] = [
	'UnconnectedPages' => [ '関連付けられていないページ', '関連付けのないページ' ],
];

/** Korean (한국어) */
$specialPageAliases['ko'] = [
	'EntityUsage' => [ '개체사용량', '개체사용량데이터' ],
	'UnconnectedPages' => [ '연결안된문서', '사이트링크없는문서' ],
	'PagesWithBadges' => [ '뱃지있는문서', '뱃지조회' ],
];

/** Luxembourgish (Lëtzebuergesch) */
$specialPageAliases['lb'] = [
	'UnconnectedPages' => [ 'Net_verbonne_Säiten', 'Ouni_Verbindung', 'Ouni_Linken_op_aner_Säiten' ],
];

/** Macedonian (македонски) */
$specialPageAliases['mk'] = [
	'UnconnectedPages' => [ 'НесврзаниСтраници' ],
];

/** Norwegian Bokmål (norsk bokmål) */
$specialPageAliases['nb'] = [
	'EntityUsage' => [ 'Entitetsbruk' ],
	'PagesWithBadges' => [ 'Sider_med_merker' ],
	'UnconnectedPages' => [ 'Ikke-tilknyttede_sider', 'Ikke-tilknytta_sider' ],
];

/** Dutch (Nederlands) */
$specialPageAliases['nl'] = [
	'UnconnectedPages' => [ 'OngekoppeldePaginas', 'OngekoppeldePagina\'s' ],
	'PagesWithBadges' => [ 'PaginasMetBadges', 'Pagina\'sMetBadges' ],
];

/** Norwegian Nynorsk (norsk nynorsk) */
$specialPageAliases['nn'] = [
	'EntityUsage' => [ 'Entitetsbruk' ],
	'PagesWithBadges' => [ 'Sider_med_merker' ],
	'UnconnectedPages' => [ 'Ikkje-tilknytta_sider' ],
];

/** Polish (polski) */
$specialPageAliases['pl'] = [
	'EntityUsage' => [ 'Wykorzystanie_obiektów', 'Wykorzystanie_elementów' ],
	'PagesWithBadges' => [ 'Strony_z_odznakami' ],
	'UnconnectedPages' => [ 'Niepołączone_strony', 'Strony_bez_interwiki', 'Strony_bez_Wikidanych' ],
];

/** Portuguese (português) */
$specialPageAliases['pt'] = [
	'UnconnectedPages' => [ 'Páginas_sem_conexões' ],
];

/** Serbian Cyrillic (српски (ћирилица)) */
$specialPageAliases['sr-ec'] = [
	'EntityUsage' => [ 'УпотребаЕнтитета', 'Подаци_о_употреби_ентитета' ],
	'PagesWithBadges' => [ 'СтранеСаЗначкама', 'ПретражиЗначке' ],
	'UnconnectedPages' => [ 'НеповезанеСтране', 'Неповезане_стране' ],
];

/** Serbian Latin (srpski (latinica)) */
$specialPageAliases['sr-el'] = [
	'EntityUsage' => [ 'UpotrebaEntiteta', 'Podaci_o_upotrebi_entiteta' ],
	'PagesWithBadges' => [ 'StraneSaZnačkama', 'PretražiZnačke' ],
	'UnconnectedPages' => [ 'NepovezaneStrane', 'Nepovezane_strane' ],
];

/** Turkish (Türkçe) */
$specialPageAliases['tr'] = [
	'UnconnectedPages' => [ 'BağlanmamışSayfalar', 'Bağlantısız', 'SiteBağlantısız' ],
];

/** Urdu (اردو) */
$specialPageAliases['ur'] = [
	'EntityUsage' => [ 'استعمال_وجود', 'استعمال_وجود_ڈیٹا' ],
	'PagesWithBadges' => [ 'صفحات_مع_علامات' ],
	'UnconnectedPages' => [ 'غیر_مربوط_صفحات', 'غیر_مربوط', 'بدون_سائٹ_روابط' ],
];

/** Vietnamese (Tiếng Việt) */
$specialPageAliases['vi'] = [
	'UnconnectedPages' => [ 'Trang_không_kết_nối', 'Trang_không_có_liên_kết_site' ],
];

/** Simplified Chinese (中文（简体）) */
$specialPageAliases['zh-hans'] = [
	'UnconnectedPages' => [ '未链接页面', '丢失链接页面' ],
];

/** Traditional Chinese (中文（繁體）) */
$specialPageAliases['zh-hant'] = [
	'UnconnectedPages' => [ '無連接頁面', '失去連接', '失去站點連接' ],
];
