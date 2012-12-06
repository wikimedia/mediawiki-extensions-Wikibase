<?php

/**
 * Internationalization file for the Wikibase Client extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
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
	'wbc-after-page-move' => 'You may also [$1 update] the associated Wikidata item to maintain language links on moved page.',
	'wbc-comment-remove' => 'Associated Wikidata item deleted. Language links removed.',
	'wbc-comment-linked' => 'A Wikidata item has been linked to this page.',
	'wbc-comment-unlink' => 'This page has been unlinked from Wikidata item. Language links removed.',
	'wbc-comment-restore' => 'Associated Wikidata item undeleted. Language links restored.',
	'wbc-comment-update' => 'Language links updated.',
	'wbc-comment-sitelink-add' => 'Language link added: $1',
	'wbc-comment-sitelink-change' => 'Language link changed from $1 to $2',
	'wbc-comment-sitelink-remove' => 'Language link removed: $1',
	'wbc-editlinks' => 'Edit links',
	'wbc-editlinkstitle' => 'Edit interlanguage links',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Show Wikidata edits in recent changes',
);

/** Message documentation (Message documentation)
 * @author Jeblad
 * @author Katie Filbert
 */
$messages['qqq'] = array(
	'wbc-desc' => '{{desc}} See also [[m:Wikidata/Glossary#Wikidata|Wikidata]].',
	'wbc-after-page-move' => 'Message on [[Special:MovePage]] on submit and successfuly move, inviting user to update associated Wikibase repo item to maintain language links on the moved page on the client.
* Parameter $1 is the link for the associated Wikibase item.',
	'wbc-comment-remove' => 'Autocomment message for client (e.g. Wikipedia) recent changes when a Wikidata item connected to a page gets deleted. This results in all the language links being removed from the page on the client.',
	'wbc-comment-linked' => 'Autocomment message in the client for when a Wikidata item is linked to a page in the client.',
	'wbc-comment-unlink' => 'Autocomment message for client (e.g. Wikipedia) recent changes when a site link to a page gets removed. This results in the associated item being disconnected from the client page and all the language links being removed.',
	'wbc-comment-restore' => 'Autocomment message for client (e.g. Wikipedia) recent changes when a Wikidata item gets undeleted and has a site link to this page. Language links get readded to the client page.',
	'wbc-comment-update' => 'Autocomment message for client (e.g. Wikipedia) recent changes when site links for a linked Wikidata item get changed. This results in language links being updated on the client page.',
	'wbc-comment-sitelink-add' => 'Autocomment message for client (e.g. Wikipedia) when a particular site link gets added on the repository. This change appears on the client as a new language link in the sidebar. $1 is the wikilink that was added, in form of [[:de:Berlin|de:Berlin]].',
	'wbc-comment-sitelink-change' => 'Autocomment message for client (e.g. Wikipedia) when a particular site link gets changed on the repository.  $1 is the wikilink for the old link and $2 is the new wikilink.  Format of wikilink is [[:de:Berlin|de:Berlin]].',
	'wbc-comment-sitelink-remove' => 'Autocomment message for client (e.g. Wikipedia) when a particular site link gets removed on the repository.  $1 is the wikilink for the link removed, in format [[:de:Berlin|de:Berlin]].',
	'wbc-editlinks' => '[[Image:InterlanguageLinks-Sidebar-Monobook.png|right]]
	This is a link to the page on Wikidata where interlanguage links of the current page can be edited. See the image on the right for how it looks.',
	'wbc-editlinkstitle' => 'This is the text on a link in the sidebar that opens a wizzard to edit interlanguage links.',
	'wbc-rc-hide-wikidata' => 'This refers to a toggle to hide or show edits (revisions) that come from Wikidata. If set to "hide", it hides edits made to the connected item in the Wikidata repository.
* Parameter $1 is a link with the text {{msg-mw|show}} or {{msg-mw|hide}}',
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
 * @author Vriullop
 * @author Àlex
 */
$messages['ca'] = array(
	'wbc-desc' => "Client per l'extensió Wikibase",
	'wbc-after-page-move' => "Podeu també [$1 actualitzar] l'element associat de Wikidata per a mantenir els enllaços d'idioma a la pàgina moguda.",
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
	'wbc-after-page-move' => 'Du kannst auch das zugeordnete Wikidata-Element [$1 aktualisieren], um Sprachlinks von verschobenen Seiten zu verwalten.',
	'wbc-comment-remove' => 'Zugeordnetes Wikidata-Objekt wurde gelöscht. Sprachlinks wurden entfernt.',
	'wbc-comment-linked' => 'Ein Wikidata-Objekt wurde mit dieser Seite verknüpft.',
	'wbc-comment-unlink' => 'Diese Seite wurde vom Wikidata-Objekt entlinkt. Sprachlinks wurden entfernt.',
	'wbc-comment-restore' => 'Zugeordnetes Wikidata-Objekt wurde wiederhergestellt. Sprachlinks wurden wiederhergestellt.',
	'wbc-comment-update' => 'Sprachlinks wurden aktualisiert',
	'wbc-comment-sitelink-add' => 'Sprachlink hinzugefügt: $1',
	'wbc-comment-sitelink-change' => 'Sprachlink $1 geändert in $2',
	'wbc-comment-sitelink-remove' => 'Sprachlink entfernt: $1',
	'wbc-editlinks' => 'Links bearbeiten',
	'wbc-editlinkstitle' => 'Links auf Artikel in anderen Sprachen bearbeiten',
	'wbc-rc-hide-wikidata' => 'Wikidata $1',
	'wbc-rc-show-wikidata-pref' => 'Wikidata-Bearbeitungen in den „Letzten Änderungen“ anzeigen',
);

/** Zazaki (Zazaki)
 * @author Erdemaslancan
 */
$messages['diq'] = array(
	'wbc-comment-langlinks-update' => 'Linka zıwani rocaniyaya',
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
	'wbc-after-page-move' => 'También puedes [$1 actualizar] el elemento Wikidata asociado para mantener los vínculos de idioma en la página que se ha movido.',
	'wbc-comment-remove' => 'Se ha borrado un elemento asociado a Wikidata. Se han eliminado los enlaces lingüísticos.',
	'wbc-comment-linked' => 'Un artículo de Wikidata ha sido enlazado a esta página.',
	'wbc-comment-unlink' => 'Esta página ha sido desenlazada de un elemento de Wikidata. Se han eliminado los enlaces lingüísticos.',
	'wbc-comment-restore' => 'Se ha restaurado un elemento asociado a Wikidata. Se han restaurado los enlaces de idioma.',
	'wbc-comment-update' => 'Los enlaces de idioma se han actualizado.',
	'wbc-comment-sitelink-add' => 'Se ha añadido un enlace de idioma: $1',
	'wbc-comment-sitelink-change' => 'Se ha cambiado el enlace de idioma de $1 a $2',
	'wbc-comment-sitelink-remove' => 'Se ha eliminado el enlace de idioma: $1',
	'wbc-editlinks' => 'Editar los enlaces',
	'wbc-editlinkstitle' => 'Editar enlaces de interlengua',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Mostrar las ediciones Wikidata en la lista de cambios recientes',
);

/** Persian (فارسی)
 * @author Reza1615
 * @author ZxxZxxZ
 */
$messages['fa'] = array(
	'wbc-desc' => 'کارخواه برای افزونهٔ ویکی‌بیس',
	'wbc-comment-update' => 'پیوندهای زبانی به‌روز شد.',
	'wbc-editlinks' => 'ویرایش پیوندها',
	'wbc-editlinkstitle' => 'افزودن پیوندهای میان‌ویکی',
	'wbc-rc-hide-wikidata' => '$1 ویکی‌داده',
	'wbc-rc-show-wikidata-pref' => 'نمایش ویرایش‌های ویکی‌داده در تغییرات اخیر',
);

/** Finnish (suomi)
 * @author Stryn
 * @author VezonThunder
 */
$messages['fi'] = array(
	'wbc-desc' => 'Wikibase-laajennuksen asiakasohjelma',
	'wbc-after-page-move' => 'Voit myös [$1 päivittää] sivuun liittyvän Wikidatan kohteen säilyttääksesi kielilinkit siirretyllä sivulla.',
	'wbc-comment-remove' => 'Sivuun liittyvä Wikidata-kohde poistettu. Kielilinkit poistettu.',
	'wbc-comment-unlink' => 'Tämä sivu ei ole enää liitettynä Wikidata-kohteeseen. Kielilinkit poistettu.',
	'wbc-comment-restore' => 'Sivuun liittyvä Wikidata-kohde palautettu. Kielilinkit palautettu.',
	'wbc-comment-update' => 'Kielilinkit päivitetty.',
	'wbc-comment-sitelink-add' => 'Kielilinkki lisätty: $1',
	'wbc-comment-sitelink-change' => 'Kielilinkki $1 muutettu muotoon $2',
	'wbc-comment-sitelink-remove' => 'Kielilinkki poistettu: $1',
	'wbc-editlinks' => 'Muokkaa linkkejä',
	'wbc-editlinkstitle' => 'Muokkaa kieltenvälisiä linkkejä',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Näytä Wikidata-muokkaukset tuoreissa muutoksissa',
);

/** French (français)
 * @author Gomoko
 * @author Ltrlg
 * @author Wyz
 */
$messages['fr'] = array(
	'wbc-desc' => 'Client pour l’extension Wikibase',
	'wbc-after-page-move' => "Vous pouvez aussi [$1 mettre à jour] l'élément Wikidata associé pour conserver les liens de langue sur la page déplacée.",
	'wbc-comment-remove' => 'Élément Wikidata associé supprimé. Liens de langue supprimés.',
	'wbc-comment-linked' => 'Un élément Wikidata a été lié à cette page.',
	'wbc-comment-unlink' => "Cette page a été dissociée de l'élément Wikidata. Liens de langue supprimés.",
	'wbc-comment-restore' => "Suppression de l'élément Wikidata associé annulée. Liens de langue rétablis.",
	'wbc-comment-update' => 'Liens de langue mis à jour.',
	'wbc-comment-sitelink-add' => 'Lien de langue ajouté: $1',
	'wbc-comment-sitelink-change' => 'Lien de langue modifié de $1 à $2',
	'wbc-comment-sitelink-remove' => 'Lien de langue supprimé: $1',
	'wbc-editlinks' => 'Modifier les liens',
	'wbc-editlinkstitle' => 'Modifier les liens interlangue',
	'wbc-rc-hide-wikidata' => 'Wikidata $1',
	'wbc-rc-show-wikidata-pref' => 'Afficher les modifications de Wikidata dans les modifications récentes',
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
	'wbc-after-page-move' => 'Tamén pode [$1 actualizar] o elemento de Wikidata asociado para manter as ligazóns lingüísticas na páxina trasladada.',
	'wbc-comment-remove' => 'Borrouse un elemento de Wikidata asociado. Elimináronse as ligazóns lingüísticas.',
	'wbc-comment-linked' => 'Esta páxina foi ligada desde un elemento de Wikidata.',
	'wbc-comment-unlink' => 'Esta páxina foi desligada do elemento de Wikidata asociado. Elimináronse as ligazóns lingüísticas.',
	'wbc-comment-restore' => 'Restaurouse un elemento de Wikidata asociado. Recuperáronse as ligazóns lingüísticas.',
	'wbc-comment-update' => 'Actualizáronse as ligazóns lingüísticas.',
	'wbc-comment-sitelink-add' => 'Engadiuse unha ligazón lingüística: $1',
	'wbc-comment-sitelink-change' => 'Cambiouse unha ligazón lingüística de $1 a $2',
	'wbc-comment-sitelink-remove' => 'Eliminouse unha ligazón lingüística: $1',
	'wbc-editlinks' => 'Editar as ligazóns',
	'wbc-editlinkstitle' => 'Editar as ligazóns interlingüísticas',
	'wbc-rc-hide-wikidata' => '$1 o Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Mostrar as modificacións de Wikidata nos cambios recentes',
);

/** Swiss German (Alemannisch)
 * @author Als-Holder
 */
$messages['gsw'] = array(
	'wbc-desc' => 'Macht e Client fir d Erwyterig Wikibase megli',
	'wbc-after-page-move' => 'Du chasch au s zuegordnet Wikidata-Elemänt [$1 aktualisiere], go Sprochlink vu verschobene Syte verwalte.',
	'wbc-editlinks' => 'Links bearbeite',
	'wbc-editlinkstitle' => 'Sprachibergryfigi Link bearbeite',
	'wbc-rc-hide-wikidata' => 'Wikidata $1',
);

/** Hebrew (עברית)
 * @author Amire80
 */
$messages['he'] = array(
	'wbc-desc' => 'לקוח להרחבה Wikibase',
	'wbc-after-page-move' => 'אפשר גם [$1 לעדכן] את הפריט המשויך בוויקיפדיה כדי לתחזק את קישורי השפה בדף שהועבר.',
	'wbc-comment-remove' => 'פריט הוויקינתונים המשויך נמחק. קישורי שפה הוסרו.',
	'wbc-comment-linked' => 'פריט ויקינתונים קוּשר לדף הזה.',
	'wbc-comment-unlink' => 'הדף הזה נותק מִפריט ויקינתונים. קישורי השפה הוסרו.',
	'wbc-comment-restore' => 'פריט הוויקינתונים המשויך שוחזר. קישורי השפה שוחזרו.',
	'wbc-comment-update' => 'קישורי השפה עודכנו.',
	'wbc-comment-sitelink-add' => 'קישור שפה הוסף: $1',
	'wbc-comment-sitelink-change' => 'קישור השפה שוּנה מ־$1 אל $2',
	'wbc-comment-sitelink-remove' => 'קישור השפה הוסר: $1',
	'wbc-editlinks' => 'עריכת קישורים',
	'wbc-editlinkstitle' => 'עריכת קישורים בין־לשוניים',
	'wbc-rc-hide-wikidata' => '$1 ויקינתונים',
	'wbc-rc-show-wikidata-pref' => 'הצגת עריכות ויקינתונים בשינויים אחרונים',
);

/** Upper Sorbian (hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'wbc-desc' => 'Klient za rozšěrjenje Wikibase',
	'wbc-comment-update' => 'Mjezyrěčne wotkazy su so zaktualizowali.',
	'wbc-editlinks' => 'Wotkazy wobdźěłać',
	'wbc-editlinkstitle' => 'Mjezyrěčne wotkazy wobdźěłać',
	'wbc-rc-hide-wikidata' => 'Wikidata $1',
	'wbc-rc-show-wikidata-pref' => 'Změny Wikidata w aktualnych změnach pokazać',
);

/** Interlingua (interlingua)
 * @author McDutchie
 */
$messages['ia'] = array(
	'wbc-desc' => 'Cliente pro le extension Wikibase',
	'wbc-editlinks' => 'Modificar ligamines',
	'wbc-editlinkstitle' => 'Modificar ligamines interlingua',
);

/** Iloko (Ilokano)
 * @author Lam-ang
 */
$messages['ilo'] = array(
	'wbc-desc' => 'Kliente para iti Wikibase a pagpaatiddog',
	'wbc-after-page-move' => 'Mabalinmo pay a [$1 pabaruen] ti mainaig a banag ti Wikidata tapno mataripatu dagiti silpo ti pagsasao ti naiyalis a panid.',
	'wbc-comment-remove' => 'Ti mainaig a banag ti Wikidata ket naikkaten. Dagiti silpo ti pagsasao ket naikkaten.',
	'wbc-comment-linked' => 'Ti Wikidata a banag ket naisilpon iti daytoy a panid.',
	'wbc-comment-unlink' => 'Daytoy a panid ket naikkat ti silpona manipud ti Wikidata a banag. Dagiti silpo ti pagsasao ket naikkaten.',
	'wbc-comment-restore' => 'Ti mainaig a banag ti Wikidata ket naisubli ti pannakaikkatna. Dagiti silpo ti pagsasao ket naipasubli.',
	'wbc-comment-update' => 'Naipabaro dagiti silpo ti pagsasao.',
	'wbc-comment-sitelink-add' => 'Nanayonan ti silpo ti pagsasao: $1',
	'wbc-comment-sitelink-change' => 'Ti silpo ti pagsasao ket nasukatan manipud ti $1 iti $2',
	'wbc-comment-sitelink-remove' => 'Naikkat ti silpo ti pagsasao: $1',
	'wbc-editlinks' => 'Nurnosen dagiti silpo',
	'wbc-editlinkstitle' => 'Urnosen dagiti sangkapagsasaoan a silpo',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Ipakita dagiti Wikidata nga inurnos idiay kinaudi a binalbaliwan',
);

/** Icelandic (íslenska)
 * @author Snævar
 */
$messages['is'] = array(
	'wbc-desc' => 'Biðlari fyrir Wikibase viðbótina',
	'wbc-after-page-move' => 'Þú mátt einnig [$1 uppfæra] viðeigandi Wikidata hlut til að viðhalda tungumálatenglum á færðu síðunni.',
	'wbc-comment-remove' => 'Tengdum Wikidata hlut eytt. Tungumálatenglar fjarlægðir.',
	'wbc-comment-linked' => 'Wikidata hlutur hefur tengst þessari síðu.',
	'wbc-comment-unlink' => 'Þessi síða hefur verið aftengd Wikidata hlut. Tungumálatenglar fjarlægðir.',
	'wbc-comment-restore' => 'Tengdur Wikidata hlutur endurvakinn. Tungumálatenglar endurvaktir.',
	'wbc-comment-update' => 'Vefsvæðis tenglar uppfærðir.',
	'wbc-comment-sitelink-add' => 'Tungumálatengli bætt við: $1',
	'wbc-comment-sitelink-change' => 'Tungumálatengli breytt frá $1 í $2',
	'wbc-comment-sitelink-remove' => 'Tungumálatengill fjarlægður: $1',
	'wbc-editlinks' => 'Breyta tenglum',
	'wbc-editlinkstitle' => 'Breyta tungumálatenglum',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Sýna Wikidata breytingar í nýjustu breytingum',
);

/** Italian (italiano)
 * @author Beta16
 * @author Gianfranco
 * @author Raoli
 * @author Sannita
 */
$messages['it'] = array(
	'wbc-desc' => "Client per l'estensione Wikibase",
	'wbc-after-page-move' => "Puoi anche [$1 aggiornare] l'elemento associato su Wikidata per trasferire gli interlink sulla nuova pagina.",
	'wbc-comment-remove' => "L'elemento di Wikidata associato è stato cancellato. I link di lingua sono stati rimossi.",
	'wbc-comment-linked' => 'Un elemento di Wikidata è stato collegato a questa pagina.',
	'wbc-comment-unlink' => "Questa pagina è stata scollegata dall'elemento di Wikidata. I link di lingua sono stati rimossi.",
	'wbc-comment-restore' => "L'elemento di Wikidata associato è stato recuperato. I link di lingua sono stati ripristinati.",
	'wbc-comment-update' => 'Link di lingua aggiornato.',
	'wbc-comment-sitelink-add' => 'Link linguistico aggiunto: $1',
	'wbc-comment-sitelink-change' => 'Link linguistico modificato da $1 a $2',
	'wbc-comment-sitelink-remove' => 'Link linguistico rimosso: $1',
	'wbc-editlinks' => 'Modifica collegamenti',
	'wbc-editlinkstitle' => 'Modifica collegamenti interlingua',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Mostra le modifiche di Wikidata nelle ultime modifiche',
);

/** Japanese (日本語)
 * @author Shirayuki
 */
$messages['ja'] = array(
	'wbc-desc' => 'Wikibase 拡張機能のクライアント',
	'wbc-after-page-move' => '移動されたページにある言語リンクを維持するために、関連付けられたウィキデータ項目を[$1 更新]することもできます。',
	'wbc-comment-remove' => '関連付けられたウィキデータ項目を削除しました。言語リンクを除去しました。',
	'wbc-comment-linked' => 'ウィキデータ項目をこのページにリンクしました。',
	'wbc-comment-unlink' => 'このページをウィキデータ項目からリンク解除しました。言語リンクを除去しました。',
	'wbc-comment-restore' => '関連付けられたウィキデータ項目を復元しました。言語リンクを復元しました。',
	'wbc-comment-update' => '言語リンクを更新しました。',
	'wbc-comment-sitelink-add' => '言語リンクを追加: $1',
	'wbc-comment-sitelink-change' => '言語リンクを $1 から $2 に変更',
	'wbc-comment-sitelink-remove' => '言語リンクを除去: $1',
	'wbc-editlinks' => 'リンクを編集',
	'wbc-editlinkstitle' => '言語間リンクを編集',
	'wbc-rc-hide-wikidata' => 'ウィキデータを$1',
	'wbc-rc-show-wikidata-pref' => '最近の更新にウィキデータの編集を表示',
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
	'wbc-after-page-move' => '또한 이동한 문서에 언어 링크를 유지하기 위해 관련된 위키데이터 항목을 [$1 업데이트]할 수 있습니다.',
	'wbc-comment-remove' => '연결한 위키데이터 항목을 삭제했습니다. 언어 링크를 제거했습니다.',
	'wbc-comment-unlink' => '이 문서는 위키데이터 항목에 연결하지 않았습니다. 언어 링크를 제거했습니다.',
	'wbc-comment-restore' => '연결한 위키데이터 항목을 복구했습니다. 언어 링크를 복구했습니다.',
	'wbc-comment-update' => '언어 링크를 업데이트했습니다.',
	'wbc-editlinks' => '링크 편집',
	'wbc-editlinkstitle' => '인터언어 링크 편집',
	'wbc-rc-hide-wikidata' => '위키데이터 $1',
	'wbc-rc-show-wikidata-pref' => '최근 바뀜에서 위키데이터 편집 보기',
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
	'wbc-after-page-move' => 'Можете и да го [$1 подновите] поврзаниот предмет на Википодатоци за да ги одржите јазичните врски на преместената страница.',
	'wbc-comment-remove' => 'Здружениот предмет од Википодатоците е избришан. Јазичните врски се избришани.',
	'wbc-comment-linked' => 'Со страницава е поврзан предмет од Википодатоците.',
	'wbc-comment-unlink' => 'На оваа страница ѝ е раскината врската со елементот од Википодатоците. Јазичните врски се отстранети.',
	'wbc-comment-restore' => 'Здружениот предмет од Википодатоците е повратен. Јазичните врски се повратени.',
	'wbc-comment-update' => 'Јазичните врски се подновени',
	'wbc-comment-sitelink-add' => 'Додадена јазична врска: $1',
	'wbc-comment-sitelink-change' => 'Изменета јазична врска од $1 на $2',
	'wbc-comment-sitelink-remove' => 'Отстранета јазична врска: $1',
	'wbc-editlinks' => 'Уреди врски',
	'wbc-editlinkstitle' => 'Уредување на меѓујазични врски',
	'wbc-rc-hide-wikidata' => '$1 Википодатоци',
	'wbc-rc-show-wikidata-pref' => 'Прикажувај ги уредувањата на Википодатоците во скорешните промени',
);

/** Marathi (मराठी)
 * @author Ydyashad
 */
$messages['mr'] = array(
	'wbc-rc-hide-wikidata' => '$१ विकिमाहिती', # Fuzzy
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
 * @author Danmichaelo
 * @author Event
 * @author Jeblad
 */
$messages['nb'] = array(
	'wbc-desc' => 'Klientutvidelse for Wikibase, det strukturerte datalageret',
	'wbc-comment-update' => 'Språklenker er oppdatert.',
	'wbc-editlinks' => 'Rediger lenker',
	'wbc-editlinkstitle' => 'Rediger språkspesifikke lenker',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Vis Wikidata-redigeringer i siste endringer',
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
	'wbc-after-page-move' => 'Чтобы исправить на переименованной странице языковые ссылки, вы можете также [$1  обновить] связанный элемент Викиданных.',
	'wbc-comment-remove' => 'Связанный элемент Викиданных удалён. Языковые ссылки ликвидированы.',
	'wbc-comment-unlink' => 'Связь этой страницы с элементом Викиданных была разорвана. Языковые ссылки удалены.',
	'wbc-comment-restore' => 'Удаление связанного элемента Викиданных отменено. Языковые ссылки восстановлены.',
	'wbc-comment-update' => 'Языковые ссылки обновлены.',
	'wbc-editlinks' => 'Редактировать ссылки',
	'wbc-editlinkstitle' => 'Редактировать межъязыковые ссылки',
	'wbc-rc-hide-wikidata' => '$1 Викиданные',
	'wbc-rc-show-wikidata-pref' => 'Показать изменения Викиданных в списке свежих правок',
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
 * @author Shanmugamp7
 * @author மதனாஹரன்
 */
$messages['ta'] = array(
	'wbc-editlinks' => 'இணைப்புக்களைத் தொகு',
	'wbc-rc-hide-wikidata' => '$1 விக்கித்தரவு',
	'wbc-rc-show-wikidata-pref' => 'விக்கித்தரவு தொகுப்புகளை அண்மைய மாற்றங்களில் காண்பி',
);

/** Telugu (తెలుగు)
 * @author Veeven
 */
$messages['te'] = array(
	'wbc-editlinks' => 'లంకెలను మార్చు',
	'wbc-rc-hide-wikidata' => 'వికీడాటాను $1',
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
	'wbc-after-page-move' => 'Bạn cũng có thể [$1 cập nhật] khoản mục Wikidata liên kết để duy trì các liên kết ngôn ngữ trên trang được di chuyển.',
	'wbc-comment-remove' => 'Đã xóa khoản mục liên kết Wikidata. Đã loại bỏ các liên kết ngôn ngữ.',
	'wbc-comment-linked' => 'Một khoản mục Wikidata đã được liên kết đến trang này.',
	'wbc-comment-unlink' => 'Đã gỡ liên kết đến khoản mục Wikidata khỏi trang này. Đã dời các liên kết ngôn ngữ.',
	'wbc-comment-restore' => 'Đã phục hồi khoản mục liên kết Wikidata. Đã phục hồi các liên kết ngôn ngữ.',
	'wbc-comment-update' => 'Đã cập nhật các liên kết ngôn ngữ.',
	'wbc-comment-sitelink-add' => 'Đã thêm liên kết ngôn ngữ: $1',
	'wbc-comment-sitelink-change' => 'Đã đổi liên kết ngôn ngữ từ $1 thành $2',
	'wbc-comment-sitelink-remove' => 'Đã loại bỏ liên kết ngôn ngữ: $1',
	'wbc-editlinks' => 'Sửa liên kết',
	'wbc-editlinkstitle' => 'Sửa liên kết giữa ngôn ngữ',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Hiện các sửa đổi Wikidata trong thay đổi gần đây',
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
