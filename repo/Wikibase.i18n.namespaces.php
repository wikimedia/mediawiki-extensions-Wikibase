<?php

/**
 * Namespace internationalization for the Wikibase extension.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
// @codingStandardsIgnoreFile

// For all well known Wikibase namespace constants, check if they are defined.
// If they are not defined, define them to be something otherwise unusable to get them out of the way.
// In effect this means that namespace translations apply only if the user defined the corresponding
// namespace constant.
$namespaceConstants = array(
	'WB_NS_DATA',     'WB_NS_DATA_TALK',  // legacy
	'WB_NS_ITEM',     'WB_NS_ITEM_TALK',
	'WB_NS_PROPERTY', 'WB_NS_PROPERTY_TALK',
	'WB_NS_QUERY',    'WB_NS_QUERY_TALK',
);

//@todo: relying on these constants to be defined or not is a pretty horrible hack.
//      these constants are not used anywhere else, they are expected to come from LocalSettings,
//      where the user has to know that the namespace constants *have* to have these names. Ugh.

foreach ( $namespaceConstants as $const ) {
	if ( !defined( $const ) ) {
		// define constant to be something that doesn't hurt.
		// let's hope nothing else is using that :)
		define( $const, -99999 );
	}
}

$namespaceNames = [];

$namespaceNames['en'] = array(
	WB_NS_DATA      => 'Data',      // legacy
	WB_NS_DATA_TALK => 'Data_talk', // legacy

	WB_NS_ITEM      => 'Item',
	WB_NS_ITEM_TALK => 'Item_talk',

	WB_NS_PROPERTY      => 'Property',
	WB_NS_PROPERTY_TALK => 'Property_talk',

	WB_NS_QUERY      => 'Query',
	WB_NS_QUERY_TALK => 'Query_talk',
);

$namespaceNames['be-tarask'] = array(
	WB_NS_DATA      => 'Зьвесткі',      // legacy
	WB_NS_DATA_TALK => 'Абмеркаваньне_зьвестак', // legacy

	WB_NS_ITEM      => 'Аб’ект',
	WB_NS_ITEM_TALK => 'Абмеркаваньне_аб’екта',

	WB_NS_PROPERTY      => 'Уласьцівасьць',
	WB_NS_PROPERTY_TALK => 'Абмеркаваньне_ўласьцівасьці',

	WB_NS_QUERY      => 'Запыт',
	WB_NS_QUERY_TALK => 'Абмеркаваньне_запыту',
);

$namespaceNames['cs'] = array(
	WB_NS_DATA      => 'Data',           // legacy
	WB_NS_DATA_TALK => 'Diskuse k datům', // legacy

	WB_NS_ITEM      => 'Položka',
	WB_NS_ITEM_TALK => 'Diskuse k položce',

	WB_NS_PROPERTY      => 'Vlastnost',
	WB_NS_PROPERTY_TALK => 'Diskuse k vlastnosti',

	WB_NS_QUERY      => 'Dotaz',
	WB_NS_QUERY_TALK => 'Diskuse k dotazu',
);

$namespaceNames['de'] = array(
	WB_NS_DATA      => 'Daten',           // legacy
	WB_NS_DATA_TALK => 'Datendiskussion', // legacy

	WB_NS_ITEM      => 'Thema',
	WB_NS_ITEM_TALK => 'Themendiskussion',

	WB_NS_PROPERTY      => 'Eigenschaft',
	WB_NS_PROPERTY_TALK => 'Eigenschaftsdiskussion',

	WB_NS_QUERY      => 'Abfrage',
	WB_NS_QUERY_TALK => 'Abfragediskussion',
);

$namespaceNames['he'] = array(
	WB_NS_DATA      => 'נתונים',      // legacy
	WB_NS_DATA_TALK => 'שיחת_נתונים', // legacy

	WB_NS_ITEM      => 'פריט',
	WB_NS_ITEM_TALK => 'שיחת_פריט',

	WB_NS_PROPERTY      => 'מאפיין',
	WB_NS_PROPERTY_TALK => 'שיחת_מאפיין',

	WB_NS_QUERY      => 'שאילתה',
	WB_NS_QUERY_TALK => 'שיחת_שאילתה',
);

$namespaceNames['it'] = array(
	WB_NS_DATA      => 'Dati',             // legacy
	WB_NS_DATA_TALK => 'Discussioni_dati', // legacy

	WB_NS_ITEM      => 'Elemento',
	WB_NS_ITEM_TALK => 'Discussioni_elemento',

	WB_NS_PROPERTY      => 'Proprietà',
	WB_NS_PROPERTY_TALK => 'Discussioni_proprietà',

	WB_NS_QUERY      => 'Query',
	WB_NS_QUERY_TALK => 'Discussioni_query',
);

$namespaceNames['nl'] = array(
	WB_NS_ITEM      => 'Item',
	WB_NS_ITEM_TALK => 'Overleg_item',

	WB_NS_PROPERTY      => 'Eigenschap',
	WB_NS_PROPERTY_TALK => 'Overleg_eigenschap',

	WB_NS_QUERY      => 'Zoekopdracht',
	WB_NS_QUERY_TALK => 'Overleg_zoekopdracht',
);

$namespaceNames['ru'] = array(
	WB_NS_DATA      => 'Данные',            // legacy
	WB_NS_DATA_TALK => 'Обсуждение_данных', // legacy

	WB_NS_ITEM      => 'Предмет',
	WB_NS_ITEM_TALK => 'Обсуждение_предмета',

	WB_NS_PROPERTY      => 'Свойство',
	WB_NS_PROPERTY_TALK => 'Обсуждение_свойства',

	WB_NS_QUERY      => 'Запрос',
	WB_NS_QUERY_TALK => 'Обсуждение_запроса',
);

$namespaceNames['vi'] = array(
	WB_NS_DATA      => 'Dữ_liệu',           // legacy
	WB_NS_DATA_TALK => 'Thảo_luận_Dữ_liệu', // legacy

	WB_NS_ITEM      => 'Khoản_mục',
	WB_NS_ITEM_TALK => 'Thảo_luận_Khoản_mục',

	WB_NS_PROPERTY      => 'Thuộc_tính',
	WB_NS_PROPERTY_TALK => 'Thảo_luận_Thuộc_tính',

	WB_NS_QUERY      => 'Truy_vấn',
	WB_NS_QUERY_TALK => 'Thảo_luận_Truy_vấn',
);
