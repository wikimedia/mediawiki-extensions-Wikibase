<?php

/**
 * Internationalization file for the Wikibase Client extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 */

$messages = array();

/** English
 * @author Katie Filbert
 * @author Jeroen De Dauw
 * @author Nikola Smolenski
 */
$messages['en'] = array(
	'wbc-desc' => 'Client for the Wikibase extension',
	'wbc-comment-langlinks-delete' => 'Associated Wikidata item deleted. Language links removed.',
	'wbc-comment-langlinks-remove' => 'This page has been unlinked from Wikidata item. Language links removed',
	'wbc-comment-langlinks-restore' => 'Associated Wikidata item undeleted. Language links restored',
	'wbc-comment-langlinks-update' => 'Language links updated',
	'wbc-editlinks' => 'Edit links',
	'wbc-editlinkstitle' => 'Edit interlanguage links',
);

/** Message documentation (Message documentation)
 * @author Jeblad
 * @author Katie Filbert
 */
$messages['qqq'] = array(
	'wbc-desc' => '{{desc}} See also [[m:Wikidata/Glossary#Wikidata|Wikidata]].',
	'wbc-comment-langlinks-delete' => 'Autocomment message for client (e.g. Wikipedia) recent changes when a Wikidata item connected to a page gets deleted. This results in all the language links being removed from the page on the client.',
	'wbc-comment-langlinks-remove' => 'Autocomment message for client (e.g. Wikipedia) recent changes when a site link to a page gets removed. This results in the associated item being disconnected from the client page and all the language links being removed.',
	'wbc-comment-langlinks-restore' => 'Autocomment message for client (e.g. Wikipedia) recent changes when a Wikidata item gets undeleted and has a site link to this page. Language links get readded to the client page.',
	'wbc-comment-langlinks-update' => 'Autocomment message for client (e.g. Wikipedia) recent changes when site links for a linked Wikidata item get changed. This results in language links being updated on the client page.',
	'wbc-editlinks' => '[[Image:InterlanguageLinks-Sidebar-Monobook.png|right]]
	This is a link to the page on Wikidata where interlanguage links of the current page can be edited. See the image on the right for how it looks.',
	'wbc-editlinkstitle' => 'This is the text on a link in the sidebar that opens a wizzard to edit interlanguage links.',
);

/** Asturian (asturianu)
 * @author Xuacu
 */
$messages['ast'] = array(
	'wbc-desc' => 'Cliente pa la estensión Wikibase',
	'wbc-editlinks' => 'Editar los enllaces',
	'wbc-editlinkstitle' => "Editar los enllaces d'interllingua",
);

/** Belarusian (Taraškievica orthography) (беларуская (тарашкевіца)‎)
 * @author Wizardist
 */
$messages['be-tarask'] = array(
	'wbc-desc' => 'Кліент пашырэньня Wikibase',
	'wbc-comment-langlinks-delete' => 'Злучаны аб’ект Вікізьвестак выдалены. Будуць выдаленыя моўныя спасылкі.',
	'wbc-comment-langlinks-remove' => 'Гэтая старонка была адлучаная да аб’екту Вікізьвестак. Моўныя спасылкі выдаленыя',
	'wbc-comment-langlinks-restore' => 'Злучаны аб’ект Вікізьвестак адноўлены. Будуць адноўленыя моўныя спасылкі',
	'wbc-comment-langlinks-update' => 'Моўныя спасылкі абноўленыя',
	'wbc-editlinks' => 'Рэдагаваць спасылкі',
	'wbc-editlinkstitle' => 'Рэдагаваць міжмоўныя спасылкі',
);

/** Breton (brezhoneg)
 * @author Fulup
 */
$messages['br'] = array(
	'wbc-editlinks' => 'Kemmañ al liammoù',
	'wbc-editlinkstitle' => 'Kemmañ al liammoù etreyezhel',
);

/** Catalan (català)
 * @author Arnaugir
 * @author Grondin
 * @author Àlex
 */
$messages['ca'] = array(
	'wbc-desc' => "Client per l'extensió Wikibase",
	'wbc-comment-langlinks-delete' => "Element associat de Wikidata suprimit. Enllaços d'idiomes eliminats.",
	'wbc-comment-langlinks-remove' => "S'ha desenllaçat aquesta pàgina de l'article de Wikidata. Els enllaços d'idioma s'han eliminat.",
	'wbc-comment-langlinks-restore' => "Element associat de Wikidata recuperat. Enllaços d'idioma restaurats.",
	'wbc-comment-langlinks-update' => "Enllaços d'idioma actualitzats.",
	'wbc-editlinks' => 'Modifica els enllaços',
	'wbc-editlinkstitle' => 'Modifica enllaços interlingües',
);

/** German (Deutsch)
 * @author Kghbln
 * @author Metalhead64
 * @author Purodha
 */
$messages['de'] = array(
	'wbc-desc' => 'Ermöglicht einen Client für die Erweiterung Wikibase',
	'wbc-comment-langlinks-delete' => 'Zugeordnetes Wikidata-Element gelöscht. Sprachlinks entfernt.',
	'wbc-comment-langlinks-remove' => 'Diese Seite wurde vom Wikidata-Element entlinkt. Sprachlinks entfernt.',
	'wbc-comment-langlinks-restore' => 'Zugeordnetes Wikidata-Element wiederhergestellt. Sprachlinks wiederhergestellt.',
	'wbc-comment-langlinks-update' => 'Sprachlinks aktualisiert',
	'wbc-editlinks' => 'Links bearbeiten',
	'wbc-editlinkstitle' => 'Links auf Artikel in anderen Sprachen bearbeiten',
);

/** Zazaki (Zazaki)
 * @author Erdemaslancan
 */
$messages['diq'] = array(
	'wbc-editlinks' => 'Gri bıvurnê',
);

/** Lower Sorbian (dolnoserbski)
 * @author Michawiki
 */
$messages['dsb'] = array(
	'wbc-desc' => 'Klient za rozšyrjenje Wikibase',
	'wbc-editlinks' => 'Wótkaze wobźěłaś',
	'wbc-editlinkstitle' => 'Mjazyrěcne wótkaze wobźěłaś',
);

/** Esperanto (Esperanto)
 * @author ArnoLagrange
 */
$messages['eo'] = array(
	'wbc-desc' => 'Kliento por la Vikidatuma etendaĵo',
	'wbc-comment-langlinks-delete' => 'Ligita vikidatuma ero estis forigita. La lingvaj ligiloj estis forviŝitaj.',
	'wbc-comment-langlinks-remove' => 'Ĉi tiu paĝo estis malligita de vikidatuma ero. La lingvaj ligiloj estis forviŝitaj.',
	'wbc-comment-langlinks-restore' => 'Ligita vikidatuma ero estis restarigita. La lingvaj ligiloj ankaŭ estis restarigitaj.',
	'wbc-comment-langlinks-update' => 'Lingvaj ligiloj ĝisdatigitaj',
	'wbc-editlinks' => 'Redaktu ligilojn',
	'wbc-editlinkstitle' => 'Redaktu interlingvajn ligilojn',
);

/** Spanish (español)
 * @author Armando-Martin
 * @author Dalton2
 */
$messages['es'] = array(
	'wbc-desc' => 'Cliente para la extensión Wikibase',
	'wbc-comment-langlinks-delete' => 'Se ha borrado un elemento asociado a Wikidata. Se han eliminado los enlaces lingüísticos.',
	'wbc-comment-langlinks-remove' => 'Esta página ha sido desenlazada de un elemento de Wikidata. Se han eliminado los enlaces lingüísticos',
	'wbc-comment-langlinks-restore' => 'Se ha restaurado un elemento asociado a Wikidata. Se han restaurado los enlaces de idioma',
	'wbc-comment-langlinks-update' => 'Se han actualizado los enlaces lingüísticos',
	'wbc-editlinks' => 'Editar los enlaces',
	'wbc-editlinkstitle' => 'Editar enlaces de interlengua',
);

/** Persian (فارسی)
 * @author Reza1615
 * @author ZxxZxxZ
 */
$messages['fa'] = array(
	'wbc-desc' => 'سرویس‌گیرنده برای افزونهٔ ویکی‌پایه',
	'wbc-comment-langlinks-delete' => 'پیوند آیتم ویکی‌داده حذف گردید.پیوند زبان حذف شد.',
	'wbc-comment-langlinks-remove' => 'این صفحه از آیتم ویکی‌داده قطع پیوند شد.پیوند زبان حذف شد.',
	'wbc-comment-langlinks-restore' => 'پیوند آیتم ویکی‌داده بازیابی شد.پیوند زبان بازیابی شد.',
	'wbc-comment-langlinks-update' => 'پیوند زبان‌ها به روز شد',
	'wbc-editlinks' => 'ویرایش پیوندها',
	'wbc-editlinkstitle' => 'افزودن پیوندهای میان‌ویکی',
);

/** Finnish (suomi)
 * @author Stryn
 */
$messages['fi'] = array(
	'wbc-comment-langlinks-update' => 'Kielilinkit päivitetty',
	'wbc-editlinks' => 'Muokkaa linkkejä',
	'wbc-editlinkstitle' => 'Muokkaa kieltenvälisiä linkkejä',
);

/** French (français)
 * @author Gomoko
 * @author Ltrlg
 * @author Wyz
 */
$messages['fr'] = array(
	'wbc-desc' => 'Client pour l’extension Wikibase',
	'wbc-comment-langlinks-delete' => 'Élément Wikidata associé supprimé. Liens de langue supprimés.',
	'wbc-comment-langlinks-remove' => "Cette page a été déliée de l'élément Wikidata. Liens de langue supprimés",
	'wbc-comment-langlinks-restore' => "Suppression de l'élément Wikidata associé annulée. Liens de langue rétablis",
	'wbc-comment-langlinks-update' => 'Liens interlangues mis à jour',
	'wbc-editlinks' => 'Modifier les liens',
	'wbc-editlinkstitle' => 'Modifier les liens interlangue',
);

/** Franco-Provençal (arpetan)
 * @author ChrisPtDe
 */
$messages['frp'] = array(
	'wbc-editlinks' => 'Changiér los lims',
	'wbc-editlinkstitle' => 'Changiér los lims entèrlengoua',
);

/** Galician (galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'wbc-desc' => 'Cliente para a extensión Wikibase',
	'wbc-comment-langlinks-delete' => 'Borrouse un elemento de Wikidata asociado. Elimináronse as ligazóns lingüísticas.',
	'wbc-comment-langlinks-remove' => 'Esta páxina foi desligada do elemento de Wikidata asociado. Elimináronse as ligazóns lingüísticas',
	'wbc-comment-langlinks-restore' => 'Restaurouse un elemento de Wikidata asociado. Recuperáronse as ligazóns lingüísticas',
	'wbc-comment-langlinks-update' => 'Actualizáronse as ligazóns lingüísticas',
	'wbc-editlinks' => 'Editar as ligazóns',
	'wbc-editlinkstitle' => 'Editar as ligazóns interlingüísticas',
);

/** Swiss German (Alemannisch)
 * @author Als-Holder
 */
$messages['gsw'] = array(
	'wbc-desc' => 'Macht e Client fir d Erwyterig Wikibase megli',
	'wbc-editlinks' => 'Links bearbeite',
	'wbc-editlinkstitle' => 'Sprachibergryfigi Link bearbeite',
);

/** Hebrew (עברית)
 * @author Amire80
 */
$messages['he'] = array(
	'wbc-desc' => 'לקוח להרחבה Wikibase',
	'wbc-comment-langlinks-delete' => 'הפריט המשויך בוויקינתונים נמחק. קישורי שפה הוסרו.',
	'wbc-comment-langlinks-remove' => 'הקישור של הדף הזה לפריט בוויקינתונים בוטל. קישורי השפה הוסרו.',
	'wbc-comment-langlinks-restore' => 'הפריט המשויך בוויקינתונים שחזר. קישורי השפה שוחזרו',
	'wbc-comment-langlinks-update' => 'קישורי השפה עודכנו',
	'wbc-editlinks' => 'עריכת קישורים',
	'wbc-editlinkstitle' => 'עריכת קישורים בין־לשוניים',
);

/** Upper Sorbian (hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'wbc-desc' => 'Klient za rozšěrjenje Wikibase',
	'wbc-comment-langlinks-delete' => 'Přirjadowany element Wikidata zhašany. Rěčne wotkazy wotstronjene.',
	'wbc-comment-langlinks-remove' => 'Tuta strona hižo wotkazowy cil element Wikidata hižo njeje. Rěčne wotkazy wotstronjene.',
	'wbc-comment-langlinks-restore' => 'Přirjadowany element Wikidata zaso wobnowjeny. Rěčne wotkazy wobnowjene.',
	'wbc-comment-langlinks-update' => 'Rěčne wotkazy zaktualizowane',
	'wbc-editlinks' => 'Wotkazy wobdźěłać',
	'wbc-editlinkstitle' => 'Mjezyrěčne wotkazy wobdźěłać',
);

/** Interlingua (interlingua)
 * @author McDutchie
 */
$messages['ia'] = array(
	'wbc-desc' => 'Cliente pro le extension Wikibase',
	'wbc-editlinks' => 'Modificar ligamines',
	'wbc-editlinkstitle' => 'Modificar ligamines interlingua',
);

/** Icelandic (íslenska)
 * @author Snævar
 */
$messages['is'] = array(
	'wbc-desc' => 'Biðlari fyrir Wikibase viðbótina',
	'wbc-comment-langlinks-delete' => 'Tengdum Wikidata hlut eytt. Tungumálatenglar fjarlægðir.',
	'wbc-comment-langlinks-remove' => 'Þessi síða hefur verið aftengd Wikidata hlut. Tungumálatenglar fjarlægðir',
	'wbc-comment-langlinks-restore' => 'Tengdur Wikidata hlut endurvakinn. Tungumálatenglar endurvaktir.',
	'wbc-comment-langlinks-update' => 'Vefsvæðis tenglar uppfærðir',
	'wbc-editlinks' => 'Breyta tenglum',
	'wbc-editlinkstitle' => 'Breyta tungumálatenglum',
);

/** Italian (italiano)
 * @author Beta16
 */
$messages['it'] = array(
	'wbc-desc' => "Client per l'estensione Wikibase",
	'wbc-comment-langlinks-delete' => "L'elemento di Wikidata associato è stato cancellato. I collegamenti interlinguistici sono stati rimossi.",
	'wbc-comment-langlinks-remove' => "Questa pagina è stata scollegata dall'elemento di Wikidata. I collegamenti interlinguistici sono stati rimossi.",
	'wbc-comment-langlinks-restore' => "La cancellazione dell'elemento di Wikidata associato è stata annullata. I collegamenti interlinguistici sono stati ripristinati.",
	'wbc-comment-langlinks-update' => 'I collegamenti interlinguistici sono stati aggiornati.',
	'wbc-editlinks' => 'Modifica collegamenti',
	'wbc-editlinkstitle' => 'Modifica collegamenti interlingua',
);

/** Japanese (日本語)
 * @author Shirayuki
 */
$messages['ja'] = array(
	'wbc-desc' => 'Wikibase 拡張機能のクライアント',
	'wbc-comment-langlinks-delete' => '関連付けられたウィキデータ項目は削除されました。言語リンクを除去しました。',
	'wbc-comment-langlinks-remove' => 'このページはウィキデータ項目からリンク解除されました。言語リンクを除去しました',
	'wbc-comment-langlinks-restore' => '関連付けられたウィキデータ項目が復元されました。言語リンクを復元しました。',
	'wbc-comment-langlinks-update' => '言語リンクを更新しました',
	'wbc-editlinks' => 'リンクを編集',
	'wbc-editlinkstitle' => '言語間リンクを編集',
);

/** Georgian (ქართული)
 * @author David1010
 */
$messages['ka'] = array(
	'wbc-editlinks' => 'ბმულების რედაქტირება',
);

/** Korean (한국어)
 * @author 아라
 */
$messages['ko'] = array(
	'wbc-desc' => '위키베이스 확장 기능을 위한 클라이언트',
	'wbc-comment-langlinks-delete' => '연결한 위키데이터 항목을 삭제했습니다. 언어 링크를 제거했습니다.',
	'wbc-comment-langlinks-remove' => '이 문서는 위키데이터 항목에 연결하지 않았습니다. 언어 링크를 제거했습니다',
	'wbc-comment-langlinks-restore' => '연결한 위키데이터 항목을 복구했습니다. 언어 링크를 복구했습니다',
	'wbc-comment-langlinks-update' => '언어 링크를 업데이트했습니다',
	'wbc-editlinks' => '링크 편집',
	'wbc-editlinkstitle' => '인터언어 링크 편집',
);

/** Colognian (Ripoarisch)
 * @author Purodha
 */
$messages['ksh'] = array(
	'wbc-desc' => 'Madd en Aanwendong vun däm WikiData Projrammzihsaz müjjelesch.',
	'wbc-editlinks' => 'Lengks ändere',
	'wbc-editlinkstitle' => 'Donn de Lenks zwesche der Schprooche aanbränge udder aanpaße',
);

/** Kurdish (Latin script) (Kurdî (latînî)‎)
 * @author George Animal
 */
$messages['ku-latn'] = array(
	'wbc-editlinks' => 'Girêdanan biguherîne',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'wbc-desc' => "Client fir d'Wikibase Erweiderung",
);

/** Macedonian (македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'wbc-desc' => 'Клиент за додатокот „Викибаза“',
	'wbc-comment-langlinks-delete' => 'Здружениот предмет од Википодатоците е избришан. Јазичните врски се избришани.',
	'wbc-comment-langlinks-remove' => 'На оваа страница ѝ е раскината врската со елементот од Википодатоците. Јазичните врски се отстранети.',
	'wbc-comment-langlinks-restore' => 'Здружениот предмет од Википодатоците е повратен. Јазичните врски се повратени.',
	'wbc-comment-langlinks-update' => 'Јазичните врски се подновени',
	'wbc-editlinks' => 'Уреди врски',
	'wbc-editlinkstitle' => 'Уредување на меѓујазични врски',
);

/** Malay (Bahasa Melayu)
 * @author Anakmalaysia
 */
$messages['ms'] = array(
	'wbc-desc' => 'Pelanggan sambungan Wikibase',
	'wbc-comment-langlinks-delete' => 'Perkara Wikidata yang berkenaan dihapuskan. Pautan bahasa dipadamkan.',
	'wbc-comment-langlinks-remove' => 'Halaman ini telah dinyahpautkan dari perkara Wikidata. Pautan bahasa dipadamkan.',
	'wbc-comment-langlinks-restore' => 'Perkara Wikidata yang berkenaan dinyahhapus. Pautan bahasa dipulihkan.',
	'wbc-comment-langlinks-update' => 'Pautan bahasa dikemaskinikan',
	'wbc-editlinks' => 'Sunting pautan',
	'wbc-editlinkstitle' => 'Sunting pautan antara bahasa',
);

/** Norwegian Bokmål (norsk (bokmål)‎)
 * @author Jeblad
 */
$messages['nb'] = array(
	'wbc-desc' => 'Klientutvidelse for Wikibase, det strukturerte datalageret',
	'wbc-editlinks' => 'Rediger lenker',
	'wbc-editlinkstitle' => 'Rediger nettstedlenker',
);

/** Dutch (Nederlands)
 * @author Siebrand
 */
$messages['nl'] = array(
	'wbc-desc' => 'Client voor de uitbreiding Wikibase',
	'wbc-comment-langlinks-delete' => 'Bijbehorend Wikidataitem verwijderd. Taalverwijzingen verwijderd.',
	'wbc-comment-langlinks-remove' => 'Deze pagina is ontkoppeld van het Wikidataitem. Taalverwijzingen zijn verwijderd',
	'wbc-comment-langlinks-restore' => 'Gekoppeld Wikidataitem teruggeplaatst. Taalverwijzingen teruggeplaatst',
	'wbc-comment-langlinks-update' => 'Taalverwijzingen bijgewerkt',
	'wbc-editlinks' => 'Verwijzingen bewerken',
	'wbc-editlinkstitle' => 'Intertaalverwijzingen bewerken',
);

/** Norwegian Nynorsk (norsk (nynorsk)‎)
 * @author Jeblad
 * @author Njardarlogar
 */
$messages['nn'] = array(
	'wbc-desc' => 'Klient for Wikibase-utvidinga',
	'wbc-editlinks' => 'Endra lenkjer',
	'wbc-editlinkstitle' => 'Endra mellomspråklege lenkjer',
);

/** Polish (polski)
 * @author BeginaFelicysym
 * @author Lazowik
 * @author Maćko
 */
$messages['pl'] = array(
	'wbc-desc' => 'Klient rozszerzenia Wikibase',
	'wbc-comment-langlinks-delete' => 'Powiązany obiekt Wikidata usunięty. Linki językowe usunięte.',
	'wbc-comment-langlinks-remove' => 'Ta strona została odlinkowana od obiektu Wikidata. Linki językowe usunięte',
	'wbc-comment-langlinks-restore' => 'Powiązany obiekt Wikidata przywrócony. Linki językowe przywrócone',
	'wbc-comment-langlinks-update' => 'Linki językowe zaktualizowane',
	'wbc-editlinks' => 'Edytuj linki',
	'wbc-editlinkstitle' => 'Edytuj linki wersji językowych',
);

/** Portuguese (português)
 * @author Helder.wiki
 * @author Malafaya
 * @author SandroHc
 */
$messages['pt'] = array(
	'wbc-desc' => 'Cliente para a extensão Wikibase',
	'wbc-comment-langlinks-delete' => 'O item associado no Wikidata foi eliminado. Foram removidos os links para outros idiomas.',
	'wbc-comment-langlinks-remove' => 'Esta página foi desvinculada do item do Wikidata. Os links para outros idiomas foram removidos',
	'wbc-comment-langlinks-restore' => 'O item do Wikidata associado foi restaurado. Os links para outros idiomas foram restaurados',
	'wbc-comment-langlinks-update' => 'Foram atualizados os links para outros idiomas',
	'wbc-editlinks' => 'Editar links',
	'wbc-editlinkstitle' => 'Editar links interlínguas',
);

/** Brazilian Portuguese (português do Brasil)
 * @author Helder.wiki
 * @author Jaideraf
 */
$messages['pt-br'] = array(
	'wbc-desc' => 'Cliente para a extensão Wikibase',
	'wbc-comment-langlinks-delete' => 'O item associado no Wikidata foi eliminado. Foram removidos os links para outros idiomas.',
	'wbc-comment-langlinks-remove' => 'Esta página foi desvinculada do item do Wikidata. Os links para outros idiomas foram removidos',
	'wbc-comment-langlinks-restore' => 'O item do Wikidata associado foi restaurado. Os links para outros idiomas foram restaurados',
	'wbc-comment-langlinks-update' => 'Foram atualizados os links para outros idiomas',
	'wbc-editlinks' => 'Editar links',
	'wbc-editlinkstitle' => 'Editar links para outros idiomas',
);

/** Romanian (română)
 * @author Stelistcristi
 */
$messages['ro'] = array(
	'wbc-editlinks' => 'Editează legăturile',
	'wbc-editlinkstitle' => 'Editează legăturile interlingvistice',
);

/** Russian (русский)
 * @author Kaganer
 * @author Александр Сигачёв
 */
$messages['ru'] = array(
	'wbc-desc' => 'Клиент для расширения Wikibase',
	'wbc-comment-langlinks-delete' => 'Связанный элемент Викиданных удалён. Языковые ссылки ликвидированы.',
	'wbc-comment-langlinks-remove' => 'Связь этой страницы с элементом Викиданных была разорвана. Языковые ссылки удалены',
	'wbc-comment-langlinks-restore' => 'Удаление связанного элемента Викиданных отменено. Языковые ссылки восстановлены',
	'wbc-comment-langlinks-update' => 'Языковые ссылки обновлены',
	'wbc-editlinks' => 'Редактировать ссылки',
	'wbc-editlinkstitle' => 'Редактировать межъязыковые ссылки',
);

/** Serbian (Cyrillic script) (српски (ћирилица)‎)
 * @author Nikola Smolenski
 * @author Rancher
 */
$messages['sr-ec'] = array(
	'wbc-desc' => 'Клијент за проширење Викибаза',
	'wbc-editlinks' => 'Уреди везе',
	'wbc-editlinkstitle' => 'Уређивање међујезичких веза',
);

/** Serbian (Latin script) (srpski (latinica)‎)
 */
$messages['sr-el'] = array(
	'wbc-desc' => 'Klijent za proširenje Vikibaza',
	'wbc-editlinks' => 'Uredi veze',
	'wbc-editlinkstitle' => 'Uređivanje međujezičkih veza',
);

/** Swedish (svenska)
 * @author Ainali
 * @author Lokal Profil
 */
$messages['sv'] = array(
	'wbc-desc' => 'Klient för tillägget Wikibase',
	'wbc-comment-langlinks-update' => 'Språklänkar uppdaterade',
	'wbc-editlinks' => 'Redigera länkar',
);

/** Tamil (தமிழ்)
 * @author மதனாஹரன்
 */
$messages['ta'] = array(
	'wbc-editlinks' => 'இணைப்புக்களைத் தொகு',
);

/** Telugu (తెలుగు)
 * @author Veeven
 */
$messages['te'] = array(
	'wbc-editlinks' => 'లంకెలను మార్చు',
);

/** Tagalog (Tagalog)
 * @author AnakngAraw
 */
$messages['tl'] = array(
	'wbc-desc' => 'Kliyente para sa dugtong na Wikibase',
	'wbc-editlinks' => 'Baguhin ang mga kawing',
	'wbc-editlinkstitle' => 'Baguhin ang mga kawing na para sa interwika',
);

/** Vietnamese (Tiếng Việt)
 * @author Minh Nguyen
 */
$messages['vi'] = array(
	'wbc-desc' => 'Trình khách của phần mở rộng Wikibase',
	'wbc-comment-langlinks-delete' => 'Đã xóa khoản mục liên kết Wikidata. Đã dời các liên kết ngôn ngữ.',
	'wbc-comment-langlinks-remove' => 'Đã gỡ liên kết đến khoản mục Wikidata khỏi trang này. Đã dời các liên kết ngôn ngữ.',
	'wbc-comment-langlinks-restore' => 'Đã phục hồi khoản mục liên kết Wikidata. Đã phục hồi các liên kết ngôn ngữ.',
	'wbc-comment-langlinks-update' => 'Đã cập nhật các liên kết ngôn ngữ',
	'wbc-editlinks' => 'Sửa liên kết',
	'wbc-editlinkstitle' => 'Sửa liên kết giữa ngôn ngữ',
);

/** Simplified Chinese (中文（简体）‎)
 * @author Linforest
 * @author Shizhao
 * @author Yfdyh000
 */
$messages['zh-hans'] = array(
	'wbc-desc' => 'Wikibase扩展客户端',
	'wbc-comment-langlinks-delete' => '关联的维基数据项目已删除。语言链接已移除。',
	'wbc-comment-langlinks-remove' => '本页已在维基数据解除链接。语言链接已移除。',
	'wbc-comment-langlinks-restore' => '关联的维基数据项目已恢复。恢复语言链接',
	'wbc-comment-langlinks-update' => '语言链接已更新',
	'wbc-editlinks' => '编辑链接',
	'wbc-editlinkstitle' => '编辑跨语言链接',
);

/** Traditional Chinese (中文（繁體）‎)
 */
$messages['zh-hant'] = array(
	'wbc-desc' => 'Wikibase擴展客戶端',
	'wbc-editlinks' => '編輯鏈接',
	'wbc-editlinkstitle' => '編輯跨語言鏈接',
);
