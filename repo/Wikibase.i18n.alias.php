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
	'NewItem' => array( 'NewItem' ),
	'NewProperty' => array( 'NewProperty' ),
	'ItemByTitle' => array( 'ItemByTitle' ),
	'ItemDisambiguation' => array( 'ItemDisambiguation' ),
	'ListDatatypes' => array( 'ListDatatypes' ),
	'SetLabel' => array( 'SetLabel' ),
	'EntitiesWithoutLabel' => array( 'EntitiesWithoutLabel' ),
);

/** Arabic (العربية) */
$specialPageAliases['ar'] = array(
	'NewItem' => array( 'إنشاء_مدخلة' ),
	'ItemByTitle' => array( 'المدخلات_بالعنوان' ),
	'ItemDisambiguation' => array( 'المدخلات_بالعلامة' ),
);

/** Aramaic (ܐܪܡܝܐ) */
$specialPageAliases['arc'] = array(
	'NewProperty' => array( 'ܕܝܠܝܘܬ̈ܐ_ܚܕ̈ܬܬܐ' ),
);

/** German (Deutsch) */
$specialPageAliases['de'] = array(
	'NewItem' => array( 'Neues_Datenelement_erstellen' ),
	'NewProperty' => array( 'Neues_Attribut_erstellen' ),
	'ItemByTitle' => array( 'Datenelement_nach_Name' ),
	'ItemDisambiguation' => array( 'Begriffsklärung_zu_Datenelement' ),
	'ListDatatypes' => array( 'Datentypen_auflisten' ),
);

/** Zazaki (Zazaki) */
$specialPageAliases['diq'] = array(
	'NewItem' => array( 'LeteVırazê' ),
	'ItemByTitle' => array( 'SernuşteyêLeti' ),
	'ItemDisambiguation' => array( 'EtiketêLeti' ),
);

/** Icelandic (íslenska) */
$specialPageAliases['is'] = array(
	'NewItem' => array( 'Búa_til_hlut' ),
	'ItemByTitle' => array( 'Hlutur_eftir_nafni' ),
	'ItemDisambiguation' => array( 'Hlutur_eftir_merkimiða' ),
);

/** Korean (한국어) */
$specialPageAliases['ko'] = array(
	'NewItem' => array( '항목만들기', '아이템만들기' ),
	'NewProperty' => array( '새속성' ),
	'ItemByTitle' => array( '제목별항목', '제목별아이템' ),
	'ItemDisambiguation' => array( '레이블별항목', '라벨별항목', '레이블별아이템', '라벨별아이템' ),
	'ListDatatypes' => array( '데이터유형목록' ),
);

/** Macedonian (македонски) */
$specialPageAliases['mk'] = array(
	'NewItem' => array( 'СоздајПредмет' ),
	'NewProperty' => array( 'НовоСвојство' ),
	'ItemByTitle' => array( 'ПредметПоНаслов' ),
	'ItemDisambiguation' => array( 'ПредметПоЕтикета' ),
	'ListDatatypes' => array( 'СписокПодаточниТипови' ),
	'SetLabel' => array( 'ЗадајЕтикета' ),
);

/** Dutch (Nederlands) */
$specialPageAliases['nl'] = array(
	'NewItem' => array( 'ItemAanmaken' ),
	'NewProperty' => array( 'NieuweEigenschap' ),
	'ItemByTitle' => array( 'ItemPerTitel' ),
	'ItemDisambiguation' => array( 'ItemPerLabel' ),
	'ListDatatypes' => array( 'GegevenstypenWeergeven' ),
	'SetLabel' => array( 'LabelInstellen' ),
	'EntitiesWithoutLabel' => array( 'EntiteitenZonderLabel' ),
);

/** Simplified Chinese (中文（简体）‎) */
$specialPageAliases['zh-hans'] = array(
	'NewItem' => array( '创建项目' ),
	'NewProperty' => array( '新建属性' ),
	'ItemByTitle' => array( '按标题搜索项目' ),
	'ItemDisambiguation' => array( '项目消歧义' ),
	'ListDatatypes' => array( '列出数据类型' ),
	'SetLabel' => array( '设置标签' ),
);