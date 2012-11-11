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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

$specialPageAliases = array();

/** English (English) */
$specialPageAliases['en'] = array(
	'EntityData' => array( 'EntityData' ),
	'NewItem' => array( 'NewItem', 'CreateItem' ),
	'NewProperty' => array( 'NewProperty', 'CreateProperty' ),
	'ItemByTitle' => array( 'ItemByTitle' ),
	'ItemDisambiguation' => array( 'ItemDisambiguation' ),
	'ListDatatypes' => array( 'ListDatatypes' ),
	'SetLabel' => array( 'SetLabel' ),
	'SetDescription' => array( 'SetDescription' ),
	'SetAliases' => array( 'SetAliases' ),
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
	'NewProperty' => array( 'ܕܝܠܝܘ̈ܬܐ_ܚܕ̈ܬܬܐ' ),
);

/** буряад (буряад) */
$specialPageAliases['bxr'] = array(
	'NewItem' => array( 'Зүйл_үүсхэхэ' ),
	'NewProperty' => array( 'Шэнэ_шэнжэ_шанар' ),
	'ItemByTitle' => array( 'Нэрээр_жагсааха' ),
	'ItemDisambiguation' => array( 'Дэлгэрэнгы_нэрэ' ),
);

/** German (Deutsch) */
$specialPageAliases['de'] = array(
	'EntityData' => array( 'Objektdaten' ),
	'NewItem' => array( 'Neues_Datenelement_erstellen' ),
	'NewProperty' => array( 'Neues_Attribut_erstellen' ),
	'ItemByTitle' => array( 'Datenelement_nach_Name' ),
	'ItemDisambiguation' => array( 'Begriffsklärung_zu_Datenelement' ),
	'ListDatatypes' => array( 'Datentypen_auflisten' ),
	'SetLabel' => array( 'Bezeichnung_festlegen' ),
	'SetDescription' => array( 'Beschreibung_festlegen' ),
	'SetAliases' => array( 'Aliasse_festlegen' ),
	'EntitiesWithoutLabel' => array( 'Objekte_ohne_Bezeichnung' ),
);

/** Zazaki (Zazaki) */
$specialPageAliases['diq'] = array(
	'NewItem' => array( 'LeteVırazê' ),
	'NewProperty' => array( 'XısusiyetêNewey' ),
	'ItemByTitle' => array( 'SernuşteyêLeti' ),
	'ItemDisambiguation' => array( 'EtiketêLeti' ),
	'ListDatatypes' => array( 'ListeyaBabetandeMelumati' ),
	'SetLabel' => array( 'SazêEtiketan' ),
);

/** Spanish (español) */
$specialPageAliases['es'] = array(
	'EntityData' => array( 'DatosDeEntidad' ),
	'NewItem' => array( 'CrearElemento' ),
	'NewProperty' => array( 'NuevaPropiedad' ),
	'ItemByTitle' => array( 'ElementoPorTítulo' ),
	'ItemDisambiguation' => array( 'DesambiguaciónDeElementos' ),
	'ListDatatypes' => array( 'ListarTiposDeDatos' ),
	'SetLabel' => array( 'AsignarEtiqueta' ),
	'EntitiesWithoutLabel' => array( 'EntidadesSinEtiqueta' ),
);

/** Finnish (suomi) */
$specialPageAliases['fi'] = array(
	'NewItem' => array( 'Luo_kohde' ),
	'NewProperty' => array( 'Uusi_ominaisuus' ),
	'ItemByTitle' => array( 'Kohde_otsikon_mukaan' ),
	'ItemDisambiguation' => array( 'Kohdetäsmennys' ),
	'SetLabel' => array( 'Aseta_nimi' ),
	'EntitiesWithoutLabel' => array( 'Aiheet_ilman_nimeä' ),
);

/** Icelandic (íslenska) */
$specialPageAliases['is'] = array(
	'NewItem' => array( 'Búa_til_hlut' ),
	'NewProperty' => array( 'Ný_staðhæfing' ),
	'ItemByTitle' => array( 'Hlutur_eftir_nafni' ),
	'ItemDisambiguation' => array( 'Hlutur_eftir_merkimiða' ),
	'ListDatatypes' => array( 'Gagnagerðir' ),
	'SetLabel' => array( 'Setja_merkimiða' ),
	'EntitiesWithoutLabel' => array( 'Færslur_án_merkimiða' ),
);

/** Italian (italiano) */
$specialPageAliases['it'] = array(
	'EntityData' => array( 'DatiEntità' ),
	'NewItem' => array( 'CreaElemento' ),
	'NewProperty' => array( 'NuovaProprietà' ),
	'ItemByTitle' => array( 'ElementiPerTitolo' ),
	'ItemDisambiguation' => array( 'ElementiDisambigui' ),
	'ListDatatypes' => array( 'ElencaTipiDati' ),
	'SetLabel' => array( 'ImpostaEtichetta' ),
	'EntitiesWithoutLabel' => array( 'EntitàSenzaEtichetta' ),
);

/** Japanese (日本語) */
$specialPageAliases['ja'] = array(
	'NewProperty' => array( '新規プロパティ' ),
);

/** Korean (한국어) */
$specialPageAliases['ko'] = array(
	'EntityData' => array( '항목데이터' ),
	'NewItem' => array( '항목만들기', '아이템만들기' ),
	'NewProperty' => array( '새속성' ),
	'ItemByTitle' => array( '제목별항목', '제목별아이템' ),
	'ItemDisambiguation' => array( '레이블별항목', '라벨별항목', '레이블별아이템', '라벨별아이템' ),
	'ListDatatypes' => array( '데이터유형목록' ),
	'SetLabel' => array( '레이블설정' ),
	'SetDescription' => array( '설명설정' ),
	'SetAliases' => array( '별명설정' ),
	'EntitiesWithoutLabel' => array( '레이블없는항목' ),
);

/** Macedonian (македонски) */
$specialPageAliases['mk'] = array(
	'EntityData' => array( 'ЕдиницаПодаток' ),
	'NewItem' => array( 'СоздајПредмет' ),
	'NewProperty' => array( 'НовоСвојство' ),
	'ItemByTitle' => array( 'ПредметПоНаслов' ),
	'ItemDisambiguation' => array( 'ПредметПоЕтикета' ),
	'ListDatatypes' => array( 'СписокПодаточниТипови' ),
	'SetLabel' => array( 'ЗадајЕтикета' ),
	'EntitiesWithoutLabel' => array( 'ЕдинициБезЕтикета' ),
);

/** Dutch (Nederlands) */
$specialPageAliases['nl'] = array(
	'EntityData' => array( 'Entiteitsgegevens' ),
	'NewItem' => array( 'ItemAanmaken' ),
	'NewProperty' => array( 'NieuweEigenschap' ),
	'ItemByTitle' => array( 'ItemPerTitel' ),
	'ItemDisambiguation' => array( 'ItemPerLabel' ),
	'ListDatatypes' => array( 'GegevenstypenWeergeven' ),
	'SetLabel' => array( 'LabelInstellen' ),
	'SetDescription' => array( 'BeschrijvingInstellen' ),
	'SetAliases' => array( 'AliassenInstellen' ),
	'EntitiesWithoutLabel' => array( 'EntiteitenZonderLabel' ),
);

/** Sicilian (sicilianu) */
$specialPageAliases['scn'] = array(
	'EntityData' => array( 'DatiEntità' ),
	'NewItem' => array( 'CreaElemento' ),
	'NewProperty' => array( 'NuovaProprietà' ),
	'ItemByTitle' => array( 'ElementiPerTitolo' ),
	'ItemDisambiguation' => array( 'ElementiDisambigui' ),
	'ListDatatypes' => array( 'ElencaTipiDati' ),
	'SetLabel' => array( 'ImpostaEtichetta' ),
	'EntitiesWithoutLabel' => array( 'EntitàSenzaEtichetta' ),
);

/** Vietnamese (Tiếng Việt) */
$specialPageAliases['vi'] = array(
	'EntityData' => array( 'Dữ_liệu_thực_thể' ),
	'NewItem' => array( 'Tạo_khoản_mục' ),
	'NewProperty' => array( 'Thuộc_tính_mới' ),
	'ItemDisambiguation' => array( 'Định_hướng_khoản_mục' ),
	'ListDatatypes' => array( 'Danh_sách_kiểu_dữ_liệu' ),
	'SetLabel' => array( 'Đặt_nhãn' ),
	'EntitiesWithoutLabel' => array( 'Thực_thể_không_nhãn' ),
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