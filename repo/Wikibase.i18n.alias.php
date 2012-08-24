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

/** Arabic (العربية) */
$specialPageAliases['ar'] = array(
	'CreateItem' => array( 'إنشاء_مدخلة' ),
	'ItemByTitle' => array( 'المدخلات_بالعنوان' ),
	'ItemByLabel' => array( 'المدخلات_بالعلامة' ),
);

/** German (Deutsch) */
$specialPageAliases['de'] = array(
	'CreateItem' => array( 'Datenelement_erstellen' ),
	'ItemByTitle' => array( 'Datenelement_nach_Name' ),
	'ItemByLabel' => array( 'Datenelement_nach_Bezeichnung' ),
);

/** Zazaki (Zazaki) */
$specialPageAliases['diq'] = array(
	'CreateItem' => array( 'LeteVırazê' ),
	'ItemByTitle' => array( 'SernuşteyêLeti' ),
	'ItemByLabel' => array( 'EtiketêLeti' ),
);

/** Icelandic (íslenska) */
$specialPageAliases['is'] = array(
	'CreateItem' => array( 'Búa_til_hlut' ),
	'ItemByTitle' => array( 'Hlutur_eftir_nafni' ),
	'ItemByLabel' => array( 'Hlutur_eftir_merkimiða' ),
);

/** Korean (한국어) */
$specialPageAliases['ko'] = array(
	'CreateItem' => array( '항목만들기', '아이템만들기' ),
	'ItemByTitle' => array( '제목별항목', '제목별아이템' ),
	'ItemByLabel' => array( '레이블별항목', '라벨별항목', '레이블별아이템', '라벨별아이템' ),
);

/** Macedonian (македонски) */
$specialPageAliases['mk'] = array(
	'CreateItem' => array( 'СоздајПредмет' ),
	'ItemByTitle' => array( 'ПредметПоНаслов' ),
	'ItemByLabel' => array( 'ПредметПоЕтикета' ),
);

/** Dutch (Nederlands) */
$specialPageAliases['nl'] = array(
	'CreateItem' => array( 'ItemAanmaken' ),
	'ItemByTitle' => array( 'ItemPerTitel' ),
	'ItemByLabel' => array( 'ItemPerLabel' ),
);