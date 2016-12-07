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
	'WB_NS_ITEM',     'WB_NS_ITEM_TALK',
	'WB_NS_PROPERTY', 'WB_NS_PROPERTY_TALK',
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

$namespaceNames = array();

$namespaceNames['en'] = array(
	WB_NS_ITEM      => 'Item',
	WB_NS_ITEM_TALK => 'Item_talk',

	WB_NS_PROPERTY      => 'Property',
	WB_NS_PROPERTY_TALK => 'Property_talk',
);

$namespaceNames['be-tarask'] = array(
	WB_NS_ITEM      => 'Аб’ект',
	WB_NS_ITEM_TALK => 'Абмеркаваньне_аб’екта',

	WB_NS_PROPERTY      => 'Уласьцівасьць',
	WB_NS_PROPERTY_TALK => 'Абмеркаваньне_ўласьцівасьці',
);

$namespaceNames['cs'] = array(
	WB_NS_ITEM      => 'Položka',
	WB_NS_ITEM_TALK => 'Diskuse k položce',

	WB_NS_PROPERTY      => 'Vlastnost',
	WB_NS_PROPERTY_TALK => 'Diskuse k vlastnosti',
);

$namespaceNames['de'] = array(
	WB_NS_ITEM      => 'Thema',
	WB_NS_ITEM_TALK => 'Themendiskussion',

	WB_NS_PROPERTY      => 'Eigenschaft',
	WB_NS_PROPERTY_TALK => 'Eigenschaftsdiskussion',
);

$namespaceNames['he'] = array(
	WB_NS_ITEM      => 'פריט',
	WB_NS_ITEM_TALK => 'שיחת_פריט',

	WB_NS_PROPERTY      => 'מאפיין',
	WB_NS_PROPERTY_TALK => 'שיחת_מאפיין',
);

$namespaceNames['it'] = array(
	WB_NS_ITEM      => 'Elemento',
	WB_NS_ITEM_TALK => 'Discussioni_elemento',

	WB_NS_PROPERTY      => 'Proprietà',
	WB_NS_PROPERTY_TALK => 'Discussioni_proprietà',
);

$namespaceNames['nl'] = array(
	WB_NS_ITEM      => 'Item',
	WB_NS_ITEM_TALK => 'Overleg_item',

	WB_NS_PROPERTY      => 'Eigenschap',
	WB_NS_PROPERTY_TALK => 'Overleg_eigenschap',
);

$namespaceNames['ru'] = array(
	WB_NS_ITEM      => 'Предмет',
	WB_NS_ITEM_TALK => 'Обсуждение_предмета',

	WB_NS_PROPERTY      => 'Свойство',
	WB_NS_PROPERTY_TALK => 'Обсуждение_свойства',
);

$namespaceNames['vi'] = array(
	WB_NS_ITEM      => 'Khoản_mục',
	WB_NS_ITEM_TALK => 'Thảo_luận_Khoản_mục',

	WB_NS_PROPERTY      => 'Thuộc_tính',
	WB_NS_PROPERTY_TALK => 'Thảo_luận_Thuộc_tính',
);
