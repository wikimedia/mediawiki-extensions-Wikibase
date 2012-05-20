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
	'wikibase-api-modify-failed' => 'Attempted modification of an item failed',
	'wikibase-api-save-failed' => 'Attempted save of item failed',
	'wikibase-api-invalid-contentmodel' => 'The content model of the page on which the item is stored is invalid',
	'wikibase-api-alias-incomplete' => 'Can not find a definition of the alias for the item',
	'wikibase-api-alias-not-found' => 'Can not find any previous alias in the item',
	'wikibase-api-alias-found' => 'Found a previous alias in the item',
	'wikibase-api-not-recognized' => 'Directive is not recognized',
	'wikibase-api-label-or-description' => 'Use either or both of label and/or description, but not noen of them',
	'wikibase-api-label-not-found' => 'Can not find a previous label for this language in the item',
	'wikibase-api-description-not-found' => 'Can not find a previous description for this language in the item',

	//content model names
	'content-model-1001' => 'Wikibase item',
);

/** Message documentation (Message documentation)
 * @author Jeblad
 * @author Siebrand
 */
$messages['qqq'] = array(
	'wikibase-desc' => '{{desc}} See also [[m:Wikidata/Glossary#Wikidata|Wikidata]].',
	'wikibase-edit' => '[[File:Screenshot WikidataRepo 2012-05-13 F.png|right|0x150px]]
[[File:Screenshot WikidataRepo 2012-05-13 A.png|right|0x150px]]
This is a generic text used for a link (fig. 1 and 3 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that puts the user interface into edit mode for an existing element of some kind.',
	'wikibase-save' => '[[File:Screenshot WikidataRepo 2012-05-13 G.png|right|0x150px]]
This is a generic text used for a link (fig. 2 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that saves what the user has done while the user interface has been in edit mode.',
	'wikibase-cancel' => '[[File:Screenshot WikidataRepo 2012-05-13 G.png|right|0x150px]]
This is a generic text used for a link (fig. 2 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that cancels what the user has done while the user interface has been in edit mode.',
	'wikibase-add' => '[[File:Screenshot WikidataRepo 2012-05-13 F.png|right|0x150px]]
[[File:Screenshot WikidataRepo 2012-05-13 A.png|right|0x150px]]
This is a generic text used for a link (fig. 3 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that puts the user interface into edit mode for an additional element of some kind.',
	'wikibase-label-edit-placeholder' => '[[File:Screenshot WikidataRepo 2012-05-13 G.png|right|0x150px]]
This is a generic text used as a placeholder while editing a new label. See also Wikidatas glossary on [[m:Wikidata/Glossary#languageattribute-label|label]].',
	'wikibase-description-edit-placeholder' => '[[File:Screenshot WikidataRepo 2012-05-13 G.png|right|0x150px]]
This is a generic text used as a placeholder while editing a new description. See also Wikidatas glossary on [[m:Wikidata/Glossary#languageattribute-description|description]].',
	'wikibase-sitelink-site-edit-placeholder' => '[[File:Screenshot WikidataRepo 2012-05-13 E.png|right|0x150px]]
This is a generic text used as a placeholder while defining the site for a new sitelink. See also Wikidatas glossary on [[m:Wikidata/Glossary#sitelink|sitelink]].',
	'wikibase-sitelink-page-edit-placeholder' => '[[File:Screenshot WikidataRepo 2012-05-13 E.png|right|0x150px]]
This is a generic text used as a placeholder while defining the page for a possibly new sitelink. See also Wikidatas glossary on [[m:Wikidata/Glossary#sitelink|sitelink]].',
	'wikibase-label-input-help-message' => '[[File:Screenshot WikidataRepo 2012-05-13 I.png|right|0x150px]]
Bubble help message for entering the label of the data set used for a specific item. Takes on additional argument that is the sub site identifier, ie. "English" in nominative singular form. See also Wikidatas glossary for [[m:Wikidata/Glossary#languageattribute-label|label]] and [[m:Wikidata/Glossary#Item|item]].',
	'wikibase-description-input-help-message' => '[[File:Screenshot WikidataRepo 2012-05-13 H.png|right|0x150px]]
Bubble help message for entering the description of the data set used for a specific item. Takes on additional argument that is the sub site identifier, ie. "English" in nominative singular form. See also Wikidatas glossary for [[m:Wikidata/Glossary#languageattribute-description|description]] and [[m:Wikidata/Glossary#Item|item]].',
	'wikibase-sitelinks' => '[[File:Screenshot WikidataRepo 2012-05-13 A.png|right|0x150px]]
Header messages for pages on a specific cluster of sites linked to this item. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]] and [[m:Wikidata/Glossary#Item|item]].',
	'wikibase-sitelinks-add' => 'Add a sitelink to a language specific page on the cluster. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]].',
	'wikibase-sitelinks-empty' => 'There are no sitelinks for any of the language specific pages on the given cluster.  See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]] and [[m:Wikidata/Glossary#sitelinks-title|title]].',
	'wikibase-sitelinks-input-help-message' => '[[File:Screenshot WikidataRepo 2012-05-13 D.png|right|0x150px]]
Bubble help message to set a sitelink to a language specific page on a given cluster. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]] and [[m:Wikidata/Glossary#sitelinks-title|title]].',
	'wikibase-remove' => '[[File:Screenshot WikidataRepo 2012-05-13 A.png|right|0x150px]]
This is a generic text used for a link (fig. 3 on [[m:Wikidata/Notes/JavaScript ui implementation]]) that removes an element of some kind, without the the user interface is put in edit mode.',
	'wikibase-propertyedittool-full' => 'A list of elements the user is assumed to enter is now complete.',
	'wikibase-propertyedittool-counter' => 'Parameters:
* $1 is the sum of elements in the list currently.',
	'wikibase-propertyedittool-counter-pending' => 'Parameters:
* $1 is the sum of elements in the list plus the ones pending (still in edit mode and not saved).
* $2 is the number of elements stored in the list (not pending).
* $3 is the message "wikibase-propertyedittool-counter-pending-pendingsubpart" with some additional markup around, expressing how many entries in the list are pending right now.',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => 'the number of pending elements within the list of site links and a leading "+". This will be inserted into parameter $3 of {{msg-mw|wikibase-propertyedittool-counter-pending}}.',
	'wikibase-sitelinksedittool-full' => 'The list of elements the user can enter is exhausted and there are no additional sites available. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|sitelinks]].',
	'wikibase-disambiguation-title' => 'Disambiguation page title. $1 is the label of the item being disambiguated.',
	'special-itembytitle' => 'The item is identified through use of the title alone and must be disambiguated as there might be several sites that uses the same title for pages. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks-title|title]] and [[m:Wikidata/Glossary#Sitelinks-site|site]].',
	'special-itembylabel' => 'The item is identified through use of the label alone and must be disambiguated as there might be several entities that uses the same label for items. See also Wikidatas glossary for [[m:Wikidata/Glossary#languageattribute-label|label]] and [[m:Wikidata/Glossary#Items|items]].',
	'content-model-1001' => 'The name for Wikibase item content model, used when describing what type of content a page contains.',
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
 * @author Metalhead64
 */
$messages['de'] = array(
	'wikibase-desc' => 'Ermöglicht ein Repositorium strukturierter Daten',
	'wikibase-edit' => 'bearbeiten',
	'wikibase-save' => 'speichern',
	'wikibase-cancel' => 'abbrechen',
	'wikibase-add' => 'hinzufügen',
	'wikibase-label-edit-placeholder' => 'Bezeichnung eingeben',
	'wikibase-description-edit-placeholder' => 'Beschreibung eingeben',
	'wikibase-move-error' => 'Du kannst keine Seiten aus dem Datennamensraum heraus- bzw. in ihn hineinverschieben.',
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
	'wikibase-propertyedittool-counter' => '({{PLURAL:$1|Ein Eintrag|$1 Einträge}})',
	'wikibase-propertyedittool-counter-pending' => '($2$3 {{PLURAL:$1|Eintrag|Einträge}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|Ein Wert wurde|$1 Werte wurden}} noch nicht gespeichert',
	'wikibase-sitelinksedittool-full' => 'Für alle bekannten Websites sind die Links auf die Seiten bereits festgelegt.',
	'wikibase-disambiguation-title' => 'Begriffsklärung für „$1“',
	'special-itembytitle' => 'Wert nach Name',
	'special-itembylabel' => 'Wert nach Bezeichnung',
	'wikibase-api-aliases-invalid-list' => 'Du musst entweder den Parameter für „setzen“ zu x angeben oder die Parameter zu „hinzufügen“ bzw. „entfernen“.',
	'wikibase-api-no-such-item' => 'Es wurde kein vorhandenes Datenelement gefunden.',
	'wikibase-api-no-token' => 'Es wurden keine Token angegeben.',
	'wikibase-api-no-data' => 'Es wurden keine zu verarbeitenden Daten gefunden.',
	'wikibase-api-cant-edit' => 'Der angemeldete Benutzer ist nicht berechtigt Bearbeitungen durchzuführen.',
	'wikibase-api-no-permissions' => 'Der angemeldete Benutzer verfügt über keine ausreichenden Berechtigungen.',
	'wikibase-api-id-xor-wikititle' => 'Gib entweder die Kennungen der Datenelemente oder ein Website-Seitennamenpaar für eine entsprechende Seite an.',
	'wikibase-api-no-such-item-id' => 'Es wurde zu dieser Kennung kein vorhandenes Datenelement gefunden.',
	'wikibase-api-link-exists' => 'Ein Artikel auf dem angegebenen Wiki ist bereits verknüpft.',
	'wikibase-api-add-with-id' => 'Mit der Kennung eines vorhanden Datenelements kann nichts hinzugefügt werden.',
	'wikibase-api-add-exists' => 'Zu einem vorhandenen Datenelement kann nichts hinzugefügt werden.',
	'wikibase-api-update-without-id' => 'Die Aktualisierung ohne eine frühere Kennung ist nicht möglich.',
	'wikibase-api-no-such-item-link' => 'Es wurde zu dieser Verknüpfung kein vorhandenes Datenelement gefunden.',
	'wikibase-api-create-failed' => 'Der Versuch ein neues Datenelement zu erstellen ist fehlgeschlagen.',
	'wikibase-api-modify-failed' => 'Der Versuch ein Datenelement zu ändern ist fehlgeschlagen.',
	'wikibase-api-save-failed' => 'Der Versuch das Datenelement zu speichern ist fehlgeschlagen.',
	'wikibase-api-invalid-contentmodel' => 'Die Inhaltsstruktur der Seite auf der das Datenelement gespeichert ist, ist ungültig.',
	'wikibase-api-alias-incomplete' => 'Es wurde keine Definition des Alias zum Datenelement gefunden.',
	'wikibase-api-alias-not-found' => 'Es wurde kein früherer Alias im Datenelement gefunden.',
	'wikibase-api-alias-found' => 'Es wurde ein früherer Alias im Datenelement gefunden.',
	'wikibase-api-not-recognized' => 'Die Richtlinie wird nicht erkannt.',
	'wikibase-api-label-or-description' => 'Verwende entweder Bezeichnung und/oder Beschreibung, aber lasse dies nicht offen.',
	'wikibase-api-label-not-found' => 'Es wurde keine frühere Bezeichnung in dieser Sprache im Datenelement gefunden.',
	'wikibase-api-description-not-found' => 'Es wurde keine frühere Beschreibung in dieser Sprache im Datenelement gefunden.',
	'content-model-1001' => 'Wikibase-Datenelement',
);

/** German (formal address) (‪Deutsch (Sie-Form)‬)
 * @author Kghbln
 */
$messages['de-formal'] = array(
	'wikibase-move-error' => 'Sie können keine Seiten aus dem Datennamensraum heraus- bzw. in ihn hineinverschieben.',
	'wikibase-label-input-help-message' => 'Geben Sie den Namen für diesen Datensatz in $1 an.',
	'wikibase-description-input-help-message' => 'Geben Sie eine kurze Beschreibung in $1 an.',
	'wikibase-sitelinks-add' => 'fügen Sie eine Verknüpfung zu einer {{SITENAME}}-Seite hinzu',
	'wikibase-sitelinks-input-help-message' => 'Legen Sie eine Verknüpfung zu einer {{SITENAME}}-Seite fest.',
	'wikibase-api-aliases-invalid-list' => 'Sie müssen entweder den Parameter für „setzen“ zu x angeben oder die Parameter zu „hinzufügen“ bzw. „entfernen“.',
	'wikibase-api-id-xor-wikititle' => 'Geben Sie entweder die Kennungen der Datenelemente oder ein Website-Seitennamenpaar für eine entsprechende Seite an.',
	'wikibase-api-label-or-description' => 'Verwenden Sie entweder Bezeichnung und/oder Beschreibung, aber lassen Sie dies nicht offen.',
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
	'wikibase-move-error' => 'No puedes mover las páginas que se encuentran en el espacio de nombres de datos, y no puedes mover páginas hacia allí.',
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
	'wikibase-propertyedittool-counter' => '$1 {{PLURAL:$1|entrada|entradas}}',
	'wikibase-propertyedittool-counter-pending' => '($2 $3 {{PLURAL:$1|entrada|entradas}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|Un valor aún no guardado|$1 valores aún no guardados}}',
	'wikibase-sitelinksedittool-full' => 'Los enlaces a las páginas están ya definidos para todos los sitios conocidos.',
	'wikibase-disambiguation-title' => 'Desambiguación para "$1"',
	'special-itembytitle' => 'Artículo por título',
	'special-itembylabel' => 'Artículo por etiqueta',
	'wikibase-api-aliases-invalid-list' => 'Es necesario proporcionar el parámetro de configuración xor al agregar o quitar parámetros',
	'wikibase-api-no-such-item' => 'No se pudo encontrar un elemento existente',
	'wikibase-api-no-token' => 'No se ha dado ninguna clave (token)',
	'wikibase-api-no-data' => 'No se ha encontrado ningún dato sobre el que operar',
	'wikibase-api-cant-edit' => 'El usuario que ha iniciado sesión no tiene permisos para editar',
	'wikibase-api-no-permissions' => 'El usuario que ha iniciado sesión no tiene derechos suficientes',
	'wikibase-api-id-xor-wikititle' => 'Proporciona el elemento ids o una pareja sitio-título para una página correspondiente',
	'wikibase-api-no-such-item-id' => 'No se pudo encontrar un elemento existente para este identificador',
	'wikibase-api-link-exists' => 'Un artículo de la wiki especificada ya está vinculado',
	'wikibase-api-add-with-id' => 'No se puede agregar con el identificador de un elemento existente',
	'wikibase-api-add-exists' => 'No se puede agregar a un elemento existente',
	'wikibase-api-update-without-id' => 'No es posible la actualización sin un identificador previo',
	'wikibase-api-no-such-item-link' => 'No se pudo encontrar un elemento existente para este enlace',
	'wikibase-api-create-failed' => 'Error al intentar crear un nuevo elemento',
	'wikibase-api-modify-failed' => 'Error en el intento de modificación de un elemento',
	'wikibase-api-save-failed' => 'Error en el intento de guardar el elemento',
	'wikibase-api-invalid-contentmodel' => 'No es válido el modelo de contenido de la página en la que se almacena el elemento',
	'wikibase-api-alias-incomplete' => 'No se puede encontrar una definición del alias para el elemento',
	'wikibase-api-alias-not-found' => 'No se puede encontrar ningún alias anterior en el elemento',
	'wikibase-api-alias-found' => 'Se ha encotrado un alias anterior en el elemento',
	'wikibase-api-not-recognized' => 'No se reconoce la directiva',
	'wikibase-api-label-or-description' => 'Utiliza la etiqueta, la descripción o ambas, pero no pueden faltar las dos',
	'wikibase-api-label-not-found' => 'No se puede encontrar una etiqueta anterior para este idioma en el elemento',
	'wikibase-api-description-not-found' => 'No se puede encontrar una descripción anterior para este idioma en el elemento',
	'content-model-1001' => 'Elemento de Wikibase',
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

/** Galician (Galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'wikibase-desc' => 'Repositorio de datos estruturados',
	'wikibase-edit' => 'editar',
	'wikibase-save' => 'gardar',
	'wikibase-cancel' => 'cancelar',
	'wikibase-add' => 'engadir',
	'wikibase-label-edit-placeholder' => 'escriba unha etiqueta',
	'wikibase-description-edit-placeholder' => 'escriba unha descrición',
	'wikibase-sitelink-site-edit-placeholder' => 'especifique o sitio',
	'wikibase-sitelink-page-edit-placeholder' => 'especifique a páxina',
	'wikibase-label-input-help-message' => 'Introduza o título deste conxunto de datos en $1.',
	'wikibase-description-input-help-message' => 'Introduza unha breve descrición en $1.',
	'wikibase-sitelinks' => 'Páxinas da Wikipedia con ligazóns cara a este elemento',
	'wikibase-sitelinks-add' => 'engada unha ligazón cara a unha páxina da Wikipedia',
	'wikibase-sitelinks-empty' => 'Ningunha páxina da Wikipedia ten ligazóns cara a este elemento.',
	'wikibase-sitelinks-input-help-message' => 'Defina unha ligazón cara a un artigo da Wikipedia.',
	'wikibase-remove' => 'eliminar',
	'wikibase-propertyedittool-full' => 'A lista de valores está completa.',
	'wikibase-sitelinksedittool-full' => 'As ligazóns cara ás páxinas xa están definidas para todos os sitios coñecidos.',
	'special-itembytitle' => 'Artigo por título',
	'special-itembylabel' => 'Artigo por etiqueta',
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
	'wikibase-description-edit-placeholder' => 'setja inn lýsingu',
	'wikibase-sitelink-site-edit-placeholder' => 'tilgreindu vefsvæði',
	'wikibase-sitelink-page-edit-placeholder' => 'tilgreindu síðu',
	'wikibase-label-input-help-message' => 'Sláðu inn titil á þessum gögnum á $1.',
	'wikibase-description-input-help-message' => 'Sláðu inn stutta lýsingu á $1.',
	'wikibase-sitelinks' => 'Wikipedia síður sem tengja á þennan hlut',
	'wikibase-sitelinks-add' => 'bæta við tengli á Wikipedia síðu',
	'wikibase-sitelinks-empty' => 'Engar Wikipedia síður tengja á þennan hlut ennþá.',
	'wikibase-sitelinks-input-help-message' => 'Settu tengil á Wikipedia grein.',
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
	'wikibase-move-error' => "U kunt pagina's in de gegevensnaamruimte niet hernoemen en u kunt er geen pagina naartoe hernoemen.",
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
	'wikibase-propertyedittool-counter' => '($1 {{PLURAL:$1|ingang|ingangen}})',
	'wikibase-propertyedittool-counter-pending' => '($2$3 {{PLURAL:$1|ingang|ingangen}})',
	'wikibase-propertyedittool-counter-pending-pendingsubpart' => '+$1',
	'wikibase-propertyedittool-counter-pending-tooltip' => '{{PLURAL:$1|Eén waarde|$1 waarden}} nog niet opgeslagen',
	'wikibase-sitelinksedittool-full' => "Verwijzingen naar pagina's die al zijn ingesteld voor alle bekende sites.",
	'wikibase-disambiguation-title' => 'Disambiguatie voor "$1"',
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
	'wikibase-edit' => 'సవరించు',
	'wikibase-save' => 'భద్రపరచు',
	'wikibase-cancel' => 'రద్దుచేయి',
	'wikibase-add' => 'చేర్చు',
	'wikibase-remove' => 'తొలగించు',
);

/** Simplified Chinese (‪中文(简体)‬)
 * @author Linforest
 */
$messages['zh-hans'] = array(
	'wikibase-desc' => '结构化数据存储库',
	'wikibase-edit' => '编辑',
	'wikibase-save' => '保存',
	'wikibase-cancel' => '取消',
	'wikibase-add' => '添加',
	'wikibase-label-edit-placeholder' => '输入标签',
	'wikibase-description-edit-placeholder' => '输入描述',
	'wikibase-sitelink-site-edit-placeholder' => '指定站点',
	'wikibase-sitelink-page-edit-placeholder' => '指定页面',
	'wikibase-label-input-help-message' => '采用$1输入该数据集的标题。',
	'wikibase-description-input-help-message' => '采用$1输入简要说明。',
	'wikibase-sitelinks' => '链接到此项的维基百科页面',
	'wikibase-sitelinks-add' => '添加指向特定维基百科页面的链接',
	'wikibase-sitelinks-empty' => '尚无维基百科页面链接到此项目。',
	'wikibase-sitelinks-input-help-message' => '设置一条指向特定维基百科文章的链接。',
	'wikibase-remove' => '删除',
	'wikibase-propertyedittool-full' => '取值列表已完整。',
	'wikibase-sitelinksedittool-full' => '已经为所有的已知站点设置了指向页面的链接。',
	'special-itembytitle' => '按标题排序的项目',
	'special-itembylabel' => '按标签排序的项目',
);

