<?php
/**
 * Internationalization file for the Wikibase extension.
 *
 * @since 0.1
 *
 * @file Wikibase.i18n.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 */

$messages = array();

/** English
 * @author Daniel Kinzler
 * @author Tobias Gritschacher
 */
$messages['en'] = array(
	'wikibase-desc' => 'Structured data repository',
	'wikibase-edit' => 'edit',
	'wikibase-save' => 'save',
	'wikibase-cancel' => 'cancel',
	'wikibase-add' => 'add',
	'wikibase-label-edit-placeholder' => 'enter label',
	'wikibase-description-edit-placeholder' => 'enter description',

	'wikibase-sitelink-site-edit-placeholder' => 'specify site',
	'wikibase-sitelink-page-edit-placeholder' => 'specify page',

	'wikibase-label-input-help-message' => 'Enter the title of this data set in $1.',
	'wikibase-description-input-help-message' => 'Enter a short desription in $1.',
	'wikibase-sitelinks' => 'Wikipedia Pages linked to this item',
	'wikibase-sitelinks-add' => 'add a link to a Wikipedia page',
	'wikibase-sitelinks-empty' => 'No Wikipedia pages linked to this item yet.',
	'wikibase-sitelinks-input-help-message' => 'Set a link to a Wikipedia article.',
	'wikibase-remove' => 'remove',
	'wikibase-propertyedittool-full' => 'List of values is complete.',
	'wikibase-sitelinksedittool-full' => 'Links to pages are already set for all known sites.',

	// Special pages
	'special-itembytitle' => 'Item by title',
	'special-itembylabel' => 'Item by label',
);

/** Message documentation (Message documentation)
 * @author Jeblad
 */
$messages['qqq'] = array(
	'wikibase-desc' => '{{desc}} See also [[m:Wikidata/Glossary#Wikidata|Wikidata]].',
	'wikibase-edit' => 'This is a generic text used for a link (fig. 1 and 3 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that puts the user interface into edit mode for an existing element of some kind.',
	'wikibase-save' => 'This is a generic text used for a link (fig. 2 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that saves what the user has done while the user interface has been in edit mode.',
	'wikibase-cancel' => 'This is a generic text used for a link (fig. 2 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that cancels what the user has done while the user interface has been in edit mode.',
	'wikibase-add' => 'This is a generic text used for a link (fig. 3 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that puts the user interface into edit mode for an additional element of some kind.',
	'wikibase-label-edit-placeholder' => 'This is a generic text used while editing a (possibly new) label. See also Wikidatas glossary on [[m:Wikidata/Glossary#languageattribute-label|label]].',
	'wikibase-description-edit-placeholder' => 'This is a generic text used while editing a (possibly new) description. See also Wikidatas glossary on [[m:Wikidata/Glossary#languageattribute-description|description]].',
	'wikibase-sitelink-site-edit-placeholder' => 'This is a generic text used while defining the site for a (possibly new) sitelink. See also Wikidatas glossary on [[m:Wikidata/Glossary#sitelink|sitelink]].',
	'wikibase-sitelink-page-edit-placeholder' => 'This is a generic text used while defining the page for a (possibly new) sitelink.',
	'wikibase-label-input-help-message' => 'Bubble help message for entering the label of the data set used for a specific item. Takes on additional argument that is the sub site identifier, ie. "English" in nominative singular form. See also Wikidatas glossary for [[m:Wikidata/Glossary#languageattribute-label|label]] and [[m:Wikidata/Glossary#Item|item]].',
	'wikibase-description-input-help-message' => 'Bubble help message for entering the description of the data set used for a specific item. Takes on additional argument that is the sub site identifier, ie. "English" in nominative singular form. See also Wikidatas glossary for [[m:Wikidata/Glossary#languageattribute-description|description]] and [[m:Wikidata/Glossary#Item|item]].',
	'wikibase-sitelinks' => 'Header messages for pages on a specific cluster of sites linked to this item. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]] and [[m:Wikidata/Glossary#Item|item]].',
	'wikibase-sitelinks-add' => 'Add a sitelink to a language specific page on the cluster. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]].',
	'wikibase-sitelinks-empty' => 'There are no sitelinks for any of the language specific pages on the given cluster.  See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]] and [[m:Wikidata/Glossary#sitelinks-title|title]].',
	'wikibase-sitelinks-input-help-message' => 'Bubble help message to set a sitelink to a language specific page on a given cluster. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]] and [[m:Wikidata/Glossary#sitelinks-title|title]].',
	'wikibase-remove' => 'This is a generic text used for a link (fig. 3 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that removes an element of some kind, without the the user interface is put in edit mode.',
	'wikibase-propertyedittool-full' => 'A list of elements the user is assumed to enter is now complete.',
	'wikibase-sitelinksedittool-full' => 'The list of elements the user can enter is exhausted and there are no additional sites available. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]].',
	'special-itembytitle' => 'The item is identified through use of the title alone and must be disambiguated as there might be several sites that uses the same title for pages. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks-title|title]] and [[m:Wikidata/Glossary#Sitelinks-site|site]].',
	'special-itembylabel' => 'The item is identified through use of the label alone and must be disambiguated as there might be several entities that uses the same label for items. See also Wikidatas glossary for [[m:Wikidata/Glossary#languageattribute-label|label]] and [[m:Wikidata/Glossary#Items|items]].',
);

/** Belarusian (Taraškievica orthography) (‪Беларуская (тарашкевіца)‬)
 * @author Wizardist
 */
$messages['be-tarask'] = array(
	'wikibase-desc' => 'Сховішча структураваных зьвестак',
	'wikibase-edit' => 'рэдагаваць',
	'wikibase-save' => 'захаваць',
	'wikibase-cancel' => 'скасаваць',
	'wikibase-add' => 'дадаць',
	'wikibase-label-edit-placeholder' => 'увядзіце метку',
	'wikibase-description-edit-placeholder' => 'увядзіце апісаньне',
	'wikibase-sitelink-site-edit-placeholder' => 'пазначце сайт',
	'wikibase-sitelink-page-edit-placeholder' => 'пазначце старонку',
	'wikibase-label-input-help-message' => 'Увядзіце назву гэтага набору зьвестак у $1.',
	'wikibase-description-input-help-message' => 'Увядзіце кароткае апісаньне ў $1.',
	'wikibase-sitelinks' => 'Старонкі Вікіпэдыі, што спасылаюцца на гэты аб’ект',
	'wikibase-sitelinks-add' => 'дадаць спасылку да старонкі Вікіпэдыі',
	'wikibase-sitelinks-empty' => 'Ніводная старонка Вікіпэдыі дагэтуль не спасылаецца на аб’ект.',
	'wikibase-sitelinks-input-help-message' => 'Дадайце спасылку на артыкул у Вікіпэдыі.',
	'wikibase-remove' => 'выдаліць',
	'wikibase-propertyedittool-full' => 'Сьпіс значэньняў выкананы.',
	'wikibase-sitelinksedittool-full' => 'Спасылкі на старонкі ёсьць ужо для ўсіх вядомых сайтаў.',
	'special-itembytitle' => 'Аб’ект паводле назвы',
	'special-itembylabel' => 'Аб’ект паводле меткі',
);

/** German (Deutsch)
 * @author Kghbln
 */
$messages['de'] = array(
	'wikibase-desc' => 'Ermöglicht ein Repositorium strukturierter Daten',
	'wikibase-edit' => 'bearbeiten',
	'wikibase-save' => 'speichern',
	'wikibase-cancel' => 'abbrechen',
	'wikibase-add' => 'hinzufügen',
	'wikibase-label-edit-placeholder' => 'Bezeichnung eingeben',
	'wikibase-description-edit-placeholder' => 'Beschreibung eingeben',
	'wikibase-sitelink-site-edit-placeholder' => 'Website angeben',
	'wikibase-sitelink-page-edit-placeholder' => 'Seite angeben',
	'wikibase-label-input-help-message' => 'Gib den Namen für diesen Datensatz in $1 an.',
	'wikibase-description-input-help-message' => 'Gib eine kurze Beschreibung in $1 an.',
	'wikibase-sitelinks' => '{{SITENAME}}-Seiten, die mit diesem Datenelement verknüpft sind',
	'wikibase-sitelinks-add' => 'füge eine Verknüpfung zu einer {{SITENAME}}-Seite hinzu',
	'wikibase-sitelinks-empty' => 'Bislang sind keine {{SITENAME}}-Seiten mit diesem Datenelement verknüpft.',
	'wikibase-sitelinks-input-help-message' => 'Leg eine Verknüpfung zu einer {{SITENAME}}-Seite fest.',
	'wikibase-remove' => 'entfernen',
	'wikibase-propertyedittool-full' => 'Die Werteliste ist vollständig.',
	'wikibase-sitelinksedittool-full' => 'Für alle bekannten Websites sind die Links auf die Seiten bereits festgelegt.',
	'special-itembytitle' => 'Wert nach Name',
	'special-itembylabel' => 'Wert nach Bezeichnung',
);

/** German (formal address) (‪Deutsch (Sie-Form)‬)
 * @author Kghbln
 */
$messages['de-formal'] = array(
	'wikibase-label-input-help-message' => 'Geben Sie den Namen für diesen Datensatz in $1 an.',
	'wikibase-description-input-help-message' => 'Geben Sie eine kurze Beschreibung in $1 an.',
	'wikibase-sitelinks-add' => 'fügen Sie eine Verknüpfung zu einer {{SITENAME}}-Seite hinzu',
	'wikibase-sitelinks-input-help-message' => 'Legen Sie eine Verknüpfung zu einer {{SITENAME}}-Seite fest.',
);

/** Lower Sorbian (Dolnoserbski)
 * @author Michawiki
 */
$messages['dsb'] = array(
	'wikibase-desc' => 'Repozitorium strukturěrowanych datow',
	'wikibase-edit' => 'wobźěłaś',
	'wikibase-save' => 'składowaś',
	'wikibase-cancel' => 'pśetergnuś',
	'wikibase-add' => 'pśidaś',
	'wikibase-label-edit-placeholder' => 'pomjenjenje zapódaś',
	'wikibase-description-edit-placeholder' => 'wopisanje zapódaś',
	'wikibase-sitelink-site-edit-placeholder' => 'sedło pódaś',
	'wikibase-sitelink-page-edit-placeholder' => 'bok pódaś',
	'wikibase-label-input-help-message' => 'Zapódaj mě toś teje datoweje sajźby w $1.',
	'wikibase-description-input-help-message' => 'Zapódaj krotke wopisanje w $1.',
	'wikibase-sitelinks' => 'Boki Wikipedije, kótarež su z toś tym elementom zwězane',
	'wikibase-sitelinks-add' => 'wótkaz bokoju Wikipedije pśidaś',
	'wikibase-sitelinks-empty' => 'Až doněnta žedne boki Wikipedije njejsu zwězane z toś tym elementom.',
	'wikibase-sitelinks-input-help-message' => 'Póstaj wótkaz k nastawkoju Wikipedije.',
	'wikibase-remove' => 'wótpóraś',
	'wikibase-propertyedittool-full' => 'Lisćina gódnotow jo dopołna.',
	'wikibase-sitelinksedittool-full' => 'Wótkaze k bokam su južo za wšykne znate sedła nastajone.',
	'special-itembytitle' => 'Zapisk pó titelu',
	'special-itembylabel' => 'Zapisk pó pomjenjenju',
);

/** Spanish (Español)
 * @author Armando-Martin
 */
$messages['es'] = array(
	'wikibase-desc' => 'Repositorio de datos estructurados',
	'wikibase-edit' => 'editar',
	'wikibase-save' => 'guardar',
	'wikibase-cancel' => 'cancelar',
	'wikibase-add' => 'añadir',
	'wikibase-label-edit-placeholder' => 'introducir la etiqueta',
	'wikibase-description-edit-placeholder' => 'introducir una descripción',
	'wikibase-sitelink-site-edit-placeholder' => 'especificar el sitio',
	'wikibase-sitelink-page-edit-placeholder' => 'especificar la página',
	'wikibase-label-input-help-message' => 'Introducir el título de este conjunto de datos en  $1.',
	'wikibase-description-input-help-message' => 'Introducir una breve descripción en  $1.',
	'wikibase-sitelinks' => 'Páginas de {{SITENAME}} enlazadas a este elemento',
	'wikibase-sitelinks-add' => 'Agregar un enlace a una página de {{SITENAME}}',
	'wikibase-sitelinks-empty' => 'No hay todavía ninguna página de {{SITENAME}} enlazada a este elemento.',
	'wikibase-sitelinks-input-help-message' => 'Poner un enlace a un artículo de Wikipedia',
	'wikibase-remove' => 'eliminar',
	'wikibase-propertyedittool-full' => 'La lista de valores está completa.',
	'wikibase-sitelinksedittool-full' => 'Los enlaces a las páginas están ya definidos para todos los sitios conocidos.',
	'special-itembytitle' => 'Artículo por título',
	'special-itembylabel' => 'Artículo por etiqueta',
);

/** Persian (فارسی)
 * @author ZxxZxxZ
 */
$messages['fa'] = array(
	'wikibase-desc' => 'داده‌های ساخت‌یافتهٔ مخزن',
	'wikibase-edit' => 'ویرایش',
	'wikibase-save' => 'ذخیره',
	'wikibase-cancel' => 'انصراف',
	'wikibase-add' => 'افزودن',
	'wikibase-label-edit-placeholder' => 'واردکردن برچسب',
	'wikibase-description-edit-placeholder' => 'واردکردن توضیحات',
	'wikibase-sitelink-site-edit-placeholder' => 'مشخص‌کردن وب‌گاه',
	'wikibase-sitelink-page-edit-placeholder' => 'مشخص‌کردن صفحه',
	'wikibase-label-input-help-message' => 'واردکردن عنوان این مجموعه داده‌ها در $1.',
	'wikibase-description-input-help-message' => 'توضیحات کوتاهی در $1 وارد کنید.',
	'wikibase-sitelinks' => 'صفحه‌هایی از ویکی‌پدیا که به این آیتم پیوند دارند',
	'wikibase-sitelinks-add' => 'افزودن پیوند به یک صفحه از ویکی‌پدیا',
	'wikibase-sitelinks-empty' => 'هیچ صفحه‌ای از ویکی‌پدیا به این آیتم پیوند ندارد.',
	'wikibase-sitelinks-input-help-message' => 'تنظیم یک لینک به مقاله‌ای از ویکی‌پدیا.',
	'wikibase-remove' => 'حذف',
	'wikibase-propertyedittool-full' => 'فهرست مقادیر کامل است.',
	'wikibase-sitelinksedittool-full' => 'پیوندها به صفحه‌ها در حال حاضر برای همهٔ وب‌گاه‌های شناخته‌شده تنظیم شده‌اند.',
	'special-itembytitle' => 'آیتم بر اساس عنوان',
	'special-itembylabel' => 'آیتم بر اساس برچسب',
);

/** French (Français)
 * @author Gomoko
 * @author Wyz
 */
$messages['fr'] = array(
	'wikibase-desc' => 'Référentiel de données structurées',
	'wikibase-edit' => 'modifier',
	'wikibase-save' => 'enregistrer',
	'wikibase-cancel' => 'annuler',
	'wikibase-add' => 'ajouter',
	'wikibase-label-edit-placeholder' => 'saisir étiquette',
	'wikibase-description-edit-placeholder' => 'saisir description',
	'wikibase-sitelink-site-edit-placeholder' => 'spécifier le site',
	'wikibase-sitelink-page-edit-placeholder' => 'spécifier la page',
	'wikibase-label-input-help-message' => 'Saisissez le titre de ces données définies dans $1.',
	'wikibase-description-input-help-message' => 'Saisissez une courte description dans $1.',
	'wikibase-sitelinks' => 'Pages Wikipédia liées à cet élément',
	'wikibase-sitelinks-add' => 'ajouter un lien vers une page de Wikipédia',
	'wikibase-sitelinks-empty' => "Aucune page de Wikipédia n'est encore liée à cet élément.",
	'wikibase-sitelinks-input-help-message' => 'Mettre un lien vers un article de Wikipédia.',
	'wikibase-remove' => 'retirer',
	'wikibase-propertyedittool-full' => 'La liste des valeurs est complète.',
	'wikibase-sitelinksedittool-full' => 'Les liens vers les pages sont déjà définis pour tous les sites connus.',
	'special-itembytitle' => 'Article par titre',
	'special-itembylabel' => 'Article par étiquette',
);

/** Swiss German (Alemannisch)
 * @author Als-Holder
 */
$messages['gsw'] = array(
	'wikibase-desc' => 'Repositorium vu strukturierte Date',
	'wikibase-edit' => 'bearbeite',
	'wikibase-save' => 'spyychere',
	'wikibase-cancel' => 'abbräche',
	'wikibase-add' => 'zuefiege',
	'wikibase-label-edit-placeholder' => 'Bezeichnig yygee',
	'wikibase-description-edit-placeholder' => 'Bschryybig yygee',
	'wikibase-label-input-help-message' => 'Gib dr Name fir dää Datesatz in $1 aa.',
	'wikibase-description-input-help-message' => 'Gib e churzi Bschryybig in $1 aa.',
	'wikibase-sitelinks' => '{{SITENAME}}-Syte, wu mit däm Datenelemänt verchnipft sin',
	'wikibase-sitelinks-add' => 'fieg e Verchnipfig zuen ere {{SITENAME}}-Syte zue',
	'wikibase-sitelinks-empty' => 'Bishär sin kei {{SITENAME}}-Syte mit däm Datenelemänt verchnipft.',
	'wikibase-remove' => 'uuseneh',
);

/** Upper Sorbian (Hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'wikibase-desc' => 'Repozitorij strukturowanych datow',
	'wikibase-edit' => 'wobdźěłać',
	'wikibase-save' => 'składować',
	'wikibase-cancel' => 'přetorhnyć',
	'wikibase-add' => 'přidać',
	'wikibase-label-edit-placeholder' => 'pomjenowanje zapodać',
	'wikibase-description-edit-placeholder' => 'wopisanje zapodać',
	'wikibase-sitelink-site-edit-placeholder' => 'sydło podać',
	'wikibase-sitelink-page-edit-placeholder' => 'stronu podać',
	'wikibase-label-input-help-message' => 'Zapodaj mjeno tuteje datoweje sadźby w $1.',
	'wikibase-description-input-help-message' => 'Zapodaj krótke wopisanje w $1.',
	'wikibase-sitelinks' => 'Strony Wikipedije, kotrež su z tutym elementom zwjazane',
	'wikibase-sitelinks-add' => 'wotkaz stronje Wikipedije přidać',
	'wikibase-sitelinks-empty' => 'Dotal žane strony Wikipedije z tutym elementom zwjazane njejsu.',
	'wikibase-sitelinks-input-help-message' => 'Wotkaz k nastawkej Wikipedije stajić.',
	'wikibase-remove' => 'wotstronić',
	'wikibase-propertyedittool-full' => 'Lisćina hódnotow je dospołna.',
	'wikibase-sitelinksedittool-full' => 'Wotkazy k stronam su hižo za wšě znate strony stajene.',
	'special-itembytitle' => 'Zapisk po titulu',
	'special-itembylabel' => 'Zapisk po pomjenowanju',
);

/** Icelandic (Íslenska)
 * @author Snævar
 */
$messages['is'] = array(
	'wikibase-desc' => 'Skipulagður gagnaþjónn',
	'wikibase-edit' => 'breyta',
	'wikibase-save' => 'vista',
	'wikibase-cancel' => 'hætta við',
	'wikibase-add' => 'bæta við',
	'wikibase-label-edit-placeholder' => 'bæta við merki',
	'wikibase-description-edit-placeholder' => 'bæta við lýsingu',
	'wikibase-sitelink-site-edit-placeholder' => 'tilgreindu vefsvæði',
	'wikibase-sitelink-page-edit-placeholder' => 'tilgreindu síðu',
	'wikibase-label-input-help-message' => 'Sláðu inn titil á þessum gögnum í $1.',
	'wikibase-description-input-help-message' => 'Sláðu inn stutta lýsingu í $1.',
	'wikibase-sitelinks' => 'Wikipedia síður sem tengja á þennan hlut',
	'wikibase-sitelinks-add' => 'bæta við tengli á Wikipedia síðu',
	'wikibase-sitelinks-empty' => 'Engar Wikipedia síður tengja á þennan hlut ennþá.',
	'wikibase-sitelinks-input-help-message' => 'Setja tengil á Wikipedia grein.',
	'wikibase-remove' => 'fjarlægja',
	'wikibase-propertyedittool-full' => 'Listi yfir gildi er tilbúinn.',
	'wikibase-sitelinksedittool-full' => 'Tenglar á síður eru þegar virkir fyrir öll þekkt vefsvæði.',
	'special-itembytitle' => 'Hlutur eftir titli',
	'special-itembylabel' => 'Hlutur eftir merki',
);

/** Italian (Italiano)
 * @author Beta16
 */
$messages['it'] = array(
	'wikibase-desc' => 'Repository di dati strutturati',
	'wikibase-edit' => 'modifica',
	'wikibase-save' => 'salva',
	'wikibase-cancel' => 'annulla',
	'wikibase-add' => 'aggiungi',
	'wikibase-label-edit-placeholder' => 'inserisci etichetta',
	'wikibase-description-edit-placeholder' => 'inserisci descrizione',
	'wikibase-sitelink-site-edit-placeholder' => 'specifica sito',
	'wikibase-sitelink-page-edit-placeholder' => 'specifica pagina',
	'wikibase-label-input-help-message' => 'Inserisci il titolo di questo insieme di dati in $1.',
	'wikibase-description-input-help-message' => 'Inserisci una breve descrizione in $1.',
	'wikibase-sitelinks' => 'Pagine di Wikipedia che sono collegate a questo elemento',
	'wikibase-sitelinks-add' => 'aggiungi un collegamento ad una pagina di Wikipedia',
	'wikibase-sitelinks-empty' => 'Nessuna pagina di Wikipedia ancora è collegata a questo elemento.',
	'wikibase-sitelinks-input-help-message' => 'Imposta un collegamento ad una voce di Wikipedia.',
	'wikibase-remove' => 'rimuovi',
	'wikibase-propertyedittool-full' => "L'elenco dei valori è completo.",
	'wikibase-sitelinksedittool-full' => 'Sono già stati impostati collegamenti alle pagine per tutti i siti conosciuti.',
	'special-itembytitle' => 'Elementi per titolo',
	'special-itembylabel' => 'Elementi per etichetta',
);

/** Japanese (日本語)
 * @author Shirayuki
 */
$messages['ja'] = array(
	'wikibase-desc' => '構造化されたデータリポジトリー',
	'wikibase-edit' => '編集',
	'wikibase-save' => '保存',
	'wikibase-cancel' => 'キャンセル',
	'wikibase-add' => '追加',
	'wikibase-label-edit-placeholder' => 'ラベルを入力',
	'wikibase-description-edit-placeholder' => '説明を入力',
	'wikibase-sitelink-site-edit-placeholder' => 'サイトを指定',
	'wikibase-sitelink-page-edit-placeholder' => 'ページを指定',
	'wikibase-sitelinks-add' => 'ウィキペディアのページへのリンクを追加',
	'wikibase-remove' => '除去',
);

/** Kurdish (Latin script) (‪Kurdî (latînî)‬)
 * @author George Animal
 */
$messages['ku-latn'] = array(
	'wikibase-edit' => 'biguherîne',
	'wikibase-save' => 'tomar bike',
	'wikibase-cancel' => 'betal bike',
	'wikibase-description-input-help-message' => 'Danasîneka kurt têkeve $1',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'wikibase-edit' => 'änneren',
	'wikibase-save' => 'späicheren',
	'wikibase-cancel' => 'ofbriechen',
	'wikibase-add' => 'derbäisetzen',
	'wikibase-description-edit-placeholder' => 'Beschreiwung aginn',
	'wikibase-sitelinks-add' => 'e Link op eng Wikipedia-Säit derbäisetzen',
	'wikibase-remove' => 'ewechhuelen',
);

/** Macedonian (Македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'wikibase-desc' => 'Складиште на структурирани податоци',
	'wikibase-edit' => 'уреди',
	'wikibase-save' => 'зачувај',
	'wikibase-cancel' => 'откажи',
	'wikibase-add' => 'додај',
	'wikibase-label-edit-placeholder' => 'внесете натпис',
	'wikibase-description-edit-placeholder' => 'внесете опис',
	'wikibase-sitelink-site-edit-placeholder' => 'укажете вики',
	'wikibase-sitelink-page-edit-placeholder' => 'укажете страница',
	'wikibase-label-input-help-message' => 'Внесете го насловот на податочниот збир во $1.',
	'wikibase-description-input-help-message' => 'Внесете краток опис за $1.',
	'wikibase-sitelinks' => 'Страници од {{SITENAME}} поврзани со оваа ставка',
	'wikibase-sitelinks-add' => 'додај врска до страница од {{SITENAME}}',
	'wikibase-sitelinks-empty' => '!Досега нема страници од {{SITENAME}} поврзани со оваа ставка.',
	'wikibase-sitelinks-input-help-message' => 'Задајте врска до статија од Википедија.',
	'wikibase-remove' => 'отстрани',
	'wikibase-propertyedittool-full' => 'Списокот на вредности е исполнет.',
	'wikibase-sitelinksedittool-full' => 'Веќе се зададени врски за страници на сите познати викија.',
	'special-itembytitle' => 'Ставка по наслов',
	'special-itembylabel' => 'Ставка по натпис',
);

/** Dutch (Nederlands)
 * @author SPQRobin
 * @author Siebrand
 */
$messages['nl'] = array(
	'wikibase-desc' => 'Repository voor gestructureerde gegevens',
	'wikibase-edit' => 'bewerken',
	'wikibase-save' => 'opslaan',
	'wikibase-cancel' => 'annuleren',
	'wikibase-add' => 'toevoegen',
	'wikibase-label-edit-placeholder' => 'geef een label op',
	'wikibase-description-edit-placeholder' => 'geef een beschrijving op',
	'wikibase-sitelink-site-edit-placeholder' => 'site opgeven',
	'wikibase-sitelink-page-edit-placeholder' => 'pagina opgeven',
	'wikibase-label-input-help-message' => 'Geef de naam van deze gegevensset in in $1.',
	'wikibase-description-input-help-message' => 'Geef een korte beschrijving in in $1.',
	'wikibase-sitelinks' => "{{SITENAME}}-pagina's gekoppeld aan dit item",
	'wikibase-sitelinks-add' => 'verwijzing toevoegen naar een Wikipediapagina',
	'wikibase-sitelinks-empty' => "Er zijn nog geen {{SITENAME}}-pagina's gekoppeld aan dit item.",
	'wikibase-sitelinks-input-help-message' => 'Geef een koppeling in naar een pagina in Wikipedia.',
	'wikibase-remove' => 'verwijderen',
	'wikibase-propertyedittool-full' => 'De lijst met waarden is compleet.',
	'wikibase-sitelinksedittool-full' => "Verwijzingen naar pagina's die al zijn ingesteld voor alle bekende sites.",
	'special-itembytitle' => 'Item gesorteerd op naam',
	'special-itembylabel' => 'Item gesorteerd op label',
);

/** Norwegian Nynorsk (‪Norsk (nynorsk)‬)
 * @author Jeblad
 */
$messages['nn'] = array(
	'wikibase-desc' => 'Strukturert datalager',
	'wikibase-edit' => 'endre',
	'wikibase-save' => 'lagre',
	'wikibase-cancel' => 'avbryt',
	'wikibase-add' => 'legg til',
	'wikibase-label-edit-placeholder' => 'lag merkelapp',
	'wikibase-description-edit-placeholder' => 'lag beskriving',
	'wikibase-sitelink-site-edit-placeholder' => 'oppgje nettstad',
	'wikibase-sitelink-page-edit-placeholder' => 'oppgje side',
	'wikibase-label-input-help-message' => 'Lag ein merkelapp for datasettet knytt til $1.',
	'wikibase-description-input-help-message' => 'Lag ein kort beskriving for datasettet knytt til $1.',
	'wikibase-sitelinks' => 'Sidene som er knytt til dette datasettet',
	'wikibase-sitelinks-add' => 'Legg til ein nettstadlekk',
	'wikibase-sitelinks-empty' => 'Det fins ingen nettstadlekker',
	'wikibase-sitelinks-input-help-message' => 'Definer ein nettstadlekk slik at den peiker på ein artikkel.',
	'wikibase-remove' => 'fjern',
	'wikibase-propertyedittool-full' => 'Lista av verdiar er nå komplett',
	'wikibase-sitelinksedittool-full' => 'Det er ikkje fleire nettstadar tilgjengeleg',
	'special-itembytitle' => 'Eit datasett er påvist ved bruk av tittel',
	'special-itembylabel' => 'Eit datasett er påvist ved bruk av merkelapp',
);

/** Portuguese (Português)
 * @author Malafaya
 */
$messages['pt'] = array(
	'wikibase-desc' => 'Repositório de dados estruturados',
	'wikibase-edit' => 'editar',
	'wikibase-save' => 'gravar',
	'wikibase-cancel' => 'cancelar',
	'wikibase-add' => 'adicionar',
	'wikibase-label-edit-placeholder' => 'introduza etiqueta',
	'wikibase-description-edit-placeholder' => 'introduza descrição',
	'wikibase-label-input-help-message' => 'Introduza o título deste conjunto de dados em  $1.',
	'wikibase-description-input-help-message' => 'Insira uma curta descrição em  $1 .',
	'wikibase-sitelinks' => 'Páginas da Wikipédia ligadas a este item',
	'wikibase-sitelinks-add' => 'adicionar uma ligação para uma página da Wikipédia',
	'wikibase-sitelinks-empty' => 'Nenhuma página da Wikipédia liga a este item ainda.',
	'wikibase-remove' => 'remover',
);

/** Brazilian Portuguese (Português do Brasil)
 * @author Jaideraf
 */
$messages['pt-br'] = array(
	'wikibase-desc' => 'Repositório de dados estruturados',
	'wikibase-edit' => 'editar',
	'wikibase-save' => 'salvar',
	'wikibase-cancel' => 'cancelar',
	'wikibase-add' => 'adicionar',
	'wikibase-label-edit-placeholder' => 'insira um rótulo',
	'wikibase-description-edit-placeholder' => 'insira uma descrição',
	'wikibase-label-input-help-message' => 'Insira o título deste conjunto de dados em $1.',
	'wikibase-description-input-help-message' => 'Insira uma curta descrição em $1 .',
	'wikibase-sitelinks' => 'Páginas da Wikipédia linkadas a este item',
	'wikibase-sitelinks-add' => 'adicione um link para uma página da Wikipédia',
	'wikibase-sitelinks-empty' => 'Ainda não há qualquer página da Wikipédia linkada a este item.',
	'wikibase-remove' => 'remover',
);

/** Russian (Русский)
 * @author Kaganer
 * @author Александр Сигачёв
 */
$messages['ru'] = array(
	'wikibase-desc' => 'Хранилище структурированных данных',
	'wikibase-edit' => 'редактировать',
	'wikibase-save' => 'сохранить',
	'wikibase-cancel' => 'отменить',
	'wikibase-add' => 'добавить',
	'wikibase-label-edit-placeholder' => 'введите метку',
	'wikibase-description-edit-placeholder' => 'введите описание',
	'wikibase-label-input-help-message' => 'Введите название этого набора данных в $1.',
	'wikibase-description-input-help-message' => 'Введите краткое описание в $1.',
	'wikibase-sitelinks' => 'Страницы Википедии, ссылающиеся на этот элемент',
	'wikibase-sitelinks-add' => 'добавить ссылку на страницу Википедии',
	'wikibase-sitelinks-empty' => 'Ни одна страница Википедии ещё не ссылается сюда.',
	'wikibase-remove' => 'убрать',
);

/** Swedish (Svenska)
 * @author Ainali
 * @author WikiPhoenix
 */
$messages['sv'] = array(
	'wikibase-desc' => 'Strukturerad datalagring',
	'wikibase-edit' => 'redigera',
	'wikibase-save' => 'spara',
	'wikibase-cancel' => 'avbryt',
	'wikibase-add' => 'lägg till',
	'wikibase-label-edit-placeholder' => 'ange etikett',
	'wikibase-description-edit-placeholder' => 'ange beskrivning',
	'wikibase-label-input-help-message' => 'Ange titeln på detta datat i  $1 .',
	'wikibase-description-input-help-message' => 'Ange en kort beskrivning i  $1.',
	'wikibase-sitelinks' => 'Wikipedia-sidor som är länkade till det här objektet',
	'wikibase-sitelinks-add' => 'lägg till en länk till en Wikipedia-sida',
	'wikibase-sitelinks-empty' => 'Inga Wikipedia-sidor länkade till det här objektet ännu.',
	'wikibase-sitelinks-input-help-message' => 'Ange en länk till en Wikipedia-artikel.',
	'wikibase-remove' => 'ta bort',
	'wikibase-propertyedittool-full' => 'Lista över värden är färdig.',
);

/** Tamil (தமிழ்)
 * @author Logicwiki
 */
$messages['ta'] = array(
	'wikibase-edit' => 'தொகு',
	'wikibase-save' => 'சேமி',
	'wikibase-cancel' => 'ரத்து செய்',
	'wikibase-add' => 'சேர்',
	'wikibase-remove' => 'நீக்கு',
);

/** Telugu (తెలుగు)
 * @author Veeven
 */
$messages['te'] = array(
	'wikibase-save' => 'భద్రపరచు',
	'wikibase-cancel' => 'రద్దుచేయి',
	'wikibase-add' => 'చేర్చు',
	'wikibase-remove' => 'తొలగించు',
);

