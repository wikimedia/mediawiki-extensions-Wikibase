<?php

/**
 * Namespace internationalization for the Wikibase extension.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @phan-file-suppress PhanUndeclaredConstant
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

$namespaceAliases = [];

$namespaceNames['en'] = [
	WB_NS_ITEM          => 'Item',
	WB_NS_ITEM_TALK     => 'Item_talk',
	WB_NS_PROPERTY      => 'Property',
	WB_NS_PROPERTY_TALK => 'Property_talk',
	WB_NS_QUERY         => 'Query',
	WB_NS_QUERY_TALK    => 'Query_talk',
];

$namespaceNames['be-tarask'] = [
	WB_NS_ITEM          => 'Аб’ект',
	WB_NS_ITEM_TALK     => 'Абмеркаваньне_аб’екта',
	WB_NS_PROPERTY      => 'Уласьцівасьць',
	WB_NS_PROPERTY_TALK => 'Абмеркаваньне_ўласьцівасьці',
	WB_NS_QUERY         => 'Запыт',
	WB_NS_QUERY_TALK    => 'Абмеркаваньне_запыту',
];

$namespaceNames['cs'] = [
	WB_NS_ITEM          => 'Položka',
	WB_NS_ITEM_TALK     => 'Diskuse k položce',
	WB_NS_PROPERTY      => 'Vlastnost',
	WB_NS_PROPERTY_TALK => 'Diskuse k vlastnosti',
	WB_NS_QUERY         => 'Dotaz',
	WB_NS_QUERY_TALK    => 'Diskuse k dotazu',
];

$namespaceNames['de'] = [
	WB_NS_ITEM          => 'Thema',
	WB_NS_ITEM_TALK     => 'Themendiskussion',
	WB_NS_PROPERTY      => 'Eigenschaft',
	WB_NS_PROPERTY_TALK => 'Eigenschaftsdiskussion',
	WB_NS_QUERY         => 'Abfrage',
	WB_NS_QUERY_TALK    => 'Abfragediskussion',
];

$namespaceNames['es'] = [
	WB_NS_ITEM          => 'Elemento',
	WB_NS_ITEM_TALK     => 'Elemento_discusión',
	WB_NS_PROPERTY      => 'Propiedad',
	WB_NS_PROPERTY_TALK => 'Propiedad_discusión',
	WB_NS_QUERY         => 'Consulta',
	WB_NS_QUERY_TALK    => 'Consulta_discusión',
];

$namespaceNames['fr'] = [
	WB_NS_ITEM          => 'Élément',
	WB_NS_ITEM_TALK     => 'Discussion élément',
	WB_NS_PROPERTY      => 'Propriété',
	WB_NS_PROPERTY_TALK => 'Discussion propriété',
	WB_NS_QUERY         => 'Requête',
	WB_NS_QUERY_TALK    => 'Discussion requête',
];

$namespaceNames['he'] = [
	WB_NS_ITEM          => 'פריט',
	WB_NS_ITEM_TALK     => 'שיחת_פריט',
	WB_NS_PROPERTY      => 'מאפיין',
	WB_NS_PROPERTY_TALK => 'שיחת_מאפיין',
	WB_NS_QUERY         => 'שאילתה',
	WB_NS_QUERY_TALK    => 'שיחת_שאילתה',
];

$namespaceNames['hi'] = [
	WB_NS_ITEM          => 'आयटम',
	WB_NS_ITEM_TALK     => 'आयटम_वार्ता',
	WB_NS_PROPERTY      => 'गुणधर्म',
	WB_NS_PROPERTY_TALK => 'गुणधर्म_वार्ता',
	WB_NS_QUERY         => 'क्वेरी',
	WB_NS_QUERY_TALK    => 'क्वेरी_वार्ता',
];

$namespaceNames['it'] = [
	WB_NS_ITEM          => 'Elemento',
	WB_NS_ITEM_TALK     => 'Discussioni_elemento',
	WB_NS_PROPERTY      => 'Proprietà',
	WB_NS_PROPERTY_TALK => 'Discussioni_proprietà',
	WB_NS_QUERY         => 'Query',
	WB_NS_QUERY_TALK    => 'Discussioni_query',
];

$namespaceNames['ko'] = [
	WB_NS_ITEM          => '항목',
	WB_NS_ITEM_TALK     => '항목토론',
	WB_NS_PROPERTY      => '속성',
	WB_NS_PROPERTY_TALK => '속성토론',
	WB_NS_QUERY         => '쿼리',
	WB_NS_QUERY_TALK    => '쿼리토론',
];

$namespaceNames['nb'] = [
	WB_NS_ITEM          => 'Element',
	WB_NS_ITEM_TALK     => 'Elementdiskusjon',
	WB_NS_PROPERTY      => 'Egenskap',
	WB_NS_PROPERTY_TALK => 'Egenskapsdiskusjon',
	WB_NS_QUERY         => 'Spørring',
	WB_NS_QUERY_TALK    => 'Spørringsdiskusjon',
];

$namespaceNames['nl'] = [
	WB_NS_ITEM          => 'Item',
	WB_NS_ITEM_TALK     => 'Overleg_item',
	WB_NS_PROPERTY      => 'Eigenschap',
	WB_NS_PROPERTY_TALK => 'Overleg_eigenschap',
	WB_NS_QUERY         => 'Zoekopdracht',
	WB_NS_QUERY_TALK    => 'Overleg_zoekopdracht',
];

$namespaceNames['nn'] = [
	WB_NS_ITEM          => 'Element',
	WB_NS_ITEM_TALK     => 'Elementdiskusjon',
	WB_NS_PROPERTY      => 'Eigenskap',
	WB_NS_PROPERTY_TALK => 'Eigenskapsdiskusjon',
	WB_NS_QUERY         => 'Spørjing',
	WB_NS_QUERY_TALK    => 'Spørjingsdiskusjon',
];

$namespaceNames['ru'] = [
	WB_NS_ITEM          => 'Предмет',
	WB_NS_ITEM_TALK     => 'Обсуждение_предмета',
	WB_NS_PROPERTY      => 'Свойство',
	WB_NS_PROPERTY_TALK => 'Обсуждение_свойства',
	WB_NS_QUERY         => 'Запрос',
	WB_NS_QUERY_TALK    => 'Обсуждение_запроса',
];

$namespaceNames['ur'] = [
	WB_NS_ITEM          => 'آئٹم',
	WB_NS_ITEM_TALK     => 'تبادلہ_خیال_آئٹم',
	WB_NS_PROPERTY      => 'خاصیت',
	WB_NS_PROPERTY_TALK => 'تبادلہ_خیال_خاصیت',
	WB_NS_QUERY         => 'استفسار',
	WB_NS_QUERY_TALK    => 'تبادلہ_خیال_استفسار',
];

$namespaceNames['vi'] = [
	WB_NS_ITEM          => 'Khoản_mục',
	WB_NS_ITEM_TALK     => 'Thảo_luận_Khoản_mục',
	WB_NS_PROPERTY      => 'Thuộc_tính',
	WB_NS_PROPERTY_TALK => 'Thảo_luận_Thuộc_tính',
	WB_NS_QUERY         => 'Truy_vấn',
	WB_NS_QUERY_TALK    => 'Thảo_luận_Truy_vấn',
];

$namespaceNames['zh'] = [
	WB_NS_ITEM          => 'Item',
	WB_NS_ITEM_TALK     => 'Item_talk',
	WB_NS_PROPERTY      => 'Property',
	WB_NS_PROPERTY_TALK => 'Property_talk',
	WB_NS_QUERY         => 'Query',
	WB_NS_QUERY_TALK    => 'Query_talk',
];

$namespaceNames['zh-hans'] = [
	WB_NS_ITEM          => '项目',
	WB_NS_ITEM_TALK     => '项目讨论',
	WB_NS_PROPERTY      => '属性',
	WB_NS_PROPERTY_TALK => '属性讨论',
	WB_NS_QUERY         => '查询',
	WB_NS_QUERY_TALK    => '查询讨论',
];

$namespaceAliases['zh-hans'] = [
	'项目' => WB_NS_ITEM,
	'项目讨论' => WB_NS_ITEM_TALK,
	'属性' => WB_NS_PROPERTY,
	'属性讨论' => WB_NS_PROPERTY_TALK,
	'查询' => WB_NS_QUERY,
	'查询讨论' => WB_NS_QUERY_TALK,
];

$namespaceNames['zh-hant'] = [
	WB_NS_ITEM          => '項目',
	WB_NS_ITEM_TALK     => '項目討論',
	WB_NS_PROPERTY      => '屬性',
	WB_NS_PROPERTY_TALK => '屬性討論',
	WB_NS_QUERY         => '查詢',
	WB_NS_QUERY_TALK    => '查詢討論',
];

$namespaceAliases['zh-hant'] = [
	'項目' => WB_NS_ITEM,
	'項目討論' => WB_NS_ITEM_TALK,
	'屬性' => WB_NS_PROPERTY,
	'屬性討論' => WB_NS_PROPERTY_TALK,
	'查詢' => WB_NS_QUERY,
	'查詢討論' => WB_NS_QUERY_TALK,
];
