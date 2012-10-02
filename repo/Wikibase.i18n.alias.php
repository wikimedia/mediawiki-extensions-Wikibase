<?php

/**
 * Aliases for the special pages of the Wikibase extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

$specialPageAliases = array();

/** English (English) */
$specialPageAliases['en'] = array(
	'CreateItem' => array( 'CreateItem' ),
	'NewProperty' => array( 'NewProperty', 'CreateProperty' ),
	'ItemByTitle' => array( 'ItemByTitle' ),
	'ItemDisambiguation' => array( 'ItemDisambiguation' ),
	'ListDatatypes' => array( 'ListDatatypes' ),
);

/** Arabic (العربية) */
$specialPageAliases['ar'] = array(
	'CreateItem' => array( 'إنشاء_مدخلة' ),
	'CreateProperty' => array( 'إنشاء_خاصية' ),
	'ItemByTitle' => array( 'المدخلات_بالعنوان' ),
	'ItemDisambiguation' => array( 'المدخلات_بالعلامة' ),
);

/** Bosnian (bosanski) */
$specialPageAliases['bs'] = array(
	'CreateProperty' => array( 'PravljenjeSvojstva' ),
);

/** German (Deutsch) */
$specialPageAliases['de'] = array(
	'CreateItem' => array( 'Datenelement_erstellen' ),
	'CreateProperty' => array( 'Attribut_erstellen' ),
	'ItemByTitle' => array( 'Datenelement_nach_Name' ),
	'ItemDisambiguation' => array( 'Begriffsklärung_zu_Datenelement' ),
);

/** Zazaki (Zazaki) */
$specialPageAliases['diq'] = array(
	'CreateItem' => array( 'LeteVırazê' ),
	'CreateProperty' => array( 'XısusiyetiVıraze' ),
	'ItemByTitle' => array( 'SernuşteyêLeti' ),
	'ItemDisambiguation' => array( 'EtiketêLeti' ),
);

/** Persian (فارسی) */
$specialPageAliases['fa'] = array(
	'CreateProperty' => array( 'ایجاد_صفت' ),
);

/** Finnish (suomi) */
$specialPageAliases['fi'] = array(
	'CreateProperty' => array( 'Luo_ominaisuus' ),
);

/** Franco-Provençal (arpetan) */
$specialPageAliases['frp'] = array(
	'CreateProperty' => array( 'Dèfenir_una_propriètât', 'DèfenirUnaPropriètât' ),
);

/** Swiss German (Alemannisch) */
$specialPageAliases['gsw'] = array(
	'CreateProperty' => array( 'Eigeschaft_aalege' ),
);

/** Haitian (Kreyòl ayisyen) */
$specialPageAliases['ht'] = array(
	'CreateProperty' => array( 'KreyePropriete' ),
);

/** Hungarian (magyar) */
$specialPageAliases['hu'] = array(
	'CreateProperty' => array( 'Tulajdonság_készítése' ),
);

/** Interlingua (interlingua) */
$specialPageAliases['ia'] = array(
	'CreateProperty' => array( 'Crear_proprietate' ),
);

/** Indonesian (Bahasa Indonesia) */
$specialPageAliases['id'] = array(
	'CreateProperty' => array( 'Buat_properti', 'BuatProperti' ),
);

/** Icelandic (íslenska) */
$specialPageAliases['is'] = array(
	'CreateItem' => array( 'Búa_til_hlut' ),
	'ItemByTitle' => array( 'Hlutur_eftir_nafni' ),
	'ItemDisambiguation' => array( 'Hlutur_eftir_merkimiða' ),
);

/** Italian (italiano) */
$specialPageAliases['it'] = array(
	'CreateProperty' => array( 'CreaProprietà' ),
);

/** Japanese (日本語) */
$specialPageAliases['ja'] = array(
	'CreateProperty' => array( 'プロパティ作成' ),
);

/** Korean (한국어) */
$specialPageAliases['ko'] = array(
	'CreateItem' => array( '항목만들기', '아이템만들기' ),
	'CreateProperty' => array( '속성추가' ),
	'ItemByTitle' => array( '제목별항목', '제목별아이템' ),
	'ItemDisambiguation' => array( '레이블별항목', '라벨별항목', '레이블별아이템', '라벨별아이템' ),
);

/** Luxembourgish (Lëtzebuergesch) */
$specialPageAliases['lb'] = array(
	'CreateProperty' => array( 'Eegeschaften_uleeën' ),
);

/** Macedonian (македонски) */
$specialPageAliases['mk'] = array(
	'CreateItem' => array( 'СоздајПредмет' ),
	'CreateProperty' => array( 'СоздајСвојство' ),
	'ItemByTitle' => array( 'ПредметПоНаслов' ),
	'ItemDisambiguation' => array( 'ПредметПоЕтикета' ),
);

/** Marathi (मराठी) */
$specialPageAliases['mr'] = array(
	'CreateProperty' => array( 'वैशिष्ट्येतयारकरा' ),
);

/** Norwegian Bokmål (norsk (bokmål)‎) */
$specialPageAliases['nb'] = array(
	'CreateProperty' => array( 'Opprett_egenskap' ),
);

/** Nedersaksisch (Nedersaksisch) */
$specialPageAliases['nds-nl'] = array(
	'CreateProperty' => array( 'Eigenschap_anmaken' ),
);

/** Dutch (Nederlands) */
$specialPageAliases['nl'] = array(
	'CreateItem' => array( 'ItemAanmaken' ),
	'CreateProperty' => array( 'EigenschapAanmaken' ),
	'ItemByTitle' => array( 'ItemPerTitel' ),
	'ItemDisambiguation' => array( 'ItemPerLabel' ),
);

/** Polish (polski) */
$specialPageAliases['pl'] = array(
	'CreateProperty' => array( 'UtwórzWłaściwość' ),
);

/** Portuguese (português) */
$specialPageAliases['pt'] = array(
	'CreateProperty' => array( 'Criar_propriedade' ),
);

/** Serbian (Cyrillic script) (српски (ћирилица)‎) */
$specialPageAliases['sr-ec'] = array(
	'CreateProperty' => array( 'Направи_својство' ),
);

/** Tagalog (Tagalog) */
$specialPageAliases['tl'] = array(
	'CreateProperty' => array( 'Likhain_ang_pag-aari' ),
);

/** Turkish (Türkçe) */
$specialPageAliases['tr'] = array(
	'CreateProperty' => array( 'ÖzellikOluştur' ),
);

/** Ukrainian (українська) */
$specialPageAliases['uk'] = array(
	'CreateProperty' => array( 'Створити_властивість' ),
);
