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
 * @author Shirayuki
 */
$messages['qqq'] = array(
	'wbc-desc' => '{{desc|name=Wikibase Client|url=http://www.mediawiki.org/wiki/Extension:Wikibase_Client}}
See also [[m:Wikidata/Glossary#Wikidata|Wikidata]].',
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

/** Bosnian (bosanski)
 * @author Edinwiki
 */
$messages['bs'] = array(
	'wbc-desc' => 'Klijent za proširenje Wikibaza',
	'wbc-after-page-move' => 'Možete također [$1 ažurirati] asociranu Wikidata stavku za održavanje jezičnih veza na premještenoj stranici.',
	'wbc-comment-remove' => 'Asocirana Wikidata stavka je izbrisana. Jezične veze su uklonjene.',
	'wbc-comment-linked' => 'Neka Wikidata stavka je povezana prema ovoj stranici.',
	'wbc-comment-unlink' => 'Ova stranica je odvojena od Wikidata stavke. Jezične veze su uklonjene.',
	'wbc-comment-restore' => 'Asocirana Wikidata stavka je vraćena. Jezične veze su sada isto vraćene.',
	'wbc-comment-update' => 'Jezične veze su ažurirane.',
	'wbc-comment-sitelink-add' => 'Jezična veza dodana: $1',
	'wbc-comment-sitelink-change' => 'Jezična veza izmjenjena sa $1 u $2',
	'wbc-comment-sitelink-remove' => 'Jezična veza uklonjena: $1',
	'wbc-editlinks' => 'Izmjeni veze',
	'wbc-editlinkstitle' => 'Izmjeni međujezične veze',
	'wbc-rc-hide-wikidata' => 'Wikidata $1',
	'wbc-rc-show-wikidata-pref' => 'Pokaži Wikidata izmjene u nedavnim izmjenama',
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

/** Czech (česky)
 * @author JAn Dudík
 */
$messages['cs'] = array(
	'wbc-desc' => 'Klient pro rozšíření Wikibase',
	'wbc-after-page-move' => 'Můžete také [ $1  aktualizovat] související položku Wikidat pro údržbu mezijazykových odkazů na přesunuté stránce.',
	'wbc-comment-remove' => 'Související položka Wikidat odstraněna. Mezijazykové odkazy odstraněny.',
	'wbc-comment-linked' => 'Položka Wikidat odkazovala na tuto stránku.',
	'wbc-comment-unlink' => 'Odkaz na tuto stránku byl odstraněn z Wikidat. Mezijazykové odkazy odstraněny.',
	'wbc-comment-restore' => 'Související položka Wikidat obnovena. Mezijazykové odkazy obnoveny.',
	'wbc-comment-update' => 'Aktualizovány mezijazykové odkazy.',
	'wbc-comment-sitelink-add' => 'Přidán mezijazykový odkaz:$1',
	'wbc-comment-sitelink-change' => 'Změněn mezijazykový odkaz z $1 na $2',
	'wbc-comment-sitelink-remove' => 'Odstraněn mezijazykový odkaz:$1',
	'wbc-editlinks' => 'Upravit odkazy',
	'wbc-editlinkstitle' => 'Editovat mezijazykové odkazy',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Zobrazit změny Wikidat v posledních změnách',
);

/** Danish (dansk)
 * @author Christian List
 * @author Poul G
 */
$messages['da'] = array(
	'wbc-desc' => 'Klient til Wikibase udvidelse',
	'wbc-after-page-move' => 'Du kan også [ $1  opdatere] det tilknyttede Wikidata emne for at bevare sprog-link til den flyttede side.',
	'wbc-comment-remove' => 'Det tilknyttede Wikidata emne er slettet. Sprog-links fjernet.',
	'wbc-comment-linked' => 'Et Wikidata emne er blevet knyttet til denne side.',
	'wbc-comment-unlink' => 'Denne side er ikke længere linket fra et Wikidata emne. Sprog-links fjernet.',
	'wbc-comment-restore' => 'Det tilknyttede Wikidata emne er genskabt. Sprog-links gendannet.',
	'wbc-comment-update' => 'Sprog-link opdateret.',
	'wbc-comment-sitelink-add' => 'Sprog-link tilføjet: $1',
	'wbc-comment-sitelink-change' => 'Sprog-link ændret fra $1 til $2',
	'wbc-comment-sitelink-remove' => 'Sprog-link fjernet: $1',
	'wbc-editlinks' => 'Rediger links',
	'wbc-editlinkstitle' => 'Rediger sprog-link',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Vis Wikidata redigeringer i de seneste ændringer',
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
	'wbc-after-page-move' => 'Vi povas [$1 ĝisdatigi] la ligitan Vikidatuman eron por pluteni la lingvan ligilon al la la movita paĝo.',
	'wbc-comment-remove' => 'Ligita Vikidatuma ero etis forigita. La lingvaj ligiloj estas forviŝitaj.',
	'wbc-comment-linked' => 'Vikidatuma ero estis ligita al ĉi tiu paĝo.',
	'wbc-comment-unlink' => 'Ĉi tiu paĝo estis malligita de la Vikidatuma ero. La lingvaj ligiloj estas forigitaj.',
	'wbc-comment-restore' => 'Ligita vikidatuma ero estis restarigita. La lingvaj ligiloj ankaŭ estis restarigitaj.',
	'wbc-comment-update' => 'Lingvaj ligiloj ĝisdatigitaj.',
	'wbc-comment-sitelink-add' => 'Lingva ligilo aldonita: $1',
	'wbc-comment-sitelink-change' => 'Lingva ligilo ŝanĝita de $1 al $2',
	'wbc-comment-sitelink-remove' => 'Lingva ligilo forigita: $1',
	'wbc-editlinks' => 'Redaktu ligilojn',
	'wbc-editlinkstitle' => 'Redaktu interlingvajn ligilojn',
	'wbc-rc-hide-wikidata' => '$1 Vikidatumoj',
	'wbc-rc-show-wikidata-pref' => 'Montru Vikidatumaj redaktoj en la lastaj ŝanĝoj',
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
 * @author Nike
 * @author Stryn
 * @author VezonThunder
 */
$messages['fi'] = array(
	'wbc-desc' => 'Wikibase-laajennuksen asiakasohjelma',
	'wbc-after-page-move' => 'Voit myös [$1 päivittää] sivuun liittyvän Wikidatan kohteen säilyttääksesi kielilinkit siirretyllä sivulla.',
	'wbc-comment-remove' => 'Sivuun liittyvä Wikidata-kohde poistettu. Kielilinkit poistettu.',
	'wbc-comment-linked' => 'Wikidata-tietue on linkitetty tähän sivuun.',
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
 * @author Crochet.david
 * @author Gomoko
 * @author Ltrlg
 * @author Wyz
 */
$messages['fr'] = array(
	'wbc-desc' => 'Client pour l’extension Wikibase',
	'wbc-after-page-move' => 'Vous pouvez aussi [$1 mettre à jour] l’élément Wikidata associé pour conserver les liens de langue sur la page déplacée.',
	'wbc-comment-remove' => 'Élément Wikidata associé supprimé. Liens de langue supprimés.',
	'wbc-comment-linked' => 'Un élément Wikidata a été lié à cette page.',
	'wbc-comment-unlink' => 'Cette page a été dissociée de l’élément Wikidata. Liens de langue supprimés.',
	'wbc-comment-restore' => 'Suppression de l’élément Wikidata associé annulée. Liens de langue rétablis.',
	'wbc-comment-update' => 'Liens de langue mis à jour.',
	'wbc-comment-sitelink-add' => 'Lien de langue ajouté : $1',
	'wbc-comment-sitelink-change' => 'Lien de langue modifié de $1 à $2',
	'wbc-comment-sitelink-remove' => 'Lien de langue supprimé : $1',
	'wbc-editlinks' => 'Modifier les liens',
	'wbc-editlinkstitle' => 'Modifier les liens interlangue',
	'wbc-rc-hide-wikidata' => 'Wikidata $1',
	'wbc-rc-show-wikidata-pref' => 'Afficher les modifications de Wikidata dans les modifications récentes',
);

/** Franco-Provençal (arpetan)
 * @author ChrisPtDe
 */
$messages['frp'] = array(
	'wbc-comment-update' => 'Lims de lengoua betâs a jorn.',
	'wbc-comment-sitelink-add' => 'Lim de lengoua apondu : $1',
	'wbc-comment-sitelink-change' => 'Lim de lengoua changiê de $1 a $2',
	'wbc-comment-sitelink-remove' => 'Lim de lengoua enlevâ : $1',
	'wbc-editlinks' => 'Changiér los lims',
	'wbc-editlinkstitle' => 'Changiér los lims entèrlengoua',
	'wbc-rc-hide-wikidata' => 'Wikidata $1',
	'wbc-rc-show-wikidata-pref' => 'Montrar los changements de Wikidata dedens los dèrriérs changements',
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
	'wbc-after-page-move' => 'Móžeš tež přirjadowany element Wikidata [$1 aktualizować], zo by mjezyrěčne wotkazy na přesunjenej stronje zarjadował.',
	'wbc-comment-remove' => 'Přirjadowany element Wikidata zhašany. Mjezyrěčne wotkazy wotstronjene.',
	'wbc-comment-linked' => 'Element Wikidata je so z tutej stronu zwjazał.',
	'wbc-comment-unlink' => 'Zwisk strony z elementom Wikidata je so wotstronił. Mjezyrěčne wotkazy wotstronjene.',
	'wbc-comment-restore' => 'Přirjadowany element Wikidata zaso wobnowjeny. Mjezyrěčne wotkazy wobnowjene.',
	'wbc-comment-update' => 'Mjezyrěčne wotkazy su so zaktualizowali.',
	'wbc-comment-sitelink-add' => 'Mjezyrěčny wotkaz přidaty: $1',
	'wbc-comment-sitelink-change' => 'Mjezyrěčny wotkaz změnjeny wot $1 do $2',
	'wbc-comment-sitelink-remove' => 'Mjezyrěčny wotkaz wotstronjeny: $1',
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
	'wbc-comment-linked' => '위키데이터 항목을 이 문서에 연결했습니다.',
	'wbc-comment-unlink' => '이 문서는 위키데이터 항목에 연결하지 않았습니다. 언어 링크를 제거했습니다.',
	'wbc-comment-restore' => '연결한 위키데이터 항목을 복구했습니다. 언어 링크를 복구했습니다.',
	'wbc-comment-update' => '언어 링크를 업데이트했습니다.',
	'wbc-comment-sitelink-add' => '언어 링크를 추가함: $1',
	'wbc-comment-sitelink-change' => '언어 링크를 $1에서 $2로 바꿈',
	'wbc-comment-sitelink-remove' => '언어 링크를 제거함: $1',
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

/** Malayalam (മലയാളം)
 * @author Praveenp
 */
$messages['ml'] = array(
	'wbc-desc' => 'വിക്കിബേസ് അനുബന്ധത്തിനുള്ള ക്ലയന്റ്',
	'wbc-after-page-move' => 'മാറ്റിയ താളിലെ ഭാഷാ കണ്ണികൾ പരിപാലിക്കുന്നതിനായി ബന്ധപ്പെട്ട വിക്കിഡേറ്റ ഇനം താങ്കൾക്ക് [$1 പുതുക്കുകയും] ചെയ്യാവുന്നതാണ്.',
	'wbc-comment-remove' => 'ബന്ധപ്പെട്ട വിക്കിഡേറ്റ ഇനം മായ്ക്കപ്പെട്ടിരിക്കുന്നു. ഭാഷാ കണ്ണികൾ നീക്കം ചെയ്തു.',
	'wbc-comment-linked' => 'ഒരു വിക്കിഡേറ്റ ഇനം ഈ താളിൽ കണ്ണി ചേർത്തിരിക്കുന്നു.',
	'wbc-comment-unlink' => 'ഈ താൾ വിക്കിഡേറ്റാ ഇനത്തിൽ നിന്നും കണ്ണി മാറ്റിയിരിക്കുന്നു. ഭാഷാ കണ്ണികൾ നീക്കം ചെയ്തു.',
	'wbc-comment-restore' => 'ബന്ധപ്പെട്ട വിക്കിഡേറ്റ ഇനം പുനഃസ്ഥാപിച്ചിരിക്കുന്നു. ഭാഷാ കണ്ണികൾ പുനഃസ്ഥാപിച്ചു.',
	'wbc-comment-update' => 'ഭാഷാ കണ്ണികൾ പുതുക്കപ്പെട്ടു.',
	'wbc-comment-sitelink-add' => 'ഭാഷാ കണ്ണി ചേർത്തു: $1',
	'wbc-comment-sitelink-change' => 'ഭാഷാ കണ്ണി $1 എന്നതിൽ നിന്ന് $2 എന്നാക്കി മാറ്റിയിരിക്കുന്നു',
	'wbc-comment-sitelink-remove' => 'ഭാഷാ കണ്ണി നീക്കം ചെയ്തു: $1',
	'wbc-editlinks' => 'കണ്ണികൾ തിരുത്തുക',
	'wbc-editlinkstitle' => 'അന്തർഭാഷാ കണ്ണികൾ തിരുത്തുക',
	'wbc-rc-hide-wikidata' => 'വിക്കിഡേറ്റ $1',
	'wbc-rc-show-wikidata-pref' => 'സമീപകാല മാറ്റങ്ങളിൽ വിക്കിഡേറ്റാ തിരുത്തലുകളും പ്രദർശിപ്പിക്കുക',
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
	'wbc-after-page-move' => 'Anda juga boleh [$1 mengemaskinikan] perkara Wikidata yang berkenaan untuk memelihara pautan bahasa pada halaman yang dipindahkan.',
	'wbc-comment-remove' => 'Perkara Wikidata yang berkenaan dihapuskan. Pautan bahasa dipadamkan.',
	'wbc-comment-linked' => 'Satu perkara Wikidata telah dipautkan ke halaman ini.',
	'wbc-comment-unlink' => 'Halaman ini telah dinyahpautkan dari perkara Wikidata. Pautan bahasa dibuang.',
	'wbc-comment-restore' => 'Perkara Wikidata yang berkenaan dinyahhapus. Pautan bahasa dipulihkan.',
	'wbc-comment-update' => 'Pautan bahasa dikemaskinikan.',
	'wbc-comment-sitelink-add' => 'Pautan bahasa dibubuh: $1',
	'wbc-comment-sitelink-change' => 'Pautan bahasa diubah daripada $1 kepada $2',
	'wbc-comment-sitelink-remove' => 'Pautan bahasa dibuang: $1',
	'wbc-editlinks' => 'Sunting pautan',
	'wbc-editlinkstitle' => 'Sunting pautan antara bahasa',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Paparkan suntingan Wikidata dalam perubahan terkini',
);

/** Norwegian Bokmål (norsk (bokmål)‎)
 * @author Danmichaelo
 * @author Event
 * @author Jeblad
 */
$messages['nb'] = array(
	'wbc-desc' => 'Klientutvidelse for Wikibase, det strukturerte datalageret',
	'wbc-after-page-move' => 'Du kan også [$1 oppdatere] det tilknyttede Wikidata-datasettet for å bevare språklenkene til den flyttede siden.',
	'wbc-comment-remove' => 'Det tilknyttede Wikidata-datasettet har blitt slettet. Språklenker har blitt fjernet.',
	'wbc-comment-linked' => 'Et Wikidata-datasett har blitt knyttet til denne siden.',
	'wbc-comment-unlink' => 'Denne siden har blitt fraknyttet et Wikidata-datasett. Språklenker har blitt fjernet.',
	'wbc-comment-restore' => 'Det tilknyttede Wikidata-datasettet har blitt gjenopprettet. Språklenker har blitt gjenopprettet.',
	'wbc-comment-update' => 'Språklenker har blitt oppdatert.',
	'wbc-comment-sitelink-add' => 'Språklenke tilført: $1',
	'wbc-comment-sitelink-change' => 'Språklenke endret fra $1 til $2',
	'wbc-comment-sitelink-remove' => 'Språklenke fjernet: $1',
	'wbc-editlinks' => 'Rediger lenker',
	'wbc-editlinkstitle' => 'Rediger språklenker – lenker til artikkelen på andre språk',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Vis Wikidata-redigeringer i siste endringer',
);

/** Dutch (Nederlands)
 * @author Siebrand
 */
$messages['nl'] = array(
	'wbc-desc' => 'Client voor de uitbreiding Wikibase',
	'wbc-after-page-move' => 'U kunt ook het gekoppelde Wikidataitem [$1 bijwerken] om de taalkoppelingen op de hernoemde pagina te kunnen beheren.',
	'wbc-comment-remove' => 'Het gekoppelde Wikidataitem is verwijderd. De taalkoppelingen zijn verwijderd.',
	'wbc-comment-linked' => 'Er is een Wikidataitem gekoppeld aan deze pagina.',
	'wbc-comment-unlink' => 'Deze pagina is ontkoppeld van het Wikidataitem. De taalkoppelingen zijn verwijderd.',
	'wbc-comment-restore' => 'Het gekoppelde Wikidataitem is teruggeplaatst. De taalkoppelingen zijn hersteld.',
	'wbc-comment-update' => 'De taalkoppelingen zijn bijgewerkt.',
	'wbc-comment-sitelink-add' => 'Taalkoppeling toegevoegd: $1',
	'wbc-comment-sitelink-change' => 'Taalkoppeling gewijzigd van $1 naar $2',
	'wbc-comment-sitelink-remove' => 'Taalkoppeling verwijderd: $1',
	'wbc-editlinks' => 'Koppelingen bewerken',
	'wbc-editlinkstitle' => 'Intertaalkoppelingen bewerken',
	'wbc-rc-hide-wikidata' => 'Wikidata $1',
	'wbc-rc-show-wikidata-pref' => 'Wikidatabewerkingen weergeven in recente wijzigingen',
);

/** Norwegian Nynorsk (norsk (nynorsk)‎)
 * @author Jeblad
 * @author Njardarlogar
 */
$messages['nn'] = array(
	'wbc-desc' => 'Klient for Wikibase-utvidinga',
	'wbc-after-page-move' => 'Du kan òg [$1 oppdatera] det tilknytte Wikidata-settet for å halda språklenkjene på den flytte sida ved like.',
	'wbc-comment-remove' => 'Tilknytt Wikidata-sett sletta. Språklenkjer fjerna.',
	'wbc-comment-linked' => 'Eit Wikidata-sett har vorte lenkja til sida.',
	'wbc-comment-unlink' => 'Lenkinga til sida har vorte fjerna frå Wikidata-settet. Språklenkjer fjerna.',
	'wbc-comment-restore' => 'Tilknytt Wikidata-sett attoppretta. Språklenkjer lagde inn att.',
	'wbc-comment-update' => 'Språklenkjer oppdaterte.',
	'wbc-comment-sitelink-add' => 'Språklenkje lagd til: $1',
	'wbc-comment-sitelink-change' => 'Språklenkje endra frå $1 til $2',
	'wbc-comment-sitelink-remove' => 'Språklenkje fjerna: $1',
	'wbc-editlinks' => 'Endra lenkjer',
	'wbc-editlinkstitle' => 'Endra mellomspråklege lenkjer',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Vis Wikidata-endringar i siste endringane',
);

/** Polish (polski)
 * @author BeginaFelicysym
 * @author Lazowik
 * @author Maćko
 * @author Odie2
 */
$messages['pl'] = array(
	'wbc-desc' => 'Klient rozszerzenia Wikibase',
	'wbc-comment-sitelink-add' => 'Łącze języka dodane: $1',
	'wbc-comment-sitelink-change' => 'Łącze języka zmienione z $1 na $2',
	'wbc-comment-sitelink-remove' => 'Łącze języka usunięte: $1',
	'wbc-editlinks' => 'Edytuj linki',
	'wbc-editlinkstitle' => 'Edytuj linki wersji językowych',
);

/** Piedmontese (Piemontèis)
 * @author Dragonòt
 */
$messages['pms'] = array(
	'wbc-desc' => "Client për l'estension Wikibase",
	'wbc-after-page-move' => "It peule ëdcò [$1 modifiché] j'element associà Wikidata për manten-e ij colegamente ëd lenga dzor le pagine tramudà.",
	'wbc-comment-remove' => 'Element associà Wikidata scanselà. Colegament ëd lenga gavà.',
	'wbc-comment-linked' => "N'element Wikidata a l'é stàit colegà a sta pagina.",
	'wbc-comment-unlink' => "Sta pagina a l'é stàita scolegà da l'element Wikidata. Colegament ëd lenga gavà.",
	'wbc-comment-restore' => 'Element associà Wikidata ripristinà. Colegament ëd lenga ripristinà.',
	'wbc-comment-update' => 'Colegament ëd lenga agiornà.',
	'wbc-comment-sitelink-add' => 'Colegament ëd lenga giontà: $1',
	'wbc-comment-sitelink-change' => 'Colegament ëd lenga cangià da $1 a $2',
	'wbc-comment-sitelink-remove' => 'Colegament ëd lenga gavà: $1',
	'wbc-editlinks' => "Modifiché j'anliure",
	'wbc-editlinkstitle' => 'Modìfica colegament antërlenga',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Smon modìfiche Wikidata ant ij cambi recent',
);

/** Portuguese (português)
 * @author Helder.wiki
 * @author Lijealso
 * @author Malafaya
 * @author SandroHc
 */
$messages['pt'] = array(
	'wbc-desc' => 'Cliente para a extensão Wikibase',
	'wbc-after-page-move' => 'Também pode [$1 actualizar] o item do Wikidata associado para manter os links de idioma na página movida.',
	'wbc-comment-remove' => 'O item associado no Wikidata foi eliminado. Foram removidos os links para outros idiomas.',
	'wbc-comment-linked' => 'Um item do Wikidata foi ligado a esta página.',
	'wbc-comment-unlink' => 'O link desta página foi retirado do item do Wikidata. Os links para outros idiomas foram removidos.',
	'wbc-comment-restore' => 'O item associado no Wikidata foi restaurado. Foram restaurados os links para outros idiomas.',
	'wbc-comment-update' => 'Foram atualizados os links para outros idiomas',
	'wbc-comment-sitelink-add' => 'Link de idioma adicionado:$1',
	'wbc-comment-sitelink-change' => 'Link de idioma alterado de  $1 para $2',
	'wbc-comment-sitelink-remove' => 'Link de idioma removido: $1',
	'wbc-editlinks' => 'Editar links',
	'wbc-editlinkstitle' => 'Editar links interlínguas',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Mostrar as edições no Wikidata nas mudanças recentes',
);

/** Brazilian Portuguese (português do Brasil)
 * @author Helder.wiki
 * @author Jaideraf
 */
$messages['pt-br'] = array(
	'wbc-desc' => 'Cliente para a extensão Wikibase',
	'wbc-after-page-move' => 'Você também pode [$1 atualizar] o item associado ao Wikidata para manter os links de idioma na página movida.',
	'wbc-comment-remove' => 'O item associado no Wikidata foi eliminado. Os links para os outros idiomas foram removidos.',
	'wbc-comment-linked' => 'Um item do Wikidata foi linkado a esta página.',
	'wbc-comment-unlink' => 'O link desta página foi retirado do item do Wikidata. Os links para os outros idiomas foram removidos.',
	'wbc-comment-restore' => 'O item associado no Wikidata foi restaurado. Os links para os outros idiomas foram restaurados.',
	'wbc-comment-update' => 'Os links para outros idiomas foram atualizados.',
	'wbc-comment-sitelink-add' => 'Link de idioma adicionado: $1',
	'wbc-comment-sitelink-change' => 'Link de idioma alterado de $1 para $2',
	'wbc-comment-sitelink-remove' => 'Link de idioma removido: $1',
	'wbc-editlinks' => 'Editar links',
	'wbc-editlinkstitle' => 'Editar links para outros idiomas',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Mostrar as edições do Wikidata nas mudanças recentes',
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
 * @author Ole Yves
 * @author Александр Сигачёв
 */
$messages['ru'] = array(
	'wbc-desc' => 'Клиент для расширения Wikibase',
	'wbc-after-page-move' => 'Чтобы исправить на переименованной странице языковые ссылки, вы можете также [$1  обновить] связанный элемент Викиданных.',
	'wbc-comment-remove' => 'Связанный элемент Викиданных удалён. Языковые ссылки ликвидированы.',
	'wbc-comment-linked' => 'Элемент Викиданных был связан с данной страницей.',
	'wbc-comment-unlink' => 'Связь этой страницы с элементом Викиданных была разорвана. Языковые ссылки удалены.',
	'wbc-comment-restore' => 'Удаление связанного элемента Викиданных отменено. Языковые ссылки восстановлены.',
	'wbc-comment-update' => 'Языковые ссылки обновлены.',
	'wbc-comment-sitelink-add' => 'Интервики-ссылка добавлена: $1.',
	'wbc-comment-sitelink-change' => 'Интервики-ссылка изменена с $1 на $2',
	'wbc-comment-sitelink-remove' => 'Интервики-ссылка удалена: $1',
	'wbc-editlinks' => 'Редактировать ссылки',
	'wbc-editlinkstitle' => 'Редактировать межъязыковые ссылки',
	'wbc-rc-hide-wikidata' => '$1 Викиданные',
	'wbc-rc-show-wikidata-pref' => 'Показать изменения Викиданных в списке свежих правок',
);

/** Sinhala (සිංහල)
 * @author පසිඳු කාවින්ද
 */
$messages['si'] = array(
	'wbc-desc' => 'විකිපාදක දිගුව සඳහා සේවාදායකයා',
	'wbc-comment-update' => 'භාෂා සබැඳි යාවත්කාලීන කරන ලදී.',
	'wbc-comment-sitelink-add' => 'භාෂා සබැඳිය එක් කරන ලදී: $1',
	'wbc-comment-sitelink-change' => 'භාෂා සබැඳිය $1 ගෙන් $2 වෙත වෙනස් වෙන ලදී',
	'wbc-comment-sitelink-remove' => 'භාෂා සබැඳිය ඉවත් කරන ලදී: $1',
	'wbc-editlinks' => 'සබැඳි සංස්කරණය කරන්න',
	'wbc-editlinkstitle' => 'අන්තර්භාෂාමය සබැඳි සංස්කරණය කරන්න',
	'wbc-rc-hide-wikidata' => '$1 විකිදත්ත',
	'wbc-rc-show-wikidata-pref' => 'මෑත වෙනස්කම්වල විකිදත්ත සංස්කරණ පෙන්වන්න',
);

/** Slovak (slovenčina)
 * @author JAn Dudík
 */
$messages['sk'] = array(
	'wbc-desc' => 'Klient pre rozšírenie Wikibase',
	'wbc-editlinks' => 'Upraviť odkazy',
	'wbc-editlinkstitle' => 'Upraviť medzijazykové odkazy',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Zobraziť úpravy Wikidat v posledných zmienách',
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
	'wbc-comment-remove' => 'Tillhörande Wikidata objekt togs bort. Språklänkar togs bort.',
	'wbc-comment-linked' => 'Ett Wikidata-objekt har länkats till den här sidan.',
	'wbc-comment-unlink' => 'Denna sida har gjorts olänkad från Wikidata-objektet. Språklänkar togs bort.',
	'wbc-comment-restore' => 'Tillhörande Wikidata-objekt togs bort. Språklänkar togs bort.',
	'wbc-comment-update' => 'Språklänkar uppdaterades.',
	'wbc-comment-sitelink-add' => 'Språklänken lades till: $1',
	'wbc-comment-sitelink-change' => 'Språklänken ändrades från $1 till $2',
	'wbc-comment-sitelink-remove' => 'Språklänken togs bort: $1',
	'wbc-editlinks' => 'Redigera länkar',
	'wbc-editlinkstitle' => 'Redigera interwikilänkar',
	'wbc-rc-hide-wikidata' => '$1 Wikidata',
	'wbc-rc-show-wikidata-pref' => 'Visa Wikidataredigeringar i senaste ändringar',
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
	'wbc-rc-show-wikidata-pref' => 'వికీడామా మార్పులను ఇటీవలి మార్పులలో చూపించు',
);

/** Tagalog (Tagalog)
 * @author AnakngAraw
 */
$messages['tl'] = array(
	'wbc-desc' => 'Kliyente para sa dugtong na Wikibase',
	'wbc-editlinks' => 'Baguhin ang mga kawing',
	'wbc-editlinkstitle' => 'Baguhin ang mga kawing na para sa interwika',
);

/** Ukrainian (українська)
 * @author Ата
 */
$messages['uk'] = array(
	'wbc-desc' => 'Клієнт для розширення Wikibase',
	'wbc-after-page-move' => "Щоб виправити мовні посилання на перейменованій сторінці, Ви також можете [$1 оновити] пов'язаний елемент Вікіданих.",
	'wbc-comment-remove' => "Пов'язаний елемент Вікіданих видалений. Мовні посилання видалені.",
	'wbc-comment-linked' => 'Елемент Вікіданих посилався на цю сторінку.',
	'wbc-comment-unlink' => "Ця сторінка була від'єднана від елемента Вікіданих. Мовні посилання видалені.",
	'wbc-comment-restore' => "Пов'язаний елемент Вікіданих відновлений. Мовні посилання відновлені.",
	'wbc-comment-update' => 'Мовні посилання оновлені.',
	'wbc-comment-sitelink-add' => 'Додано мовне посилання: $1',
	'wbc-comment-sitelink-change' => 'Мовне посилання змінено з $1 на $2',
	'wbc-comment-sitelink-remove' => 'Мовне посилання видалено: $1',
	'wbc-editlinks' => 'Редагувати посилання',
	'wbc-editlinkstitle' => 'Редагувати міжмовні посилання',
	'wbc-rc-hide-wikidata' => '$1 Вікідані',
	'wbc-rc-show-wikidata-pref' => 'Показати зміни Вікіданих у списку нових редагувань',
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
 * @author Stevenliuyi
 * @author Yfdyh000
 */
$messages['zh-hans'] = array(
	'wbc-desc' => 'Wikibase扩展客户端',
	'wbc-after-page-move' => '您还可以[$1 更新]关联的维基数据项目，使其链接至移动后的页面。',
	'wbc-comment-remove' => '关联的维基数据项目已删除。语言链接已移除。',
	'wbc-comment-linked' => '一个维基数据项目已链接至此页面。',
	'wbc-comment-unlink' => '本页已解除维基数据项目的链接。语言链接已移除。',
	'wbc-comment-restore' => '关联的维基数据项目已还原。语言链接已恢复。',
	'wbc-comment-update' => '语言链接已更新。',
	'wbc-comment-sitelink-add' => '添加语言链接：$1',
	'wbc-comment-sitelink-change' => '语言链接从$1更改为$2',
	'wbc-comment-sitelink-remove' => '删除语言链接：$1',
	'wbc-editlinks' => '编辑链接',
	'wbc-editlinkstitle' => '编辑跨语言链接',
	'wbc-rc-hide-wikidata' => '$1维基数据',
	'wbc-rc-show-wikidata-pref' => '在最近更改中显示维基数据的编辑',
);

/** Traditional Chinese (中文（繁體）‎)
 * @author Stevenliuyi
 */
$messages['zh-hant'] = array(
	'wbc-desc' => 'Wikibase擴展客戶端',
	'wbc-after-page-move' => '您還可以[$1 更新]關聯的維基數據項目，使其連結至移動後的頁面。',
	'wbc-comment-remove' => '關聯的維基數據項目已刪除。語言連結已移除。',
	'wbc-comment-linked' => '一個維基數據項目已連結至此頁面。',
	'wbc-comment-unlink' => '本頁已解除維基數據項目的連結。語言連結已移除。',
	'wbc-comment-restore' => '關聯的維基數據項目已還原。語言連結已恢復。',
	'wbc-comment-update' => '語言連結已更新。',
	'wbc-comment-sitelink-add' => '添加語言連結：$1',
	'wbc-comment-sitelink-change' => '語言連結從$1更改為$2',
	'wbc-comment-sitelink-remove' => '刪除語言連結：$1',
	'wbc-editlinks' => '編輯鏈接',
	'wbc-editlinkstitle' => '編輯跨語言鏈接',
	'wbc-rc-hide-wikidata' => '$1維基數據',
	'wbc-rc-show-wikidata-pref' => '在最近更改中顯示維基數據的編輯',
);
