<?php

/**
 * Namespace internationalization for the Wikibase extension.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */

// For all well known Wikibase namespace constants, check if they are defined.
// If they are not defined, define them to be something otherwise unusable to get them out of the way.
// In effect this means that namespace translations apply only if the user defined the corresponding
// namespace constant.
$namespaceConstants = [
	'WB_NS_ITEM',     'WB_NS_ITEM_TALK',
	'WB_NS_PROPERTY', 'WB_NS_PROPERTY_TALK',
	'WB_NS_QUERY',    'WB_NS_QUERY_TALK',
];

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

$namespaceNames['en'] = [
	WB_NS_ITEM      => 'Item',
	WB_NS_ITEM_TALK => 'Item_talk',

	WB_NS_PROPERTY      => 'Property',
	WB_NS_PROPERTY_TALK => 'Property_talk',

	WB_NS_QUERY      => 'Query',
	WB_NS_QUERY_TALK => 'Query_talk',
];

$namespaceNames['be-tarask'] = [
	WB_NS_ITEM      => 'Аб’ект',
	WB_NS_ITEM_TALK => 'Абмеркаваньне_аб’екта',

	WB_NS_PROPERTY      => 'Уласьцівасьць',
	WB_NS_PROPERTY_TALK => 'Абмеркаваньне_ўласьцівасьці',

	WB_NS_QUERY      => 'Запыт',
	WB_NS_QUERY_TALK => 'Абмеркаваньне_запыту',
];

$namespaceNames['cs'] = [
	WB_NS_ITEM      => 'Položka',
	WB_NS_ITEM_TALK => 'Diskuse k položce',

	WB_NS_PROPERTY      => 'Vlastnost',
	WB_NS_PROPERTY_TALK => 'Diskuse k vlastnosti',

	WB_NS_QUERY      => 'Dotaz',
	WB_NS_QUERY_TALK => 'Diskuse k dotazu',
];

$namespaceNames['de'] = [
	WB_NS_ITEM      => 'Thema',
	WB_NS_ITEM_TALK => 'Themendiskussion',

	WB_NS_PROPERTY      => 'Eigenschaft',
	WB_NS_PROPERTY_TALK => 'Eigenschaftsdiskussion',

	WB_NS_QUERY      => 'Abfrage',
	WB_NS_QUERY_TALK => 'Abfragediskussion',
];

$namespaceNames['es'] = [
	WB_NS_ITEM      => 'Elemento',
	WB_NS_ITEM_TALK => 'Elemento_discusión',

	WB_NS_PROPERTY      => 'Propiedad',
	WB_NS_PROPERTY_TALK => 'Propiedad_discusión',

	WB_NS_QUERY      => 'Consulta',
	WB_NS_QUERY_TALK => 'Consulta_discusión',
];

$namespaceNames['he'] = [
	WB_NS_ITEM      => 'פריט',
	WB_NS_ITEM_TALK => 'שיחת_פריט',

	WB_NS_PROPERTY      => 'מאפיין',
	WB_NS_PROPERTY_TALK => 'שיחת_מאפיין',

	WB_NS_QUERY      => 'שאילתה',
	WB_NS_QUERY_TALK => 'שיחת_שאילתה',
];

$namespaceNames['it'] = [
	WB_NS_ITEM      => 'Elemento',
	WB_NS_ITEM_TALK => 'Discussioni_elemento',

	WB_NS_PROPERTY      => 'Proprietà',
	WB_NS_PROPERTY_TALK => 'Discussioni_proprietà',

	WB_NS_QUERY      => 'Query',
	WB_NS_QUERY_TALK => 'Discussioni_query',
];

$namespaceNames['ko'] = [
	WB_NS_ITEM      => '항목',
	WB_NS_ITEM_TALK => '항목토론',

	WB_NS_PROPERTY      => '속성',
	WB_NS_PROPERTY_TALK => '속성토론',

	WB_NS_QUERY      => '쿼리',
	WB_NS_QUERY_TALK => '쿼리토론',
];

$namespaceNames['nl'] = [
	WB_NS_ITEM      => 'Item',
	WB_NS_ITEM_TALK => 'Overleg_item',

	WB_NS_PROPERTY      => 'Eigenschap',
	WB_NS_PROPERTY_TALK => 'Overleg_eigenschap',

	WB_NS_QUERY      => 'Zoekopdracht',
	WB_NS_QUERY_TALK => 'Overleg_zoekopdracht',
];

$namespaceNames['ru'] = [
	WB_NS_ITEM      => 'Предмет',
	WB_NS_ITEM_TALK => 'Обсуждение_предмета',

	WB_NS_PROPERTY      => 'Свойство',
	WB_NS_PROPERTY_TALK => 'Обсуждение_свойства',

	WB_NS_QUERY      => 'Запрос',
	WB_NS_QUERY_TALK => 'Обсуждение_запроса',
];

$namespaceNames['ur'] = [
	WB_NS_ITEM      => 'آئٹم',
	WB_NS_ITEM_TALK => 'تبادلہ_خیال_آئٹم',
	WB_NS_PROPERTY      => 'خاصیت',
	WB_NS_PROPERTY_TALK => 'تبادلہ_خیال_خاصیت',
	WB_NS_QUERY      => 'استفسار',
	WB_NS_QUERY_TALK => 'تبادلہ_خیال_استفسار',
];

$namespaceNames['vi'] = [
	WB_NS_ITEM      => 'Khoản_mục',
	WB_NS_ITEM_TALK => 'Thảo_luận_Khoản_mục',

	WB_NS_PROPERTY      => 'Thuộc_tính',
	WB_NS_PROPERTY_TALK => 'Thảo_luận_Thuộc_tính',

	WB_NS_QUERY      => 'Truy_vấn',
	WB_NS_QUERY_TALK => 'Thảo_luận_Truy_vấn',
];
