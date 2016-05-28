<?php

/**
 * Aliases for the special pages of the Wikibase Repository extension.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
// @codingStandardsIgnoreFile

$specialPageAliases = array();

/** English (English) */
$specialPageAliases['en'] = array(
	'DispatchStats' => array( 'DispatchStats' ),
	'EntitiesWithoutDescription' => array( 'EntitiesWithoutDescription' ),
	'EntitiesWithoutLabel' => array( 'EntitiesWithoutLabel' ),
	'EntityData' => array( 'EntityData' ),
	'GoToLinkedPage' => array( 'GoToLinkedPage' ),
	'ItemByTitle' => array( 'ItemByTitle' ),
	'ItemDisambiguation' => array( 'ItemDisambiguation' ),
	'ItemsWithoutSitelinks' => array( 'ItemsWithoutSitelinks' ),
	'ListDatatypes' => array( 'ListDatatypes' ),
	'ListProperties' => array( 'ListProperties' ),
	'MergeItems' => array( 'MergeItems', 'MergeItem' ),
	'MyLanguageFallbackChain' => array( 'MyLanguageFallbackChain' ),
	'NewItem' => array( 'NewItem', 'CreateItem' ),
	'NewProperty' => array( 'NewProperty', 'CreateProperty' ),
	'RedirectEntity' => array( 'RedirectEntity', 'EntityRedirect', 'ItemRedirect', 'RedirectItem' ),
	'SetAliases' => array( 'SetAliases' ),
	'SetDescription' => array( 'SetDescription' ),
	'SetLabel' => array( 'SetLabel' ),
	'SetLabelDescriptionAliases' => array( 'SetLabelDescriptionAliases' ),
	'SetSiteLink' => array( 'SetSiteLink' ),
);

/** Arabic (العربية) */
$specialPageAliases['ar'] = array(
	'DispatchStats' => array( 'إحصاءات_الوصول' ),
	'EntitiesWithoutDescription' => array( 'الكيانات_بدون_وصف' ),
	'EntitiesWithoutLabel' => array( 'الكيانات_بدون_علامة' ),
	'EntityData' => array( 'بيانات_الكيانات' ),
	'GoToLinkedPage' => array( 'الذهاب_للصفحة_الموصولة' ),
	'ItemByTitle' => array( 'المدخلات_بالعنوان' ),
	'ItemDisambiguation' => array( 'المدخلات_بالعلامة' ),
	'ItemsWithoutSitelinks' => array( 'المدخلات_بدون_وصلات_موقع' ),
	'ListDatatypes' => array( 'عرض_أنواع_البيانات' ),
	'MergeItems' => array( 'دمج_المدخلات' ),
	'MyLanguageFallbackChain' => array( 'سلسلة_رجوع_لغتي' ),
	'NewItem' => array( 'إنشاء_مدخلة' ),
	'NewProperty' => array( 'خاصية_جديدة' ),
	'RedirectEntity' => array( 'كينونة_تحويل', 'مدخلة_تحويل' ),
	'SetAliases' => array( 'ضبط_الكنى' ),
	'SetDescription' => array( 'ضبط_الوصف' ),
	'SetLabel' => array( 'ضبط_العلامة' ),
	'SetLabelDescriptionAliases' => array( 'ضبط_كنى_وصف_العلامات' ),
	'SetSiteLink' => array( 'ضبط_وصلة_الموقع' ),
);

/** Aramaic (ܐܪܡܝܐ) */
$specialPageAliases['arc'] = array(
	'NewProperty' => array( 'ܕܝܠܝܘ̈ܬܐ_ܚܕ̈ܬܬܐ' ),
);

/** Egyptian Arabic (مصرى) */
$specialPageAliases['arz'] = array(
	'DispatchStats' => array( 'احصاءات_الوصول' ),
	'EntitiesWithoutDescription' => array( 'الكيانات_من_غير_وصف' ),
	'EntitiesWithoutLabel' => array( 'الكيانات_من_غير_علامه' ),
	'EntityData' => array( 'بيانات_الكيانات' ),
	'ItemByTitle' => array( 'المدخلات_بالعنوان' ),
	'ItemDisambiguation' => array( 'المدخلات_بالعلامه' ),
	'ItemsWithoutSitelinks' => array( 'المدخلات_من_غير_وصلات_موقع' ),
	'ListDatatypes' => array( 'عرض_انواع_البيانات' ),
	'MergeItems' => array( 'دمج_مدخلات' ),
	'MyLanguageFallbackChain' => array( 'سلسله_رجوع_اللغه_بتاعتى' ),
	'NewItem' => array( 'عمل_مدخله' ),
	'NewProperty' => array( 'خاصيه_جديده' ),
	'SetAliases' => array( 'ضبط_الالياس' ),
	'SetDescription' => array( 'ضبط_الوصف' ),
	'SetLabel' => array( 'ضبط_العلامه' ),
	'SetSiteLink' => array( 'ضبط-وصله_الموقع' ),
);

/** буряад (буряад) */
$specialPageAliases['bxr'] = array(
	'ItemByTitle' => array( 'Нэрээр_жагсааха' ),
	'ItemDisambiguation' => array( 'Дэлгэрэнгы_нэрэ' ),
	'NewItem' => array( 'Зүйл_үүсхэхэ' ),
	'NewProperty' => array( 'Шэнэ_шэнжэ_шанар' ),
);

/** Min Dong Chinese (Mìng-dĕ̤ng-ngṳ̄) */
$specialPageAliases['cdo'] = array(
	'DispatchStats' => array( '特派統計' ),
	'EntitiesWithoutLabel' => array( '無標籤其條目' ),
	'EntityData' => array( '條目數據' ),
	'ItemByTitle' => array( '標題其單單' ),
	'ItemDisambiguation' => array( '消除歧義其單單' ),
	'ItemsWithoutSitelinks' => array( '無站點鏈接其條目' ),
	'ListDatatypes' => array( '數據類型其單單' ),
	'MyLanguageFallbackChain' => array( '我其語言鏈' ),
	'NewItem' => array( '新其單單', '創建單單' ),
	'NewProperty' => array( '新其屬性', '創建屬性' ),
	'SetAliases' => array( '設置同義詞' ),
	'SetDescription' => array( '設置描述' ),
	'SetLabel' => array( '設置標籤' ),
	'SetSiteLink' => array( '設置站點鏈接' ),
);

/** Czech (česky) */
$specialPageAliases['cs'] = array(
	'DispatchStats' => array( 'Statistiky_distribuce' ),
	'EntitiesWithoutDescription' => array( 'Entity_bez_popisku' ),
	'EntitiesWithoutLabel' => array( 'Entity_bez_štítku' ),
	'EntityData' => array( 'Data_entity' ),
	'GoToLinkedPage' => array( 'Jít_na_odkazovanou_stránku' ),
	'ItemByTitle' => array( 'Položka_podle_názvu' ),
	'ItemDisambiguation' => array( 'Rozcestník_položek' ),
	'ItemsWithoutSitelinks' => array( 'Položky_bez_odkazů' ),
	'ListDatatypes' => array( 'Seznam_datových_typů' ),
	'ListProperties' => array( 'Seznam_vlastností' ),
	'MergeItems' => array( 'Sloučit_položky' ),
	'MyLanguageFallbackChain' => array( 'Můj_řetězec_záložních_jazkyů' ),
	'NewItem' => array( 'Vytvořit_položku', 'Nová_položka' ),
	'NewProperty' => array( 'Vytvořit_vlastnost', 'Nová_vlastnost' ),
	'RedirectEntity' => array( 'Přesměrovat_entitu' ),
	'SetAliases' => array( 'Nastavit_aliasy', 'Přidat_aliasy' ),
	'SetDescription' => array( 'Nastavit_popisek', 'Přidat_popisek' ),
	'SetLabel' => array( 'Nastavit_štítek', 'Přidat_štítek' ),
	'SetLabelDescriptionAliases' => array( 'Nastavit_štítek_popisek_nebo_aliasy' ),
	'SetSiteLink' => array( 'Nastavit_odkaz_na_článek', 'Přidat_odkaz_na_článek' ),
);

/** German (Deutsch) */
$specialPageAliases['de'] = array(
	'DispatchStats' => array( 'Abfertigungsstatistiken' ),
	'EntitiesWithoutDescription' => array( 'Objekte_ohne_Beschreibung' ),
	'EntitiesWithoutLabel' => array( 'Objekte_ohne_Bezeichnung' ),
	'EntityData' => array( 'Objektdaten' ),
	'GoToLinkedPage' => array( 'Gehe_zur_verlinkten_Seite' ),
	'ItemByTitle' => array( 'Datenelement_nach_Name' ),
	'ItemDisambiguation' => array( 'Begriffsklärung_zu_Datenelement' ),
	'ItemsWithoutSitelinks' => array( 'Objekte_ohne_Websitelinks' ),
	'ListDatatypes' => array( 'Datentypen_auflisten' ),
	'MergeItems' => array( 'Objekte_zusammenführen' ),
	'MyLanguageFallbackChain' => array( 'Meine_Alternativsprachenabfolge' ),
	'NewItem' => array( 'Neues_Datenelement_erstellen' ),
	'NewProperty' => array( 'Neues_Attribut_erstellen' ),
	'SetAliases' => array( 'Aliasse_festlegen' ),
	'SetDescription' => array( 'Beschreibung_festlegen' ),
	'SetLabel' => array( 'Bezeichnung_festlegen' ),
	'SetLabelDescriptionAliases' => array( 'Bezeichnung_Beschreibung_oder_Aliasse_festlegen' ),
	'SetSiteLink' => array( 'Websitelink_festlegen' ),
);

/** Zazaki (Zazaki) */
$specialPageAliases['diq'] = array(
	'ItemByTitle' => array( 'SernuşteyêLeteyi' ),
	'ItemDisambiguation' => array( 'EtiketêLeteyi' ),
	'ListDatatypes' => array( 'ListaBabetanêMelumati' ),
	'NewItem' => array( 'LeteVırazên' ),
	'NewProperty' => array( 'XısusiyetêNeweyi' ),
	'SetLabel' => array( 'SazêEtiketan' ),
);

/** Esperanto (Esperanto) */
$specialPageAliases['eo'] = array(
	'ItemByTitle' => array( 'Eroj_laŭ_titolo' ),
	'NewItem' => array( 'Nova_ero' ),
	'NewProperty' => array( 'Nova_eco' ),
);

/** Spanish (español) */
$specialPageAliases['es'] = array(
	'EntitiesWithoutDescription' => array( 'EntidadesSinDescripción' ),
	'EntitiesWithoutLabel' => array( 'EntidadesSinEtiqueta' ),
	'EntityData' => array( 'DatosDeEntidad' ),
	'ItemByTitle' => array( 'ElementoPorTítulo' ),
	'ItemDisambiguation' => array( 'DesambiguaciónDeElementos' ),
	'ItemsWithoutSitelinks' => array( 'ElementosSinEnlaces' ),
	'ListDatatypes' => array( 'ListarTiposDeDatos' ),
	'MergeItems' => array( 'CombinarElementos' ),
	'NewItem' => array( 'CrearElemento' ),
	'NewProperty' => array( 'NuevaPropiedad' ),
	'SetAliases' => array( 'DefinirAlias' ),
	'SetDescription' => array( 'DefinirDescripción' ),
	'SetLabel' => array( 'AsignarEtiqueta' ),
	'SetSiteLink' => array( 'DefinirEnlaceSitio' ),
);

/** Finnish (suomi) */
$specialPageAliases['fi'] = array(
	'EntitiesWithoutDescription' => array( 'Aiheet_ilman_kuvausta' ),
	'EntitiesWithoutLabel' => array( 'Aiheet_ilman_nimeä' ),
	'ItemByTitle' => array( 'Hae_kohdetta_otsikolla' ),
	'ItemDisambiguation' => array( 'Kohteet_samalla_nimellä' ),
	'ItemsWithoutSitelinks' => array( 'Kohteet_ilman_sivustolinkkejä' ),
	'MergeItems' => array( 'Yhdistä_kohteita' ),
	'NewItem' => array( 'Uusi_kohde' ),
	'NewProperty' => array( 'Uusi_ominaisuus' ),
	'SetAliases' => array( 'Aseta_aliakset' ),
	'SetDescription' => array( 'Aseta_kuvaus' ),
	'SetLabel' => array( 'Aseta_nimi' ),
	'SetSiteLink' => array( 'Aseta_sivustolinkki' ),
);

/** Icelandic (íslenska) */
$specialPageAliases['is'] = array(
	'EntitiesWithoutLabel' => array( 'Færslur_án_merkimiða' ),
	'ItemByTitle' => array( 'Hlutur_eftir_nafni' ),
	'ItemDisambiguation' => array( 'Hlutur_eftir_merkimiða' ),
	'ListDatatypes' => array( 'Gagnagerðir' ),
	'NewItem' => array( 'Búa_til_hlut' ),
	'NewProperty' => array( 'Ný_staðhæfing' ),
	'SetLabel' => array( 'Setja_merkimiða' ),
);

/** Italian (italiano) */
$specialPageAliases['it'] = array(
	'DispatchStats' => array( 'StatistichePropagazione' ),
	'EntitiesWithoutDescription' => array( 'EntitàSenzaDescrizione' ),
	'EntitiesWithoutLabel' => array( 'EntitàSenzaEtichetta' ),
	'EntityData' => array( 'DatiEntità' ),
	'ItemByTitle' => array( 'ElementiPerTitolo' ),
	'ItemDisambiguation' => array( 'ElementiDisambigui' ),
	'ItemsWithoutSitelinks' => array( 'ElementiSenzaSitelinks' ),
	'ListDatatypes' => array( 'ElencaTipiDati' ),
	'MergeItems' => array( 'UnisciElementi' ),
	'NewItem' => array( 'CreaElemento', 'NuovoElemento' ),
	'NewProperty' => array( 'NuovaProprietà' ),
	'SetAliases' => array( 'ImpostaAlias' ),
	'SetDescription' => array( 'ImpostaDescrizione' ),
	'SetLabel' => array( 'ImpostaEtichetta' ),
	'SetSiteLink' => array( 'ImpostaSitelink' ),
);

/** Japanese (日本語) */
$specialPageAliases['ja'] = array(
	'DispatchStats' => array( '発送統計' ),
	'EntitiesWithoutDescription' => array( '説明のない実体', '説明のないエンティティ' ),
	'EntitiesWithoutLabel' => array( 'ラベルのない実体', 'ラベルのないエンティティ' ),
	'EntityData' => array( '実体データ', 'エンティティデータ' ),
	'ItemByTitle' => array( 'タイトルから項目を探す' ),
	'ItemDisambiguation' => array( '項目の曖昧さ回避' ),
	'ItemsWithoutSitelinks' => array( 'サイトリンクのない項目' ),
	'ListDatatypes' => array( 'データ型一覧' ),
	'NewItem' => array( '新規項目' ),
	'NewProperty' => array( '新規プロパティ', '新規特性' ),
	'SetAliases' => array( '別名の設定' ),
	'SetDescription' => array( '説明の設定' ),
	'SetLabel' => array( 'ラベルの設定' ),
	'SetSiteLink' => array( 'サイトリンクの設定' ),
);

/** Korean (한국어) */
$specialPageAliases['ko'] = array(
	'DispatchStats' => array( '전송통계' ),
	'EntitiesWithoutDescription' => array( '설명없는개체' ),
	'EntitiesWithoutLabel' => array( '레이블없는개체' ),
	'EntityData' => array( '개체데이터' ),
	'GoToLinkedPage' => array( '링크된문서로가기', '링크된문서로이동' ),
	'ItemByTitle' => array( '제목별항목' ),
	'ItemDisambiguation' => array( '레이블별항목', '라벨별항목' ),
	'ItemsWithoutSitelinks' => array( '사이트링크없는개체' ),
	'ListDatatypes' => array( '데이터유형목록' ),
	'MergeItems' => array( '항목병합' ),
	'MyLanguageFallbackChain' => array( '내언어폴백체인' ),
	'NewItem' => array( '새항목', '항목만들기' ),
	'NewProperty' => array( '새속성', '속성만들기' ),
	'SetAliases' => array( '별칭설정' ),
	'SetDescription' => array( '설명설정' ),
	'SetLabel' => array( '레이블설정' ),
	'SetSiteLink' => array( '사이트링크설정' ),
);

/** Luxembourgish (Lëtzebuergesch) */
$specialPageAliases['lb'] = array(
	'DispatchStats' => array( 'Statistike_verbreeden' ),
	'EntitiesWithoutDescription' => array( 'Elementer_ouni_Beschreiwung' ),
	'EntitiesWithoutLabel' => array( 'Elementer_ouni_Etiquette' ),
	'ItemByTitle' => array( 'Element_nom_Titel' ),
	'ItemDisambiguation' => array( 'Homonymie_vun_engem_Element' ),
	'ItemsWithoutSitelinks' => array( 'Elementer_ouni_Weblinken' ),
	'ListDatatypes' => array( 'Lëscht_vun_Datentypen' ),
	'MergeItems' => array( 'Elementer_fusionéieren' ),
	'MyLanguageFallbackChain' => array( 'Meng_Ersatzsproochketten' ),
	'NewItem' => array( 'Neit_Element', 'Element_uleeën' ),
	'NewProperty' => array( 'Eegeschaft_uleeën' ),
	'SetAliases' => array( 'Aliase_festleeën' ),
	'SetDescription' => array( 'Beschreiwung_festleeën' ),
	'SetLabel' => array( 'Etiquette_festleeën' ),
);

/** Northern Luri (لۊری شومالی) */
$specialPageAliases['lrc'] = array(
	'EntitiesWithoutDescription' => array( 'چیایی_کە_توضی_نارئن' ),
	'EntitiesWithoutLabel' => array( 'چیایی_کە_ریتئراز_نارئن' ),
	'ItemsWithoutSitelinks' => array( 'چیایی_کئ_ھوم_پئیڤأند_دیارگە_نارئن' ),
	'ListDatatypes' => array( 'میزوٙنکاری_جوٙر_دادە_یا' ),
	'MergeItems' => array( 'سأریأک_سازی_چیا' ),
	'MyLanguageFallbackChain' => array( 'زأنجیرە_دئماکاری_زوٙن_مئ' ),
	'SetAliases' => array( 'میزوٙنکاری_ھوم_قأطاریا' ),
	'SetDescription' => array( 'میزوٙنکاری_توضی' ),
	'SetLabel' => array( 'میزوٙنکاری_ریتئراز' ),
	'SetLabelDescriptionAliases' => array( 'میزوٙنکاری_نیائن_ریتئراز_توضی_سی_ھوم_قأطاریا' ),
	'SetSiteLink' => array( 'میزوٙنکاری_ھوم_پئیڤأند_دیارگھ' ),
);

/** Macedonian (македонски) */
$specialPageAliases['mk'] = array(
	'DispatchStats' => array( 'СтатистикиСпроведување' ),
	'EntitiesWithoutDescription' => array( 'ЕдинициБезОпис' ),
	'EntitiesWithoutLabel' => array( 'ЕдинициБезНатпис' ),
	'EntityData' => array( 'ЕдиницаПодатоци' ),
	'GoToLinkedPage' => array( 'ОдиНаСврзанаСтраница' ),
	'ItemByTitle' => array( 'ПредметПоНаслов' ),
	'ItemDisambiguation' => array( 'ПојаснувањеНаПредмет' ),
	'ItemsWithoutSitelinks' => array( 'ПредметиБезВикиврски' ),
	'ListDatatypes' => array( 'СписокПодаточниТипови' ),
	'MergeItems' => array( 'СпојПредмети' ),
	'MyLanguageFallbackChain' => array( 'МојЛанецНаРезервниЈазици' ),
	'NewItem' => array( 'СоздајПредмет' ),
	'NewProperty' => array( 'НовоСвојство' ),
	'SetAliases' => array( 'ЗадајАлијаси' ),
	'SetDescription' => array( 'ЗадајОпис' ),
	'SetLabel' => array( 'ЗадајНатпис' ),
	'SetLabelDescriptionAliases' => array( 'ЗадајАлијасиОписНатпис' ),
	'SetSiteLink' => array( 'ЗадајВикиврска' ),
);

/** Dutch (Nederlands) */
$specialPageAliases['nl'] = array(
	'DispatchStats' => array( 'Verwerkingsstatistieken' ),
	'EntitiesWithoutDescription' => array( 'EntiteitenZonderBeschrijving' ),
	'EntitiesWithoutLabel' => array( 'EntiteitenZonderLabel' ),
	'EntityData' => array( 'Entiteitsgegevens' ),
	'GoToLinkedPage' => array( 'NaarGekoppeldePaginaGaan' ),
	'ItemByTitle' => array( 'ItemPerTitel' ),
	'ItemDisambiguation' => array( 'ItemPerLabel' ),
	'ItemsWithoutSitelinks' => array( 'ItemsZonderSitekoppelingen' ),
	'ListDatatypes' => array( 'GegevenstypenWeergeven' ),
	'ListProperties' => array( 'EigenschappenWeergeven' ),
	'MergeItems' => array( 'ItemsSamenvoegen' ),
	'NewItem' => array( 'ItemAanmaken' ),
	'NewProperty' => array( 'NieuweEigenschap' ),
	'RedirectEntity' => array( 'EntiteitDoorverwijzen', 'ItemDoorverwijzen' ),
	'SetAliases' => array( 'AliassenInstellen' ),
	'SetDescription' => array( 'BeschrijvingInstellen' ),
	'SetLabel' => array( 'LabelInstellen' ),
	'SetLabelDescriptionAliases' => array( 'LabelbeschrijvingsaliassenInstellen' ),
	'SetSiteLink' => array( 'SitekoppelingInstellen' ),
);

/** Polish (polski) */
$specialPageAliases['pl'] = array(
	'EntitiesWithoutDescription' => array( 'Encje_bez_opisu' ),
	'EntitiesWithoutLabel' => array( 'Encje_bez_etykiety' ),
);

/** Sicilian (sicilianu) */
$specialPageAliases['scn'] = array(
	'EntitiesWithoutLabel' => array( 'EntitàSenzaEtichetta' ),
	'EntityData' => array( 'DatiEntità' ),
	'ItemByTitle' => array( 'ElementiPerTitolo' ),
	'ItemDisambiguation' => array( 'ElementiDisambigui' ),
	'ListDatatypes' => array( 'ElencaTipiDati' ),
	'NewItem' => array( 'CreaElemento' ),
	'NewProperty' => array( 'NuovaProprietà' ),
	'SetLabel' => array( 'ImpostaEtichetta' ),
);

/** Swedish (svenska) */
$specialPageAliases['sv'] = array(
	'EntitiesWithoutDescription' => array( 'Objekt_utan_beskrivning' ),
	'EntitiesWithoutLabel' => array( 'Objekt_utan_etikett' ),
	'EntityData' => array( 'Objektdata' ),
	'ItemByTitle' => array( 'Objekt_efter_titel' ),
	'ItemDisambiguation' => array( 'Objektsärskiljning' ),
	'ItemsWithoutSitelinks' => array( 'Objekt_utan_webbplatslänk' ),
	'ListDatatypes' => array( 'Lista_datatyper' ),
	'MergeItems' => array( 'Slå_ihop_objekt' ),
	'MyLanguageFallbackChain' => array( 'Min_språkåterfallskedja' ),
	'NewItem' => array( 'Nytt_objekt', 'Skapa_objekt' ),
	'NewProperty' => array( 'Ny_egenskap', 'Skapa_egenskap' ),
	'SetAliases' => array( 'Ange_alias' ),
	'SetDescription' => array( 'Ange_beskrivning' ),
	'SetLabel' => array( 'Ange_etikett' ),
	'SetSiteLink' => array( 'Ange_webbplatslänk' ),
);

/** Vietnamese (Tiếng Việt) */
$specialPageAliases['vi'] = array(
	'DispatchStats' => array( 'Thống_kê_truyền_bá' ),
	'EntitiesWithoutDescription' => array( 'Thực_thể_không_miêu_tả', 'Thực_thể_không_mô_tả' ),
	'EntitiesWithoutLabel' => array( 'Thực_thể_không_nhãn' ),
	'EntityData' => array( 'Dữ_liệu_thực_thể' ),
	'GoToLinkedPage' => array( 'Đi_đến_trang_liên_kết' ),
	'ItemByTitle' => array( 'Khoản_mục_theo_tên' ),
	'ItemDisambiguation' => array( 'Định_hướng_khoản_mục' ),
	'ItemsWithoutSitelinks' => array( 'Khoản_mục_không_có_liên_kết_dịch_vụ', 'Khoản_mục_không_có_liên_kết_site' ),
	'ListDatatypes' => array( 'Danh_sách_kiểu_dữ_liệu' ),
	'MergeItems' => array( 'Hợp_nhất_khoản_mục', 'Gộp_khoản_mục' ),
	'MyLanguageFallbackChain' => array( 'Chuỗi_ngôn_ngữ_thay_thế_của_tôi' ),
	'NewItem' => array( 'Tạo_khoản_mục' ),
	'NewProperty' => array( 'Thuộc_tính_mới' ),
	'SetAliases' => array( 'Đặt_tên_khác' ),
	'SetDescription' => array( 'Đặt_miêu_tả', 'Đặt_mô_tả' ),
	'SetLabel' => array( 'Đặt_nhãn' ),
	'SetSiteLink' => array( 'Đặt_liên_kết_dịch_vụ' ),
);

/** Simplified Chinese (中文（简体）‎) */
$specialPageAliases['zh-hans'] = array(
	'DispatchStats' => array( '发送统计' ),
	'EntitiesWithoutDescription' => array( '无说明实体' ),
	'EntitiesWithoutLabel' => array( '无标签实体' ),
	'EntityData' => array( '实体数据' ),
	'GoToLinkedPage' => array( '前往已链接页面' ),
	'ItemByTitle' => array( '项按标题' ),
	'ItemDisambiguation' => array( '项消歧义' ),
	'ItemsWithoutSitelinks' => array( '无网站链接项' ),
	'ListDatatypes' => array( '数据类型列表' ),
	'MergeItems' => array( '合并项' ),
	'MyLanguageFallbackChain' => array( '我的语言备选链' ),
	'NewItem' => array( '创建项' ),
	'NewProperty' => array( '新属性' ),
	'SetAliases' => array( '设置别名' ),
	'SetDescription' => array( '设置说明' ),
	'SetLabel' => array( '设置标签' ),
	'SetLabelDescriptionAliases' => array( '设置标签说明和别名' ),
	'SetSiteLink' => array( '设置网站链接' ),
);

/** Traditional Chinese (中文（繁體）‎) */
$specialPageAliases['zh-hant'] = array(
	'DispatchStats' => array( '發佈統計' ),
	'EntitiesWithoutDescription' => array( '無類型實體' ),
	'EntitiesWithoutLabel' => array( '無標籤實體' ),
	'EntityData' => array( '實體資料' ),
	'GoToLinkedPage' => array( '前往已連結頁面' ),
	'ItemByTitle' => array( '依標題搜尋項目' ),
	'ItemDisambiguation' => array( '項目消歧義' ),
	'ItemsWithoutSitelinks' => array( '無網站連結項目' ),
	'ListDatatypes' => array( '資料型態清單' ),
	'MergeItems' => array( '合併項目' ),
	'MyLanguageFallbackChain' => array( '我的備用語言鏈' ),
	'NewItem' => array( '建立項目' ),
	'NewProperty' => array( '新增屬性', '添加屬性' ),
	'SetAliases' => array( '設定別名' ),
	'SetDescription' => array( '設定描述' ),
	'SetLabel' => array( '設定標籤' ),
	'SetLabelDescriptionAliases' => array( '設定標籤說明和別名' ),
	'SetSiteLink' => array( '設定網站連結' ),
);

/** Chinese (Taiwan) (中文（台灣）‎) */
$specialPageAliases['zh-tw'] = array(
	'NewItem' => array( '建立項目' ),
	'NewProperty' => array( '新增屬性' ),
);
