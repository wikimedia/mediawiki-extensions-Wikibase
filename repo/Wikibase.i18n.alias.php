<?php

/**
 * Aliases for the special pages of the Wikibase extension.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
// @codingStandardsIgnoreFile

$specialPageAliases = array();

/** English (English) */
$specialPageAliases['en'] = array(
	'DispatchStats' => array( 'DispatchStats' ),
	'EntityData' => array( 'EntityData' ),
	'NewItem' => array( 'NewItem', 'CreateItem' ),
	'NewProperty' => array( 'NewProperty', 'CreateProperty' ),
	'ItemByTitle' => array( 'ItemByTitle' ),
	'GoToLinkedPage' => array( 'GoToLinkedPage' ),
	'ItemDisambiguation' => array( 'ItemDisambiguation' ),
	'ListDatatypes' => array( 'ListDatatypes' ),
	'SetLabel' => array( 'SetLabel' ),
	'SetDescription' => array( 'SetDescription' ),
	'SetAliases' => array( 'SetAliases' ),
	'SetSiteLink' => array( 'SetSiteLink' ),
	'MergeItems' => array( 'MergeItems' ),
	'EntitiesWithoutLabel' => array( 'EntitiesWithoutLabel' ),
	'EntitiesWithoutDescription' => array( 'EntitiesWithoutDescription' ),
	'ItemsWithoutSitelinks' => array( 'ItemsWithoutSitelinks' ),
	'MyLanguageFallbackChain' => array( 'MyLanguageFallbackChain' ),
);

/** Arabic (العربية) */
$specialPageAliases['ar'] = array(
	'DispatchStats' => array( 'إحصاءات_الوصول' ),
	'EntityData' => array( 'بيانات_الكيانات' ),
	'NewItem' => array( 'إنشاء_مدخلة' ),
	'NewProperty' => array( 'خاصية_جديدة' ),
	'ItemByTitle' => array( 'المدخلات_بالعنوان' ),
	'ItemDisambiguation' => array( 'المدخلات_بالعلامة' ),
	'ListDatatypes' => array( 'عرض_أنواع_البيانات' ),
	'SetLabel' => array( 'ضبط_العلامة' ),
	'SetDescription' => array( 'ضبط_الوصف' ),
	'SetAliases' => array( 'ضبط_الكنى' ),
	'SetSiteLink' => array( 'ضبط_وصلة_الموقع' ),
	'MergeItems' => array( 'دمج_المدخلات' ),
	'EntitiesWithoutLabel' => array( 'الكيانات_بدون_علامة' ),
	'EntitiesWithoutDescription' => array( 'الكيانات_بدون_وصف' ),
	'ItemsWithoutSitelinks' => array( 'المدخلات_بدون_وصلات_موقع' ),
	'MyLanguageFallbackChain' => array( 'سلسلة_رجوع_لغتي' ),
);

/** Aramaic (ܐܪܡܝܐ) */
$specialPageAliases['arc'] = array(
	'NewProperty' => array( 'ܕܝܠܝܘ̈ܬܐ_ܚܕ̈ܬܬܐ' ),
);

/** Egyptian Spoken Arabic (مصرى) */
$specialPageAliases['arz'] = array(
	'DispatchStats' => array( 'احصاءات_الوصول' ),
	'EntityData' => array( 'بيانات_الكيانات' ),
	'NewItem' => array( 'عمل_مدخله' ),
	'NewProperty' => array( 'خاصيه_جديده' ),
	'ItemByTitle' => array( 'المدخلات_بالعنوان' ),
	'ItemDisambiguation' => array( 'المدخلات_بالعلامه' ),
	'ListDatatypes' => array( 'عرض_انواع_البيانات' ),
	'SetLabel' => array( 'ضبط_العلامه' ),
	'SetDescription' => array( 'ضبط_الوصف' ),
	'SetAliases' => array( 'ضبط_الالياس' ),
	'SetSiteLink' => array( 'ضبط-وصله_الموقع' ),
	'MergeItems' => array( 'دمج_مدخلات' ),
	'EntitiesWithoutLabel' => array( 'الكيانات_من_غير_علامه' ),
	'EntitiesWithoutDescription' => array( 'الكيانات_من_غير_وصف' ),
	'ItemsWithoutSitelinks' => array( 'المدخلات_من_غير_وصلات_موقع' ),
	'MyLanguageFallbackChain' => array( 'سلسله_رجوع_اللغه_بتاعتى' ),
);

/** буряад (буряад) */
$specialPageAliases['bxr'] = array(
	'NewItem' => array( 'Зүйл_үүсхэхэ' ),
	'NewProperty' => array( 'Шэнэ_шэнжэ_шанар' ),
	'ItemByTitle' => array( 'Нэрээр_жагсааха' ),
	'ItemDisambiguation' => array( 'Дэлгэрэнгы_нэрэ' ),
);

/** Min Dong Chinese (Mìng-dĕ̤ng-ngṳ̄) */
$specialPageAliases['cdo'] = array(
	'DispatchStats' => array( '特派統計' ),
	'EntityData' => array( '條目數據' ),
	'NewItem' => array( '新其單單', '創建單單' ),
	'NewProperty' => array( '新其屬性', '創建屬性' ),
	'ItemByTitle' => array( '標題其單單' ),
	'ItemDisambiguation' => array( '消除歧義其單單' ),
	'ListDatatypes' => array( '數據類型其單單' ),
	'SetLabel' => array( '設置標籤' ),
	'SetDescription' => array( '設置描述' ),
	'SetAliases' => array( '設置同義詞' ),
	'SetSiteLink' => array( '設置站點鏈接' ),
	'EntitiesWithoutLabel' => array( '無標籤其條目' ),
	'ItemsWithoutSitelinks' => array( '無站點鏈接其條目' ),
	'MyLanguageFallbackChain' => array( '我其語言鏈' ),
);

/** German (Deutsch) */
$specialPageAliases['de'] = array(
	'DispatchStats' => array( 'Abfertigungsstatistiken' ),
	'EntityData' => array( 'Objektdaten' ),
	'NewItem' => array( 'Neues_Datenelement_erstellen' ),
	'NewProperty' => array( 'Neues_Attribut_erstellen' ),
	'ItemByTitle' => array( 'Datenelement_nach_Name' ),
	'GoToLinkedPage' => array( 'Gehe_zur_verlinkten_Seite' ),
	'ItemDisambiguation' => array( 'Begriffsklärung_zu_Datenelement' ),
	'ListDatatypes' => array( 'Datentypen_auflisten' ),
	'SetLabel' => array( 'Bezeichnung_festlegen' ),
	'SetDescription' => array( 'Beschreibung_festlegen' ),
	'SetAliases' => array( 'Aliasse_festlegen' ),
	'SetSiteLink' => array( 'Websitelink_festlegen' ),
	'MergeItems' => array( 'Objekte_zusammenführen' ),
	'EntitiesWithoutLabel' => array( 'Objekte_ohne_Bezeichnung' ),
	'EntitiesWithoutDescription' => array( 'Objekte_ohne_Beschreibung' ),
	'ItemsWithoutSitelinks' => array( 'Objekte_ohne_Websitelinks' ),
	'MyLanguageFallbackChain' => array( 'Meine_Alternativsprachenabfolge' ),
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
	'SetDescription' => array( 'DefinirDescripción' ),
	'SetAliases' => array( 'DefinirAlias' ),
	'SetSiteLink' => array( 'DefinirEnlaceSitio' ),
	'MergeItems' => array( 'CombinarElementos' ),
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
	'DispatchStats' => array( 'StatistichePropagazione' ),
	'EntityData' => array( 'DatiEntità' ),
	'NewItem' => array( 'CreaElemento', 'NuovoElemento' ),
	'NewProperty' => array( 'NuovaProprietà' ),
	'ItemByTitle' => array( 'ElementiPerTitolo' ),
	'ItemDisambiguation' => array( 'ElementiDisambigui' ),
	'ListDatatypes' => array( 'ElencaTipiDati' ),
	'SetLabel' => array( 'ImpostaEtichetta' ),
	'SetDescription' => array( 'ImpostaDescrizione' ),
	'SetAliases' => array( 'ImpostaAlias' ),
	'SetSiteLink' => array( 'ImpostaSitelink' ),
	'MergeItems' => array( 'UnisciElementi' ),
	'EntitiesWithoutLabel' => array( 'EntitàSenzaEtichetta' ),
	'EntitiesWithoutDescription' => array( 'EntitàSenzaDescrizione' ),
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
	'EntitiesWithoutDescription' => array( '説明のない実体', '説明のないエンティティ' ),
	'ItemsWithoutSitelinks' => array( 'サイトリンクのない項目' ),
);

/** Korean (한국어) */
$specialPageAliases['ko'] = array(
	'DispatchStats' => array( '전송통계' ),
	'EntityData' => array( '개체데이터' ),
	'NewItem' => array( '새항목', '항목만들기' ),
	'NewProperty' => array( '새속성', '속성만들기' ),
	'ItemByTitle' => array( '제목별항목' ),
	'ItemDisambiguation' => array( '레이블별항목', '라벨별항목' ),
	'ListDatatypes' => array( '데이터유형목록' ),
	'SetLabel' => array( '레이블설정' ),
	'SetDescription' => array( '설명설정' ),
	'SetAliases' => array( '별칭설정' ),
	'SetSiteLink' => array( '사이트링크설정' ),
	'MergeItems' => array( '항목병합' ),
	'EntitiesWithoutLabel' => array( '레이블없는개체' ),
	'EntitiesWithoutDescription' => array( '설명없는개체' ),
	'ItemsWithoutSitelinks' => array( '사이트링크없는개체' ),
	'MyLanguageFallbackChain' => array( '내언어폴백체인' ),
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
	'MergeItems' => array( 'Elementer_fusionéieren' ),
	'EntitiesWithoutLabel' => array( 'Elementer_ouni_Etiquette' ),
	'EntitiesWithoutDescription' => array( 'Elementer_ouni_Beschreiwung' ),
	'ItemsWithoutSitelinks' => array( 'Elementer_ouni_Weblinken' ),
	'MyLanguageFallbackChain' => array( 'Meng_Ersatzsproochketten' ),
);

/** Macedonian (македонски) */
$specialPageAliases['mk'] = array(
	'DispatchStats' => array( 'СтатистикиСпроведување' ),
	'EntityData' => array( 'ЕдиницаПодатоци' ),
	'NewItem' => array( 'СоздајПредмет' ),
	'NewProperty' => array( 'НовоСвојство' ),
	'ItemByTitle' => array( 'ПредметПоНаслов' ),
	'GoToLinkedPage' => array( 'ОдиНаСврзанаСтраница' ),
	'ItemDisambiguation' => array( 'ПојаснувањеНаПредмет' ),
	'ListDatatypes' => array( 'СписокПодаточниТипови' ),
	'SetLabel' => array( 'ЗадајНатпис' ),
	'SetDescription' => array( 'ЗадајОпис' ),
	'SetAliases' => array( 'ЗадајАлијаси' ),
	'SetSiteLink' => array( 'ЗадајВикиврска' ),
	'MergeItems' => array( 'СпојПредмети' ),
	'EntitiesWithoutLabel' => array( 'ЕдинициБезНатпис' ),
	'EntitiesWithoutDescription' => array( 'ЕдинициБезОпис' ),
	'ItemsWithoutSitelinks' => array( 'ПредметиБезВикиврски' ),
	'MyLanguageFallbackChain' => array( 'МојЛанецНаРезервниЈазици' ),
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
	'SetSiteLink' => array( 'SitekoppelingInstellen' ),
	'MergeItems' => array( 'ItemsSamenvoegen' ),
	'EntitiesWithoutLabel' => array( 'EntiteitenZonderLabel' ),
	'EntitiesWithoutDescription' => array( 'EntiteitenZonderBeschrijving' ),
	'ItemsWithoutSitelinks' => array( 'ItemsZonderSitekoppelingen' ),
);

/** Polish (polski) */
$specialPageAliases['pl'] = array(
	'EntitiesWithoutLabel' => array( 'Encje_bez_etykiety' ),
	'EntitiesWithoutDescription' => array( 'Encje_bez_opisu' ),
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

/** Swedish (svenska) */
$specialPageAliases['sv'] = array(
	'EntityData' => array( 'Objektdata' ),
	'NewItem' => array( 'Nytt_objekt', 'Skapa_objekt' ),
	'NewProperty' => array( 'Ny_egenskap', 'Skapa_egenskap' ),
	'ItemByTitle' => array( 'Objekt_efter_titel' ),
	'ItemDisambiguation' => array( 'Objektsärskiljning' ),
	'ListDatatypes' => array( 'Lista_datatyper' ),
	'SetLabel' => array( 'Ange_etikett' ),
	'SetDescription' => array( 'Ange_beskrivning' ),
	'SetAliases' => array( 'Ange_alias' ),
	'SetSiteLink' => array( 'Ange_webbplatslänk' ),
	'MergeItems' => array( 'Slå_ihop_objekt' ),
	'EntitiesWithoutLabel' => array( 'Objekt_utan_etikett' ),
	'EntitiesWithoutDescription' => array( 'Objekt_utan_beskrivning' ),
	'ItemsWithoutSitelinks' => array( 'Objekt_utan_webbplatslänk' ),
	'MyLanguageFallbackChain' => array( 'Min_språkåterfallskedja' ),
);

/** Vietnamese (Tiếng Việt) */
$specialPageAliases['vi'] = array(
	'DispatchStats' => array( 'Thống_kê_truyền_bá' ),
	'EntityData' => array( 'Dữ_liệu_thực_thể' ),
	'NewItem' => array( 'Tạo_khoản_mục' ),
	'NewProperty' => array( 'Thuộc_tính_mới' ),
	'ItemByTitle' => array( 'Khoản_mục_theo_tên' ),
	'GoToLinkedPage' => array( 'Đi_đến_trang_liên_kết' ),
	'ItemDisambiguation' => array( 'Định_hướng_khoản_mục' ),
	'ListDatatypes' => array( 'Danh_sách_kiểu_dữ_liệu' ),
	'SetLabel' => array( 'Đặt_nhãn' ),
	'SetDescription' => array( 'Đặt_miêu_tả', 'Đặt_mô_tả' ),
	'SetAliases' => array( 'Đặt_tên_khác' ),
	'SetSiteLink' => array( 'Đặt_liên_kết_dịch_vụ' ),
	'MergeItems' => array( 'Hợp_nhất_khoản_mục', 'Gộp_khoản_mục' ),
	'EntitiesWithoutLabel' => array( 'Thực_thể_không_nhãn' ),
	'EntitiesWithoutDescription' => array( 'Thực_thể_không_miêu_tả' ),
	'ItemsWithoutSitelinks' => array( 'Khoản_mục_không_có_liên_kết_dịch_vụ', 'Khoản_mục_không_có_liên_kết_site' ),
	'MyLanguageFallbackChain' => array( 'Chuỗi_ngôn_ngữ_thay_thế_của_tôi' ),
);

/** Simplified Chinese (中文（简体）‎) */
$specialPageAliases['zh-hans'] = array(
	'DispatchStats' => array( '发送统计' ),
	'EntityData' => array( '实体数据' ),
	'NewItem' => array( '创建项' ),
	'NewProperty' => array( '新属性' ),
	'ItemByTitle' => array( '项按标题' ),
	'GoToLinkedPage' => array( '前往已链接页面' ),
	'ItemDisambiguation' => array( '项消歧义' ),
	'ListDatatypes' => array( '数据类型列表' ),
	'SetLabel' => array( '设置标签' ),
	'SetDescription' => array( '设置说明' ),
	'SetAliases' => array( '设置别名' ),
	'SetSiteLink' => array( '设置网站链接' ),
	'MergeItems' => array( '合并项' ),
	'EntitiesWithoutLabel' => array( '无标签实体' ),
	'EntitiesWithoutDescription' => array( '无说明实体' ),
	'ItemsWithoutSitelinks' => array( '无网站链接项' ),
	'MyLanguageFallbackChain' => array( '我的语言备选链' ),
);

/** Traditional Chinese (中文（繁體）‎) */
$specialPageAliases['zh-hant'] = array(
	'DispatchStats' => array( '發佈統計' ),
	'EntityData' => array( '實體資料' ),
	'NewItem' => array( '建立項目' ),
	'NewProperty' => array( '新增屬性' ),
	'ItemByTitle' => array( '依標題搜尋項目' ),
	'GoToLinkedPage' => array( '前往已連結頁面' ),
	'ItemDisambiguation' => array( '項目消歧義' ),
	'ListDatatypes' => array( '資料型態清單' ),
	'SetLabel' => array( '設定標籤' ),
	'SetDescription' => array( '設定描述' ),
	'SetAliases' => array( '設定別名' ),
	'SetSiteLink' => array( '設定網站連結' ),
	'MergeItems' => array( '合併項目' ),
	'EntitiesWithoutLabel' => array( '無標籤實體' ),
	'EntitiesWithoutDescription' => array( '無類型實體' ),
	'ItemsWithoutSitelinks' => array( '無網站連結項目' ),
	'MyLanguageFallbackChain' => array( '我的備用語言鏈' ),
);

/** Chinese (Taiwan) (中文（台灣）‎) */
$specialPageAliases['zh-tw'] = array(
	'NewItem' => array( '建立項目' ),
	'NewProperty' => array( '新增屬性' ),
);