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
	'DispatchStats' => array( 'DispatchStats' ),
	'EntityData' => array( 'EntityData' ),
	'NewItem' => array( 'NewItem', 'CreateItem' ),
	'NewProperty' => array( 'NewProperty', 'CreateProperty' ),
	'ItemByTitle' => array( 'ItemByTitle' ),
	'ItemDisambiguation' => array( 'ItemDisambiguation' ),
	'ListDatatypes' => array( 'ListDatatypes' ),
	'SetLabel' => array( 'SetLabel' ),
	'SetDescription' => array( 'SetDescription' ),
	'SetAliases' => array( 'SetAliases' ),
	'SetSiteLink' => array( 'SetSiteLink' ),
	'EntitiesWithoutLabel' => array( 'EntitiesWithoutLabel' ),
	'ItemsWithoutSitelinks' => array( 'ItemsWithoutSitelinks' ),
	'MyLanguageFallbackChain' => array( 'MyLanguageFallbackChain' ),
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
	'DispatchStats' => array( 'Abfertigungsstatistiken' ),
	'EntityData' => array( 'Objektdaten' ),
	'NewItem' => array( 'Neues_Datenelement_erstellen' ),
	'NewProperty' => array( 'Neues_Attribut_erstellen' ),
	'ItemByTitle' => array( 'Datenelement_nach_Name' ),
	'ItemDisambiguation' => array( 'Begriffsklärung_zu_Datenelement' ),
	'ListDatatypes' => array( 'Datentypen_auflisten' ),
	'SetLabel' => array( 'Bezeichnung_festlegen' ),
	'SetDescription' => array( 'Beschreibung_festlegen' ),
	'SetAliases' => array( 'Aliasse_festlegen' ),
	'SetSiteLink' => array( 'Websitelink_festlegen' ),
	'EntitiesWithoutLabel' => array( 'Objekte_ohne_Bezeichnung' ),
	'ItemsWithoutSitelinks' => array( 'Objekte_ohne_Websitelinks' ),
);

/** Zazaki (Zazaki) */
$specialPageAliases['diq'] = array(
	'NewItem' => array( 'LeteVırazé' ),
	'NewProperty' => array( 'XısusiyetéNewey' ),
	'ItemByTitle' => array( 'SernuşteyéLeti' ),
	'ItemDisambiguation' => array( 'EtiketéLeti' ),
	'ListDatatypes' => array( 'ListeyaBabetandeMelumati' ),
	'SetLabel' => array( 'SazéEtiketan' ),
);

/** Esperanto (Esperanto) */
$specialPageAliases['eo'] = array(
	'NewItem' => array( 'Nova_ero' ),
	'NewProperty' => array( 'Nova_eco' ),
	'ItemByTitle' => array( 'Eroj_laŭ_titolo' ),
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
	'ItemByTitle' => array( 'Hae_kohdetta_otsikolla' ),
	'ItemDisambiguation' => array( 'Kohteet_samalla_nimellä' ),
	'SetLabel' => array( 'Aseta_nimi' ),
	'SetDescription' => array( 'Aseta_kuvaus' ),
	'SetAliases' => array( 'Aseta_aliakset' ),
	'EntitiesWithoutLabel' => array( 'Aiheet_ilman_nimeä' ),
	'ItemsWithoutSitelinks' => array( 'Kohteet_ilman_sivustolinkkejä' ),
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
	'NewItem' => array( 'CreaElemento', 'NuovoElemento' ),
	'NewProperty' => array( 'NuovaProprietà' ),
	'ItemByTitle' => array( 'ElementiPerTitolo' ),
	'ItemDisambiguation' => array( 'ElementiDisambigui' ),
	'ListDatatypes' => array( 'ElencaTipiDati' ),
	'SetLabel' => array( 'ImpostaEtichetta' ),
	'SetDescription' => array( 'ImpostaDescrizione' ),
	'SetAliases' => array( 'ImpostaAlias' ),
	'EntitiesWithoutLabel' => array( 'EntitàSenzaEtichetta' ),
	'ItemsWithoutSitelinks' => array( 'ElementiSenzaSitelinks' ),
);

/** Japanese (日本語) */
$specialPageAliases['ja'] = array(
	'DispatchStats' => array( '発送統計' ),
	'EntityData' => array( '実体データ', 'エンティティデータ' ),
	'NewItem' => array( '新規項目' ),
	'NewProperty' => array( '新規プロパティ', '新規特性' ),
	'ItemByTitle' => array( 'タイトルから項目を探す' ),
	'ItemDisambiguation' => array( '項目の曖昧さ回避' ),
	'ListDatatypes' => array( 'データ型一覧' ),
	'SetLabel' => array( 'ラベルの設定' ),
	'SetDescription' => array( '説明の設定' ),
	'SetAliases' => array( '別名の設定' ),
	'SetSiteLink' => array( 'サイトリンクの設定' ),
	'EntitiesWithoutLabel' => array( 'ラベルのない実体', 'ラベルのないエンティティ' ),
	'ItemsWithoutSitelinks' => array( 'サイトリンクのない項目' ),
);

/** Korean (한국어) */
$specialPageAliases['ko'] = array(
	'DispatchStats' => array( '전송통계' ),
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
	'ItemsWithoutSitelinks' => array( '사이트링크없는항목' ),
);

/** Luxembourgish (Lëtzebuergesch) */
$specialPageAliases['lb'] = array(
	'DispatchStats' => array( 'Statistike_verbreeden' ),
	'NewItem' => array( 'Neit_Element', 'Element_uleeën' ),
	'NewProperty' => array( 'Eegeschaft_uleeën' ),
	'ItemByTitle' => array( 'Element_nom_Titel' ),
	'ItemDisambiguation' => array( 'Homonymie_vun_engem_Element' ),
	'ListDatatypes' => array( 'Lëscht_vun_Datentypen' ),
	'SetLabel' => array( 'Etiquette_festleeën' ),
	'SetDescription' => array( 'Beschreiwung_festleeën' ),
	'SetAliases' => array( 'Aliase_festleeën' ),
	'EntitiesWithoutLabel' => array( 'Elementer_ouni_Etiquette' ),
	'ItemsWithoutSitelinks' => array( 'Elementer_ouni_Weblinken' ),
);

/** Macedonian (македонски) */
$specialPageAliases['mk'] = array(
	'DispatchStats' => array( 'СтатистикиСпроведување' ),
	'EntityData' => array( 'ЕдиницаПодатоци' ),
	'NewItem' => array( 'СоздајПредмет' ),
	'NewProperty' => array( 'НовоСвојство' ),
	'ItemByTitle' => array( 'ПредметПоНаслов' ),
	'ItemDisambiguation' => array( 'ПојаснувањеНаПредмет' ),
	'ListDatatypes' => array( 'СписокПодаточниТипови' ),
	'SetLabel' => array( 'ЗадајЕтикета' ),
	'SetDescription' => array( 'ЗадајОпис' ),
	'SetAliases' => array( 'ЗадајАлијаси' ),
	'SetSiteLink' => array( 'ЗадајВикиврска' ),
	'EntitiesWithoutLabel' => array( 'ЕдинициБезЕтикета' ),
	'ItemsWithoutSitelinks' => array( 'ПредметиБезВикиврски' ),
);

/** Dutch (Nederlands) */
$specialPageAliases['nl'] = array(
	'DispatchStats' => array( 'Verwerkingsstatistieken' ),
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
	'ItemsWithoutSitelinks' => array( 'ItemsZonderSitekoppelingen' ),
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
	'DispatchStats' => array( 'Thống_kê_truyền_bá' ),
	'EntityData' => array( 'Dữ_liệu_thực_thể' ),
	'NewItem' => array( 'Tạo_khoản_mục' ),
	'NewProperty' => array( 'Thuộc_tính_mới' ),
	'ItemByTitle' => array( 'Khoản_mục_theo_tên' ),
	'ItemDisambiguation' => array( 'Định_hướng_khoản_mục' ),
	'ListDatatypes' => array( 'Danh_sách_kiểu_dữ_liệu' ),
	'SetLabel' => array( 'Đặt_nhãn' ),
	'SetDescription' => array( 'Đặt_miêu_tả', 'Đặt_mô_tả' ),
	'SetAliases' => array( 'Đặt_tên_khác' ),
	'EntitiesWithoutLabel' => array( 'Thực_thể_không_nhãn' ),
	'ItemsWithoutSitelinks' => array( 'Khoản_mục_không_có_liên_kết_site' ),
);

/** Simplified Chinese (中文（简体）‎) */
$specialPageAliases['zh-hans'] = array(
	'DispatchStats' => array( '派遣统计' ),
	'EntityData' => array( '实体数据' ),
	'NewItem' => array( '创建项目' ),
	'NewProperty' => array( '新建属性' ),
	'ItemByTitle' => array( '按标题搜索项目' ),
	'ItemDisambiguation' => array( '项目消歧义' ),
	'ListDatatypes' => array( '列出数据类型' ),
	'SetLabel' => array( '设置标签' ),
	'SetDescription' => array( '设置描述' ),
	'SetAliases' => array( '设置别名' ),
	'EntitiesWithoutLabel' => array( '无标签实体' ),
	'ItemsWithoutSitelinks' => array( '无站点链接项目' ),
);

/** Traditional Chinese (中文（繁體）‎) */
$specialPageAliases['zh-hant'] = array(
	'DispatchStats' => array( '派遣統計' ),
	'EntityData' => array( '實體數據' ),
	'NewItem' => array( '創建項目' ),
	'NewProperty' => array( '新建屬性' ),
	'ItemByTitle' => array( '按標題搜索項目' ),
	'ItemDisambiguation' => array( '項目消歧義' ),
	'ListDatatypes' => array( '列出數據類型' ),
	'SetLabel' => array( '設置標籤' ),
	'SetDescription' => array( '設置描述' ),
	'SetAliases' => array( '設置別名' ),
	'EntitiesWithoutLabel' => array( '沒有標籤的實體' ),
	'ItemsWithoutSitelinks' => array( '沒有條目連結的項目' ),
);
