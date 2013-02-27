<?php

/**
 * Internationalization file for the WikibaseLib extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 */

$messages = array();

/** English
 * @author Jeroen De Dauw
 */
$messages['en'] = array(
	'wikibase-lib-desc' => 'Holds common functionality for the Wikibase and Wikibase Client extensions',
	'wikibase-entity-item' => 'item',
	'wikibase-entity-property' => 'property',
	'wikibase-entity-query' => 'query',
	'wikibase-error-unexpected' => 'An unexpected error occurred.',
	'wikibase-error-save-generic' => 'An error occurred while trying to perform save and because of this, your changes could not be completed.',
	'wikibase-error-remove-generic' => 'An error occurred while trying to perform remove and because of this, your changes could not be completed.',
	'wikibase-error-save-connection' => 'A connection error has occurred while trying to perform save, and because of this your changes could not be completed. Please check your Internet connection.',
	'wikibase-error-remove-connection' => 'A connection error occurred while trying to perform remove, and because of this your changes could not be completed. Please check your Internet connection.',
	'wikibase-error-save-timeout' => 'We are experiencing technical difficulties, and because of this your "save" could not be completed.',
	'wikibase-error-remove-timeout' => 'We are experiencing technical difficulties, and because of this your "remove" could not be completed.',
	'wikibase-error-autocomplete-connection' => 'Could not query Wikipedia API. Please try again later.',
	'wikibase-error-autocomplete-response' => 'Server responded: $1',
	'wikibase-error-ui-client-error' => 'The connection to the client page failed. Please try again later.',
	'wikibase-error-ui-no-external-page' => 'The specified article could not be found on the corresponding site.',
	'wikibase-error-ui-cant-edit' => 'You are not allowed to perform this action.',
	'wikibase-error-ui-no-permissions' => 'You do not have sufficient rights to perform this action.',
	'wikibase-error-ui-link-exists' => 'You cannot link to this page because another item already links to it.',
	'wikibase-error-ui-session-failure' => 'Your session has expired. Please log in again.',
	'wikibase-error-ui-edit-conflict' => 'There is an edit conflict. Please reload and save again.',
	'wikibase-replicationnote' => 'Please notice that it can take several minutes until the changes are visible on all wikis',
	'wikibase-sitelinks' => 'List of pages linked to this item',
	'wikibase-sitelinks-sitename-columnheading' => 'Language',
	'wikibase-sitelinks-siteid-columnheading' => 'Code',
	'wikibase-sitelinks-link-columnheading' => 'Linked article',
	'wikibase-tooltip-error-details' => 'Details',
	'datatypes-type-wikibase-item' => 'Item',
	'datatypes-type-commonsMedia' => 'Commons media file',
);

/** Message documentation (Message documentation)
 * @author Jeblad
 * @author Nemo bis
 * @author Nnemo
 * @author Raymond
 * @author Shirayuki
 * @author Waldir
 */
$messages['qqq'] = array(
	'wikibase-lib-desc' => '{{desc|name=Wikibase Lib|url=http://www.mediawiki.org/wiki/Extension:WikibaseLib}}',
	'wikibase-entity-item' => "How we refer to entities of type item. See also Wikidata's glossary on [[m:Wikidata/Glossary#item|item]].
{{Identical|Item}}",
	'wikibase-entity-property' => 'How we refer to entities of type property. See also Wikidatas glossary on [[m:Wikidata/Glossary#entity|entity]].
{{Identical|Property}}',
	'wikibase-entity-query' => 'How we refer to entities of type query. See also Wikidatas glossary on [[m:Wikidata/Glossary#entity|entity]].
{{Identical|Query}}',
	'wikibase-error-unexpected' => 'Error message that is used as a fallback message if no other message can be assigned to the error that occurred. This error message being displayed should never happen. However, there may be "unexpected" errors not covered by the implemented error handling.',
	'wikibase-error-save-generic' => 'Generic error message for an error happening during a save operation.',
	'wikibase-error-remove-generic' => 'Generic error message for an error happening during a remove operation',
	'wikibase-error-save-connection' => 'Error message for an error happening during a save operation. The error might most likely be caused by a connection problem.',
	'wikibase-error-remove-connection' => 'Error message for an error happening during a remove operation. The error might most likely be caused by a connection problem.',
	'wikibase-error-save-timeout' => 'Error message for an error happening during a save operation. The error was caused by a request time out.',
	'wikibase-error-remove-timeout' => 'Error message for an error happening during a remove operation. The error was caused by a request time out.',
	'wikibase-error-autocomplete-connection' => 'Error message for page auto-complete input box; displayed when API could not be reached.',
	'wikibase-error-autocomplete-response' => 'When querying the API for auto-completion fails, this message contains more detailed information about the error. $1 is the actual server error response or jQuery error code (e.g. when the server did not respond).',
	'wikibase-error-ui-client-error' => 'This is a human readable version of the API error "wikibase-api-client-error" which is shown in the UI.',
	'wikibase-error-ui-no-external-page' => 'This is a human readable version of the API error "wikibase-api-no-external-page" which is shown in the UI.',
	'wikibase-error-ui-cant-edit' => 'This is a human readable version of the API error "wikibase-api-cant-edit" which is shown in the UI.',
	'wikibase-error-ui-no-permissions' => 'This is a human readable version of the API error "wikibase-api-no-permission" which is shown in the UI.',
	'wikibase-error-ui-link-exists' => 'This is a human readable version of the API error "wikibase-api-link-exists" which is shown in the UI.',
	'wikibase-error-ui-session-failure' => 'This is a human readable version of the API error "wikibase-api-session-failure" which is shown in the UI.',
	'wikibase-error-ui-edit-conflict' => 'This is a human readable version of the API error "edit-conflict" which is shown in the UI.
Note that the default message says the user shall "reload and save", but after a reload the content that should be saved will be lost.',
	'wikibase-replicationnote' => 'Note telling the user that it can take a few minutes until the made changes are visible on all wikis.',
	'wikibase-sitelinks' => '[[File:Screenshot WikidataRepo 2012-05-13 A.png|right|0x150px]]
Header messages for pages on a specific cluster of sites linked to this item. See also Wikidatas glossary for [[m:Wikidata/Glossary#sitelinks|site links]] and [[m:Wikidata/Glossary#Item|item]].',
	'wikibase-sitelinks-sitename-columnheading' => 'Site links table column heading for the column containing the language names.
{{Identical|Language}}',
	'wikibase-sitelinks-siteid-columnheading' => 'Site links table column heading for the column containing the language codes.',
	'wikibase-sitelinks-link-columnheading' => 'Site links table column heading for the column containg the title/link of/to the referenced (Wikipedia) page.',
	'wikibase-tooltip-error-details' => 'Link within an error tooltip that will unfold additional information regarding the error (i.e. the more specific error message returned from the underlying API).',
	'datatypes-type-wikibase-item' => 'The name of a data type for items in Wikibase.
{{Identical|Item}}',
	'datatypes-type-commonsMedia' => 'The name of a data type for media files on Wikimedia Commons (proper name, capitalised in English; first letter capitalised anyway in this message and relatives).',
);

/** Asturian (asturianu)
 * @author Xuacu
 */
$messages['ast'] = array(
	'wikibase-lib-desc' => 'Contién les funciones comúnes pa les estensiones Wikibase y Wikibase Client.',
	'wikibase-entity-item' => 'elementu',
	'wikibase-entity-property' => 'propiedá',
	'wikibase-entity-query' => 'consulta',
	'wikibase-sitelinks' => 'Llista de páxines enllazaes a esti elementu',
	'wikibase-sitelinks-sitename-columnheading' => 'Llingua',
	'wikibase-sitelinks-siteid-columnheading' => 'Códigu',
	'wikibase-sitelinks-link-columnheading' => 'Artículu enllazáu',
	'datatypes-type-wikibase-item' => 'Elementu',
	'datatypes-type-commonsMedia' => 'Ficheru multimedia de Commons',
);

/** Belarusian (Taraškievica orthography) (беларуская (тарашкевіца)‎)
 * @author Wizardist
 */
$messages['be-tarask'] = array(
	'wikibase-lib-desc' => 'Утрымлівае агульны функцыянал пашырэньняў Wikibase і Wikibase Client.',
	'wikibase-entity-item' => 'аб’ект',
	'wikibase-entity-property' => 'уласьцівасьць',
	'wikibase-entity-query' => 'запыт',
	'wikibase-error-unexpected' => 'Узьнікла нечаканая памылка.',
	'wikibase-error-save-generic' => 'У час спробы захаваньня адбылася памылка, з-за чаго зьмены не былі ўнесеныя цалкам.',
	'wikibase-error-remove-generic' => 'У час спробы выдаленьня адбылася памылка, з-за чаго зьмены не былі ўнесеныя цалкам.',
	'wikibase-error-save-connection' => 'У час спробы захаваньня адбылася памылка злучэньня, з-за чаго вашыя зьмены не былі захаваныя. Калі ласка, праверце вашае злучэньне з Інтэрнэтам.',
	'wikibase-error-remove-connection' => 'Пры выдаленьні ўзьнікла памылка сувязі, з-за гэтага вашыя зьмены маглі не захавацца. Праверце вашае злучэньне з Інтэрнэтам, калі ласка.',
	'wikibase-error-save-timeout' => 'Мы маем тэхнічныя праблемы, з-за чаго немагчыма завершыць захаваньне.',
	'wikibase-error-remove-timeout' => 'Мы маем тэхнічныя праблемы, з-за чаго немагчыма завершыць выдаленьне.',
	'wikibase-error-autocomplete-connection' => 'Не атрымалася запытаць Wikipedia API. Калі ласка, паспрабуйце пазьней.',
	'wikibase-error-autocomplete-response' => 'Адказ сэрвэра: $1',
	'wikibase-error-ui-client-error' => 'Зьнікла сувязь з кліенцкай старонкай. Паспрабуйце пазьней, калі ласка.',
	'wikibase-error-ui-no-external-page' => 'Пазначаны артыкул на адпаведным сайце ня знойдзены.',
	'wikibase-error-ui-cant-edit' => 'Вам не дазволена выканаць гэтае дзеяньне.',
	'wikibase-error-ui-no-permissions' => 'Вам бракуе правоў для гэтага дзеяньня.',
	'wikibase-error-ui-link-exists' => 'Вы ня можаце спаслацца на гэтую старонку, бо іншы аб’ект ужо на яе спасылаецца.',
	'wikibase-error-ui-session-failure' => 'Вашая сэсія скончылася. Увайдзіце ў сыстэму зноў, калі ласка.',
	'wikibase-error-ui-edit-conflict' => 'Адбыўся канфлікт рэдагаваньня. Абнавіце старонку і захавайце зноў, калі ласка.',
	'wikibase-replicationnote' => 'Будзьце ласкавыя заўважыць, што зьмены могуць зьявіцца ў вікі-праектах толькі празь некалькі хвілін.',
	'wikibase-sitelinks' => 'Сьпіс старонак, што спасылаюцца на гэты аб’ект',
	'wikibase-sitelinks-sitename-columnheading' => 'Мова',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
	'wikibase-sitelinks-link-columnheading' => 'Злучаны артыкул',
	'wikibase-tooltip-error-details' => 'Падрабязнасьці',
	'datatypes-type-wikibase-item' => 'Аб’ект',
	'datatypes-type-commonsMedia' => 'Мэдыяфайл зь Вікісховішча',
);

/** Bulgarian (български)
 * @author Spiritia
 */
$messages['bg'] = array(
	'wikibase-entity-item' => 'обект',
	'wikibase-entity-property' => 'свойство',
	'wikibase-entity-query' => 'заявка',
	'wikibase-error-unexpected' => 'Възникна неочаквана грешка.',
	'wikibase-error-save-generic' => 'Промените не могат да бъдат завършени, поради възникнала грешка при опита за съхраняване.',
	'wikibase-error-remove-generic' => 'Промените не могат да бъдат завършени, поради възникнала грешка при опита за изтриване.',
	'wikibase-error-save-connection' => 'Промените не могат да бъдат завършени, поради възникнал проблем с интернет връзката при опита за съхраняване. Проверете интернет връзката си.',
	'wikibase-error-remove-connection' => 'Промените не могат да бъдат завършени, поради възникнал проблем с интернет връзката при опита за изтриване. Проверете интернет връзката си.',
	'wikibase-error-save-timeout' => 'Поради възникнали технически трудности съхраняването не можа да бъде завършено.',
	'wikibase-error-remove-timeout' => 'Поради възникнали технически трудности изтриването не можа да бъде завършено.',
	'wikibase-error-autocomplete-connection' => 'Възникна проблем със заявката към API интерфейса на Уикипедия. Опитайте по-късно.',
	'wikibase-error-autocomplete-response' => 'Отговор на сървъра: $1',
	'wikibase-error-ui-no-external-page' => 'Посочената статия не беше намерена в съответния сайт.',
	'wikibase-error-ui-cant-edit' => 'Нямате права да извършите това действие.',
	'wikibase-error-ui-no-permissions' => 'Нямате необходимите права да извършите това действие.',
	'wikibase-error-ui-link-exists' => 'Свързването с тази страница е невъзможно. С нея вече е свързан друг обект от Уикиданни.',
	'wikibase-error-ui-session-failure' => 'Сесията ви е изтекла. Влезте отново в системата.',
	'wikibase-error-ui-edit-conflict' => 'Настъпил е конфликт на редакции. Презаредете и съхранете отново.',
	'wikibase-replicationnote' => 'Може да отнеме няколко минути, докато промените се отразят във всички уикита.',
	'wikibase-sitelinks' => 'Списък на страниците, свързани с този обект',
	'wikibase-sitelinks-sitename-columnheading' => 'Език',
	'wikibase-sitelinks-siteid-columnheading' => 'Езиков код',
	'wikibase-sitelinks-link-columnheading' => 'Свързана статия',
	'wikibase-tooltip-error-details' => 'Подробности',
	'datatypes-type-wikibase-item' => 'Обект',
	'datatypes-type-commonsMedia' => 'Файл от Общомедия',
);

/** Bosnian (bosanski)
 * @author Edinwiki
 */
$messages['bs'] = array(
	'wikibase-lib-desc' => 'Sadrži zajedničku funkcionalnost za Wikibazu i proširenje za Wikibaza klijent.',
	'wikibase-entity-item' => 'stavka',
	'wikibase-entity-property' => 'svojstvo',
	'wikibase-entity-query' => 'upit',
	'wikibase-sitelinks' => 'Spisak stranica koje su povezane uz ovu stavku',
	'wikibase-sitelinks-sitename-columnheading' => 'Jezik',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Povezana stranica',
	'datatypes-type-wikibase-item' => 'Stavka',
	'datatypes-type-commonsMedia' => 'Commons medijska datoteka',
);

/** Catalan (català)
 * @author Arnaugir
 * @author Grondin
 * @author Toniher
 */
$messages['ca'] = array(
	'wikibase-lib-desc' => 'Té la funcionalitat comuna per les extensions de Wikibase i de Wikibase Client',
	'wikibase-entity-item' => 'element',
	'wikibase-entity-property' => 'propietat',
	'wikibase-entity-query' => 'consulta',
	'wikibase-sitelinks' => 'Llista de les pàgines vinculades a aquest element',
	'wikibase-sitelinks-sitename-columnheading' => 'Llengua',
	'wikibase-sitelinks-siteid-columnheading' => 'Codi',
	'wikibase-sitelinks-link-columnheading' => 'Article enllaçat',
	'datatypes-type-wikibase-item' => 'Element',
	'datatypes-type-commonsMedia' => 'Fitxer multimèdia de Commons',
);

/** Czech (česky)
 * @author Danny B.
 * @author JAn Dudík
 * @author Vks
 */
$messages['cs'] = array(
	'wikibase-lib-desc' => 'Obsahuje běžné funkce pro rozšíření Wikibase a Wikibase klient',
	'wikibase-entity-item' => 'položka',
	'wikibase-entity-property' => 'vlastnost',
	'wikibase-entity-query' => 'dotaz',
	'wikibase-sitelinks' => 'Seznam stránek svázaných s touto položkou',
	'wikibase-sitelinks-sitename-columnheading' => 'Jazyk',
	'wikibase-sitelinks-siteid-columnheading' => 'Kód',
	'datatypes-type-wikibase-item' => 'Položka',
	'datatypes-type-commonsMedia' => 'Commons multimediální soubor',
);

/** Danish (dansk)
 * @author Poul G
 */
$messages['da'] = array(
	'wikibase-lib-desc' => 'Fælles funktionalitet for Wikibase og Wikibase klient udvidelser',
	'wikibase-entity-item' => 'emne',
	'wikibase-entity-property' => 'egenskab',
	'wikibase-entity-query' => 'forespørgsel',
	'wikibase-sitelinks' => 'Liste over sider, der er knyttet til dette emne',
	'wikibase-sitelinks-sitename-columnheading' => 'Sprog',
	'wikibase-sitelinks-siteid-columnheading' => 'Kode',
	'wikibase-sitelinks-link-columnheading' => 'Linket artikel',
	'datatypes-type-wikibase-item' => 'Emne',
	'datatypes-type-commonsMedia' => 'Commons media-fil',
);

/** German (Deutsch)
 * @author G.Hagedorn
 * @author Kghbln
 * @author Metalhead64
 */
$messages['de'] = array(
	'wikibase-lib-desc' => 'Stellt von den Erweiterungen Wikibase und Wikibase Client gemeinsam genutzte Funktionen bereit',
	'wikibase-entity-item' => 'Datenobjekt',
	'wikibase-entity-property' => 'Eigenschaft',
	'wikibase-entity-query' => 'Abfrage',
	'wikibase-error-unexpected' => 'Es ist ein unerwarteter Fehler aufgetreten.',
	'wikibase-error-save-generic' => 'Beim Speichern ist ein Fehler aufgetreten. Die Änderungen konnten daher nicht vollständig durchgeführt werden.',
	'wikibase-error-remove-generic' => 'Beim Versuch zu entfernen, ist ein Fehler aufgetreten. Diese Änderungen konnten daher nicht fertig durchgeführt werden.',
	'wikibase-error-save-connection' => 'Beim Versuch zu speichern ist ein Verbindungsfehler aufgetreten. Diese Änderungen konnten daher nicht fertig durchgeführt werden. Die Internetverbindung sollte überprüft werden.',
	'wikibase-error-remove-connection' => 'Beim Versuch zu entfernen ist ein Verbindungsfehler aufgetreten. Diese Änderungen konnten daher nicht fertig durchgeführt werden. Die Internetverbindung sollte überprüft werden.',
	'wikibase-error-save-timeout' => 'Wir haben technische Schwierigkeiten. Diese Änderungen konnten daher nicht fertig gespeichert werden.',
	'wikibase-error-remove-timeout' => 'Wir haben technische Schwierigkeiten. Diese Änderungen konnten daher nicht fertig gespeichert werden.',
	'wikibase-error-autocomplete-connection' => 'Die Wikipedia-API konnte nicht abgefragt werden. Bitte versuche es später noch einmal.',
	'wikibase-error-autocomplete-response' => 'Serverantwort: $1',
	'wikibase-error-ui-client-error' => 'Die Verbindung zur externen Webseite ist gescheitert. Bitte versuche es später noch einmal.',
	'wikibase-error-ui-no-external-page' => 'Der angegebene Artikel konnte nicht auf der zugehörigen Website gefunden werden.',
	'wikibase-error-ui-cant-edit' => 'Du bist nicht berechtigt, diese Aktion auszuführen.',
	'wikibase-error-ui-no-permissions' => 'Du hast keine ausreichende Berechtigung, um diese Aktion auszuführen.',
	'wikibase-error-ui-link-exists' => 'Du kannst nicht auf diese Seite verlinken, da ein anderes Datenobjekt bereits auf sie verlinkt.',
	'wikibase-error-ui-session-failure' => 'Deine Sitzung ist abgelaufen. Du musst dich daher erneut anmelden.',
	'wikibase-error-ui-edit-conflict' => 'Es gab einen Bearbeitungskonflikt. Bitte lade und speichere die Seite erneut.',
	'wikibase-replicationnote' => 'Bitte bedenke, dass es einige Minuten dauern kann, bis die Änderungen auf allen Wikis sichtbar sind.',
	'wikibase-sitelinks' => 'Liste der Seiten, die mit diesem Datenobjekt verknüpft sind',
	'wikibase-sitelinks-sitename-columnheading' => 'Sprache',
	'wikibase-sitelinks-siteid-columnheading' => 'Code',
	'wikibase-sitelinks-link-columnheading' => 'Verlinkter Artikel',
	'wikibase-tooltip-error-details' => 'Einzelheiten',
	'datatypes-type-wikibase-item' => 'Datenobjekt',
	'datatypes-type-commonsMedia' => 'Mediendatei auf Commons',
);

/** Zazaki (Zazaki)
 * @author Erdemaslancan
 */
$messages['diq'] = array(
	'wikibase-entity-item' => 'çêki',
	'wikibase-entity-query' => 'persen',
	'wikibase-sitelinks-sitename-columnheading' => 'Zıwan',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'datatypes-type-wikibase-item' => 'Çêki',
);

/** Lower Sorbian (dolnoserbski)
 * @author Michawiki
 */
$messages['dsb'] = array(
	'wikibase-lib-desc' => 'Stoj powšyknu funkcionalnosć za rozšyrjeni Wikibase a Wikibase Client k dispoziciji',
	'wikibase-entity-item' => 'element',
	'wikibase-entity-property' => 'kakosć',
	'wikibase-entity-query' => 'wótpšašanje',
	'wikibase-error-save-generic' => 'Pśi składowanju jo zmólka nastała, a togodla njedaju se změny pśewjasć.',
	'wikibase-error-remove-generic' => 'Pśi wótpóranju jo zmólka nastała, a togodla njedaju se změny pśewjasć.',
	'wikibase-error-save-connection' => 'Zwiskowa zmólka jo pśi składowanju namakała a twóje změny njedaju se togodla pśewjasć. Pšosym pśeglědaj swój internetowy zwisk.',
	'wikibase-error-remove-connection' => 'Zwiskowa zmólka jo pśi wótporanju namakała a twóje změny njedaju se togodla pśewjasć. Pšosym pśeglědaj swój internetowy zwisk.',
	'wikibase-error-save-timeout' => 'Mamy techniske śěžkosći a togodla njedajo se nic składowaś.',
	'wikibase-error-remove-timeout' => 'Mamy techniske śěžkosći a togodla njedajo se nic wótpóraś.',
	'wikibase-error-autocomplete-connection' => 'API Wikipedije njedajo se napšašowaś. Pšosym wopytaj pózdźej hyšći raz.',
	'wikibase-error-autocomplete-response' => 'Serwer jo wótegronił: $1',
	'wikibase-error-ui-client-error' => 'Zwisk k eksternemu webbokoju jo se njeraźił. Pšosym wopytaj pózdźej hyšći raz.',
	'wikibase-error-ui-no-external-page' => 'Pódany nastawk njedajo se na wótpowědujucem sedle namakaś.',
	'wikibase-error-ui-cant-edit' => 'Njesmějoš toś tu akciju wuwjasć.',
	'wikibase-error-ui-no-permissions' => 'Njamaš dosć pšawow, aby toś tu akciju wuwjadł.',
	'wikibase-error-ui-link-exists' => 'Njamóžoš k toś tomu bokoju wótkazowaś, dokulaž drugi element južo k njomu wótkazujo.',
	'wikibase-error-ui-session-failure' => 'Twójo pósejźenje jo se pśepadnuło. Pšosym pśizjaw se hyšći raz.',
	'wikibase-error-ui-edit-conflict' => 'Jo wobźěłowański konflikt dał. Pšosym zacytuj a składuj znowego.',
	'wikibase-sitelinks' => 'Lisćina bokow, kótarež su z toś tym elementom zwězane',
	'wikibase-sitelinks-sitename-columnheading' => 'Rěc',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Wótkazany nastawk',
	'wikibase-tooltip-error-details' => 'Drobnostki',
	'datatypes-type-wikibase-item' => 'Element',
	'datatypes-type-commonsMedia' => 'Medijowa dataja na Wikimedia Commons',
);

/** Esperanto (Esperanto)
 * @author ArnoLagrange
 */
$messages['eo'] = array(
	'wikibase-lib-desc' => 'Enhavas komunajn funckiojn por Vikidatumaj kaj por la Vikidatuma klienta etendaĵo',
	'wikibase-entity-item' => 'ero',
	'wikibase-entity-property' => 'Atributo',
	'wikibase-entity-query' => 'Serĉomendo',
	'wikibase-sitelinks' => 'Listo de paĝoj ligitaj al ĉi tiu ero',
	'wikibase-sitelinks-sitename-columnheading' => 'Lingvo',
	'wikibase-sitelinks-siteid-columnheading' => 'Kodo',
	'wikibase-sitelinks-link-columnheading' => 'Ligita artikolo',
	'datatypes-type-wikibase-item' => 'Ero',
	'datatypes-type-commonsMedia' => 'Multrimeda dosiero en Komunejo',
);

/** Spanish (español)
 * @author Armando-Martin
 * @author Dalton2
 * @author Pegna
 * @author Savh
 * @author Vivaelcelta
 */
$messages['es'] = array(
	'wikibase-lib-desc' => 'Contiene una funcionalidad común para las extensiones Wikibase y cliente de Wikibase.',
	'wikibase-entity-item' => 'elemento',
	'wikibase-entity-property' => 'propiedad',
	'wikibase-entity-query' => 'consulta',
	'wikibase-error-unexpected' => 'Ocurrió un error inesperado.',
	'wikibase-error-save-generic' => 'Hubo un error al intentar guardar, por lo que no se pudieron almacenar los cambios.',
	'wikibase-error-remove-generic' => 'Hubo un error al intentar realizar la eliminación, y debido a esto no se pudieron completar los cambios.',
	'wikibase-error-save-connection' => 'Ha ocurrido un error de conexión al intentar guardar, y debido a esto no se pudieron completar los cambios. Compruebe su conexión a internet.',
	'wikibase-error-remove-connection' => 'Hubo un error de conexión al intentar eliminar, y debido a esto no se pudieron completar tus cambios. Comprueba tu conexión a internet.',
	'wikibase-error-save-timeout' => 'Estamos experimentando dificultades técnicas, y debido a esto no se pudieron terminar de guardar tus cambios.',
	'wikibase-error-remove-timeout' => 'Estamos experimentando dificultades técnicas, y debido a esto no se pudo finalizar la eliminación.',
	'wikibase-error-autocomplete-connection' => 'No se pudo consultar en la API de Wikipedia. Inténtalo de nuevo más tarde.',
	'wikibase-error-autocomplete-response' => 'Tu servidor respondió: $1',
	'wikibase-error-ui-client-error' => 'Error en la conexión a la página del cliente. Por favor, inténtalo más tarde.',
	'wikibase-error-ui-no-external-page' => 'No se encontró el artículo especificado en el sitio correspondiente.',
	'wikibase-error-ui-cant-edit' => 'No estás autorizado para realizar esta acción.',
	'wikibase-error-ui-no-permissions' => 'No tienes suficientes derechos para realizar esta acción.',
	'wikibase-error-ui-link-exists' => 'No se puede vincular a esta página porque otro elemento ya se vincula a ella.',
	'wikibase-error-ui-session-failure' => 'Tu sesión ha caducado. Inicia la sesión de nuevo.',
	'wikibase-error-ui-edit-conflict' => 'Hay un conflicto de edición. Por favor, vuelve a cargar y guarda de nuevo.',
	'wikibase-replicationnote' => 'Tenga en cuenta que puede tardar varios minutos, hasta que los cambios sean visibles en todas las wikis.',
	'wikibase-sitelinks' => 'Lista de páginas enlazadas a este elemento',
	'wikibase-sitelinks-sitename-columnheading' => 'Idioma',
	'wikibase-sitelinks-siteid-columnheading' => 'Código',
	'wikibase-sitelinks-link-columnheading' => 'Artículo enlazado',
	'wikibase-tooltip-error-details' => 'Detalles',
	'datatypes-type-wikibase-item' => 'Elemento',
	'datatypes-type-commonsMedia' => 'Archivo multimedia de Commons',
);

/** Estonian (eesti)
 * @author Avjoska
 * @author Pikne
 */
$messages['et'] = array(
	'wikibase-entity-item' => 'üksus',
	'wikibase-entity-property' => 'omadus',
	'wikibase-entity-query' => 'päring',
	'wikibase-error-unexpected' => 'Ilmnes tundmatu tõrge.',
	'wikibase-error-ui-cant-edit' => 'Sul pole lubatud seda toimingut sooritada.',
	'wikibase-error-ui-no-permissions' => 'Sul pole selle toimingu sooritamiseks vajalikke õigusi.',
	'wikibase-error-ui-session-failure' => 'Seanss on aegunud. Palun logi uuesti sisse.',
	'wikibase-error-ui-edit-conflict' => 'Esines redigeerimiskonflikt. Palun värskenda lehekülge ja salvesta uuesti.',
	'wikibase-sitelinks' => 'Sellele üksusele viitavate lehekülgede loend',
	'wikibase-sitelinks-sitename-columnheading' => 'Keel',
	'wikibase-sitelinks-siteid-columnheading' => 'Kood',
	'wikibase-sitelinks-link-columnheading' => 'Lingitud artikkel',
	'wikibase-tooltip-error-details' => 'Üksikasjad',
	'datatypes-type-wikibase-item' => 'Üksus',
	'datatypes-type-commonsMedia' => 'Commonsi meediafail',
);

/** Persian (فارسی)
 * @author Dalba
 * @author Reza1615
 * @author ZxxZxxZ
 */
$messages['fa'] = array(
	'wikibase-lib-desc' => 'نگهداری قابلیت‌های اساسی برای ویکی‌بیس و افزونه‌های کارخواه ویکی‌بیس',
	'wikibase-entity-item' => 'آیتم',
	'wikibase-entity-property' => 'ویژگی',
	'wikibase-entity-query' => 'کوئری',
	'wikibase-error-unexpected' => 'یک خطای غیرمنتظره رخ داد.',
	'wikibase-error-save-generic' => 'خطایی هنگام تلاش برای انجام ذخیره‌سازی رخ داد و به این خاطر تکمیل تغییراتتان ناموفق بود.',
	'wikibase-error-remove-generic' => 'خطایی هنگام تلاش برای حذف‌کردن رخ داد و به این خاطر تکمیل تغییراتتان ناموفق بود.',
	'wikibase-error-save-connection' => 'هنگام انجام ذخیره‌سازی خطایی در اتصال رخ داد، و به این دلیل امکان تکمیل تغییرات شما نبود. خواهشمندیم اتصال اینترنتی خود را بررسی کنید.',
	'wikibase-error-remove-connection' => 'هنگام حذف‌کردن خطایی رخ داد و به این دلیل امکان تکمیل تغییراتتان نبود. خواهشمندیم اتصال اینترنتی خود را بررسی کنید.',
	'wikibase-error-save-timeout' => 'در حال حاضر با مشکلات فنی‌ای روبه‌رو شده‌ایم و به همین خاطر «ذخیره‌سازی» شما کامل نشد.',
	'wikibase-error-remove-timeout' => 'در حال حاضر با مشکلات فنیی‌ای روبه‌رو شده‌ایم و به همین خاطر عمل «حذف‌کردن» کامل نشد.',
	'wikibase-error-autocomplete-connection' => 'امکان پرسمان از واسط برنامه‌نویسی کاربردی وجود نداشت. لطفاً بعداً امتحان کنید.',
	'wikibase-error-autocomplete-response' => 'پاسخ سرور: $1',
	'wikibase-error-ui-client-error' => 'اتصال به صفحهٔ کارخواه ناموفق بود. لطفاً بعداً امتحان کنید.',
	'wikibase-error-ui-no-external-page' => 'مقالهٔ یادشده در وب‌گاه مربوطه پیدا نشد.',
	'wikibase-error-ui-cant-edit' => 'شما مجاز به انجام این عمل نیستید.',
	'wikibase-error-ui-no-permissions' => 'شما دسترسی‌های لازم برای انجام این عمل را ندارید.',
	'wikibase-error-ui-link-exists' => 'نمی‌توانید به این صفحه پیوند دهید چون آیتم دیگری از قبل به آن پیوند داده‌است.',
	'wikibase-error-ui-session-failure' => 'نشست شما منقضی شده‌است. لطفاً دوباره به سامانه وارد شوید.',
	'wikibase-error-ui-edit-conflict' => 'تعارض ویرایشی رخ داده است. خواهشمندیم از نو بارگذاری و ذخیره کنید.',
	'wikibase-replicationnote' => 'لطفا توجه کنید چند دقیقه زمان لازم است تا تغییرات در همهٔ ویکی‌ها قابل مشاهده باشد.',
	'wikibase-sitelinks' => 'فهرست صفحه‌هایی که به این آیتم پیوند دارند',
	'wikibase-sitelinks-sitename-columnheading' => 'زبان',
	'wikibase-sitelinks-siteid-columnheading' => 'کد',
	'wikibase-sitelinks-link-columnheading' => 'مقالهٔ پیوندداده‌شده',
	'wikibase-tooltip-error-details' => 'جزئیات',
	'datatypes-type-wikibase-item' => 'آیتم',
	'datatypes-type-commonsMedia' => 'پرونده‌های ویکی‌انبار',
);

/** Finnish (suomi)
 * @author Crt
 * @author Nike
 * @author Stryn
 * @author VezonThunder
 */
$messages['fi'] = array(
	'wikibase-lib-desc' => 'Sisältää Wikibase- ja Wikibase Client -laajennuksille yhteistä toiminnallisuutta',
	'wikibase-entity-item' => 'kohde',
	'wikibase-entity-property' => 'ominaisuus',
	'wikibase-entity-query' => 'kysely',
	'wikibase-error-unexpected' => 'Odottamaton virhe.',
	'wikibase-error-save-generic' => 'Tallennus epäonnistui. Muutoksiasi ei voitu toteuttaa.',
	'wikibase-error-remove-generic' => 'Poistaminen epäonnistui. Muutoksiasi ei voitu toteuttaa.',
	'wikibase-error-save-connection' => 'Tallennettaessa tapahtui yhteysvirhe. Muutoksiasi ei voitu toteuttaa. Tarkista Internet-yhteytesi.',
	'wikibase-error-remove-connection' => 'Poistaminen epäonnistui verkkovirheen takia. Muutoksiasi ei voitu toteuttaa. Tarkista Internet-yhteytesi.',
	'wikibase-error-save-timeout' => 'Sivustolla on teknisiä ongelmia. Tallennustasi ei voitu toteuttaa.',
	'wikibase-error-remove-timeout' => 'Sivustolla on teknisiä ongelmia. Poistoasi ei voitu toteuttaa.',
	'wikibase-error-autocomplete-connection' => 'Kysely Wikipedian rajapinnalta epäonnistui. Yritä myöhemmin uudelleen.',
	'wikibase-error-autocomplete-response' => 'Palvelin vastasi: $1',
	'wikibase-error-ui-client-error' => 'Yhteys asiakassivuun epäonnistui. Yritä myöhemmin uudelleen.',
	'wikibase-error-ui-no-external-page' => 'Määritettyä artikkelia ei löytynyt vastaavalta sivustolta.',
	'wikibase-error-ui-cant-edit' => 'Sinulla ei ole oikeutta suorittaa tätä toimintoa.',
	'wikibase-error-ui-no-permissions' => 'Sinulla ei ole tämän toiminnon suorittamiseen vaadittavia oikeuksia.',
	'wikibase-error-ui-link-exists' => 'Et voi lisätä linkkiä tähän sivuun, koska toisessa kohteessa on jo sama linkki.',
	'wikibase-error-ui-session-failure' => 'Istuntosi on vanhentunut. Kirjaudu sisään uudelleen.',
	'wikibase-error-ui-edit-conflict' => 'Tapahtui muokkausristiriita. Päivitä sivu ja tallenna uudelleen.',
	'wikibase-replicationnote' => 'Huomaa, että voi kestää useita minuutteja ennen kuin muutokset näkyvät kaikissa wikeissä.',
	'wikibase-sitelinks' => 'Luettelo tähän kohteeseen linkitetyistä sivuista',
	'wikibase-sitelinks-sitename-columnheading' => 'Kieli',
	'wikibase-sitelinks-siteid-columnheading' => 'Koodi',
	'wikibase-sitelinks-link-columnheading' => 'Linkitetty artikkeli',
	'wikibase-tooltip-error-details' => 'Tiedot',
	'datatypes-type-wikibase-item' => 'Kohde',
	'datatypes-type-commonsMedia' => 'Commonsin mediatiedosto',
);

/** French (français)
 * @author Alno
 * @author Arkanosis
 * @author Crochet.david
 * @author DavidL
 * @author Gomoko
 * @author Ltrlg
 * @author Nnemo
 * @author Tititou36
 * @author Wyz
 */
$messages['fr'] = array(
	'wikibase-lib-desc' => 'Regroupe des fonctionnalités communes aux extensions Wikibase et Wikibase Client',
	'wikibase-entity-item' => 'élément',
	'wikibase-entity-property' => 'propriété',
	'wikibase-entity-query' => 'requête',
	'wikibase-error-unexpected' => 'Une erreur inattendue s’est produite.',
	'wikibase-error-save-generic' => "Une erreur est survenue lors de l'enregistrement, en conséquence, vos modifications n'ont pas pu être prises en compte.",
	'wikibase-error-remove-generic' => "Une erreur est survenue lors de la suppression, en conséquence, vos modifications n'ont pas pu être prises en compte.",
	'wikibase-error-save-connection' => "Une erreur de connexion est survenue lors de l'enregistrement, en conséquence, vos modifications n'ont pas pu être prises en compte. Vérifiez votre connexion Internet.",
	'wikibase-error-remove-connection' => "Une erreur de connexion est survenue lors de la suppression, en conséquence, vos modifications n'ont pas pu être prises en compte. Vérifiez votre connexion Internet.",
	'wikibase-error-save-timeout' => 'Nous rencontrons actuellement quelques problèmes techniques, la sauvegarde que vous avez demandée ne peut être réalisée.',
	'wikibase-error-remove-timeout' => "Nous rencontrons quelques problèmes techniques, en conséquence la suppression que vous avez demandée n'a pas pu être réalisée.",
	'wikibase-error-autocomplete-connection' => "Impossible d'interroger l'API Wikipedia. Veuillez réessayer plus tard.",
	'wikibase-error-autocomplete-response' => 'Le serveur a répondu&nbsp;: $1',
	'wikibase-error-ui-client-error' => 'Échec de la connexion à la page client. Veuillez réessayer ultérieurement.',
	'wikibase-error-ui-no-external-page' => "L'article spécifié est introuvable sur le site correspondant.",
	'wikibase-error-ui-cant-edit' => 'Vous n’êtes pas autorisé(e) à effectuer cette action.',
	'wikibase-error-ui-no-permissions' => 'Vous n’avez pas de droits suffisants pour effectuer cette action.',
	'wikibase-error-ui-link-exists' => "Vous ne pouvez pas faire de lien vers cette page parce qu'un autre élément la référence déjà.",
	'wikibase-error-ui-session-failure' => 'Votre session a expiré. Veuillez vous connecter à nouveau.',
	'wikibase-error-ui-edit-conflict' => 'Il y a conflit d’édition. Rechargez la page et enregistrez de nouveau.',
	'wikibase-replicationnote' => 'Veuillez noter que cela peut prendre plusieurs minutes avant que les modifications soient visibles sur tous les wikis.',
	'wikibase-sitelinks' => 'Liste des pages liées à cet élément',
	'wikibase-sitelinks-sitename-columnheading' => 'Langue',
	'wikibase-sitelinks-siteid-columnheading' => 'Code',
	'wikibase-sitelinks-link-columnheading' => 'Articles liés',
	'wikibase-tooltip-error-details' => 'Détails',
	'datatypes-type-wikibase-item' => 'Élément',
	'datatypes-type-commonsMedia' => 'Fichier multimédia de Commons',
);

/** Franco-Provençal (arpetan)
 * @author ChrisPtDe
 */
$messages['frp'] = array(
	'wikibase-entity-item' => 'èlèment',
	'wikibase-entity-property' => 'propriètât',
	'wikibase-entity-query' => 'demanda',
	'wikibase-sitelinks' => 'Lista de les pâges liyêyes a cet’èlèment',
	'wikibase-sitelinks-sitename-columnheading' => 'Lengoua',
	'wikibase-sitelinks-siteid-columnheading' => 'Code',
	'wikibase-sitelinks-link-columnheading' => 'Articllo liyê',
	'datatypes-type-wikibase-item' => 'Èlèment',
	'datatypes-type-commonsMedia' => 'Fichiér mèdia de Commons',
);

/** Galician (galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'wikibase-lib-desc' => 'Contén funcionalidades comúns para as extensións Wikibase e Wikibase Client',
	'wikibase-entity-item' => 'elemento',
	'wikibase-entity-property' => 'propiedade',
	'wikibase-entity-query' => 'pescuda',
	'wikibase-error-unexpected' => 'Houbo un erro inesperado.',
	'wikibase-error-save-generic' => 'Houbo un erro ao levar a cabo o gardado, polo que non se puideron completar os cambios.',
	'wikibase-error-remove-generic' => 'Houbo un erro ao levar a cabo a eliminación, polo que non se puideron completar os cambios.',
	'wikibase-error-save-connection' => 'Houbo un erro na conexión ao levar a cabo o gardado, polo que non se puideron completar os cambios. Comprobe a súa conexión á internet.',
	'wikibase-error-remove-connection' => 'Houbo un erro na conexión ao levar a cabo a eliminación, polo que non se puideron completar os cambios. Comprobe a súa conexión á internet.',
	'wikibase-error-save-timeout' => 'Estamos experimentando dificultades técnicas, polo que non se puido completar o gardado.',
	'wikibase-error-remove-timeout' => 'Estamos experimentando dificultades técnicas, polo que non se puido completar a eliminación.',
	'wikibase-error-autocomplete-connection' => 'Non se puido pescudar na API da Wikipedia. Inténteo de novo máis tarde.',
	'wikibase-error-autocomplete-response' => 'O servidor respondeu: $1',
	'wikibase-error-ui-client-error' => 'Fallou a conexión coa páxina do cliente. Inténteo de novo máis tarde.',
	'wikibase-error-ui-no-external-page' => 'Non se puido atopar o artigo especificado no sitio correspondente.',
	'wikibase-error-ui-cant-edit' => 'Non lle está permitido levar a cabo esa acción.',
	'wikibase-error-ui-no-permissions' => 'Non ten os dereitos necesarios para levar a cabo esta acción.',
	'wikibase-error-ui-link-exists' => 'Non pode ligar con esta páxina porque xa hai outro elemento que liga con ela.',
	'wikibase-error-ui-session-failure' => 'A súa sesión caducou. Acceda ao sistema de novo.',
	'wikibase-error-ui-edit-conflict' => 'Hai un conflito de edición. Volva cargar a páxina e garde de novo.',
	'wikibase-replicationnote' => 'Teña en conta que pode levar varios minutos que as modificacións sexan visibles en todos os wikis',
	'wikibase-sitelinks' => 'Lista de páxinas con ligazóns cara a este elemento',
	'wikibase-sitelinks-sitename-columnheading' => 'Lingua',
	'wikibase-sitelinks-siteid-columnheading' => 'Código',
	'wikibase-sitelinks-link-columnheading' => 'Artigo ligado',
	'wikibase-tooltip-error-details' => 'Detalles',
	'datatypes-type-wikibase-item' => 'Elemento',
	'datatypes-type-commonsMedia' => 'Ficheiro multimedia de Commons',
);

/** Swiss German (Alemannisch)
 * @author Als-Holder
 */
$messages['gsw'] = array(
	'wikibase-lib-desc' => 'Stellt vu dr Erwyterige Wikibase un Wikibase Client gmeinsam gnutzti Funktione z Verfiegig',
	'wikibase-entity-item' => 'Objäkt',
	'wikibase-entity-property' => 'Eigeschaft',
	'wikibase-entity-query' => 'Abfrog',
	'wikibase-sitelinks' => '{{SITENAME}}-Syte, wu mit däm Datenelemänt verchnipft sin', # Fuzzy
	'datatypes-type-wikibase-item' => 'Objäkt',
	'datatypes-type-commonsMedia' => 'Mediedatei uf dr Commons',
);

/** Hebrew (עברית)
 * @author Amire80
 */
$messages['he'] = array(
	'wikibase-lib-desc' => 'הפעולות המשותפות להרחבות Wikibase ו־Wikibase Client',
	'wikibase-entity-item' => 'פריט',
	'wikibase-entity-property' => 'מאפיין',
	'wikibase-entity-query' => 'שאילתה',
	'wikibase-error-unexpected' => 'אירעה שגיאה בלתי־צפויה.',
	'wikibase-error-save-generic' => 'אירעה שגיאה בעת ניסיון לבצע שמירה ובגלל זה לא ניתן להשלים את השינויים שלך.',
	'wikibase-error-remove-generic' => 'אירעה שגיאה בעת ניסיון לבצע הסרה ובגלל זה לא ניתן להשלים את השינויים שלך.',
	'wikibase-error-save-connection' => 'אירעה שגיאת התחברות בעת ניסיון לבצע שמירה ובגלל זה לא ניתן להשלים את השינויים שלך. נא לבדוק את חיבור האינטרנט שלך.',
	'wikibase-error-remove-connection' => 'אירעה שגיאה בעת ניסיון לבצע הסרה ובגלל זה לא ניתן להשלים את השינויים שלך. נא לבדוק את חיבור האינטרנט שלך.',
	'wikibase-error-save-timeout' => 'יש לנו קשיים טכניים ובגלל זה לא ניתן להשלים את השמירה שלך.',
	'wikibase-error-remove-timeout' => 'יש לנו קשיים טכניים ובגלל זה לא ניתן להשלים את ההסרה שלך.',
	'wikibase-error-autocomplete-connection' => 'לא ניתן לבצע שאילתה מתוך API של ויקיפדיה. נא לנסות שוב מאוחר יותר.',
	'wikibase-error-autocomplete-response' => 'השרת ענה: $1',
	'wikibase-error-ui-client-error' => 'החיבור לדף הלקוח נכשל. נא לנסות שוב מאוחר יותר.',
	'wikibase-error-ui-no-external-page' => 'הערך שהוזן לא נמצא באתר המתאים.',
	'wikibase-error-ui-cant-edit' => 'אין לך הרשאה לבצע את הפעולה הזאת.',
	'wikibase-error-ui-no-permissions' => 'אין לך מספיק הרשאות לבצע את הפעולה הזאת.',
	'wikibase-error-ui-link-exists' => 'אין לך אפשרות לקשר לדף הזה כי פריט אחר כבר מקשר אליו.',
	'wikibase-error-ui-session-failure' => 'השיחה שלך פגה. נא להיכנס שוב.',
	'wikibase-error-ui-edit-conflict' => 'אירעה התנגשות עריכה. נא לרענן את הדף ולשמור מחדש.',
	'wikibase-replicationnote' => 'יש לשים לב לכך שייקח מספר דקות עד שהשינויים יוצגו בכל אתרי הוויקי',
	'wikibase-sitelinks' => 'רשימת הדפים המקושרים לפריט הזה.',
	'wikibase-sitelinks-sitename-columnheading' => 'שפה',
	'wikibase-sitelinks-siteid-columnheading' => 'קוד',
	'wikibase-sitelinks-link-columnheading' => 'ערך מקושר',
	'wikibase-tooltip-error-details' => 'פרטים',
	'datatypes-type-wikibase-item' => 'פריט',
	'datatypes-type-commonsMedia' => 'קובץ מדיה בוויקישיתוף',
);

/** Upper Sorbian (hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'wikibase-lib-desc' => 'Steji powšitkownu funkcionalnosć za rozšěrjeni Wikibase a Wikibase Client k dispoziciji',
	'wikibase-entity-item' => 'element',
	'wikibase-entity-property' => 'kajkosć',
	'wikibase-entity-query' => 'naprašowanje',
	'wikibase-error-unexpected' => 'Njewočakowany zmylk je wustupił.',
	'wikibase-error-save-generic' => 'Při składowanju je zmylk wustupił, a tohodla njedachu so změny přewjesć.',
	'wikibase-error-remove-generic' => 'Při wotstronjenu je zmylk wustupił, a tohodla njedachu so twoje změny přewjesć.',
	'wikibase-error-save-connection' => 'Zwiskowy zmylk je při składowanju wustupił a twoje změny njedadźa so tohodla přewjesć. Prošu přepruwuj swój internetowy zwisk.',
	'wikibase-error-remove-connection' => 'Zwiskowy zmylk je při wotstronjenju wustupił a tohodla njedadźa so twoje změny přewjesć. Prošu přepruwuj swój internetowy zwisk.',
	'wikibase-error-save-timeout' => 'Mamy techniske ćežkosće a tohodla njeda so ničo składować.',
	'wikibase-error-remove-timeout' => 'Mamy techniske ćežkosće a tohodla njeda so ničo wotstronić.',
	'wikibase-error-autocomplete-connection' => 'API Wikipedije njeda so naprašować. Prošu spytaj pozdźišo hišće raz.',
	'wikibase-error-autocomplete-response' => 'Serwer wotmołwi: $1',
	'wikibase-error-ui-client-error' => 'Zwisk k eksternej webstronje je so njeporadźił. Prošu spytaj pozdźišo hišće raz.',
	'wikibase-error-ui-no-external-page' => 'Podaty nastawk njeda so na wotpowědowacym sydle namakać.',
	'wikibase-error-ui-cant-edit' => 'Njesměš tutu akciju wuwjesć.',
	'wikibase-error-ui-no-permissions' => 'Nimaš dosć prawow, zo by tutu akciju wuwjedł.',
	'wikibase-error-ui-link-exists' => 'Njemóžeš k tutej stronje wotkazować, dokelž druhi element hižo k njej wotkazuje.',
	'wikibase-error-ui-session-failure' => 'Twoje posedźenje je spadnyło. Prošu přizjew so hišće raz.',
	'wikibase-error-ui-edit-conflict' => 'Je wobdźěłowanski konflikt wustupił. Prošu začituj a składuj znowa.',
	'wikibase-replicationnote' => 'Prošu dźiwaj na to, zo móže wjacore mjeńšiny trać, doniž změny na wšěch wikijach widźomne njejsu.',
	'wikibase-sitelinks' => 'Lisćina stronow, kotrež su z tutym elementom zwjazane',
	'wikibase-sitelinks-sitename-columnheading' => 'Rěč',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Wotkazany nastawk',
	'wikibase-tooltip-error-details' => 'Podrobnosće',
	'datatypes-type-wikibase-item' => 'Element',
	'datatypes-type-commonsMedia' => 'Medijowa dataja na Wikimedia Commons',
);

/** Hungarian (magyar)
 * @author Tgr
 */
$messages['hu'] = array(
	'wikibase-lib-desc' => 'A Wikibase és a Wikibase kliens kiterjesztések közös funkcióit tartalmazza',
	'wikibase-entity-item' => 'tétel',
	'wikibase-entity-property' => 'tulajdonság',
	'wikibase-entity-query' => 'lekérdezés',
	'wikibase-sitelinks' => 'Ehhez a fogalomhoz kapcsolt lapok listája',
	'wikibase-sitelinks-sitename-columnheading' => 'Nyelv',
	'wikibase-sitelinks-siteid-columnheading' => 'Kód',
	'wikibase-sitelinks-link-columnheading' => 'Kapcsolt szócikk',
	'datatypes-type-wikibase-item' => 'Tétel',
	'datatypes-type-commonsMedia' => 'Commons médiafájl',
);

/** Interlingua (interlingua)
 * @author McDutchie
 */
$messages['ia'] = array(
	'wikibase-lib-desc' => 'Contine functionalitate commun pro le extensiones Wikibase e Wikibase Client',
	'wikibase-sitelinks' => 'Lista de paginas ligate a iste objecto',
);

/** Indonesian (Bahasa Indonesia)
 * @author පසිඳු කාවින්ද
 */
$messages['id'] = array(
	'wikibase-sitelinks-sitename-columnheading' => 'Bahasa',
);

/** Iloko (Ilokano)
 * @author Lam-ang
 */
$messages['ilo'] = array(
	'wikibase-lib-desc' => 'Agtengngel kadagiti sapasap a pamay-an para kadagiti Wikibase ken Wikibase a kliente a pagpaatiddog',
	'wikibase-entity-item' => 'banag',
	'wikibase-entity-property' => 'tagikua',
	'wikibase-entity-query' => 'panagbiruk',
	'wikibase-error-unexpected' => 'Adda rimsua a maysa a saan a nanamnama a biddut.',
	'wikibase-error-save-generic' => 'Ada biddut a napasamak bayat nga agar-aramidka ti panagidulin iti daytoy, saan a malpas dagiti panagibalbaliwmo.',
	'wikibase-error-remove-generic' => 'Adda biddut a napasamak bayat nga agar-aramidka ti panagikkat ti daytoy, saan a malpas dagiti panagibalbaliwmo.',
	'wikibase-error-save-connection' => 'Adda biddut napasamak ti panakaikapet bayat nga agar-aramid ti panagidulin, ken gapu ti daytoy dagiti panagibalwbaliwmo ket saan a malpas. Pangngaasi a kitaem ti panakaikapetmo ti internet.',
	'wikibase-error-remove-connection' => 'Adda biddut napasamak ti panakaikapet bayat nga agar-aramid ti panagikkat, ken gapu ti daytoy dagiti panagibalwbaliwmo ket saan a malpas. Pangngaasi a kitaem ti panakaikapetmo ti internet.',
	'wikibase-error-save-timeout' => 'Makasansanay kami kadagiti teknikal a parikut, ken gapu ti daytoy ti "indulinmo" ket saan a malpas.',
	'wikibase-error-remove-timeout' => 'Makasansanay kami kadagiti teknikal a parikut, ken gapu ti daytoy ti "panagikkatmo" ket saan a malpas.',
	'wikibase-error-autocomplete-connection' => 'Saan a makagun-od ti Wikipedia API. Pangngaasi a padasem manen no madamdama.',
	'wikibase-error-autocomplete-response' => 'Simmungbat ti server: $1',
	'wikibase-error-ui-client-error' => 'Ti panakaikapet ti kliente a panid ket napaay. Pangngaasi a padasem manen no madamdama.',
	'wikibase-error-ui-no-external-page' => 'Ti naitudo nga artikulo ket saan a mabirukan idiay maipada a sitio.',
	'wikibase-error-ui-cant-edit' => 'Saanmo a mabalin ti agaramid ti daytoy a tignay.',
	'wikibase-error-ui-no-permissions' => 'Awan ti umanay a karbengam nga agaramid ti daytoy a tignay.',
	'wikibase-error-ui-link-exists' => 'Saanka a makasilpo ti daytoy a panid gaputa adda ti maysa a banagen a nakasilpo ti daytoy.',
	'wikibase-error-ui-session-failure' => 'Ti gimongam ket nagpason. Pangngaasi a sumrekka manen.',
	'wikibase-error-ui-edit-conflict' => 'Adda kasinnupiat a panagurnos. Pangngaasi nga ikarga ken idulin manen.',
	'wikibase-replicationnote' => 'Pangngaasi nga ammuem a mabalin nga agpaut ti adu a minutos aginggana dagiti panagbalbaliw ket makita kadagiti amin a wiki',
	'wikibase-sitelinks' => 'Listaan dagiti panid a naisilpo ti daytoy a banag',
	'wikibase-sitelinks-sitename-columnheading' => 'Pagsasao',
	'wikibase-sitelinks-siteid-columnheading' => 'Kodigo',
	'wikibase-sitelinks-link-columnheading' => 'Naisilpo nga artikulo',
	'wikibase-tooltip-error-details' => 'Dagiti salaysay',
	'datatypes-type-wikibase-item' => 'Banag',
	'datatypes-type-commonsMedia' => 'Midia a papeles ti Commons',
);

/** Icelandic (íslenska)
 * @author Snævar
 */
$messages['is'] = array(
	'wikibase-lib-desc' => 'Inniheldur almenna virkni fyrir Wikibase og Wikibase Client.',
	'wikibase-entity-item' => 'hlutur',
	'wikibase-entity-property' => 'eiginleiki',
	'wikibase-entity-query' => 'fyrirspurn',
	'wikibase-error-unexpected' => 'Óvænt villa átti sér stað.',
	'wikibase-error-save-generic' => 'Villa átti sér stað þegar þú reyndir að framkvæma vistun og því mistókst að vista breytingarnar þínar.',
	'wikibase-error-remove-generic' => 'Villa átti sér stað þegar þú reyndir að fjarlægja hlut og því mistókst að ljúka breytingum þínum.',
	'wikibase-error-save-connection' => 'Tengingar villa átti sér stað þegar reynt var að framkvæma vistun og því mistókst að ljúka breytingunum þínum. Athugaðu hvort þú sért tengd/ur netinu.',
	'wikibase-error-remove-connection' => 'Tengingar villa átti sér stað þegar þú reyndir að framkvæma fjarlægingu og því mistókst að ljúka breytingum þínum. Vinsamlegast athugaðu hvort þú sért tengd/ur netinu.',
	'wikibase-error-save-timeout' => 'Við höfum orðið fyrir tæknilegum örðugleikum og því mistókst að ljúka vistun.',
	'wikibase-error-remove-timeout' => 'Við höfum orðið fyrir tæknilegum örðugleikum og því mistókst að ljúka fjarlægingu.',
	'wikibase-error-autocomplete-connection' => 'Mistókst að senda fyrirspurn til Wikipedia. Vinsamlegast reyndu aftur síðar.',
	'wikibase-error-autocomplete-response' => 'Vefþjónninn svaraði: $1',
	'wikibase-error-ui-client-error' => 'Tenging við biðlarann mistókst. Vinsamlegast reyndu aftur síðar.',
	'wikibase-error-ui-no-external-page' => 'Greinin sem tilgreind var fannst ekki á vefsíðunni.',
	'wikibase-error-ui-cant-edit' => 'Þú getur ekki gert þessa aðgerð.',
	'wikibase-error-ui-no-permissions' => 'Þú hefur ekki tilætluð réttindi til þess að framkvæma þessa aðgerð.',
	'wikibase-error-ui-link-exists' => 'Þú getur ekki tengt í þessa síðu því annar hlutur tengir nú þegar í hana.',
	'wikibase-error-ui-session-failure' => 'Setan þín rann út. Vinsamlegast skráðu þig inn aftur.',
	'wikibase-error-ui-edit-conflict' => 'Breytingarárekstur. Vinsamlegast endurhladdu síðunni og vistaðu aftur.',
	'wikibase-replicationnote' => 'Athugaðu að það tekur nokkrar mínútur þangað til breytingarnar eru sýnilegar á öllum wiki verkefnum.',
	'wikibase-sitelinks' => 'Listi yfir síður sem tengja á þennan hlut',
	'wikibase-sitelinks-sitename-columnheading' => 'Tungumál',
	'wikibase-sitelinks-siteid-columnheading' => 'Kóði',
	'wikibase-sitelinks-link-columnheading' => 'Tengd grein',
	'wikibase-tooltip-error-details' => 'Nánar',
	'datatypes-type-wikibase-item' => 'Hlutur',
	'datatypes-type-commonsMedia' => 'Commons margmiðlunarskrá',
);

/** Italian (italiano)
 * @author Beta16
 * @author Raoli
 */
$messages['it'] = array(
	'wikibase-lib-desc' => 'Contiene le funzionalità comuni per le estensioni Wikibase e Wikibase Client.',
	'wikibase-entity-item' => 'elemento',
	'wikibase-entity-property' => 'proprietà',
	'wikibase-entity-query' => 'interrogazione',
	'wikibase-error-unexpected' => 'Si è verificato un errore imprevisto.',
	'wikibase-error-save-generic' => 'Si è verificato un errore durante il tentativo di salvataggio, perciò le tue modifiche potrebbero non essere state completamente memorizzate.',
	'wikibase-error-remove-generic' => 'Si è verificato un errore durante il tentativo di rimozione, perciò le tue modifiche potrebbero non essere state completamente memorizzate.',
	'wikibase-error-save-connection' => 'Si è verificato un errore di connessione durante il tentativo di salvataggio, perciò le tue modifiche potrebbero non essere state completamente memorizzate. Per favore, controlla la tua connessione ad internet.',
	'wikibase-error-remove-connection' => 'Si è verificato un errore di connessione durante il tentativo di rimozione, perciò le tue modifiche potrebbero non essere state completamente memorizzate. Per favore, controlla la tua connessione ad internet.',
	'wikibase-error-save-timeout' => 'Stiamo riscontrando difficoltà tecniche, perciò il tuo salvataggio potrebbe non essere stato completato.',
	'wikibase-error-remove-timeout' => 'Stiamo riscontrando difficoltà tecniche, perciò la tua rimozione potrebbe non essere stata completata.',
	'wikibase-error-autocomplete-connection' => 'Non è possibile interrogare le API di Wikipedia. Riprova più tardi.',
	'wikibase-error-autocomplete-response' => 'Risposta del server: $1',
	'wikibase-error-ui-client-error' => 'La connessione alla pagina client non è riuscita. Riprova più tardi.',
	'wikibase-error-ui-no-external-page' => 'La voce specificata non è stata trovata sul sito corrispondente.',
	'wikibase-error-ui-cant-edit' => 'Non sei autorizzato ad eseguire questa azione.',
	'wikibase-error-ui-no-permissions' => 'Non hai i diritti sufficienti per eseguire questa azione.',
	'wikibase-error-ui-link-exists' => 'Non puoi inserire un collegamento a questa pagina perché un altro elemento già collega ad essa.',
	'wikibase-error-ui-session-failure' => 'La sessione è scaduta. Accedi nuovamente.',
	'wikibase-error-ui-edit-conflict' => 'Si è verificato un conflitto di edizione. Si prega di ricaricare e salvare di nuovo.',
	'wikibase-replicationnote' => 'Potrebbero essere necessari diversi minuti prima che le modifiche siano visibili su tutti i wiki',
	'wikibase-sitelinks' => 'Elenco delle pagine collegate a questo elemento',
	'wikibase-sitelinks-sitename-columnheading' => 'Lingua',
	'wikibase-sitelinks-siteid-columnheading' => 'Codice',
	'wikibase-sitelinks-link-columnheading' => 'Voce collegata',
	'wikibase-tooltip-error-details' => 'Dettagli',
	'datatypes-type-wikibase-item' => 'Elemento',
	'datatypes-type-commonsMedia' => 'File multimediale su Commons',
);

/** Japanese (日本語)
 * @author Shirayuki
 */
$messages['ja'] = array(
	'wikibase-lib-desc' => 'ウィキベースとウィキベースクライアント拡張機能で共通の機能を保持する',
	'wikibase-entity-item' => '項目',
	'wikibase-entity-property' => 'プロパティ',
	'wikibase-entity-query' => 'クエリ',
	'wikibase-error-unexpected' => '予期しないエラーが発生しました。',
	'wikibase-error-autocomplete-response' => 'サーバーの応答: $1',
	'wikibase-error-ui-no-external-page' => '指定した記事は、対応するサイト内で見つかりませんでした。',
	'wikibase-error-ui-cant-edit' => 'この操作を行うことは許可されていません。',
	'wikibase-error-ui-no-permissions' => 'あなたにはこの操作を実行する権限がありません。',
	'wikibase-error-ui-link-exists' => '別の項目から既にリンクしているため、このページにはリンクできません。',
	'wikibase-error-ui-session-failure' => 'セッションの期限が切れました。再度ログインしてください。',
	'wikibase-error-ui-edit-conflict' => '編集が競合しました。再読込して再度保存してください。',
	'wikibase-replicationnote' => '変更内容をすべてのウィキに反映させるのに時間がかかる場合があることにご注意ください',
	'wikibase-sitelinks' => 'この項目にリンクしているページの一覧',
	'wikibase-sitelinks-sitename-columnheading' => '言語',
	'wikibase-sitelinks-siteid-columnheading' => 'コード',
	'wikibase-sitelinks-link-columnheading' => 'リンクされている記事',
	'wikibase-tooltip-error-details' => '詳細',
	'datatypes-type-wikibase-item' => '項目',
	'datatypes-type-commonsMedia' => 'コモンズのメディアファイル',
);

/** Georgian (ქართული)
 * @author David1010
 */
$messages['ka'] = array(
	'wikibase-sitelinks-sitename-columnheading' => 'ენა',
	'wikibase-sitelinks-siteid-columnheading' => 'კოდი',
);

/** Korean (한국어)
 * @author 아라
 */
$messages['ko'] = array(
	'wikibase-lib-desc' => '위키베이스와 위키베이스 클라이언트 확장 기능을 위한 공통 함수를 얻습니다',
	'wikibase-entity-item' => '항목',
	'wikibase-entity-property' => '속성',
	'wikibase-entity-query' => '쿼리',
	'wikibase-error-unexpected' => '예기치 않은 오류가 발생했습니다.',
	'wikibase-error-save-generic' => '저장을 수행하는 동안 오류가 발생했고 이 때문에 바뀜을 완료할 수 없습니다.',
	'wikibase-error-remove-generic' => '삭제를 수행하는 동안 오류가 발생했고 이 때문에 바뀜을 완료할 수 없습니다.',
	'wikibase-error-save-connection' => '저장을 수행하는 동안 연결 오류가 발생했으며 이 때문에 바뀜을 완료할 수 없습니다. 인터넷 연결을 확인하세요.',
	'wikibase-error-remove-connection' => '삭제를 수행하는 동안 연결 오류가 발생했으며 이 때문에 바뀜을 완료할 수 없습니다. 인터넷 연결을 확인하세요.',
	'wikibase-error-save-timeout' => '기술적인 문제가 있기 때문에 이 "save"가 완료되지 않았습니다.',
	'wikibase-error-remove-timeout' => '기술적인 문제가 있기 때문에 이 "remove"가 완료되지 않았습니다.',
	'wikibase-error-autocomplete-connection' => '위키백과 API를 쿼리할 수 없습니다. 나중에 다시 시도하세요.',
	'wikibase-error-autocomplete-response' => '서버 응답: $1',
	'wikibase-error-ui-client-error' => '클라이언트 문서에 연결에 실패했습니다. 나중에 다시 시도하세요.',
	'wikibase-error-ui-no-external-page' => '지정한 문서는 해당 사이트에서 찾을 수 없습니다.',
	'wikibase-error-ui-cant-edit' => '이 작업을 수행하는 것이 허용되지 않습니다.',
	'wikibase-error-ui-no-permissions' => '이 작업을 수행할 수 있는 충분한 권한이 없습니다.',
	'wikibase-error-ui-link-exists' => '다른 항목을 이미 링크했기 때문에 이 문서에 링크할 수 없습니다.',
	'wikibase-error-ui-session-failure' => '세션이 만료되었습니다. 다시 로그인하세요.',
	'wikibase-error-ui-edit-conflict' => '편집 충돌했습니다. 다시 불러오고 나서 다시 저장하세요.',
	'wikibase-replicationnote' => '바뀜을 모든 위키에 보이는 시간이 걸릴 수 있음에 주의하세요',
	'wikibase-sitelinks' => '이 항목으로 링크한 문서 목록',
	'wikibase-sitelinks-sitename-columnheading' => '언어',
	'wikibase-sitelinks-siteid-columnheading' => '코드',
	'wikibase-sitelinks-link-columnheading' => '링크한 문서',
	'wikibase-tooltip-error-details' => '자세한 사항',
	'datatypes-type-wikibase-item' => '항목',
	'datatypes-type-commonsMedia' => '공용 미디어 파일',
);

/** Colognian (Ripoarisch)
 * @author Purodha
 */
$messages['ksh'] = array(
	'wikibase-lib-desc' => 'Jemeinsamme Fungxjuhne för di Projramm-Zohsäz <i lang="en">Wikibase</i> un <i lang="en">Wikibase Client</i>.',
	'wikibase-entity-item' => 'Jäjeschtand',
	'wikibase-entity-property' => 'Eijeschaff',
	'wikibase-entity-query' => 'Frooch',
	'wikibase-sitelinks-sitename-columnheading' => 'Schprooch',
	'wikibase-sitelinks-siteid-columnheading' => 'Köözel',
	'datatypes-type-wikibase-item' => 'Jäjeschtand',
	'datatypes-type-commonsMedia' => 'Meedijedattei vun Wikkimeedija Commons',
);

/** Kurdish (Latin script) (Kurdî (latînî)‎)
 * @author George Animal
 */
$messages['ku-latn'] = array(
	'wikibase-sitelinks-sitename-columnheading' => 'Ziman',
);

/** Kirghiz (Кыргызча)
 * @author Growingup
 */
$messages['ky'] = array(
	'wikibase-sitelinks-sitename-columnheading' => 'Тил',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'wikibase-sitelinks' => 'Lëscht vun de Säiten déi mat dësem Objet verlinkt sinn',
	'wikibase-sitelinks-sitename-columnheading' => 'Sprooch',
);

/** Macedonian (македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'wikibase-lib-desc' => 'Содржи почести функции за додатоците Викибаза и Клиент на Викибазата.',
	'wikibase-entity-item' => 'единица',
	'wikibase-entity-property' => 'својство',
	'wikibase-entity-query' => 'барање',
	'wikibase-error-unexpected' => 'Се појави неочекувана грешка.',
	'wikibase-error-save-generic' => 'Се појави грешка при обидот да ги зачувам промените. Затоа, бараната постапка не е извршена.',
	'wikibase-error-remove-generic' => 'Наидов на грешка при обидот да го извршам отстранувањето. Затоа, бараната постапка не е извршена.',
	'wikibase-error-save-connection' => 'Не можев да ги зачувам промените бидејќи се појави грешка во линијата. Проверете си ја врската со интернет.',
	'wikibase-error-remove-connection' => 'Не можев да го извршам отстранувањето бидејќи се појави грешка во линијата. Проверете си ја врската со интернет.',
	'wikibase-error-save-timeout' => 'Се соочуваме со технички потешкотии. Затоа, не можев да ги зачувам вашите промени.',
	'wikibase-error-remove-timeout' => 'Се соочуваме со технички потешкотии. Затоа, не можев да го извршам отстранувањето.',
	'wikibase-error-autocomplete-connection' => 'Не можев да го добијам API-то на Википедија. Обидете се подоцна.',
	'wikibase-error-autocomplete-response' => 'Одговор на опслужувачот: $1',
	'wikibase-error-ui-client-error' => 'Врската со клиентската страница е прекината. Обидете се подоцна.',
	'wikibase-error-ui-no-external-page' => 'Укажаната статија не е најдена на соодветното вики.',
	'wikibase-error-ui-cant-edit' => 'Не сте овластени да ја извршите оваа постапка.',
	'wikibase-error-ui-no-permissions' => 'Ги немате потребните права за да го извршите ова дејство.',
	'wikibase-error-ui-link-exists' => 'Не можете да ставите врска за оваа страница бидејќи веќе има друг предмет што води до неа.',
	'wikibase-error-ui-session-failure' => 'Сесијата истече. Најавете се повторно.',
	'wikibase-error-ui-edit-conflict' => 'Се јави спротиставеност во уредувањата. Превчитајте и зачувајте повторно.',
	'wikibase-replicationnote' => 'Имајте предвид дека се потребни неколку минути за промените да станат видливи на сите викија',
	'wikibase-sitelinks' => 'Список на страници поврзани со овој предмет',
	'wikibase-sitelinks-sitename-columnheading' => 'Јазик',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
	'wikibase-sitelinks-link-columnheading' => 'Сврзана статија',
	'wikibase-tooltip-error-details' => 'Подробно',
	'datatypes-type-wikibase-item' => 'Предмет',
	'datatypes-type-commonsMedia' => 'Медиумска податотека од Ризницата',
);

/** Malayalam (മലയാളം)
 * @author Praveenp
 */
$messages['ml'] = array(
	'wikibase-lib-desc' => 'വിക്കിബേസിനും വിക്കിബേസ് ക്ലയന്റ് അനുബന്ധങ്ങൾക്കുമുള്ള പൊതു പ്രവർത്തനരീതി',
	'wikibase-entity-item' => 'ഇനം',
	'wikibase-entity-property' => 'ഗുണഗണങ്ങൾ',
	'wikibase-entity-query' => 'ആവശ്യം',
	'wikibase-sitelinks' => 'ഈ ഇനത്തിലേയ്ക്ക് കണ്ണി ചേർത്തിട്ടുള്ള താളുകളുടെ പട്ടിക',
	'wikibase-sitelinks-sitename-columnheading' => 'ഭാഷ',
	'wikibase-sitelinks-siteid-columnheading' => 'കോഡ്',
	'wikibase-sitelinks-link-columnheading' => 'കണ്ണിചേർത്തിട്ടുള്ള ലേഖനം',
	'datatypes-type-wikibase-item' => 'ഇനം',
	'datatypes-type-commonsMedia' => 'കോമൺസിൽ നിന്നുള്ള മീഡിയ പ്രമാണം',
);

/** Malay (Bahasa Melayu)
 * @author Anakmalaysia
 */
$messages['ms'] = array(
	'wikibase-lib-desc' => 'Memegang kefungsian sepunya untuk sambungan Wikibase dan Wikibase Client',
	'wikibase-entity-item' => 'perkara',
	'wikibase-entity-property' => 'sifat',
	'wikibase-entity-query' => 'pertanyaan',
	'wikibase-error-unexpected' => 'Berlakunya ralat luar jangkaan.',
	'wikibase-error-save-generic' => 'Suatu ralat telah berlaku apabila cuba melakukan penyimpanan; oleh itu, pengubahan anda tidak dapat disiapkan.',
	'wikibase-error-remove-generic' => 'Suatu ralat telah berlaku apabila cuba melakukan pembuangan; oleh itu, pengubahan anda tidak dapat disiapkan.',
	'wikibase-error-save-connection' => 'Ralat penyambungan telah berlaku apabila cuba melakukan penyimpanan; oleh itu, pengubahan anda tidak dapat disiapkan. Sila semak sambungan Internet anda.',
	'wikibase-error-remove-connection' => 'Ralat penyambungan telah berlaku apabila cuba melakukan penyimpanan; oleh itu, pengubahan anda tidak dapat disiapkan. Sila semak sambungan Internet anda.',
	'wikibase-error-save-timeout' => 'Kami sedang mengalami kesulitan teknikal, oleh itu "simpanan" anda tidak dapat dilengkapkan.',
	'wikibase-error-remove-timeout' => 'Kami sedang mengalami kesulitan teknikal, oleh itu "pembuangan" anda tidak dapat dilengkapkan.',
	'wikibase-error-autocomplete-connection' => 'API Wikipedia tidak dapat ditanya. Sila cuba lagi kemudian.',
	'wikibase-error-autocomplete-response' => 'Pelayan membalas: $1',
	'wikibase-error-ui-client-error' => 'Sambungan dengan halaman pelanggan gagal. Sila cuba lagi kemudian.',
	'wikibase-error-ui-no-external-page' => 'Rencana yang dinyatakan tidak dapat dijumpai di halaman yang berpadanan.',
	'wikibase-error-ui-cant-edit' => 'Anda tidak dibenarkan melakukan tindakan ini.',
	'wikibase-error-ui-no-permissions' => 'Anda tidak cukup hak untuk melakukan tindakan ini.',
	'wikibase-error-ui-link-exists' => 'Anda tidak boleh membuat pautan ke halaman ini kerana satu lagi perkara sudah berpaut dengannya.',
	'wikibase-error-ui-session-failure' => 'Sesi anda sudah berakhir. Sila log masuk semula.',
	'wikibase-error-ui-edit-conflict' => 'Terdapat percanggahan suntingan. Sila muat semula dan simpan semula.',
	'wikibase-replicationnote' => 'Sila ambil perhatian bahawa masa beberapa minit mungkin perlu diambil sehingga semua perubahan kelihatan di semua wiki',
	'wikibase-sitelinks' => 'Senarai halaman yang berpaut pada perkara ini',
	'wikibase-sitelinks-sitename-columnheading' => 'Bahasa',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Rencana terpaut',
	'wikibase-tooltip-error-details' => 'Butiran',
	'datatypes-type-wikibase-item' => 'Perkara',
	'datatypes-type-commonsMedia' => 'Fail media Commons',
);

/** Norwegian Bokmål (norsk bokmål)
 * @author Danmichaelo
 * @author Event
 * @author Jeblad
 */
$messages['nb'] = array(
	'wikibase-lib-desc' => 'Felles funksjonalitet for Wikibase, det strukturerte datalageret',
	'wikibase-entity-item' => 'datasett',
	'wikibase-entity-property' => 'egenskap',
	'wikibase-entity-query' => 'spørring',
	'wikibase-error-unexpected' => 'En uventet feil oppsto.',
	'wikibase-error-save-generic' => 'En feil oppstod under forsøket på å lagre oppføringen, og på grunn av dette så kunne ikke endringen gjennomføres.',
	'wikibase-error-remove-generic' => 'En feil oppstod under forsøket på å fjerne oppføringen, og på grunn av dette så kunne ikke endringen gjennomføres.',
	'wikibase-error-save-connection' => 'En feil oppstod under forsøket på å lagre oppføringen, og på grunn av dette så kunne ikke endringen gjennomføres. Sjekk din tilknytting til internett.',
	'wikibase-error-remove-connection' => 'En feil oppstod under forsøket på å fjerne oppføringen, og på grunn av dette så kunne ikke endringen gjennomføres. Sjekk din tilknytting til internett.',
	'wikibase-error-save-timeout' => 'Vi har tekniske problemer, og på grunn av dette så kan vi ikke gjennomføre lagring av oppføringen.',
	'wikibase-error-remove-timeout' => 'Vi har tekniske problemer, og på grunn av dette så kan vi ikke gjennomføre fjerning av oppføringen.',
	'wikibase-error-autocomplete-connection' => 'Kunne ikke spørre mot Wikipedias API. Prøv igjen senere.',
	'wikibase-error-autocomplete-response' => 'Tjeneren svarte: $1',
	'wikibase-error-ui-client-error' => 'Kontakten med klientsiden feilet. Forsøk på nytt senere.',
	'wikibase-error-ui-no-external-page' => 'Den angitte artikkelen ble ikke funnet på det tilhørende nettstedet.',
	'wikibase-error-ui-cant-edit' => 'Du har ikke lov til å utføre denne handlingen.',
	'wikibase-error-ui-no-permissions' => 'Du har ikke tilstrekkelige rettigheter til å utføre denne handlingen.',
	'wikibase-error-ui-link-exists' => 'Du kan ikke lenke til denne siden fordi et annet datasett lenker allerede til den.',
	'wikibase-error-ui-session-failure' => 'Din arbeidsøkt er avsluttet, logg inn på nytt om du vil fortsette.',
	'wikibase-error-ui-edit-conflict' => 'Det er påvist en redigeringskonflikt. Kopier dine endringer, last siden på nytt, endre og lagre på nytt.',
	'wikibase-replicationnote' => 'Vær oppmerksom på at det kan ta flere minutter før endringene er synlig på alle wikier',
	'wikibase-sitelinks' => 'Sider som lenkes til dette datasettet',
	'wikibase-sitelinks-sitename-columnheading' => 'Språk',
	'wikibase-sitelinks-siteid-columnheading' => 'Kode',
	'wikibase-sitelinks-link-columnheading' => 'Lenket artikkel',
	'wikibase-tooltip-error-details' => 'Detaljer',
	'datatypes-type-wikibase-item' => 'Datasett',
	'datatypes-type-commonsMedia' => 'Commons mediafil',
);

/** Dutch (Nederlands)
 * @author Saruman
 * @author Siebrand
 */
$messages['nl'] = array(
	'wikibase-lib-desc' => 'Bevat gemeenschappelijke functies voor de uitbreidingen Wikibase en Wikibase Client',
	'wikibase-entity-item' => 'item',
	'wikibase-entity-property' => 'eigenschap',
	'wikibase-entity-query' => 'zoekopdracht',
	'wikibase-error-unexpected' => 'Er is een onverwachte fout opgetreden.',
	'wikibase-error-save-generic' => 'Er is een fout opgetreden tijdens het opslaan van uw wijzigingen. Uw wijzigingen konden niet worden opgeslagen.',
	'wikibase-error-remove-generic' => 'Er is een fout opgetreden tijdens het verwijderen. Uw wijzigingen konden niet worden opgeslagen.',
	'wikibase-error-save-connection' => 'Er is een fout in de verbinding opgetreden tijdens het opslaan. Uw wijzigingen konden niet worden opgeslagen. Controleer uw internetverbinding.',
	'wikibase-error-remove-connection' => 'Er is een fout in de verbinding opgetreden tijdens het verwijderen. Uw wijzigingen konden niet worden opgeslagen. Controleer uw internetverbinding.',
	'wikibase-error-save-timeout' => 'Wij ondervinden technische problemen. Uw wijzigingen kunnen niet worden opgeslagen.',
	'wikibase-error-remove-timeout' => 'ij ondervinden technische problemen. Uw wijzigingen kunnen niet worden opgeslagen.',
	'wikibase-error-autocomplete-connection' => 'Het was niet mogelijk de Wikipedia-API te bereiken. Probeer het later opnieuw.',
	'wikibase-error-autocomplete-response' => 'Antwoord van server: $1',
	'wikibase-error-ui-client-error' => 'De verbinding met de externe pagina kon niet gemaakt worden. Probeer het later nog eens.',
	'wikibase-error-ui-no-external-page' => 'De opgegeven pagina kon niet worden gevonden op de overeenkomende site.',
	'wikibase-error-ui-cant-edit' => 'U mag deze handeling niet uitvoeren.',
	'wikibase-error-ui-no-permissions' => 'U hebt geen rechten om deze handeling uit te voeren.',
	'wikibase-error-ui-link-exists' => 'U kunt geen koppeling naar deze pagina maken omdat een ander item er al aan gekoppeld is.',
	'wikibase-error-ui-session-failure' => 'Uw sessie is verlopen. Meld u opnieuw aan.',
	'wikibase-error-ui-edit-conflict' => 'Er is een bewerkingsconflict opgetreden. Laad de pagina opnieuw en sla uw wijzigingen opnieuw op.',
	'wikibase-replicationnote' => "Het kan een aantal minuten duren voor alle wijzigingen op alle wiki's zichtbaar zijn",
	'wikibase-sitelinks' => "Lijst met pagina's gekoppeld aan dit item",
	'wikibase-sitelinks-sitename-columnheading' => 'Taal',
	'wikibase-sitelinks-siteid-columnheading' => 'Code',
	'wikibase-sitelinks-link-columnheading' => 'Gekoppelde pagina',
	'wikibase-tooltip-error-details' => 'Details',
	'datatypes-type-wikibase-item' => 'Item',
	'datatypes-type-commonsMedia' => 'Mediabestand van Commons',
);

/** Norwegian Nynorsk (norsk nynorsk)
 * @author Jeblad
 * @author Njardarlogar
 */
$messages['nn'] = array(
	'wikibase-lib-desc' => 'Har felles funksjonalitet for Wikibase- og Wikibase Client-utvidingane',
	'wikibase-entity-item' => 'sett',
	'wikibase-entity-property' => 'eigenskap',
	'wikibase-entity-query' => 'spørjing',
	'wikibase-error-unexpected' => 'Det oppstod ein uventa feil.',
	'wikibase-error-save-generic' => 'Ein feil oppstod under forsøket på å lagre oppføringa, og på grunn av dette så kunne ikkje endringa gjennomførast.',
	'wikibase-error-remove-generic' => 'Ein feil oppstod under forsøket på å fjerne oppføringa, og på grunn av dette så kunne ikkje endringa gjennomførast.',
	'wikibase-error-save-connection' => 'Ein feil oppstod under forsøket på å lagre oppføringa, og på grunn av dette så kunne ikkje endringa gjennomførast. Sjekk di tilknyting til internett.',
	'wikibase-error-remove-connection' => 'Ein feil oppstod under forsøket på å fjerne oppføringa, og på grunn av dette så kunne ikkje endringa gjennomførast. Sjekk di tilknyting til internett.',
	'wikibase-error-save-timeout' => 'Vi har tekniske problem, og på grunn av dette så kan vi ikkje gjennomføre lagring av oppføringa.',
	'wikibase-error-remove-timeout' => 'Vi har tekniske problem, og på grunn av dette så kan vi ikkje gjennomføre fjerning av oppføringa.',
	'wikibase-error-autocomplete-connection' => 'Kunne ikkje spørje mot API-en til Wikipedia. Prøv om att seinare.',
	'wikibase-error-autocomplete-response' => 'Tenaren svarte: $1',
	'wikibase-error-ui-client-error' => 'Kontakten med klientsida feila. Freista på nytt seinare.',
	'wikibase-error-ui-no-external-page' => 'Den oppgjevne artikkelen vart ikkje funnen på den tilhøyrande nettstaden.',
	'wikibase-error-ui-cant-edit' => 'Du har ikkje lov til å utføre denne handlinga.',
	'wikibase-error-ui-no-permissions' => 'Du har ikkje tilstrekkelege rettar til å utføre denne handlinga.',
	'wikibase-error-ui-link-exists' => 'Du kan ikkje lenke til denne sida fordi eit anna datasett lenker allereie til den.',
	'wikibase-error-ui-session-failure' => 'Din arbeidsøkt er avslutta, logg inn på nytt om du vil fortsette.',
	'wikibase-error-ui-edit-conflict' => 'Det er ein endringskonflikt på gang. Lasta sida på nytt og lagra på nytt.',
	'wikibase-replicationnote' => 'Ver merksam på at det kan ta fleire minutt før endringane vert synlege på alle wikiane.',
	'wikibase-sitelinks' => 'Sidene som er knytte til dette datasettet',
	'wikibase-sitelinks-sitename-columnheading' => 'Språk',
	'wikibase-sitelinks-siteid-columnheading' => 'Kode',
	'wikibase-sitelinks-link-columnheading' => 'Artikkel som er lenkja til',
	'wikibase-tooltip-error-details' => 'Detaljar',
	'datatypes-type-wikibase-item' => 'Sett',
	'datatypes-type-commonsMedia' => 'Mediefil frå Commons',
);

/** Polish (polski)
 * @author BeginaFelicysym
 * @author Kpjas
 * @author Lazowik
 * @author Maćko
 */
$messages['pl'] = array(
	'wikibase-lib-desc' => 'Zawiera elementy wspólne dla rozszerzeń Wikibase i Wikibase Client',
	'wikibase-entity-item' => 'element',
	'wikibase-entity-property' => 'właściwość',
	'wikibase-entity-query' => 'zapytanie',
	'wikibase-sitelinks' => 'Lista stron powiązanych z tym elementem',
	'wikibase-sitelinks-sitename-columnheading' => 'Język',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Powiązany artykuł',
	'datatypes-type-wikibase-item' => 'Obiekt',
	'datatypes-type-commonsMedia' => 'Plik multimedialny na Commons',
);

/** Piedmontese (Piemontèis)
 * @author Borichèt
 * @author Dragonòt
 * @author පසිඳු කාවින්ද
 */
$messages['pms'] = array(
	'wikibase-lib-desc' => "A conten dle funsionalità comun-e a j'estension Wikibase e Wikibase Client",
	'wikibase-entity-item' => 'Element',
	'wikibase-entity-property' => 'propietà',
	'wikibase-entity-query' => 'anterogassion',
	'wikibase-error-save-generic' => "A l'é capitaje n'eror an provand a fé salva e për sòn, ij tò cambi a peulo pa esse completà.",
	'wikibase-error-remove-generic' => "A l'é capitaje n'eror an provand a fé gava e për sòn, ij tò cambi a peulo pa esse completà.",
	'wikibase-error-save-connection' => "A l'é capitaje n'eror ëd conession an provand a fé salva, e për sòn ij tò cambi a peulo pa esse completà. Për piasì contròla toa conession sla Ragnà.",
	'wikibase-error-remove-connection' => "A l'é capitaje n'eror ëd conession an provand a fé gava, e për sòn ij tò cambi a peulo pa esse completà. Për piasì contròla toa conession sla Ragnà.",
	'wikibase-error-save-timeout' => 'I stoma sperimentand dle dificoltà técniche, e për sòn tò "save" a peul pa esse completà.',
	'wikibase-error-remove-timeout' => 'I stoma sperimentand dle dificoltà técniche, e për sòn tò "remove" a peul pa esse completà.',
	'wikibase-error-autocomplete-connection' => 'As peulo pa ciamesse le API ëd Wikimedia. Për piasì preuva torna pi tard.',
	'wikibase-error-autocomplete-response' => "Sërvent a l'ha arspondù: $1",
	'wikibase-error-ui-client-error' => "La conession a la pagina dël client a l'ha falì. Për piasì preuva torna pi tard.",
	'wikibase-error-ui-no-external-page' => 'La vos specificà a peul pa esse trovà dzor ël sit corispondent.',
	'wikibase-error-ui-cant-edit' => "It peule pa fé st'assion-sì.",
	'wikibase-error-ui-no-permissions' => "It l'has pa basta drit për fé st'assion.",
	'wikibase-error-ui-link-exists' => "It peule pa coleghé sta pagina përchè n'àutr element a l'é già colegà.",
	'wikibase-error-ui-session-failure' => "Toa session a l'é finìa. Për piasì intra torna.",
	'wikibase-error-ui-edit-conflict' => 'A-i è un conflit ëd modìfiche. Për piasì torna carié e salva torna.',
	'wikibase-sitelinks' => "Lista ëd le pàgine gropà a st'element",
	'wikibase-sitelinks-sitename-columnheading' => 'Lenga',
	'wikibase-sitelinks-siteid-columnheading' => 'Còdes',
	'wikibase-sitelinks-link-columnheading' => 'Vos colegà',
	'wikibase-tooltip-error-details' => 'Detaj',
	'datatypes-type-wikibase-item' => 'Element',
	'datatypes-type-commonsMedia' => 'Archivi ëd mojen ëd Commons',
);

/** Pashto (پښتو)
 * @author Ahmed-Najib-Biabani-Ibrahimkhel
 */
$messages['ps'] = array(
	'wikibase-sitelinks-sitename-columnheading' => 'ژبه',
);

/** Portuguese (português)
 * @author Hamilton Abreu
 * @author Helder.wiki
 * @author Waldir
 */
$messages['pt'] = array(
	'wikibase-lib-desc' => 'Contém funcionalidades comuns para as extensões Wikibase e Wikibase Client',
	'wikibase-entity-item' => 'elemento',
	'wikibase-entity-property' => 'propriedade',
	'wikibase-entity-query' => 'consulta',
	'wikibase-sitelinks' => 'Lista de páginas com links para este elemento',
	'wikibase-sitelinks-sitename-columnheading' => 'Idioma',
	'wikibase-sitelinks-siteid-columnheading' => 'Código',
	'wikibase-sitelinks-link-columnheading' => 'Artigo associado',
	'datatypes-type-wikibase-item' => 'Item',
	'datatypes-type-commonsMedia' => 'Ficheiro de mídia do Commons',
);

/** Brazilian Portuguese (português do Brasil)
 * @author Jaideraf
 * @author 555
 */
$messages['pt-br'] = array(
	'wikibase-lib-desc' => 'Mantém a funcionalidade comum para as extensões Wikibase e Wikibase Client',
	'wikibase-entity-item' => 'item',
	'wikibase-entity-property' => 'propriedade',
	'wikibase-entity-query' => 'consulta',
	'wikibase-sitelinks' => 'Lista de páginas com links para este item',
	'wikibase-sitelinks-sitename-columnheading' => 'Idioma',
	'wikibase-sitelinks-siteid-columnheading' => 'Código',
	'wikibase-sitelinks-link-columnheading' => 'Artigo linkado',
	'datatypes-type-wikibase-item' => 'Item',
	'datatypes-type-commonsMedia' => 'Arquivo de mídia do Commons',
);

/** Romanian (română)
 * @author Stelistcristi
 */
$messages['ro'] = array(
	'wikibase-sitelinks-sitename-columnheading' => 'Limbă',
	'wikibase-sitelinks-siteid-columnheading' => 'Cod',
	'wikibase-sitelinks-link-columnheading' => 'Articol legat',
);

/** tarandíne (tarandíne)
 * @author Joetaras
 */
$messages['roa-tara'] = array(
	'wikibase-entity-item' => 'vôsce',
	'wikibase-entity-property' => 'probbietà',
	'wikibase-sitelinks-sitename-columnheading' => 'Lènghe',
	'wikibase-sitelinks-siteid-columnheading' => 'Codece',
	'datatypes-type-wikibase-item' => 'Vôsce',
);

/** Russian (русский)
 * @author Amire80
 * @author Kaganer
 * @author Ole Yves
 * @author ShinePhantom
 */
$messages['ru'] = array(
	'wikibase-lib-desc' => 'Общие функции расширений Wikibase и Wikibase Client',
	'wikibase-entity-item' => 'элемент',
	'wikibase-entity-property' => 'свойство',
	'wikibase-entity-query' => 'запрос',
	'wikibase-error-unexpected' => 'Произошла непредвиденная ошибка.',
	'wikibase-error-save-generic' => 'Произошла ошибка при попытке выполнить сохранение, поэтому ваши изменения не могут быть завершены.',
	'wikibase-error-remove-generic' => 'Произошла ошибка при попытке выполнить удаление, поэтому ваши изменения не могут быть завершены.',
	'wikibase-error-save-connection' => 'При попытке выполнить сохранение произошла ошибка подключения, поэтому ваши изменения не могут быть завершены. Пожалуйста, проверьте своё подключение к интернету.',
	'wikibase-error-remove-connection' => 'При попытке выполнить удаление произошла ошибка подключения, поэтому ваши изменения не могут быть завершены. Пожалуйста, проверьте своё подключение к интернету.',
	'wikibase-error-save-timeout' => 'Мы испытываем технические трудности, поэтому ваше изменение не может быть завершено.',
	'wikibase-error-remove-timeout' => 'Мы переживаем технические трудности, поэтому ваше удаление не может быть завершено.',
	'wikibase-error-autocomplete-connection' => 'Не удалось запросить API Википедии. Пожалуйста, повторите попытку позднее.',
	'wikibase-error-autocomplete-response' => 'Сервер ответил: $1',
	'wikibase-error-ui-client-error' => 'Сбой подключения к странице клиента. Пожалуйста, повторите попытку позднее.',
	'wikibase-error-ui-no-external-page' => 'Не удалось найти указанную статью на соответствующем сайте.',
	'wikibase-error-ui-cant-edit' => 'Вы не можете выполнить это действие.',
	'wikibase-error-ui-no-permissions' => 'У вас не хватает прав для выполнения этого действия.',
	'wikibase-error-ui-link-exists' => 'Вы не можете сослаться на эту страницу, так как другой элемент (объект) уже ссылается на неё.',
	'wikibase-error-ui-session-failure' => 'Ваша сессия истекла. Пожалуйста, войдите в систему снова.',
	'wikibase-error-ui-edit-conflict' => 'Существует конфликт редактирования. Перезагрузите и сохраните снова.',
	'wikibase-replicationnote' => 'Пожалуйста, обратите внимание, что может пройти несколько минут, пока изменения станут видны во всех вики-проектах',
	'wikibase-sitelinks' => 'Список страниц, ссылающихся на этот элемент',
	'wikibase-sitelinks-sitename-columnheading' => 'Язык',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
	'wikibase-sitelinks-link-columnheading' => 'Связанные статьи',
	'wikibase-tooltip-error-details' => 'Подробности',
	'datatypes-type-wikibase-item' => 'Элемент',
	'datatypes-type-commonsMedia' => 'Медиафайл на Викисклде',
);

/** Sinhala (සිංහල)
 * @author පසිඳු කාවින්ද
 */
$messages['si'] = array(
	'wikibase-entity-item' => 'අයිතමය',
	'wikibase-entity-property' => 'ගුණාංගය',
	'wikibase-entity-query' => 'ප්‍රශ්නය',
	'wikibase-sitelinks' => 'මෙම අයිතමය සඳහා සම්බන්ධිත පිටු ලැයිස්තුව',
	'wikibase-sitelinks-sitename-columnheading' => 'භාෂාව',
	'wikibase-sitelinks-siteid-columnheading' => 'කේතය',
	'wikibase-sitelinks-link-columnheading' => 'සබැඳිගත ලිපිය',
	'datatypes-type-wikibase-item' => 'අයිතමය',
	'datatypes-type-commonsMedia' => 'කොමන්ස් මාධ්‍ය ගොනුව',
);

/** Swedish (svenska)
 * @author Lokal Profil
 */
$messages['sv'] = array(
	'wikibase-sitelinks' => 'Sidor som är länkade till det här objektet',
	'wikibase-sitelinks-sitename-columnheading' => 'Språk',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Länkad artikel',
);

/** Tamil (தமிழ்)
 * @author மதனாஹரன்
 */
$messages['ta'] = array(
	'wikibase-entity-item' => 'உருப்படி',
	'wikibase-entity-query' => 'வினவல்',
	'wikibase-sitelinks-sitename-columnheading' => 'மொழி',
	'wikibase-sitelinks-siteid-columnheading' => 'குறியீடு',
	'wikibase-sitelinks-link-columnheading' => 'இணைத்த கட்டுரை',
	'datatypes-type-wikibase-item' => 'உருப்படி',
	'datatypes-type-commonsMedia' => 'பொதுவூடகக் கோப்பு',
);

/** Telugu (తెలుగు)
 * @author Veeven
 */
$messages['te'] = array(
	'wikibase-sitelinks-sitename-columnheading' => 'భాష',
);

/** Tagalog (Tagalog)
 * @author AnakngAraw
 */
$messages['tl'] = array(
	'wikibase-lib-desc' => 'Naghahawak ng karaniwang panunungkulan para sa mga pandugtong ng Wikibase at Wikibase Client',
	'wikibase-sitelinks' => 'Listahan ng mga pahinang nakakawing papunta sa bagay na ito',
	'wikibase-sitelinks-sitename-columnheading' => 'Wika',
	'wikibase-sitelinks-siteid-columnheading' => 'Kodigo',
	'wikibase-sitelinks-link-columnheading' => 'Artikulong nakakawing',
);

/** Uyghur (Arabic script) (ئۇيغۇرچە)
 * @author Sahran
 */
$messages['ug-arab'] = array(
	'wikibase-entity-query' => 'سۈرۈشتۈر',
	'wikibase-sitelinks-sitename-columnheading' => 'تىل',
	'wikibase-sitelinks-siteid-columnheading' => 'كود',
	'datatypes-type-wikibase-item' => 'تۈر',
);

/** Ukrainian (українська)
 * @author AS
 * @author Base
 * @author Steve.rusyn
 * @author Ата
 */
$messages['uk'] = array(
	'wikibase-lib-desc' => 'Загальні функції розширень Wikibase і Wikibase Client',
	'wikibase-entity-item' => 'елемент',
	'wikibase-entity-property' => 'властивість',
	'wikibase-entity-query' => 'запит',
	'wikibase-error-unexpected' => 'Сталася невідома помилка',
	'wikibase-error-save-generic' => 'Сталася помилка під час спроби виконати збереження, через це Ваші зміни не можуть бути здійснені.',
	'wikibase-error-remove-generic' => 'Сталась помилка під час спроби виконати вилучення, через це Ваші зміни не можуть бути здійснені.',
	'wikibase-error-save-connection' => "Під час спроби здійснити виконати збереження сталась помилка з'єднання, через це Ваші зміни не можуть бути здійснені. Будь ласка, перевірте Ваше з'єднання з Інтернетом.",
	'wikibase-error-autocomplete-response' => 'Сервер відповів: $1',
	'wikibase-sitelinks' => 'Список сторінок, що посилаються на цей елемент',
	'wikibase-sitelinks-sitename-columnheading' => 'Мова',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
	'wikibase-sitelinks-link-columnheading' => "Пов'язані статті",
	'wikibase-tooltip-error-details' => 'Деталі',
	'datatypes-type-wikibase-item' => 'Елемент',
	'datatypes-type-commonsMedia' => 'Медіафайл з Вікісховища',
);

/** Vietnamese (Tiếng Việt)
 * @author Minh Nguyen
 * @author පසිඳු කාවින්ද
 */
$messages['vi'] = array(
	'wikibase-lib-desc' => 'Các chức năng chung của các phần mở rộng Wikibase và Trình khách Wikibase',
	'wikibase-entity-item' => 'khoản mục',
	'wikibase-entity-property' => 'thuộc tính',
	'wikibase-entity-query' => 'truy vấn',
	'wikibase-error-unexpected' => 'Đã xuất hiện lỗi bất ngờ.',
	'wikibase-error-save-generic' => 'Đã gặp lỗi khi lưu nên không thể thực hiện các thay đổi của bạn.',
	'wikibase-error-remove-generic' => 'Đã gặp lỗi nên không thể thực hiện tác vụ loại bỏ.',
	'wikibase-error-save-connection' => 'Đã gặp lỗi kết nối khi lưu nên không thể thực hiện các thay đổi của bạn. Xin hãy kiểm tra kết nối Internet của bạn.',
	'wikibase-error-remove-connection' => 'Đã gặp lỗi nên không thể thực hiện tác vụ loại bỏ. Xin hãy kiểm tra kết nối Internet của bạn.',
	'wikibase-error-save-timeout' => 'Chúng tôi đang gặp trục trặc kỹ thuật nên không thể thực hiện tác vụ lưu của bạn.',
	'wikibase-error-remove-timeout' => 'Chúng tôi đang gặp trục trặc kỹ thuật nên không thể thực hiện tác vụ loại bỏ của bạn.',
	'wikibase-error-autocomplete-connection' => 'Không thể truy vấn API của Wikipedia. Xin hãy thử lại sau.',
	'wikibase-error-autocomplete-response' => 'Máy chủ đã phản hồi: $1',
	'wikibase-error-ui-client-error' => 'Kết nối đến trang khách bị thất bại. Xin hãy thử lại sau.',
	'wikibase-error-ui-no-external-page' => 'Không tìm thấy bài chỉ định trên site tương ứng.',
	'wikibase-error-ui-cant-edit' => 'Bạn không được phép thực hiện thao tác này.',
	'wikibase-error-ui-no-permissions' => 'Bạn không có đủ quyền để thực hiện thao tác này.',
	'wikibase-error-ui-link-exists' => 'Không thể đặt liên kết đến trang này vì một khoản mục khác đã liên kết với nó.',
	'wikibase-error-ui-session-failure' => 'Phiên của bạn đã hết hạn. Xin hãy đăng nhập lại.',
	'wikibase-error-ui-edit-conflict' => 'Một mâu thuẫn sửa đổi đã xảy ra. Xin hãy tải lại và lưu lần nữa.',
	'wikibase-replicationnote' => 'Xin lưu ý, có thể phải chờ vài phút để cho các wiki trình bày được các thay đổi',
	'wikibase-sitelinks' => 'Các trang được liên kết đến khoản mục này',
	'wikibase-sitelinks-sitename-columnheading' => 'Ngôn ngữ',
	'wikibase-sitelinks-siteid-columnheading' => 'Mã',
	'wikibase-sitelinks-link-columnheading' => 'Bài viết liên kết',
	'wikibase-tooltip-error-details' => 'Chi tiết',
	'datatypes-type-wikibase-item' => 'Khoản mục',
	'datatypes-type-commonsMedia' => 'Tập tin phương tiện Commons',
);

/** Simplified Chinese (中文（简体）‎)
 * @author Hydra
 * @author Shizhao
 * @author Stevenliuyi
 * @author Yfdyh000
 * @author 乌拉跨氪
 */
$messages['zh-hans'] = array(
	'wikibase-lib-desc' => '储存Wikibase及其客户端的共同功能',
	'wikibase-entity-item' => '项目',
	'wikibase-entity-property' => '属性',
	'wikibase-entity-query' => '查询',
	'wikibase-error-unexpected' => '发生意外错误。',
	'wikibase-error-save-generic' => '进行保存时发生错误，因此您所做的变更可能未被完成。',
	'wikibase-error-remove-generic' => '进行删除时发生错误，因此您所做的变更可能未被完成。',
	'wikibase-error-save-connection' => '进行保存时发生连接错误，因此您的变更可能未被完成。请检查您的因特网连接。',
	'wikibase-error-remove-connection' => '进行删除时发生连接错误，因此您的变更可能未被完成。请检查您的因特网连接。',
	'wikibase-error-save-timeout' => '我们遇到了技术问题，因此无法完成您的保存操作。',
	'wikibase-error-remove-timeout' => '我们遇到了技术问题，因此无法完成您的删除操作。',
	'wikibase-error-autocomplete-connection' => '无法查询维基百科API。请稍后重试。',
	'wikibase-error-autocomplete-response' => '服务器响应：$1',
	'wikibase-error-ui-client-error' => '无法连接到客户端页面。请稍后重试。',
	'wikibase-error-ui-no-external-page' => '在相应的站点上找不到指定的条目。',
	'wikibase-error-ui-cant-edit' => '您不能执行此操作。',
	'wikibase-error-ui-no-permissions' => '您没有足够的权限执行此操作。',
	'wikibase-error-ui-link-exists' => '因为另一项目已链接，您不能链接到此页面。',
	'wikibase-error-ui-session-failure' => '您的会话已过期。请重新登录。',
	'wikibase-error-ui-edit-conflict' => '发生编辑冲突。请刷新再重新保存。',
	'wikibase-replicationnote' => '更改可能需要几分钟的时间才能在所有的维基上看到，敬请留意。',
	'wikibase-sitelinks' => '链接到本项目的页面列表',
	'wikibase-sitelinks-sitename-columnheading' => '语言',
	'wikibase-sitelinks-siteid-columnheading' => '代码',
	'wikibase-sitelinks-link-columnheading' => '链接的条目',
	'wikibase-tooltip-error-details' => '详细信息',
	'datatypes-type-wikibase-item' => '项目',
	'datatypes-type-commonsMedia' => '共享资源媒体文件',
);

/** Traditional Chinese (中文（繁體）‎)
 * @author Justincheng12345
 */
$messages['zh-hant'] = array(
	'wikibase-lib-desc' => '儲存維基基礎及其客戶端的共同功能',
	'wikibase-entity-item' => '項目',
	'wikibase-entity-property' => '屬性',
	'wikibase-entity-query' => '查詢',
	'wikibase-sitelinks' => '鏈接到此項目的頁面清單',
	'wikibase-sitelinks-sitename-columnheading' => '語言',
	'wikibase-sitelinks-siteid-columnheading' => '代碼',
	'wikibase-sitelinks-link-columnheading' => '已連結的條目',
	'datatypes-type-wikibase-item' => '項目',
	'datatypes-type-commonsMedia' => '共享資源媒體檔案',
);
