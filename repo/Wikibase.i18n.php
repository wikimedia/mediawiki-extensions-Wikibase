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
	'wikibase-move-error' => 'You cannot move pages that are in the data namespace, and you cannot move pages into it.',
	'wikibase-sitelink-site-edit-placeholder' => 'specify site',
	'wikibase-sitelink-page-edit-placeholder' => 'specify page',
	'wikibase-label-input-help-message' => 'Enter the title of this data set in $1.',
	'wikibase-description-input-help-message' => 'Enter a short description in $1.',
	'wikibase-sitelinks' => 'List of Pages Linked to This Item',
	'wikibase-sitelinks-add' => 'add a link to a site-link',
	'wikibase-sitelinks-empty' => 'No site-link for this item yet.',
	'wikibase-sitelinks-input-help-message' => 'Set a link to a page related to this item.',
	'wikibase-remove' => 'remove',
	'wikibase-propertyedittool-full' => 'List of values is complete.',
	'wikibase-propertyedittool-counter' => '($1 {{PLURAL:$1|entry|entries}})',
	'wikibase-propertyedittool-counter-pending' => '($2$3 {{PLURAL:$1|entry|entries}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|One value|$1 values}} not saved yet',
	'wikibase-sitelinksedittool-full' => 'Links to pages are already set for all known sites.',
	'wikibase-disambiguation-title' => 'Disambiguation for "$1"',

	'wikibase-tooltip-error-details' => 'Details',
	'wikibase-error-save-generic' => 'An error occurred while trying to save your changes. Your changes could not be stored.',
	'wikibase-error-save-connection' => 'Your changes could not be stored. Please check your internet connection.',
	'wikibase-error-save-timeout' => 'We are experiencing technical difficulties. Your changes could not be stored.',

	// Special pages
	'special-itembytitle' => 'Item by title',
	'special-itembylabel' => 'Item by label',

	// API errors
	'wikibase-api-aliases-invalid-list' => 'You need to either provide the set parameter xor the add or remove parameters',
	'wikibase-api-no-such-item' => 'There are no such item to be found',
	'wikibase-api-no-token' => 'There are no token given',
	'wikibase-api-no-data' => 'It is not found any data to operate upon',
	'wikibase-api-cant-edit' => 'The logged in user is not allowed to edit',
	'wikibase-api-no-permissions' => 'The logged in user does not have sufficient rights',
	'wikibase-api-id-xor-wikititle' => 'Either provide the item ids or a site-title pair for a corresponding page',
	'wikibase-api-no-such-item' => 'Could not find an existing item',
	'wikibase-api-no-such-item-id' => 'Could not find an existing item for this id',
	'wikibase-api-link-exists' => 'An article on the specified wiki is already linked',
	'wikibase-api-add-with-id' => 'Can not add with the id of an existing item',
	'wikibase-api-add-exists' => 'Can not add to an existing item',
	'wikibase-api-update-without-id' => 'Update without an previous id is not possible',
	'wikibase-api-no-such-item-link' => 'Could not find an existing item for this link',
	'wikibase-api-create-failed' => 'Attempted creation of new item failed',
	'wikibase-api-save-failed' => 'Attempted save of item failed',
	'wikibase-api-invalid-contentmodel' => 'The content model of the page on which the item is stored is invalid',
	'wikibase-api-alias-incomplete' => 'Can not find a definition of the alias for the item',
	'wikibase-api-alias-not-found' => 'Can not find any previous alias in the item',
	'wikibase-api-alias-found' => 'Found a previous alias in the item',
	'wikibase-api-not-recognized' => 'Directive is not recognized',
	'wikibase-api-label-or-description' => 'Use either or both of label and/or description, but not noen of them',
	'wikibase-api-label-not-found' => 'Can not find any previous label in the item',
	'wikibase-api-description-not-found' => 'Can not find any previous description in the item',
	'wikibase-api-label-found' => 'Found a previous label in the item',
	'wikibase-api-description-found' => 'Found a previous description in the item',
);

/** Message documentation (Message documentation) */
$messages['qqq'] = array(
	'wikibase-sitelinks' => 'Heading for the site links table.',
	'wikibase-propertyedittool-counter' => '$1 is the sum of elements in the list currently.',
	'wikibase-propertyedittool-counter-pending' => '$1 is the sum of elements in the list plus the ones pending (still in edit mode and not saved). $2 is the number of elements stored in the list (not pending). $3 is the message "wikibase-propertyedittool-counter-pending-pendingsubpart" with some additional markup around, expressing how many entries in the list are pending right now.',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => 'the number of pending elements within the list of site links and a leading "+". This will be inserted into "wikibase-propertyedittool-counter-pending" parameter $3.',
	'wikibase-desc' => '{{desc}}',
	'wikibase-disambiguation-title' => 'Disambiguation page title. $1 is the label of the item being disambiguated.',
	'wikibase-label-input-help-message' => '$1: name of the language',
	'wikibase-description-input-help-message' => '$1: name of the language',
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
	'wikibase-label-input-help-message' => 'Увядзіце назву гэтага набору зьвестак у $1.',
	'wikibase-description-input-help-message' => 'Увядзіце кароткае апісаньне ў $1.',
	'wikibase-sitelinks' => 'Старонкі Вікіпэдыі, што спасылаюцца на гэты аб’ект',
	'wikibase-sitelinks-add' => 'дадаць спасылку да старонкі Вікіпэдыі',
	'wikibase-sitelinks-empty' => 'Ніводная старонка Вікіпэдыі дагэтуль не спасылаецца на аб’ект.',
	'wikibase-remove' => 'выдаліць',
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
	'wikibase-label-input-help-message' => 'Zapódaj mě toś teje datoweje sajźby w $1.',
	'wikibase-description-input-help-message' => 'Zapódaj krotke wopisanje w $1.',
	'wikibase-sitelinks' => 'Boki Wikipedije, kótarež su z toś tym elementom zwězane',
	'wikibase-sitelinks-add' => 'wótkaz bokoju Wikipedije pśidaś',
	'wikibase-sitelinks-empty' => 'Až doněnta žedne boki Wikipedije njejsu zwězane z toś tym elementom.',
	'wikibase-remove' => 'wótpóraś',
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
	'wikibase-label-input-help-message' => 'Saisissez le titre de ces données définies dans $1.',
	'wikibase-description-input-help-message' => 'Saisissez une courte description dans $1.',
	'wikibase-sitelinks' => 'Pages Wikipédia liées à cet élément',
	'wikibase-sitelinks-add' => 'ajouter un lien vers une page de Wikipédia',
	'wikibase-sitelinks-empty' => "Aucune page de Wikipédia n'est encore liée à cet élément.",
	'wikibase-remove' => 'retirer',
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
	'wikibase-edit' => 'bewerken',
	'wikibase-save' => 'opslaan',
	'wikibase-cancel' => 'annuleren',
	'wikibase-add' => 'toevoegen',
	'wikibase-label-edit-placeholder' => 'geef een label op',
	'wikibase-description-edit-placeholder' => 'geef een beschrijving op',
	'wikibase-sitelinks' => "{{SITENAME}}-pagina's gekoppeld aan dit item",
	'wikibase-sitelinks-add' => 'verwijzing toevoegen naar een Wikipediapagina',
	'wikibase-sitelinks-empty' => "Er zijn nog geen {{SITENAME}}-pagina's gekoppeld aan dit item.",
	'wikibase-remove' => 'verwijderen',
);

/** Norwegian (Norsk bokmål)
 * @author jeblad
 */
$messages['no'] = array(
	'wikibase-desc' => 'Strukturert datalager',
	'wikibase-edit' => 'rediger',
	'wikibase-save' => 'lagre',
	'wikibase-cancel' => 'avdryt',
	'wikibase-add' => 'legg til',
	'wikibase-label-edit-placeholder' => 'lag etikett',
	'wikibase-description-edit-placeholder' => 'lag beskrivelse',
	'wikibase-label-input-help-message' => 'Legg til etikett for datasettet i $1.',
	'wikibase-description-input-help-message' => 'Legg til en kort beskrivelse for datasettet i $1.',
	'wikibase-sitelinks' => 'Sider på Wikipedia som er lenket til denne item',
	'wikibase-sitelinks-add' => 'legg til en lenke til en side på Wikipedia',
	'wikibase-sitelinks-empty' => 'Ingen sider på Wikipedia lenker til denne item.',
	'wikibase-remove' => 'fjern',
	'wikibase-api-no-such-item' => 'Kan ikke finne noen slik item',
	'wikibase-api-no-token' => 'Det er ikke gitt noe token',
	'wikibase-api-no-data' => 'Det er ikke gitt noe data som kan prosesseres',
	'wikibase-api-cant-edit' => 'Den innlogede brukeren har ikke lo til å redigere',
	'wikibase-api-no-permissions' => 'Den innloggede brukeren har ikke tilstrekkelige rettigheter',
	'wikibase-api-id-xor-wikititle' => 'Enten oppgi id\'er for items eller par av nettsted og titler for tilsvarende sider',
	'wikibase-api-no-such-item' => 'Det finnes ingen slik item',
	'wikibase-api-no-such-item-id' => 'Kan ikke finne en eksisterende item for denne id',
	'wikibase-api-link-exists' => 'En artikkel på den spesifiserte wikien er allerede lenket',
	'wikibase-api-add-with-id' => 'Kan ikke legge til når det finnes en id for et eksisterende item',
	'wikibase-api-add-exists' => 'Kan ikke legge til et eksisterende item',
	'wikibase-api-no-such-item-link' => 'Kan ikke finne en eksisterende item for denne lenken',
	'wikibase-api-create-failed' => 'Forsøk på å skape et nytt item feilet',
	'wikibase-api-invalid-contentmodel' => 'Innholdsmodellen for siden hvor denne item er lagret er ugyldig',
	'wikibase-api-alias-incomplete' => 'Kan ikke finne en definisjon for alias for denne item',
	'wikibase-api-alias-not-found' => 'Kan ikke finne noe tidligere alias i item',
	'wikibase-api-alias-found' => 'Fant et tidligere alias i item',
	'wikibase-api-not-recognized' => 'Denne operasjonen ble ikke gjenkjent',
	'wikibase-api-label-or-description' => 'Bruk enten en eller begge av etikett og/eller beskrivelse, men ikke ingen av dem',
	'wikibase-api-label-not-found' => 'Kan ikke finne noen tidligere etikett i item',
	'wikibase-api-description-not-found' => 'Kan ikke finne noen tidligere beskrivelse i item',
	'wikibase-api-label-found' => 'Fant en tidligere etikett i item',
	'wikibase-api-description-found' => 'Fant en tidligere beskrivelse i item',
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
	'wikibase-remove' => 'ta bort',
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

