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
 * @author Marius Hoch
 * @author Jeblad
 */
$messages['en'] = array(
	'wikibase-client-desc' => 'Client for the Wikibase extension',
	'specialpages-group-wikibaseclient' => 'Wikidata client',
	'wikibase-after-page-move' => 'You may also [$1 update] the associated Wikidata item to maintain language links on moved page.',
	'wikibase-comment-remove' => 'Associated Wikidata item deleted. Language links removed.',
	'wikibase-comment-linked' => 'A Wikidata item has been linked to this page.',
	'wikibase-comment-unlink' => 'This page has been unlinked from Wikidata item. Language links removed.',
	'wikibase-comment-restore' => 'Associated Wikidata item undeleted. Language links restored.',
	'wikibase-comment-update' => 'Wikidata item changed',
	'wikibase-comment-sitelink-add' => 'Language link added: $1',
	'wikibase-comment-sitelink-change' => 'Language link changed from $1 to $2',
	'wikibase-comment-sitelink-remove' => 'Language link removed: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|change|changes}}',
	'wikibase-editlinks' => 'Edit links',
	'wikibase-editlinkstitle' => 'Edit interlanguage links',
	'wikibase-linkitem-addlinks' => 'Add links',
	'wikibase-linkitem-alreadylinked' => 'The page you wanted to link with is already attached to an [$1 item] on the central data repository which links to $2 on this site. Items can only have one page per site attached. Please choose a different page to link with.',
	'wikibase-linkitem-close' => 'Close dialog and reload page',
	'wikibase-linkitem-failure' => 'An unknown error occurred while trying to link the given page.',
	'wikibase-linkitem-title' => 'Link with page',
	'wikibase-linkitem-linkpage' => 'Link with page',
	'wikibase-linkitem-selectlink' => 'Please select a site and a page you want to link this page with.',
	'wikibase-linkitem-input-site' => 'Language:',
	'wikibase-linkitem-input-page' => 'Page:',
	'wikibase-linkitem-invalidsite' => 'Unknown or invalid site selected',
	'wikibase-linkitem-confirmitem-text' => 'The page you chose is already linked to an [$1 item on our central data repository]. Please confirm that the pages shown below are the ones you want to link with this page.',
	'wikibase-linkitem-confirmitem-button' => 'Confirm',
	'wikibase-linkitem-not-loggedin-title' => 'You need to be logged in',
	'wikibase-linkitem-not-loggedin' => 'You need to be logged in on this wiki and in the [$1 central data repository] to use this feature.',
	'wikibase-linkitem-success-create' => 'The pages have successfully been linked. You can find the newly created item containing the links in our [$1 central data repository].',
	'wikibase-linkitem-success-link' => 'The pages have successfully been linked. You can find the item containing the links in our [$1 central data repository].',
	'wikibase-property-notfound' => '$1 property not found.',
	'wikibase-property-notsupportedyet' => 'Wikibase does not yet support property type for $1 property.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Show Wikidata edits in recent changes',
	'wikibase-watchlist-show-changes-pref' => 'Show Wikidata edits in your watchlist',
	'wikibase-error-invalid-entity-id' => 'The ID entered is unknown to the system. Please use a valid entity ID.',
	'special-unconnectedpages' => 'Pages not connected to items',
	'wikibase-unconnectedpages-legend' => 'Unconnected pages options',
	'wikibase-unconnectedpages-page' => 'Start result list with page:',
	'wikibase-unconnectedpages-submit' => 'Go',
	'wikibase-unconnectedpages-invalid-language' => '"$1" is not a valid language code.',
	'wikibase-unconnectedpages-page-warning' => 'The page title could not be used for the query and is ignored.',
	'wikibase-unconnectedpages-iwdata-label' => 'Only pages with interlanguagelinks',
	'wikibase-unconnectedpages-format-row' => '($1 interlanguagelinks on the page)',
	'wikibase-pageinfo-entity-id' => 'Wikidata Item ID',
	'wikibase-pageinfo-entity-id-none' => 'None',
);

/** Message documentation (Message documentation)
 * @author Jeblad
 * @author Katie Filbert
 * @author Lloffiwr
 * @author Marius Hoch
 * @author Metalhead64
 * @author Minh Nguyen
 * @author Raymond
 * @author Shirayuki
 * @author Vadgt
 */
$messages['qqq'] = array(
	'wikibase-client-desc' => '{{desc|name=Wikibase Client|url=http://www.mediawiki.org/wiki/Extension:Wikibase_Client}}
See also [[d:Wikidata:Glossary#Wikidata|Wikidata]].',
	'specialpages-group-wikibaseclient' => '{{doc-special-group|that=is related to Wikidata clients|like=[[Special:UnconnectedPages]]}}',
	'wikibase-after-page-move' => 'Message on [[Special:MovePage]] on submit and successfully move, inviting user to update associated Wikibase repository item to maintain language links on the moved page on the client.

Parameters:
* $1 - the link for the associated Wikibase item.',
	'wikibase-comment-remove' => 'Autocomment message for client (e.g. Wikipedia) recent changes when a Wikidata item connected to a page gets deleted. This results in all the language links being removed from the page on the client.',
	'wikibase-comment-linked' => 'Autocomment message in the client for when a Wikidata item is linked to a page in the client.',
	'wikibase-comment-unlink' => 'Autocomment message for client (e.g. Wikipedia) recent changes when a site link to a page gets removed. This results in the associated item being disconnected from the client page and all the language links being removed.',
	'wikibase-comment-restore' => 'Autocomment message for client (e.g. Wikipedia) recent changes when a Wikidata item gets undeleted and has a site link to this page. Language links get readded to the client page.',
	'wikibase-comment-update' => 'Autocomment message for client (e.g. Wikipedia) recent changes when a linked Wikidata item get changed. This can include label changes and statements.',
	'wikibase-comment-sitelink-add' => 'Autocomment message for client (e.g. Wikipedia) when a particular site link gets added on the repository. This change appears on the client as a new language link in the sidebar.

Parameters:
* $1 - the wikilink that was added, in form of [[:de:Berlin|de:Berlin]]',
	'wikibase-comment-sitelink-change' => 'Autocomment message for client (e.g. Wikipedia) when a particular site link gets changed on the repository.

Parameters:
* $1 - the wikilink for the old link
* $2 - the new wikilink
Format of wikilink is [[:de:Berlin|de:Berlin]].',
	'wikibase-comment-sitelink-remove' => 'Autocomment message for client (e.g. Wikipedia) when a particular site link gets removed on the repository.  $1 is the wikilink for the link removed, in format [[:de:Berlin|de:Berlin]].',
	'wikibase-comment-multi' => 'Summary shown in [[Special:RecentChanges]] and on [[Special:WatchList]] for an entry that represents multiple changes on the Wikibase repository.

Parameters:
* $1 - the number of changes; is always at least 2.',
	'wikibase-editlinks' => '[[Image:InterlanguageLinks-Sidebar-Monobook.png|right]]
	This is a link to the page on Wikidata where interlanguage links of the current page can be edited. See the image on the right for how it looks.
{{Identical|Edit link}}',
	'wikibase-editlinkstitle' => "This is the '''tooltip''' text on a link in the sidebar that opens a wizard to edit interlanguage links.

The link text is {{msg-mw|Wikibase-editlinks}}.",
	'wikibase-linkitem-addlinks' => 'Link in the sidebar asking to add language links and link the current page with pages on other sites. Only visible in case the current page has no langlinks.
{{Identical|Add link}}',
	'wikibase-linkitem-alreadylinked' => 'Tells that the page which the user wanted to link with the current one is already attached to an item on the central data repository. That item already links to an article on this site while items can only have one page per site attached.

Parameters:
* $1 - a link to the item
* $2 - the name of the page in the current wiki the item already links to',
	'wikibase-linkitem-close' => 'Button text asking to close the current dialog and to reload the page.',
	'wikibase-linkitem-failure' => 'Shown in case an error occurred which is not an API error (like a linking conflict)',
	'wikibase-linkitem-title' => 'Title for the dialog which allows linking the current page with a page on another site.',
	'wikibase-linkitem-linkpage' => 'Button in the dialog which allows linking the current page with a page on another site. Usable after the user inserted a site and a page to link.',
	'wikibase-linkitem-selectlink' => "Explaining the user that he can choose a site and a page that should be linked with the one he's currently on.",
	'wikibase-linkitem-input-site' => 'Label for the (autocompleted) inputbox asking for a site/ language.
{{Identical|Language}}',
	'wikibase-linkitem-input-page' => 'Label for the (autocompleted) inputbox asking for a page.
{{Identical|Page}}',
	'wikibase-linkitem-invalidsite' => 'Tooltip shown if the user entered an invalid site to link pages with',
	'wikibase-linkitem-confirmitem-text' => 'Text shown above a table containing links to other pages. Asks the user to confirm that the links are correct and should be linked with the current page.

Parameters:
* $1 - the URL to the item which links to the shown pages',
	'wikibase-linkitem-confirmitem-button' => 'Button label below a table containing links to other pages. Asks the user to confirm that he wants to link them with the current page.
{{Identical|Confirm}}',
	'wikibase-linkitem-not-loggedin-title' => 'Title of the dialog telling the user that he needs to login on both the repo and client to use this feature.',
	'wikibase-linkitem-not-loggedin' => 'This messages informs the user that he needs to be logged in on both this wiki and the repository to use this feature.

Parameters:
* $1 - the URI to the login form of the repository',
	'wikibase-linkitem-success-create' => 'Success message after a new item has been created which contains links to the page the user is currently on and the one entered. $1 holds a URL pointing to the new item.',
	'wikibase-linkitem-success-link' => 'Success message after the page the user currently is on has been linked with an item. $1 holds a URL pointing to the item.',
	'wikibase-property-notfound' => 'Message for property parser function when a property is not found. Parameters:
* $1 - the name of the property',
	'wikibase-property-notsupportedyet' => 'Used as error message. Parameters:
* $1 - property label',
	'wikibase-rc-hide-wikidata' => 'This refers to a toggle to hide or show edits (revisions) that come from Wikidata. If set to "hide", it hides edits made to the connected item in the Wikidata repository.

Parameters:
* $1 - a link with the text {{msg-mw|show}} or {{msg-mw|hide}}',
	'wikibase-rc-show-wikidata-pref' => 'Option in the recent changes section of preferences to show wikibase changes by default in recent changes.',
	'wikibase-watchlist-show-changes-pref' => 'Option in the watchlist section of preferences to always show wikibase edits by default in the watchlist.',
	'wikibase-error-invalid-entity-id' => 'Generic error message when an invalid entity ID was entered.',
	'special-unconnectedpages' => '{{doc-special|UnconnectedPages}}',
	'wikibase-unconnectedpages-legend' => 'Legend for the options box at the special page for pages not connected to sitelinks.',
	'wikibase-unconnectedpages-page' => 'Label for the input field to specify the title of the article at which the search result list should start. This belongs to the options box of the special page for pages not connected to sitelinks.',
	'wikibase-unconnectedpages-submit' => 'Text for the submit button in the options box of the special page for pages not connected to sitelinks.
{{Identical|Go}}',
	'wikibase-unconnectedpages-invalid-language' => 'Warning message about invalid language used at the special page for pages not connected to sitelinks.',
	'wikibase-unconnectedpages-page-warning' => 'The title that is typed in could not be used of some kind of reason. This can happen for example when the title has an interwiki prefix to another language, or a namespace that is not used for Wikibase entities.',
	'wikibase-unconnectedpages-iwdata-label' => 'Text for the checkbox. Should say that only pages that have interlanguage-links should be listed.',
	'wikibase-unconnectedpages-format-row' => 'Formatting of the additional data for each row in the result set on the special page for pages not connected to sitelinks.

This message follows the page title (with link).

Parameters:
* $1 - number of inter-language links',
	'wikibase-pageinfo-entity-id' => 'A link to the corresponding Wikibase Item',
	'wikibase-pageinfo-entity-id-none' => 'The page is not linked with a wikibase item.
{{Identical|None}}',
);

/** Afrikaans (Afrikaans)
 * @author Naudefj
 */
$messages['af'] = array(
	'wikibase-client-desc' => 'Kliënt vir die Wikibase-uitbreiding',
	'specialpages-group-wikibaseclient' => 'Wikidata-kliënt',
	'wikibase-after-page-move' => 'U kan ook die gekoppelde Wikidata-item [$1 wysig] om die taalskakels op die geskuifde bladsy by te werk.',
	'wikibase-comment-remove' => 'Die gekoppelde Wikidata-item is geskrap. Taalskakels is verwyder.',
	'wikibase-comment-linked' => "'n Wikidata-item is aan hierdie bladsy gekoppel.",
	'wikibase-comment-unlink' => 'Hierdie bladsy is ontkoppel van die Wikidata-item. Die taalskakels is verwyder.',
	'wikibase-comment-restore' => 'Die gekoppelde Wikidata-item is teruggeplaas. Taalskakels is herstel.',
	'wikibase-comment-update' => 'Wikidata-item is bygewerk',
	'wikibase-comment-sitelink-add' => 'Taalskakel bygevoeg: $1',
	'wikibase-comment-sitelink-change' => 'Taalskakel is van $1 na $2 verander',
	'wikibase-comment-sitelink-remove' => 'Taalskakel is verwyder: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|wysiging|wysigings}}',
	'wikibase-editlinks' => 'Wysig skakels',
	'wikibase-editlinkstitle' => 'Wysig skakels tussen tale',
	'wikibase-linkitem-addlinks' => 'Voeg skakels by',
	'wikibase-linkitem-alreadylinked' => "Die bladsy waarheen u wil skakel is reeds aan 'n [$1 item] in die sentrale data-resevoir gekoppel, wat skakel na $2 op hierdie webwerf. Items kan slegs na één bladsy per webwerf verwys. Kies 'n ander bladsy om na te skakel.",
	'wikibase-linkitem-close' => 'Sluit venster en herlaai die bladsy',
	'wikibase-linkitem-failure' => "'n Onbekende fout het tydens die skakeling na die gegewe bladsy voorgekom.",
	'wikibase-linkitem-title' => 'Skakel met bladsy',
	'wikibase-linkitem-linkpage' => 'Skakel met bladsy',
	'wikibase-linkitem-selectlink' => 'Kies die webwerf en bladsy na waarheen u die bladsy wil skakel.',
	'wikibase-linkitem-input-site' => 'Taal:',
	'wikibase-linkitem-input-page' => 'Bladsy:',
	'wikibase-linkitem-invalidsite' => 'Onbekende of ongeldige webwerf gekies',
	'wikibase-linkitem-confirmitem-text' => "Die bladsy wat u gekies het is reeds aan 'n [$1 item in ons sentrale data-resevoir] gekoppel. Bevestig dat die onderstaande bladsye inderdaad die bladsye is waaraan u hierdie bladsy wil skakel.",
	'wikibase-linkitem-confirmitem-button' => 'Bevestig',
	'wikibase-linkitem-not-loggedin-title' => 'U moet aangemeld wees',
	'wikibase-linkitem-not-loggedin' => 'U moet by hierdie wiki en die [$1 sentrale data-resevoir] aangemeld wees om hierdie funksie te kan gebruik.',
	'wikibase-linkitem-success-create' => 'Die bladsye is suksesvol geskakel. U kan die nuut geskepte item met die skakels in die [$1 sentrale data-resevoir] vind.',
	'wikibase-linkitem-success-link' => 'Die bladsye is suksesvol geskakel. U kan die item met die skakels in die [$1 sentrale data-resevoir] vind.',
	'wikibase-property-notfound' => 'Eienskap $1 nie gevind nie.',
	'wikibase-property-notsupportedyet' => 'Wikibase ondersteun nog nie die eienskapstipe vir die eienskap $1 nie.',
	'wikibase-rc-hide-wikidata' => '$1 wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Wys Wikidata-wysigings in onlangse wysigings',
	'wikibase-watchlist-show-changes-pref' => 'Wys Wikidata-wysigings in u dophoulys',
	'wikibase-error-invalid-entity-id' => "Die opgegewe ID is nie in die stelsel nie. Gebruik 'n geldig entiteit-ID.",
	'special-unconnectedpages' => 'Bladsye wat nie aan items gekoppel is nie',
	'wikibase-unconnectedpages-legend' => 'Voorkeure vir ongekoppelde bladsye',
	'wikibase-unconnectedpages-page' => 'Begin resultaatlys met bladsy:',
	'wikibase-unconnectedpages-submit' => 'OK',
	'wikibase-unconnectedpages-invalid-language' => '"$1" is nie \'n geldige taalkode nie.',
	'wikibase-unconnectedpages-page-warning' => 'Die bladsynaam kan nie vir die soekopdrag gebruik word nie en is geïgnoreer.',
	'wikibase-unconnectedpages-iwdata-label' => 'Slegs bladsye met taalskakels',
	'wikibase-unconnectedpages-format-row' => '($1 taalskakel{{PLURAL:$1||s}} op die bladsy)',
);

/** Arabic (العربية)
 * @author Abanima
 * @author Ali1
 * @author Peadara
 * @author Tarawneh
 */
$messages['ar'] = array(
	'wikibase-client-desc' => 'عميل امتداد ويكيبيس',
	'wikibase-after-page-move' => 'يمكنك أيضا [$1 تحديث] بند ويكي بيانات المرتبط بها للحفاظ على روابط اللغة ضمن الصفحة المنقولة.',
	'wikibase-comment-remove' => 'تم حذف بند ويكيبيانات المرتبطة. و تم إزالة ارتباطات اللغة.',
	'wikibase-comment-linked' => 'تم ربط عنصر ويكيبيانات مع هذه الصفحة.',
	'wikibase-comment-unlink' => 'تم فصل ارتباط هذه الصفحة من البند ويكيبيانات. تم إزالة روابط اللغة.',
	'wikibase-comment-restore' => 'تم استرجاع بند ويكيبيانات المرتبط. روابط اللغة أعيدت.',
	'wikibase-comment-update' => 'روابط اللغة حُدثت.',
	'wikibase-comment-sitelink-add' => 'وصلة اللغة المُضافة:$1',
	'wikibase-comment-sitelink-change' => 'تم تعديل وصلة اللغة من $1 إلى $2',
	'wikibase-comment-sitelink-remove' => 'وصلة اللغة المُلغاة:$1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|تعديل|تعديلات}}',
	'wikibase-editlinks' => 'تعديل الارتباطات',
	'wikibase-editlinkstitle' => 'تحرير ارتباطات اللغات البينية',
	'wikibase-linkitem-addlinks' => 'إضافة روابط',
	'wikibase-linkitem-alreadylinked' => 'هذه الصفحة التي تريد ربطها مرتبطة بالفعل إلى [ $1 عنصر] في المستودع المركزي للبيانات والذي يرتبط ب $2 على هذا الموقع. بإمكان العناصر إلحاق صفحة واحدة فقط لكل موقع. الرجاء اختيار صفحة مختلفة للربط.',
	'wikibase-linkitem-close' => 'أغلق مربع الحوار وأعد تحميل الصفحة',
	'wikibase-linkitem-failure' => 'حدث خطأ غير معروف أثناء محاولة الارتباط بالصفحة المعينة.',
	'wikibase-linkitem-title' => 'اربط مع الصفحة',
	'wikibase-linkitem-linkpage' => 'اربط مع الصفحة',
	'wikibase-linkitem-selectlink' => 'رجاء اختر موقعا و صفحة للربط مع هذه الصفحة.',
	'wikibase-linkitem-input-site' => 'اللغة:',
	'wikibase-linkitem-input-page' => 'صفحة:',
	'wikibase-linkitem-invalidsite' => 'تم اختيار موقع غير معروف أو غير صحيح',
	'wikibase-linkitem-confirmitem-text' => 'الصفحة التي قمت باختيارها مرتبط بالفعل إلى [ $1  عنصر في المستودع المركزي للبيانات لدينا]. الرجاء التأكد من أن الصفحات المبينة أدناه هي تلك التي تريد ربطها مع هذه الصفحة.',
	'wikibase-linkitem-confirmitem-button' => 'أكّد',
	'wikibase-linkitem-not-loggedin-title' => 'يتوجب عليك تسجيل الدخول',
	'wikibase-linkitem-not-loggedin' => 'لاستخدام هذه الميزة انت بحاجة إلى تسجيل الدخول على هذه الويكي وعلى [ $1   مستودع البيانات المركزي].',
	'wikibase-linkitem-success-create' => 'تم ربط الصفحات بنجاح. يمكنك العثور على العنصر الذي يحتوي على الارتباطات الذي تم إنشاؤها حديثا على [ $1  مستودعنا المركزي للبيانات].',
	'wikibase-linkitem-success-link' => 'تم ربط الصفحات بنجاح. يمكنك العثور على العنصر الذي يحتوي على الارتباطات على [ $1  مستودعنا المركزي للبيانات].',
	'wikibase-rc-hide-wikidata' => '$1 ويكيبيانات',
	'wikibase-rc-show-wikidata-pref' => 'إظهار عمليات تحرير ويكيبيانات في صفحة أحدث التغييرات',
);

/** Aramaic (ܐܪܡܝܐ)
 * @author Basharh
 */
$messages['arc'] = array(
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|ܫܘܚܠܦܐ|ܫܘܚܠܦ̈ܐ}}',
	'wikibase-editlinks' => 'ܫܚܠܦ ܐܣܘܪ̈ܐ',
	'wikibase-linkitem-addlinks' => 'ܐܘܣܦ ܐܣܘܪ̈ܐ',
	'wikibase-linkitem-input-site' => 'ܠܫܢܐ:',
	'wikibase-linkitem-input-page' => 'ܦܐܬܐ:',
	'wikibase-linkitem-confirmitem-button' => 'ܫܪܪ',
	'wikibase-rc-hide-wikidata' => '$1 ܘܝܩܝܓܠܝܬ̈ܐ',
	'wikibase-unconnectedpages-submit' => 'ܙܠ',
);

/** Asturian (asturianu)
 * @author Xuacu
 */
$messages['ast'] = array(
	'wikibase-client-desc' => 'Cliente pa la estensión Wikibase',
	'specialpages-group-wikibaseclient' => 'Cliente de Wikidata',
	'wikibase-after-page-move' => "Tamién pue [$1 anovar] l'elementu asociáu de Wikidata pa caltener los enllaces d'idioma na páxina treslladada.",
	'wikibase-comment-remove' => "Desanicióse l'elementu asociáu de Wikidata. Quitaronse los enllaces de llingua.",
	'wikibase-comment-linked' => 'Enllazóse un elementu de Wikidata a esta páxina.',
	'wikibase-comment-unlink' => "Desenllazóse esta páxina d'un elementu de Wikidata. Quitaronse los enllaces de llingua.",
	'wikibase-comment-restore' => "Recuperóse l'elementu asociáu de Wikidata. Restauraronse los enllaces de llingua.",
	'wikibase-comment-update' => "Camudó l'elementu de Wikidata",
	'wikibase-comment-sitelink-add' => 'Amestóse un enllaz de llingua: $1',
	'wikibase-comment-sitelink-change' => 'Camudó un enllaz de llingua de $1 a $2',
	'wikibase-comment-sitelink-remove' => 'Desanicióse un enllaz de llingua: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|cambiu|cambios}}',
	'wikibase-editlinks' => 'Editar los enllaces',
	'wikibase-editlinkstitle' => "Editar los enllaces d'interllingua",
	'wikibase-linkitem-addlinks' => 'Amestar enllaces',
	'wikibase-linkitem-alreadylinked' => "La páxina que quería enllazar con esta, yá ta enllazada con un [$1 elementu] del depósitu central de datos qu'enllaza con $2 d'esti sitiu. Los elementos sólo pueden tener enllazada una páxina por sitiu. Por favor, escueya una páxina diferente pa enllazar.",
	'wikibase-linkitem-close' => 'Zarrar el diálogu y recargar la páxina',
	'wikibase-linkitem-failure' => 'Hebo un error desconocíu al intentar enllazar la páxina dada.',
	'wikibase-linkitem-title' => 'Enllazar con páxina',
	'wikibase-linkitem-linkpage' => 'Enllazar con páxina',
	'wikibase-linkitem-selectlink' => 'Por favor, seleicione un sitiu y una páxina cola que quiera enllazar esta páxina.',
	'wikibase-linkitem-input-site' => 'Llingua:',
	'wikibase-linkitem-input-page' => 'Páxina:',
	'wikibase-linkitem-invalidsite' => 'Seleicionóse un sitiu desconocíu o inválidu',
	'wikibase-linkitem-confirmitem-text' => "La paxina qu'escoyó yá ta enllazada con un [$1 elementu del depósitu central de datos]. Confirme que les páxines que s'amuesen más abaxo son les que quier enllazar con esta páxina.",
	'wikibase-linkitem-confirmitem-button' => 'Confirmar',
	'wikibase-linkitem-not-loggedin-title' => "Tien d'aniciar sesión",
	'wikibase-linkitem-not-loggedin' => "Tien d'aniciar sesión nesta wiki y nel [$1 depósitu central de datos] pa usar esta carauterística.",
	'wikibase-linkitem-success-create' => "Les páxines enllazaronse correutamente. Pue alcontrar l'elementu recién creáu que contién los enllaces nel [$1 depósitu central de datos].",
	'wikibase-linkitem-success-link' => "Les páxines enllazaronse correutamente. Pue alcontrar l'elementu que contién los enllaces nel [$1 depósitu central de datos].",
	'wikibase-property-notfound' => "Nun s'alcontró la propiedá $1.",
	'wikibase-property-notsupportedyet' => 'Wikibase inda nun sofita tipu de propiedá pa la propiedá $1.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Amosar les ediciones de Wikidata nos cambios de recién',
	'wikibase-watchlist-show-changes-pref' => 'Amosar les ediciones de Wikidata na so llista de vixilancia',
	'wikibase-error-invalid-entity-id' => "La ID qu'escribió ye desconocida pal sistema. Por favor, use una ID d'entidá válida.",
	'special-unconnectedpages' => 'Páxines nun coneutaes con elementos',
	'wikibase-unconnectedpages-legend' => 'Opciones pa les páxines nun coneutaes',
	'wikibase-unconnectedpages-page' => 'Principiar la llista de resultaos pola páxina:',
	'wikibase-unconnectedpages-submit' => 'Dir',
	'wikibase-unconnectedpages-invalid-language' => "«$1» nun ye un códigu d'idioma válidu.",
	'wikibase-unconnectedpages-page-warning' => 'El títulu de la páxina nun pudo usase pa la consulta ya inoróse.',
	'wikibase-unconnectedpages-iwdata-label' => 'Namái páxines con enllaces interllingüísticos',
	'wikibase-unconnectedpages-format-row' => '($1 enllaces interllingüísticos na páxina)',
	'wikibase-pageinfo-entity-id' => "ID d'elementu de Wikidata",
	'wikibase-pageinfo-entity-id-none' => 'Dengún',
);

/** Bashkir (башҡортса)
 * @author Comp1089
 */
$messages['ba'] = array(
	'wikibase-editlinks' => 'Һылтанмаларҙы төҙәт',
);

/** Belarusian (беларуская)
 * @author Чаховіч Уладзіслаў
 */
$messages['be'] = array(
	'wikibase-client-desc' => 'Кліент для пашырэння Wikibase',
	'wikibase-after-page-move' => "Каб захаваць міжмоўныя спасылкі на перанесеную старонку, вы можаце [$1 абнавіць] злучаны аб'ект у Вікідадзеных.",
	'wikibase-comment-remove' => "Злучаны аб'ект выдалены з Вікізвестак. Моўныя спасылкі былі выдалены.",
	'wikibase-comment-linked' => "Аб'ект Вікізвестак быў злучаны з гэтай старонкай.",
	'wikibase-comment-unlink' => "Гэта старонка была аддзелена ад аб'екта Вікізвестак. Моўныя спасылкі выдалены.",
	'wikibase-comment-restore' => "Выдаленне злучанага аб'екта Вікізвестак скасавана. Моўныя спасылкі адноўлены.",
	'wikibase-comment-update' => 'Моўныя спасылкі абноўлены.',
	'wikibase-comment-sitelink-add' => 'Дададзена моўная спасылка: $1',
	'wikibase-comment-sitelink-change' => 'Моўная спасылка зменена з $1 на $2',
	'wikibase-comment-sitelink-remove' => 'Моўная спасылка выдалена: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|змена|змены|зменаў}}',
	'wikibase-editlinks' => 'Правіць спасылкі',
	'wikibase-editlinkstitle' => 'Правіць міжмоўныя спасылкі',
	'wikibase-linkitem-addlinks' => 'Дадаць спасылкі',
	'wikibase-linkitem-alreadylinked' => "Старонку, якую вы хочаце злучыць, ужо далучана да [$1 аб'екта] ў цэнтральным рэпазыторыі, які спасылаецца на $2 на гэтым сайце. Аб'екты могуць мець толькі па адной старонцы з аднаго сайта. Выберыце, калі ласка, іншую старонку.",
	'wikibase-linkitem-close' => 'Закрыць дыялогі і абнавіць старонку',
	'wikibase-linkitem-failure' => 'Пры далучэнні старонкі ўзнікла невядомая памылка.',
	'wikibase-linkitem-title' => 'Злучэнне са старонкай',
	'wikibase-linkitem-linkpage' => 'Злучыць са старонкай',
	'wikibase-linkitem-selectlink' => 'Выберыце сайт і старонку, з якімі вы хочаце злучыць гэту старонку.',
	'wikibase-linkitem-input-site' => 'Мова:',
	'wikibase-linkitem-input-page' => 'Старонка:',
	'wikibase-linkitem-invalidsite' => 'Выбраны невядомы ці няслушны сайт',
	'wikibase-linkitem-confirmitem-text' => "Старонка, якую вы выбралі, ужо далучана да [$1 аб'екта ў цэнтральным рэпазыторыі]. Пацвердзіце, калі ласка, што ўказаныя ніжэй старонкі з'яўляецца тымі, з якімі вы хочаце злучыць гэту старонку.",
	'wikibase-linkitem-confirmitem-button' => 'Пацвердзіць',
	'wikibase-linkitem-not-loggedin-title' => 'Вы павінны ўвайсці ў сітэму',
	'wikibase-linkitem-not-loggedin' => 'Для карыстання гэтай функцыяй вы павінны ўвайсці ў гэту вікі і [$1 цэнтральны рэпазыторый].',
	'wikibase-linkitem-success-create' => "Старонкі былі паспяхова злучаны. Новы аб'ект са спасылкамі вы можаце знайсці ў нашым [$1 цэнтральным рэпазыторыі].",
	'wikibase-linkitem-success-link' => "Старонкі былі паспяхова злучаны. Новы аб'ект са спасылкамі вы можаце пабачыць у нашым [$1 цэнтральным рэпазыторыі].",
	'wikibase-rc-hide-wikidata' => '$1 Вікідадзеныя',
	'wikibase-rc-show-wikidata-pref' => 'Паказваць праўкі Вікізвестак у спісе апошніх змен',
);

/** Belarusian (Taraškievica orthography) (беларуская (тарашкевіца)‎)
 * @author Wizardist
 */
$messages['be-tarask'] = array(
	'wikibase-client-desc' => 'Кліент для пашырэньня Wikibase',
	'specialpages-group-wikibaseclient' => 'Кліент Вікізьвестак',
	'wikibase-after-page-move' => 'Каб захаваць міжмоўныя спасылкі на перанесеную старонку, вы можаце [$1 абнавіць] злучаны аб’ект у Вікізьвестках.',
	'wikibase-comment-remove' => 'Злучаны аб’ект выдалены зь Вікізьвестак. Моўныя спасылкі былі выдаленыя.',
	'wikibase-comment-linked' => 'Аб’ект Вікізьвестак быў злучаны з гэтай старонкай.',
	'wikibase-comment-unlink' => 'Гэтая старонка была адлучаная ад аб’екта Вікізьвестак. Моўныя спасылкі выдаленыя.',
	'wikibase-comment-restore' => 'Выдаленьне злучанага аб’екта Вікізьвестак скасавана. Моўныя спасылкі адноўленыя.',
	'wikibase-comment-update' => 'Зьменены аб’ект Вікізьвестак',
	'wikibase-comment-sitelink-add' => 'Дададзеная моўная спасылка: $1',
	'wikibase-comment-sitelink-change' => 'Моўная спасылка зьмененая з $1 на $2',
	'wikibase-comment-sitelink-remove' => 'Моўная спасылка выдаленая: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|зьмена|зьмены|зьменаў}}',
	'wikibase-editlinks' => 'Правіць спасылкі',
	'wikibase-editlinkstitle' => 'Рэдагаваць міжмоўныя спасылкі',
	'wikibase-linkitem-addlinks' => 'Дадаць спасылкі',
	'wikibase-linkitem-alreadylinked' => 'Старонку, якую вы хочаце злучыць, ужо далучаная да [$1 аб’екта] ў цэнтральным рэпазыторыі, які спасылаецца на $2 на гэтым сайце. Аб’екты могуць мець толькі па адной старонцы з аднаго сайту. Выберыце, калі ласка, інушю старонку.',
	'wikibase-linkitem-close' => 'Зачыніць дыялёгі і абнавіць старонку',
	'wikibase-linkitem-failure' => 'Пры далучэньні старонкі ўзьнікла невядомая памылка.',
	'wikibase-linkitem-title' => 'Злучэньне са старонкай',
	'wikibase-linkitem-linkpage' => 'Злучыць са старонкай',
	'wikibase-linkitem-selectlink' => 'Выберыце сайт і старонку, зь якімі вы хочаце злучыць гэтую старонку.',
	'wikibase-linkitem-input-site' => 'Мова:',
	'wikibase-linkitem-input-page' => 'Старонка:',
	'wikibase-linkitem-invalidsite' => 'Выбраны невядомы ці няслушны сайт',
	'wikibase-linkitem-confirmitem-text' => 'Старонка, якую вы выбралі, ужо далучаная да [$1 аб’екта ў цэнтральным рэпазыторыі]. Пацьвердзіце, калі ласка, што паказаныя ніжэй старонкі ёсьць тымі, зь якімі вы хочаце злучыць гэтую старонку.',
	'wikibase-linkitem-confirmitem-button' => 'Пацьвердзіць',
	'wikibase-linkitem-not-loggedin-title' => 'Вы мусіце ўвайсьці ў сыстэму',
	'wikibase-linkitem-not-loggedin' => 'Для карыстаньня гэтай функцыяй вы мусіце ўвайсьці ў гэтую вікі і [$1 цэнтральны рэпазыторый].',
	'wikibase-linkitem-success-create' => 'Старонкі былі пасьпяхова злучаныя. Новы аб’ект са спасылкамі вы можаце знайсьці ў нашым [$1 цэнтральным рэпазыторыі].',
	'wikibase-linkitem-success-link' => 'Старонкі былі пасьпяхова злучаныя. Новы аб’ект са спасылкамі вы можаце пабачыць у нашым [$1 цэнтральным рэпазыторыі].',
	'wikibase-property-notfound' => 'Уласьцівасьць «$1» ня знойдзеная.',
	'wikibase-property-notsupportedyet' => 'Вікізьвесткі пакуль не падтрымліваюць тып уласьцівасьці «$1».',
	'wikibase-rc-hide-wikidata' => '$1 Вікізьвесткі',
	'wikibase-rc-show-wikidata-pref' => 'Паказваць праўкі Вікізьвестак у сьпісе апошніх зьменаў',
	'wikibase-watchlist-show-changes-pref' => 'Паказваць зьмены Вікізьвестак у вашым сьпісе назіраньня',
	'wikibase-error-invalid-entity-id' => 'Уведзены ідэнтыфікатар не вядомы сыстэме. Калі ласка, выкарыстоўвайце існыя ідэнтыфікатары аб’ектаў.',
	'special-unconnectedpages' => 'Старонкі, ня злучаныя з аб’ектамі',
	'wikibase-unconnectedpages-legend' => 'Налады нязлучаных старонак',
	'wikibase-unconnectedpages-page' => 'Паказваць вынікі ад гэтага артыкула:', # Fuzzy
	'wikibase-unconnectedpages-submit' => 'Паказаць',
	'wikibase-unconnectedpages-invalid-language' => '«$1» — няслушны код мовы.',
	'wikibase-unconnectedpages-page-warning' => 'Назва старонкі ня можа быць скарыстаная ў запыце, таму ігнаруецца.',
	'wikibase-unconnectedpages-iwdata-label' => 'Толькі старонкі зь міжмоўнымі спасылкамі',
	'wikibase-unconnectedpages-format-row' => '(на старонцы $1 {{PLURAL:$1|міжмоўная спасылка|міжмоўныя спасылкі|міжмоўных спасылак}})',
);

/** Bulgarian (български)
 * @author Spiritia
 */
$messages['bg'] = array(
	'wikibase-after-page-move' => 'Можете да [$1 актуализирате] свързания обект от Уикиданните с цел поддръжка на междуезиковите препратки към преместената страница.',
	'wikibase-comment-remove' => 'Асоциираният обект от Уикиданните е изтрит. Междуезиковите препратки са премахнати.',
	'wikibase-comment-sitelink-add' => 'Добавена междуезикова препратка: $1',
	'wikibase-comment-sitelink-change' => 'Променена междуезикова препратка: от $1 на $2',
	'wikibase-comment-sitelink-remove' => 'Премахната междуезикова препратка: $1',
	'wikibase-editlinks' => 'Редактиране',
	'wikibase-editlinkstitle' => 'Редактиране на междуезиковите препратки',
	'wikibase-linkitem-failure' => 'При опита за свързване с дадената страница възникна неизвестна грешка.',
	'wikibase-linkitem-selectlink' => 'Изберете сайта и страницата от него, с която искате да свържете тази.',
	'wikibase-linkitem-input-site' => 'Език:',
	'wikibase-linkitem-input-page' => 'Страница:',
	'wikibase-linkitem-invalidsite' => 'Избран е неизвестен или невалиден сайт',
	'wikibase-linkitem-confirmitem-text' => 'Избраната страница е вече свързана с [$1 обект от нашето централно хранилище с данни]. Потвърдете, ако страниците, показани по-долу, са онези, които искате да свържете с тази страница.',
	'wikibase-linkitem-not-loggedin-title' => 'Трябва да сте влезли в системата',
);

/** Bengali (বাংলা)
 * @author Aftab1995
 * @author Bellayet
 * @author Leemon2010
 * @author Sankarshan
 */
$messages['bn'] = array(
	'wikibase-client-desc' => 'উইকিবেজ এক্সটেনশনের জন্য গ্রাহক',
	'specialpages-group-wikibaseclient' => 'উইকিউপাত্ত গ্রাহক',
	'wikibase-after-page-move' => 'এছাড়া আপনি স্থানান্তর করা পাতাতে ভাষার লিংক বজায় রাখার জন্য সংশ্লিষ্ট উইকিউপাত্তের আইটেমটি [$1 হালনাগাদ] করতে পারেন।',
	'wikibase-comment-remove' => 'সংশ্লিষ্ট উইকিউপাত্ত আইটেম অপসারিত হয়েছে। ভাষার সংযোগ অপসারিত হয়েছে।',
	'wikibase-comment-linked' => 'একটি উইকিউপাত্ত আইটেম এই পাতার সাথে সংযুক্ত হয়েছে।',
	'wikibase-comment-unlink' => 'এই পাতাটি উইকিউপাত্তের আইটেম থেকে সংযোগমুক্ত করা হয়েছে। ভাষার সংযোগ অপসারিত হয়েছে।',
	'wikibase-comment-update' => 'উইকিউপাত্তের আইটেম পরিবর্তন হয়েছে।',
	'wikibase-comment-sitelink-add' => 'ভাষা সংযোগ যোগ হয়েছে: $1',
	'wikibase-comment-sitelink-change' => 'ভাষা সংযোগ $1 থেকে $2 এ পরিবর্তন হয়েছে',
	'wikibase-comment-sitelink-remove' => 'ভাষা সংযোগ অপসারিত হয়েছে: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|পরিবর্তন|পরিবর্তনসমূহ}}',
	'wikibase-editlinks' => 'আন্তঃউইকি সংযোগ সম্পাদনা',
	'wikibase-editlinkstitle' => 'আন্তঃভাষার লিংকগুলি সম্পাদনা করুন',
	'wikibase-linkitem-addlinks' => 'আন্তঃউইকি সংযোগ দিন',
	'wikibase-linkitem-alreadylinked' => 'আপনি পাতাটি কেন্দ্রীয় তথ্য ভান্ডারের [$1 আইটেমের] সাথে সংযুক্ত করতে চাইছেন তা ইতোমধ্যে এই সাইটের $2 এর সাথে সংযুক্ত রয়েছে। একটি আইটেম কোন সাইটে কেবল একটি মাত্র পাতার সাথে সংযুক্ত হতে পারে। অনুগ্রহ করে সংযোগ করতে অন্য একটি পাতা পছন্দ করুন।',
	'wikibase-linkitem-close' => 'ডায়ালগ বন্ধ এবং পাতা পুনরায় লোড করো',
	'wikibase-linkitem-failure' => 'আপনার দেওয়া পাতায় সংযোগ করতে একটি অজানা ত্রুটি দেখা দিয়েছে।',
	'wikibase-linkitem-title' => 'পাতার সাথে সংযোগ',
	'wikibase-linkitem-linkpage' => 'পাতার সাথে সংযোগ',
	'wikibase-linkitem-selectlink' => 'একটি সাইট এবং পাতা নির্বাচন করুন যেটির সাথে আপনি এই পাতার সংযোগ দিতে ইচ্ছুক।',
	'wikibase-linkitem-input-site' => 'ভাষা:',
	'wikibase-linkitem-input-page' => 'পাতা:',
	'wikibase-linkitem-invalidsite' => 'অজানা বা অবৈধ সাইট নির্বাচন করা হয়েছে',
	'wikibase-linkitem-confirmitem-text' => 'আপনি যে পাতাটি পছন্দ করেছেন তা ইতোমধ্যে [আমাদের কেন্দ্রীয় তথ্যভান্ডারে $1 আইটেমের] সাথে সংযুক্ত আছে। অনুগ্রহ করে নিশ্চিত করুন যে নিচে প্রদর্শিত পাতাগুলোর একটির সাথে আপনি এই পাতার সংযোগ করতে চান।',
	'wikibase-linkitem-confirmitem-button' => 'নিশ্চিত করুন',
	'wikibase-linkitem-not-loggedin-title' => 'আপনার প্রবেশ করা প্রয়োজন',
	'wikibase-linkitem-not-loggedin' => 'এই বৈশিষ্ট্য ব্যবহার করতে আপনাকে এই উইকি এবং  [$1 কেন্দ্রীয় তথ্য ভান্ডারে] প্রবেশ (লগ ইন) করতে হবে।',
	'wikibase-linkitem-success-create' => 'এই পাতাটি সফলভাবে সংযুক্ত হয়েছে। আমাদের [$1 কেন্দ্রীয় তথ্য ভান্ডারে] আপনি নতুন তৈরি এই আইটেমের লিঙ্ক খুঁজে পাবেন।',
	'wikibase-linkitem-success-link' => 'এই পাতাটি সফলভাবে সংযুক্ত হয়েছে। আমাদের [$1 কেন্দ্রীয় তথ্য ভান্ডারে] আপনি নতুন তৈরি এই আইটেমের লিঙ্ক খুঁজে পাবেন।',
	'wikibase-property-notfound' => '$1 বৈশিষ্ট্য পাওয়া যায়নি।',
	'wikibase-rc-hide-wikidata' => 'উইকিউপাত্ত $1',
	'wikibase-rc-show-wikidata-pref' => 'উইকিউপাত্তের সম্পাদনাগুলো সাম্প্রতিক পরিবর্তনে দেখানো হোক',
	'wikibase-watchlist-show-changes-pref' => 'উইকিউপাত্তের সম্পাদনাগুলো নজরতালিকায় দেখাও',
	'wikibase-error-invalid-entity-id' => 'প্রবেশ করানো আইডিটি সিস্টেমের অজানা। দয়া করে একটি বৈধ ভুক্তি আইডি ব্যবহার করুন।',
	'special-unconnectedpages' => 'পাতাটি আইটেমের সাথে সংযুক্ত হয়নি',
	'wikibase-unconnectedpages-legend' => 'অসংযুক্ত পাতার বিকল্প',
	'wikibase-unconnectedpages-page' => 'যে পাতা দিয়ে ফলাফলের তালিকা শুরু হবে:',
	'wikibase-unconnectedpages-submit' => 'যাও',
	'wikibase-unconnectedpages-invalid-language' => '"$1" কোনো সঠিক ভাষার কোড নয়।',
	'wikibase-pageinfo-entity-id' => 'উইকিউপাত্ত আইটেম আইডি',
	'wikibase-pageinfo-entity-id-none' => 'কোনটিই নয়',
);

/** Breton (brezhoneg)
 * @author Fohanno
 * @author Fulup
 * @author VIGNERON
 */
$messages['br'] = array(
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|kemm|kemm}}', # Fuzzy
	'wikibase-editlinks' => 'Kemmañ al liammoù',
	'wikibase-editlinkstitle' => 'Kemmañ al liammoù etreyezhel',
	'wikibase-linkitem-addlinks' => 'Ouzhpennañ liammoù',
	'wikibase-linkitem-input-site' => 'Yezh :',
	'wikibase-linkitem-input-page' => 'Pajenn :',
	'wikibase-linkitem-confirmitem-button' => 'Kadarnaat',
	'special-unconnectedpages' => 'Pajennoù n’int ket liammet gant elfennoù',
);

/** Bosnian (bosanski)
 * @author DzWiki
 * @author Edinwiki
 */
$messages['bs'] = array(
	'wikibase-client-desc' => 'Klijent za proširenje Wikibaza',
	'wikibase-after-page-move' => 'Možete također [$1 ažurirati] asociranu Wikidata stavku za održavanje jezičnih veza na premještenoj stranici.',
	'wikibase-comment-remove' => 'Asocirana Wikidata stavka je izbrisana. Jezične veze su uklonjene.',
	'wikibase-comment-linked' => 'Neka Wikidata stavka je povezana prema ovoj stranici.',
	'wikibase-comment-unlink' => 'Ova stranica je odvojena od Wikidata stavke. Jezične veze su uklonjene.',
	'wikibase-comment-restore' => 'Asocirana Wikidata stavka je vraćena. Jezične veze su sada isto vraćene.',
	'wikibase-comment-update' => 'Jezične veze su ažurirane.', # Fuzzy
	'wikibase-comment-sitelink-add' => 'Jezična veza dodana: $1',
	'wikibase-comment-sitelink-change' => 'Jezična veza izmjenjena sa $1 u $2',
	'wikibase-comment-sitelink-remove' => 'Jezična veza uklonjena: $1',
	'wikibase-editlinks' => 'Uredi veze',
	'wikibase-editlinkstitle' => 'Uredi međujezične veze',
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Pokaži Wikidata izmjene u nedavnim izmjenama',
);

/** Catalan (català)
 * @author Arnaugir
 * @author Grondin
 * @author Toniher
 * @author Vriullop
 * @author Àlex
 */
$messages['ca'] = array(
	'wikibase-client-desc' => "Client per l'extensió Wikibase",
	'specialpages-group-wikibaseclient' => 'Client de Wikidata',
	'wikibase-after-page-move' => "Podeu també [$1 actualitzar] l'element associat de Wikidata per a mantenir els enllaços d'idioma a la pàgina moguda.",
	'wikibase-comment-remove' => 'Element associat de Wikidata eliminat. Enllaços de llengua suprimits.',
	'wikibase-comment-linked' => 'Un element de Wikidata ha estat enllaçat a aquesta pàgina.',
	'wikibase-comment-unlink' => "Aquesta pàgina ha estat deslligada de l'element Wikidata. Enllaços de llengua suprimits.",
	'wikibase-comment-restore' => 'Element associat de Wikidata recuperat. Enllaços de llengua restaurats.',
	'wikibase-comment-update' => 'Element Wikidata modificat',
	'wikibase-comment-sitelink-add' => 'Afegit enllaç de llengua: $1',
	'wikibase-comment-sitelink-change' => 'Enllaç de llengua canviat de $1 a $2',
	'wikibase-comment-sitelink-remove' => 'Tret enllaç de llengua: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|canvi|canvis}}',
	'wikibase-editlinks' => 'Modifica els enllaços',
	'wikibase-editlinkstitle' => 'Modifica enllaços interlingües',
	'wikibase-linkitem-addlinks' => 'Afegeix enllaços',
	'wikibase-linkitem-alreadylinked' => 'La pàgina amb la que voleu enllaçar ja està definida en un [$1 element] del repositori central de dades que enllaça a $2 en aquest lloc. Els elements només poden estar relacionats amb una pàgina per lloc. Escolliu una pàgina diferent per enllaçar-hi.',
	'wikibase-linkitem-close' => 'Tanca la caixa de diàleg i recarrega la pàgina',
	'wikibase-linkitem-failure' => "S'ha produït un error desconegut en intentar enllaçar a la pàgina indicada.",
	'wikibase-linkitem-title' => 'Enllaça amb la pàgina',
	'wikibase-linkitem-linkpage' => 'Enllaça amb la pàgina',
	'wikibase-linkitem-selectlink' => 'Seleccioneu un lloc i una pàgina que vulgueu enllaçar amb aquesta.',
	'wikibase-linkitem-input-site' => 'Llengua:',
	'wikibase-linkitem-input-page' => 'Pàgina:',
	'wikibase-linkitem-invalidsite' => 'Lloc seleccionat no conegut o no vàlid',
	'wikibase-linkitem-confirmitem-text' => 'La pàgina que heu indicat ja està enllaçada a un [$1 element del repositori central de dades]. Confirmeu que les pàgines que es mostren a continuació són les que voleu enllaçar amb aquesta pàgina.',
	'wikibase-linkitem-confirmitem-button' => 'Confirmat',
	'wikibase-linkitem-not-loggedin-title' => 'Cal iniciar una sessió',
	'wikibase-linkitem-not-loggedin' => 'Cal que inicieu una sessió en aquest wiki i en el [$1 repositori central de dades] per utilitzar aquesta funcionalitat.',
	'wikibase-linkitem-success-create' => 'Les pàgines han estat lligades correctament. Podeu trobar el nou element creat amb els enllaços en el [$1 repositori central de dades].',
	'wikibase-linkitem-success-link' => "Les pàgines han estat lligades correctament. Podeu trobar l'element que conté els enllaços en el [$1 repositori central de dades].",
	'wikibase-property-notfound' => "No s'ha trobat la propietat $1",
	'wikibase-property-notsupportedyet' => 'Wikibase encara no pot fer servir el tipus de propietat $1 per a la propietat.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Mostra les modificacions de Wikidata en els canvis recents',
	'wikibase-watchlist-show-changes-pref' => 'Mostra les modificacions a Wikidata en la llista de seguiment',
	'wikibase-error-invalid-entity-id' => "L'ID introduït és desconegut al sistema. Utilitzeu un ID d'entitat vàlid.",
	'special-unconnectedpages' => 'Pàgines no connectats a elements',
	'wikibase-unconnectedpages-legend' => 'Opcions de pàgines no connectades',
	'wikibase-unconnectedpages-page' => 'Mostra la llista des de la pàgina:',
	'wikibase-unconnectedpages-submit' => 'Vés-hi',
	'wikibase-unconnectedpages-invalid-language' => '«$1» no és un codi de llengua vàlid.',
	'wikibase-unconnectedpages-page-warning' => "El títol de pàgina no s'ha pogut utilitzar per a la consulta i s'ha ignorat.",
	'wikibase-unconnectedpages-iwdata-label' => 'Només pàgines amb enllaços entre llengües',
	'wikibase-unconnectedpages-format-row' => '($1 enllaços entre llengües a la pàgina)',
);

/** Chechen (нохчийн)
 * @author Умар
 */
$messages['ce'] = array(
	'wikibase-after-page-move' => 'Викихаамашчохь [$1 керла якха] хьой цӀе хийцина агӀона хьажориг.',
	'wikibase-rc-hide-wikidata' => '$1 Викихаамаш',
);

/** Sorani Kurdish (کوردی)
 * @author Calak
 */
$messages['ckb'] = array(
	'wikibase-after-page-move' => 'باشترە ھەروەھا برگەی ویکیدراوەی پەیوەندیداریش [$1 نوێ بکەیەوە] ھەتا پەیوەندەکانی زمان لە پەڕە گوازراوەکەدا بمێنێتەوە.',
	'wikibase-comment-remove' => 'بەندی ویکیدراوەی پەیوەندیدار سڕایەوە. بەستەرەکانی زمان لابران.',
	'wikibase-comment-linked' => 'بەندێکی ویکیدراوە بەم پەڕەیە بەستەر دراوە.',
	'wikibase-comment-unlink' => 'ئەم پەڕەیە بە بەندی ویکیدراوە بەستەر نەدراوە. بەستەرەکانی زمان لابران.',
	'wikibase-comment-restore' => 'بەندی ویکیدراوەی پەیوەندیدار گەڕایەوە. بەستەرەکانی زمان ھێنرایەوە.',
	'wikibase-comment-update' => 'بەندی ویکیدراوە گۆڕدرا',
	'wikibase-comment-sitelink-add' => 'بەستەری زمانی زیادکراو: $1',
	'wikibase-comment-sitelink-change' => 'بەستەری زمان لە $1 گۆڕدرا بۆ $2',
	'wikibase-comment-sitelink-remove' => 'بەستەری زمانی سڕدراو: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|گۆڕانکاری}}',
	'wikibase-editlinks' => 'بەستەرەکان دەستکاری بکە',
	'wikibase-editlinkstitle' => 'بەستەرەکانی نێوانویکی دەستکاری بکە',
	'wikibase-linkitem-addlinks' => 'بەستەرەکان زیاد بکە',
	'wikibase-linkitem-close' => 'وتووێژەکە ببەستە و دیسان پەڕەکە بار بکەوە',
	'wikibase-linkitem-title' => 'بەستەر بدە بۆ پەڕە',
	'wikibase-linkitem-linkpage' => 'بەستەر بدە بۆ پەڕە',
	'wikibase-linkitem-selectlink' => 'تکایە پێگە و پەڕەیەک کە دەتەوێ بەم پەڕەیە بەستەر بدرێ ھەڵبژێرە.',
	'wikibase-linkitem-input-site' => 'زمان:',
	'wikibase-linkitem-input-page' => 'پەڕە:',
	'wikibase-linkitem-invalidsite' => 'پێگەیەکی نەناسراو یان نادروست ھەڵبژێردراوە',
	'wikibase-linkitem-confirmitem-text' => 'پەڕەیەک کە ھەڵت‌بژاردووە پێشتر بۆ [$1 بەندێکی خەزێنەی ناوەندیی داروەکانمان] بەستەر دراوە. تکایە پشتدار بکەرەوە ئەم پەڕانەی لە ژێرەوە نیشان دراون ھەر ئەوانەن تۆ دەتەوێ بەم پەڕەیە بەستەر بدرێن.',
	'wikibase-linkitem-confirmitem-button' => 'پشتدار بکەرەوە',
	'wikibase-linkitem-not-loggedin' => 'بۆ بەکارھێنانی ئەم تایبەتمەندییە دەبێ بچییە ژوورە لەم ویکییە و لە [$1 خەزێنەی ناوەندیی داروە]دا.',
	'wikibase-linkitem-success-create' => 'پەڕەکان سەرکەوتووانە بەستەر دران. دەتوانی بەندە تازە دروست‌کراوەکە کە بەستەرەکانی تێدایە لە [$1 خەزێنەی ناوەندیی دراوەکان]ماندا بدۆزییەوە.',
	'wikibase-linkitem-success-link' => 'پەڕەکان سەرکەوتووانە بەستەر دران. دەتوانی ئەو بەندەی بەستەرەکانی تێدایە لە [$1 خەزێنەی ناوەندیی دراوەکان]ماندا بدۆزییەوە.',
	'wikibase-property-notfound' => 'تایبەتمەندیی $1 نەدۆزرایەوە.',
	'wikibase-rc-hide-wikidata' => 'ویکیدراوه $1',
	'wikibase-rc-show-wikidata-pref' => 'دەستکارییەکانی ویکیدراوە لە دوایین گۆڕانکارییەکاندا نیشان بدە',
	'wikibase-watchlist-show-changes-pref' => 'دەستکارییەکانی ویکیدراوە لە پێرستی چاودێرییەکەت نیشان بدە',
	'wikibase-unconnectedpages-submit' => 'بڕۆ',
);

/** Crimean Turkish (Cyrillic script) (къырымтатарджа (Кирилл)‎)
 * @author Don Alessandro
 */
$messages['crh-cyrl'] = array(
	'wikibase-editlinks' => 'Багълантыларны денъиштир',
);

/** Crimean Turkish (Latin script) (qırımtatarca (Latin)‎)
 * @author Don Alessandro
 */
$messages['crh-latn'] = array(
	'wikibase-editlinks' => 'Bağlantılarnı deñiştir',
);

/** Czech (česky)
 * @author JAn Dudík
 * @author Littledogboy
 * @author Mormegil
 */
$messages['cs'] = array(
	'wikibase-client-desc' => 'Klient pro rozšíření Wikibase',
	'specialpages-group-wikibaseclient' => 'Klient Wikidat',
	'wikibase-after-page-move' => 'Můžete také [$1 aktualizovat] související položku Wikidat, aby na přesunuté stránce zůstaly odkazy na další jazyky.',
	'wikibase-comment-remove' => 'Související položka Wikidat odstraněna. Mezijazykové odkazy odstraněny.',
	'wikibase-comment-linked' => 'S touto stránkou byla provázána položka Wikidat.',
	'wikibase-comment-unlink' => 'Odkaz na tuto stránku byl odstraněn z Wikidat. Mezijazykové odkazy odstraněny.',
	'wikibase-comment-restore' => 'Související položka Wikidat obnovena. Mezijazykové odkazy obnoveny.',
	'wikibase-comment-update' => 'Změněna položka na Wikidatech',
	'wikibase-comment-sitelink-add' => 'Přidán mezijazykový odkaz: $1',
	'wikibase-comment-sitelink-change' => 'Mezijazykový odkaz změněn z $1 na $2',
	'wikibase-comment-sitelink-remove' => 'Odstraněn mezijazykový odkaz: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|změna|změny|změn}}',
	'wikibase-editlinks' => 'Upravit odkazy',
	'wikibase-editlinkstitle' => 'Editovat mezijazykové odkazy',
	'wikibase-linkitem-addlinks' => 'Přidat odkazy',
	'wikibase-linkitem-alreadylinked' => 'Stránka, na kterou chcete odkázat, je již použita u [$1 položky] v ústředním úložišti, která odkazuje na $2 na tomto serveru. Položky smí mít pouze pro každý server jen jeden odkaz. Prosím, zvolte jinou stránku.',
	'wikibase-linkitem-close' => 'Zavřít dialog a obnovit stránku',
	'wikibase-linkitem-failure' => 'Došlo k neznámé chybě při pokusu o odkaz na danou stránku.',
	'wikibase-linkitem-title' => 'Propojit se stránkou',
	'wikibase-linkitem-linkpage' => 'Propojit se stránkou',
	'wikibase-linkitem-selectlink' => 'Prosím vyberte jazyk a stránku, kterou chcete propojit s touto stránkou.',
	'wikibase-linkitem-input-site' => 'Jazyk:',
	'wikibase-linkitem-input-page' => 'Stránka:',
	'wikibase-linkitem-invalidsite' => 'Neznámá nebo neplatná stránka',
	'wikibase-linkitem-confirmitem-text' => 'Na zvolenou stránku již odkazuje [$1 položka v našem ústředním úložišti]. Potvrďte, že s touto stránkou chcete provázat níže zobrazené stránky.',
	'wikibase-linkitem-confirmitem-button' => 'Potvrdit',
	'wikibase-linkitem-not-loggedin-title' => 'Musíte být přihlášen',
	'wikibase-linkitem-not-loggedin' => 'Je třeba být přihlášen na této wiki a [$1  Wikidatech], chcete-li použít tuto funkci.',
	'wikibase-linkitem-success-create' => 'Stránky byly úspěšně propojeny. Nově vytvořenou položku s odkazy na ně najdete v [$1 centrálním úložišti dat].',
	'wikibase-linkitem-success-link' => 'Stránky byly úspěšně spojeny. Byla založena položka obsahující tyto odkazy v [$1  centrálním datovém úložišti].',
	'wikibase-property-notfound' => 'Vlastnost $1 nebyla nalezena.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Zobrazit změny Wikidat v posledních změnách',
	'wikibase-watchlist-show-changes-pref' => 'Zobrazit změny Wikidat ve sledovaných stránkách',
	'special-unconnectedpages' => 'Stránky nepropojené s položkou',
	'wikibase-unconnectedpages-legend' => 'Nastavení nepropojených stránek',
	'wikibase-unconnectedpages-page' => 'Seznam výsledků začít stránkou:',
	'wikibase-unconnectedpages-submit' => 'Provést',
	'wikibase-unconnectedpages-invalid-language' => '„$1“ není platný kód jazyka.',
	'wikibase-unconnectedpages-page-warning' => 'Název stránky nelze pro dotaz použít, takže se ignoruje.',
	'wikibase-unconnectedpages-iwdata-label' => 'Jen stránky s mezijazykovými odkazy',
	'wikibase-unconnectedpages-format-row' => '(stránka obsahuje $1 {{PLURAL:$1|mezijazykový odkaz|mezijazykové odkazy|mezijazykových odkazů}})',
);

/** Church Slavic (словѣ́ньскъ / ⰔⰎⰑⰂⰡⰐⰠⰔⰍⰟ)
 * @author ОйЛ
 */
$messages['cu'] = array(
	'wikibase-editlinks' => 'исправи съвѧꙁи',
);

/** Welsh (Cymraeg)
 * @author Lloffiwr
 */
$messages['cy'] = array(
	'specialpages-group-wikibaseclient' => 'Cleient Wiciddata',
	'wikibase-after-page-move' => "Gallwch [$1 ddiweddaru]'r eitem berthnasol ar Wikidata er mwyn cynnal y cysylltiadau rhwng ieithoedd ar y dudalen a symudwyd.",
	'wikibase-comment-linked' => "Cysylltwyd eitem Wikidata â'r dudalen hon.",
	'wikibase-comment-update' => 'Diweddarwyd y cysylltau rhwng ieithoedd.', # Fuzzy
	'wikibase-comment-sitelink-add' => 'Ychwanegwyd y cyswllt rhwng ieithoedd $1',
	'wikibase-comment-sitelink-change' => 'Newidiwyd y cyswllt rhwng ieithoedd o $1 i $2',
	'wikibase-comment-sitelink-remove' => 'Tynnwyd cyswllt rhwng ieithoedd i ffwrdd: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|newid}}',
	'wikibase-editlinks' => 'Golygu cysylltau',
	'wikibase-editlinkstitle' => 'Golygu cysylltau rhyngwici rhwng ieithoedd',
	'wikibase-linkitem-addlinks' => 'Ychwanegu cysylltau',
	'wikibase-linkitem-alreadylinked' => "Mae'r dudalen yr ydych am gysylltu â hi eisoes wedi ei chysylltu ag [$1 eitem] ar y cof data canolog, a honno'n cysylltu â'r dudalen $2 ar y wefan hon. Dim ond un dudalen y wici sy'n gallu cael ei chysylltu ag eitem. Dewiswch dudalen arall i gysylltu â hi.",
	'wikibase-linkitem-title' => 'Cysylltu â thudalen',
	'wikibase-linkitem-linkpage' => "Cysyllter â'r dudalen",
	'wikibase-linkitem-input-site' => 'Iaith:',
	'wikibase-linkitem-input-page' => 'Tudalen:',
	'wikibase-linkitem-invalidsite' => 'Dewiswyd gwefan anhysbys neu annilys',
	'wikibase-linkitem-confirmitem-button' => 'Cadarnhaer',
	'wikibase-linkitem-not-loggedin-title' => 'Rhaid mewngofnodi i barhau',
	'wikibase-property-notfound' => "Ni ddaethpwyd o hyd i'r nodwedd $1.",
	'wikibase-rc-hide-wikidata' => '$1 y golygiadau ar Wiciddata',
	'wikibase-rc-show-wikidata-pref' => 'Dangos golygiadau i Wiciddata ymhlith y newidiadau diweddar',
	'wikibase-watchlist-show-changes-pref' => 'Dangos golygiadau Wiciddata ar eich rhestr wylio',
	'wikibase-error-invalid-entity-id' => "Mae'r ID a roddwyd yn anhysbys i'r system. Defnyddiwch ID dilys i'r endid data.",
	'wikibase-unconnectedpages-submit' => 'Gwneler',
	'wikibase-unconnectedpages-invalid-language' => 'Nid yw "$1" yn gôd iaith dilys',
);

/** Danish (dansk)
 * @author Byrial
 * @author Christian List
 * @author Hede2000
 * @author HenrikKbh
 * @author Poul G
 * @author Steenth
 */
$messages['da'] = array(
	'wikibase-client-desc' => 'Klient til Wikibase-udvidelsen',
	'specialpages-group-wikibaseclient' => 'Wikidata-klient',
	'wikibase-after-page-move' => 'Du kan også [$1 opdatere] det tilknyttede Wikidata-emne for at bevare sproghenvisninger på den flyttede side.',
	'wikibase-comment-remove' => 'Det tilknyttede Wikidata-emne er slettet. Sproghenvisninger er fjernet.',
	'wikibase-comment-linked' => 'Et Wikidata-emne er blevet knyttet til denne side.',
	'wikibase-comment-unlink' => 'Denne side er ikke længere linket fra et Wikidata-emne. Sproghenvisninger er fjernet.',
	'wikibase-comment-restore' => 'Det tilknyttede Wikidata-emne er genskabt. Sproghenvisninger er gendannet.',
	'wikibase-comment-update' => 'Wikidata-emnet er ændret',
	'wikibase-comment-sitelink-add' => 'Sproghenvisning tilføjet: $1',
	'wikibase-comment-sitelink-change' => 'Sproghenvisning ændret fra $1 til $2',
	'wikibase-comment-sitelink-remove' => 'Sproghenvisning fjernet: $1',
	'wikibase-comment-multi' => '{{PLURAL:$1|ændring|ændringer}}',
	'wikibase-editlinks' => 'Rediger links',
	'wikibase-editlinkstitle' => 'Rediger sproglinks',
	'wikibase-linkitem-addlinks' => 'Tilføj links',
	'wikibase-linkitem-alreadylinked' => 'Siden du ønskede at sammenkæde med er allerede knyttet til et andet [$1 emne] i det centrale datadepot, som linker til $2 på dette websted. Emner kan kun knyttes til én side for hvert websted. Vælg venligst en anden side, der skal linkes med.',
	'wikibase-linkitem-close' => 'Luk dialogboksen og genindlæs siden igen',
	'wikibase-linkitem-failure' => 'Der opstod en ukendt fejl under forsøget på at sammenkæde den angivne side.',
	'wikibase-linkitem-title' => 'Link til side',
	'wikibase-linkitem-linkpage' => 'Link til side',
	'wikibase-linkitem-selectlink' => 'Vælg et websted og en side, du vil sammenkæde denne side med.',
	'wikibase-linkitem-input-site' => 'Sprog:',
	'wikibase-linkitem-input-page' => 'Side:',
	'wikibase-linkitem-invalidsite' => 'Ugyldigt eller ukendt websted valgt',
	'wikibase-linkitem-confirmitem-text' => 'Den side, du har valgt, er allerede knyttet til et [$1 emne i vores centrale datadepot]. Bekræft, at siderne, der vises nedenfor, er dem, du vil sammenkæde med.',
	'wikibase-linkitem-confirmitem-button' => 'Bekræft',
	'wikibase-linkitem-not-loggedin-title' => 'Du skal være logget ind',
	'wikibase-linkitem-not-loggedin' => 'Du skal være logget ind på denne wiki og i det [$1 centrale datadepot] for at bruge denne funktion.',
	'wikibase-linkitem-success-create' => 'Siderne er blevet linket. Du kan finde det nyoprettede emne, der indeholder links, i vores [$1 centrale datadepot].',
	'wikibase-linkitem-success-link' => 'Siderne er blevet linket. Du kan finde det emne, der indeholder links, i vores [$1 centrale datadepot].',
	'wikibase-property-notfound' => 'Egenskaben $1 ikke fundet.',
	'wikibase-property-notsupportedyet' => 'Wikibase understøtter endnu ikke egenskabstypen for egenskaben $1.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Vis Wikidata-redigeringer i seneste ændringer',
	'wikibase-watchlist-show-changes-pref' => 'Vis Wikidata-redigeringer på din overvågningsliste',
	'wikibase-error-invalid-entity-id' => 'Den indtastede ID er ukendt i systemet. Anvend venligst en gyldig ID.',
	'special-unconnectedpages' => 'Sider, der ikke er forbundet til emner',
	'wikibase-unconnectedpages-legend' => 'Indstillinger for ikke-forbundne sider',
	'wikibase-unconnectedpages-page' => 'Start resultatliste med side:',
	'wikibase-unconnectedpages-submit' => 'Udfør',
	'wikibase-unconnectedpages-invalid-language' => '"$1" er ikke en gyldig sprogkode.',
	'wikibase-unconnectedpages-page-warning' => 'Sidens titel kunne ikke anvendes til forespørgslen og blev ignoreret.',
	'wikibase-unconnectedpages-iwdata-label' => 'Kun sider med sproghenvisninger',
	'wikibase-unconnectedpages-format-row' => '($1 sproghenvisninger på siden)',
);

/** German (Deutsch)
 * @author Kghbln
 * @author Metalhead64
 * @author Purodha
 */
$messages['de'] = array(
	'wikibase-client-desc' => 'Ermöglicht einen Client für die Erweiterung Wikibase',
	'specialpages-group-wikibaseclient' => 'Wikidata-Client',
	'wikibase-after-page-move' => 'Du kannst auch das zugeordnete Wikidata-Element [$1 aktualisieren], um Sprachlinks von verschobenen Seiten zu verwalten.',
	'wikibase-comment-remove' => 'Zugeordnetes Wikidata-Objekt wurde gelöscht. Sprachlinks wurden entfernt.',
	'wikibase-comment-linked' => 'Ein Wikidata-Objekt wurde mit dieser Seite verknüpft.',
	'wikibase-comment-unlink' => 'Diese Seite wurde vom Wikidata-Objekt entlinkt. Sprachlinks wurden entfernt.',
	'wikibase-comment-restore' => 'Zugeordnetes Wikidata-Objekt wurde wiederhergestellt. Sprachlinks wurden wiederhergestellt.',
	'wikibase-comment-update' => 'Das Wikidata-Objekt wurde geändert',
	'wikibase-comment-sitelink-add' => 'Sprachlink hinzugefügt: $1',
	'wikibase-comment-sitelink-change' => 'Sprachlink $1 geändert in $2',
	'wikibase-comment-sitelink-remove' => 'Sprachlink entfernt: $1',
	'wikibase-comment-multi' => '{{PLURAL:$1|Eine Änderung|$1 Änderungen}}',
	'wikibase-editlinks' => 'Links bearbeiten',
	'wikibase-editlinkstitle' => 'Links auf Artikel in anderen Sprachen bearbeiten',
	'wikibase-linkitem-addlinks' => 'Links hinzufügen',
	'wikibase-linkitem-alreadylinked' => 'Die Seite, die du verlinken willst, ist bereits einem [$1 Objekt] in unserem zentralen Datenrepositorium zugeordnet, das auf $2 auf dieser Website verlinkt. Es kann nur eine Seite pro Website einem Objekt zugeordnet werden. Bitte wähle eine andere Seite, die verlinkt werden soll.',
	'wikibase-linkitem-close' => 'Dialog schließen und Seite neu laden',
	'wikibase-linkitem-failure' => 'Beim Verlinken der angegebenen Seite ist ein unbekannter Fehler aufgetreten.',
	'wikibase-linkitem-title' => 'Mit Seite verlinken',
	'wikibase-linkitem-linkpage' => 'Mit Seite verlinken',
	'wikibase-linkitem-selectlink' => 'Bitte wähle eine Website und eine Seite aus, die du mit dieser Seite verlinken willst.',
	'wikibase-linkitem-input-site' => 'Sprache:',
	'wikibase-linkitem-input-page' => 'Seite:',
	'wikibase-linkitem-invalidsite' => 'Unbekannte oder ungültige Website ausgewählt',
	'wikibase-linkitem-confirmitem-text' => 'Die ausgewählte Seite ist bereits mit einem [$1 Objekt in unserem zentralen Datenrepositorium] verlinkt. Bitte bestätige, dass die unten stehenden Seiten diejenigen sind, die du mit dieser Seite verlinken willst.',
	'wikibase-linkitem-confirmitem-button' => 'Bestätigen',
	'wikibase-linkitem-not-loggedin-title' => 'Du musst angemeldet sein',
	'wikibase-linkitem-not-loggedin' => 'Du musst auf diesem Wiki und im [$1 zentralen Datenrepositorium] angemeldet sein, um diese Funktion nutzen zu können.',
	'wikibase-linkitem-success-create' => 'Die Seiten wurden erfolgreich verlinkt. Du findest das neu erstellte Objekt, das die Links enthält, in unserem [$1 zentralen Datenrepositorium].',
	'wikibase-linkitem-success-link' => 'Die Seiten wurden erfolgreich verlinkt. Du findest das Objekt, das die Links enthält, in unserem [$1 zentralen Datenrepositorium].',
	'wikibase-property-notfound' => 'Die Eigenschaft „$1“ wurde nicht gefunden.',
	'wikibase-property-notsupportedyet' => 'Wikibase unterstützt den Eigenschaftstyp der Eigenschaft „$1“ noch nicht.',
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Wikidata-Bearbeitungen in den „Letzten Änderungen“ anzeigen',
	'wikibase-watchlist-show-changes-pref' => 'Wikidata-Bearbeitungen in der Beobachtungsliste anzeigen',
	'wikibase-error-invalid-entity-id' => 'Die eingegebene Kennung ist für das System unbekannt. Bitte verwende eine gültige Objektkennung.',
	'special-unconnectedpages' => 'Seiten, die nicht mit Objekten verbunden sind',
	'wikibase-unconnectedpages-legend' => 'Optionen für nicht verbundene Seiten',
	'wikibase-unconnectedpages-page' => 'Ergebnisliste mit Seite starten:',
	'wikibase-unconnectedpages-submit' => 'Los',
	'wikibase-unconnectedpages-invalid-language' => '„$1“ ist kein gültiger Sprachcode.',
	'wikibase-unconnectedpages-page-warning' => 'Der Seitenname kann nicht für die Abfrage verwendet werden und wurde ignoriert.',
	'wikibase-unconnectedpages-iwdata-label' => 'Nur Seiten mit Interlanguagelinks',
	'wikibase-unconnectedpages-format-row' => '($1 Interlanguagelinks auf der Seite)',
	'wikibase-pageinfo-entity-id' => 'Wikidata-Objektkennung',
	'wikibase-pageinfo-entity-id-none' => 'Keine',
);

/** Zazaki (Zazaki)
 * @author Erdemaslancan
 * @author Gorizon
 * @author Mirzali
 */
$messages['diq'] = array(
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|vurnayış|vurnayışi}}',
	'wikibase-editlinks' => 'Gırey bıvurne',
	'wikibase-linkitem-input-site' => 'Zıwan:',
	'wikibase-linkitem-input-page' => 'Pele:',
);

/** Lower Sorbian (dolnoserbski)
 * @author Michawiki
 */
$messages['dsb'] = array(
	'wikibase-client-desc' => 'Klient za rozšyrjenje Wikibase',
	'wikibase-after-page-move' => 'Móžoš teke pśirědowany element Wikidata [$1 aktualizěrowaś], aby mjazyrěcne wótkaze na pśesunjonem boku zarědował.',
	'wikibase-comment-remove' => 'Pśirědowany element Wikidata jo wulašowany. Mjazyrěcne wótkaze wótpórane.',
	'wikibase-comment-linked' => 'Element Wikidata jo se z toś tym bokom zwězał.',
	'wikibase-comment-unlink' => 'Zwisk boka z elementom Wikidata jo se wópórał. Mjazyrěcne wótkaze wótpórane.',
	'wikibase-comment-restore' => 'Pśirědowany element Wikidata jo wótnowjony. Mjazyrěcne wótkaze wótnowjone.',
	'wikibase-comment-update' => 'Mjazyrěcne wótkaze su se zaktualizěrowali.',
	'wikibase-comment-sitelink-add' => 'Mjazyrěcny wótkaz pśidany: $1',
	'wikibase-comment-sitelink-change' => 'Mjazyrěcny wótkaz změnjony wót $1 do $2',
	'wikibase-comment-sitelink-remove' => 'Mjazyrěcny wótkaz wótpórany: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|změna|změnje|změny|změnow}}',
	'wikibase-editlinks' => 'Wótkaze wobźěłaś',
	'wikibase-editlinkstitle' => 'Mjazyrěcne wótkaze wobźěłaś',
	'wikibase-linkitem-addlinks' => 'Wótkaze pśidaś',
	'wikibase-linkitem-alreadylinked' => 'Bok, z kótarymž coš zwězaś, słuša južo k [$1 elementoju] w centralnem datowem repozitoriumje, kótaryž  pokazujo na $2 na toś tom sedle. Elementy mógu jano jaden bok na sedło měś. Pšosym wubjeŕ drugi bok, z kótarymž se zwězujo.',
	'wikibase-linkitem-close' => 'Dialog zacyniś a bok znowego zacytaś',
	'wikibase-linkitem-failure' => 'Pśi wopyśe z datym bokom zwězaś, jo njeznata zmólka nastała.',
	'wikibase-linkitem-title' => 'Z bokom zwězaś',
	'wikibase-linkitem-linkpage' => 'Z bokom zwězaś',
	'wikibase-linkitem-selectlink' => 'Pšosym wubjeŕ sedło a bok, kótarejž coš z toś tom bokom zwězaś.',
	'wikibase-linkitem-input-site' => 'Rěc:',
	'wikibase-linkitem-input-page' => 'Bok:',
	'wikibase-linkitem-invalidsite' => 'Njeznate abo njepłaśiwe sedło wubrane',
	'wikibase-linkitem-confirmitem-text' => 'Bok, kótaryž sy wubrał, jo južo z [$1 elementom w našom centralnem datowem repozitoriumje] zwězany. Pšosym wobkšuś, až slědujuce boki su te, kótarež coš z toś tym bokom zwězaś.',
	'wikibase-linkitem-confirmitem-button' => 'Wobkšuśiś',
	'wikibase-linkitem-not-loggedin-title' => 'Musyš pśizjawjony byś',
	'wikibase-linkitem-not-loggedin' => 'Musyš w toś tom wikiju a w [$1 centralnem datowem repozitoriumje] pśizjawjony byś, aby toś tu funkciju wužywał.',
	'wikibase-linkitem-success-create' => 'Boki su se wuspěšnje zwězali. Móžoš nowy element, kótaryž wopśimujo wótkaze, w našom [$1 centralnem datowem repozitoriumje] namakaś.',
	'wikibase-linkitem-success-link' => 'Boki su se wuspěšnje zwězali. Móžoš element, kótaryž wopśimujo wótkaze, w našom [$1 centralnem datowem repozitoriumje] namakaś.',
	'wikibase-property-notfound' => 'Kakosć $1 njejo se namakała.',
	'wikibase-property-notsupportedyet' => 'Wikibase hyšći njepóděra kakosćowy typ za kakosć $1.',
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Změny Wikidata w aktualnych změnach pokazaś',
	'wikibase-watchlist-show-changes-pref' => 'Změny Wikidata w twójich wobglědowańkach pokazaś',
	'wikibase-error-invalid-entity-id' => 'Zapódany ID jo systemoju njeznaty. Pšosym wužyj płaśiwy entitowy ID.',
);

/** Greek (Ελληνικά)
 * @author Geraki
 */
$messages['el'] = array(
	'wikibase-comment-update' => 'Οι γλωσσικοί σύνδεσμοι ενημερώθηκαν.',
	'wikibase-comment-sitelink-add' => 'Γλωσσικός σύνδεσμος προστέθηκε: $1',
	'wikibase-comment-sitelink-change' => 'Γλωσσικός σύνδεσμος άλλαξε από $1 σε $2',
	'wikibase-comment-sitelink-remove' => 'Γλωσσικός σύνδεσμος αφαιρέθηκε: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|αλλαγή|αλλαγές}}',
	'wikibase-editlinks' => 'Επεξεργασία συνδέσμων',
	'wikibase-editlinkstitle' => 'Επεξεργασία διαγλωσσικών συνδέσεων',
	'wikibase-linkitem-addlinks' => 'Προσθήκη συνδέσεων',
	'wikibase-linkitem-alreadylinked' => 'Η σελίδα με την οποία θέλατε να συνδέσετε έχει ήδη προσαρτηθεί σε ένα [$1 αντικείμενο] του κεντρικού αποθετηρίου δεδομένων που συνδέει στο $2 σε αυτό το site. Τα αντικείμενα μπορούν να έχουν μόνο μία συνδεμένη σελίδα ανά site. Παρακαλούμε επιλέξτε μια διαφορετική σελίδα για να συνδέσετε.',
	'wikibase-linkitem-close' => 'Κλείσετε το παράθυρο διαλόγου και φορτώσετε εκ νέου τη σελίδα',
	'wikibase-linkitem-failure' => 'Ένα άγνωστο σφάλμα παρουσιάστηκε κατά την προσπάθεια να συνδεθεί η συγκεκριμένη σελίδα.',
	'wikibase-linkitem-title' => 'Σύνδεση με σελίδα',
	'wikibase-linkitem-linkpage' => 'Σύνδεση με σελίδα',
	'wikibase-linkitem-selectlink' => 'Παρακαλούμε επιλέξτε ένα ιστότοπο και μια σελίδα που θέλετε να συνδέσετε με αυτή τη σελίδα.',
	'wikibase-linkitem-input-site' => 'Γλώσσα:',
	'wikibase-linkitem-input-page' => 'Σελίδα:',
	'wikibase-linkitem-invalidsite' => 'Άγνωστο ή μη έγκυρο site επιλέχτηκε',
	'wikibase-linkitem-confirmitem-text' => 'Η σελίδα που επιλέξατε είναι ήδη συνδεδεμένη με ένα [$1 αντικείμενο στο κεντρικό μας αποθετήριο δεδομένων]. Παρακαλούμε βεβαιωθείτε ότι οι σελίδες που φαίνεται παρακάτω είναι αυτές που θέλετε να συνδέσετε με την παρούσα σελίδα.',
	'wikibase-linkitem-confirmitem-button' => 'Επιβεβαίωση',
	'wikibase-linkitem-not-loggedin-title' => 'Πρέπει να έχετε συνδεθεί',
	'wikibase-linkitem-not-loggedin' => 'Πρέπει να έχετε συνδεθεί σε αυτό το wiki και στο [$1 κεντρικό αποθετήριο δεδομένων] για να χρησιμοποιήσετε αυτή τη δυνατότητα.',
	'wikibase-linkitem-success-create' => 'Οι σελίδες έχουν συνδεθεί με επιτυχία. Μπορείτε να βρείτε το μόλις δημιουργημένο αντικείμενο που περιέχει τους συνδέσεις στο [$1 κεντρικό αποθετήριο δεδομένων].',
	'wikibase-linkitem-success-link' => 'Οι σελίδες έχουν συνδεθεί με επιτυχία. Μπορείτε να βρείτε το αντικείμενο που περιέχει τους συνδέσεις στο [$1 κεντρικό αποθετήριο δεδομένων].',
	'wikibase-property-notfound' => 'Η ιδιότητα $1 δεν βρέθηκε.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Εμφάνιση των επεξεργασιών στο Wikidata στις πρόσφατες αλλαγές',
	'wikibase-watchlist-show-changes-pref' => 'Εμφάνιση αλλαγών στα Wikidata στη λίστα παρακολούθησης σας',
);

/** Esperanto (Esperanto)
 * @author ArnoLagrange
 * @author KuboF
 * @author Tlustulimu
 */
$messages['eo'] = array(
	'wikibase-client-desc' => 'Kliento por la Vikidatuma etendaĵo',
	'specialpages-group-wikibaseclient' => 'Vikidatuma kliento',
	'wikibase-after-page-move' => 'Vi povas [$1 ĝisdatigi] la ligitan Vikidatuman eron por pluteni la lingvan ligilon al la la movita paĝo.',
	'wikibase-comment-remove' => 'Ligita Vikidatuma ero etis forigita. La lingvaj ligiloj estas forviŝitaj.',
	'wikibase-comment-linked' => 'Vikidatuma ero estis ligita al ĉi tiu paĝo.',
	'wikibase-comment-unlink' => 'Ĉi tiu paĝo estis malligita de la Vikidatuma ero. La lingvaj ligiloj estas forigitaj.',
	'wikibase-comment-restore' => 'Ligita vikidatuma ero estis restarigita. La lingvaj ligiloj ankaŭ estis restarigitaj.',
	'wikibase-comment-update' => 'Vikidatuma ero ĝisdatigita.',
	'wikibase-comment-sitelink-add' => 'Lingva ligilo aldonita: $1',
	'wikibase-comment-sitelink-change' => 'Lingva ligilo ŝanĝita de $1 al $2',
	'wikibase-comment-sitelink-remove' => 'Lingva ligilo forigita: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|ŝanĝo|ŝanĝoj}}',
	'wikibase-editlinks' => 'Redaktu ligilojn',
	'wikibase-editlinkstitle' => 'Redaktu interlingvajn ligilojn',
	'wikibase-linkitem-addlinks' => 'Aldoni ligilojn',
	'wikibase-linkitem-close' => 'Fermi dialogon kaj reŝargi paĝon',
	'wikibase-linkitem-failure' => 'Okazis nekonata eraro dum provo ligi donitan paĝon.',
	'wikibase-linkitem-title' => 'Ligi kun paĝo',
	'wikibase-linkitem-linkpage' => 'Ligi kun paĝo',
	'wikibase-linkitem-selectlink' => 'Bonvolu elekti retejon kaj paĝon kun kiu vi volas ligi tiun ĉi paĝon.',
	'wikibase-linkitem-input-site' => 'Lingvo:',
	'wikibase-linkitem-input-page' => 'Paĝo:',
	'wikibase-linkitem-invalidsite' => 'Nekonata aŭ nevalida retejo',
	'wikibase-linkitem-confirmitem-text' => 'La paĝo kiun vi elektis jam estas ligita al [$1 ero en nia centra datuma deponejo]. Bonvolu konfirmi ke la paĝoj montritaj sube estas tiuj, kiujn vi volas ligi kun tiu ĉi paĝo.',
	'wikibase-linkitem-confirmitem-button' => 'Konfirmi',
	'wikibase-linkitem-not-loggedin-title' => 'Vi bezonas esti ensalutinta',
	'wikibase-linkitem-not-loggedin' => 'Vi bezonas esti ensalutinta en tiu ĉi vikio kaj en  [$1 centra datuma deponejo] por uzi tiun ĉi funkcion.',
	'wikibase-linkitem-success-create' => 'La paĝoj estas sukcese interligitaj. Nove kreitan eron, kiu enhavas la ligilojn, vi povas trovi en nia [$1 centra datuma deponejo].',
	'wikibase-linkitem-success-link' => 'La paĝoj estas sukcese interligitaj. Eron, kiu enhavas la ligilojn, vi povas trovi en nia [$1 centra datuma deponejo].',
	'wikibase-property-notfound' => 'Atributo $1 ne estas trovita.',
	'wikibase-property-notsupportedyet' => 'Wikibase ankoraŭ ne subtenas atributan specon por atribuo $1.',
	'wikibase-rc-hide-wikidata' => '$1 Vikidatumoj',
	'wikibase-rc-show-wikidata-pref' => 'Montru Vikidatumajn redaktojn en la lastaj ŝanĝoj',
	'wikibase-watchlist-show-changes-pref' => 'Montri Vikidatumajn redaktojn en via atentaro',
	'wikibase-error-invalid-entity-id' => 'La enigita identigilo estas nekonata de la sistemo. Bonvolu uzi validan identigilon.',
	'special-unconnectedpages' => 'Paĝo nekonektitaj al eroj',
	'wikibase-unconnectedpages-legend' => 'Opcioj de nekonektitaj paĝoj',
	'wikibase-unconnectedpages-page' => 'Komenci rezutoliston per paĝo:',
	'wikibase-unconnectedpages-submit' => 'Ek',
	'wikibase-unconnectedpages-invalid-language' => '"$1" ne estas valida lingva kodo.',
	'wikibase-unconnectedpages-iwdata-label' => 'Nur paĝon kun interlingva ligiloj',
	'wikibase-unconnectedpages-format-row' => '($1 interlingvaj ligiloj en la paĝo)',
);

/** Spanish (español)
 * @author Armando-Martin
 * @author Dalton2
 * @author Hazard-SJ
 * @author Hereñu
 * @author Pegna
 * @author Vivaelcelta
 */
$messages['es'] = array(
	'wikibase-client-desc' => 'Cliente para la extensión Wikibase',
	'specialpages-group-wikibaseclient' => 'Cliente de Wikidata',
	'wikibase-after-page-move' => 'También puedes [$1 actualizar] el elemento Wikidata asociado para mantener los vínculos de idioma en la página que se ha movido.',
	'wikibase-comment-remove' => 'Se ha borrado un elemento asociado a Wikidata. Se han eliminado los enlaces lingüísticos.',
	'wikibase-comment-linked' => 'Un artículo de Wikidata ha sido enlazado a esta página.',
	'wikibase-comment-unlink' => 'Esta página ha sido desenlazada de un elemento de Wikidata. Se han eliminado los enlaces lingüísticos.',
	'wikibase-comment-restore' => 'Se ha restaurado un elemento asociado a Wikidata. Se han restaurado los enlaces de idioma.',
	'wikibase-comment-update' => 'Cambió el elemento de Wikidata',
	'wikibase-comment-sitelink-add' => 'Se ha añadido un enlace de idioma: $1',
	'wikibase-comment-sitelink-change' => 'Se ha cambiado el enlace de idioma de $1 a $2',
	'wikibase-comment-sitelink-remove' => 'Se ha eliminado el enlace de idioma: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|cambio|cambios}}',
	'wikibase-editlinks' => 'Editar los enlaces',
	'wikibase-editlinkstitle' => 'Editar enlaces de interlengua',
	'wikibase-linkitem-addlinks' => 'Añadir enlace',
	'wikibase-linkitem-alreadylinked' => 'La página que quieres enlazar con esta, ya está enlazado en [$1 item] en el repositorio de datos central que une a $2 a este sitio. Los elementos sólo pueden tener una sola página por sitio enlazado. Por favor, elija una página diferente para enlazarlo.',
	'wikibase-linkitem-close' => 'Cierre el cuadro de dialogo y recargue la página',
	'wikibase-linkitem-failure' => 'Se produjo un error desconocido al intentar enlazar la página dada.',
	'wikibase-linkitem-title' => 'Enlace con la página',
	'wikibase-linkitem-linkpage' => 'Enlace con la página',
	'wikibase-linkitem-selectlink' => 'Por favor, seleccione un sitio y una página que deseé vincultar a esta página.',
	'wikibase-linkitem-input-site' => 'Idioma:',
	'wikibase-linkitem-input-page' => 'Página:',
	'wikibase-linkitem-invalidsite' => 'Seleccionó un sitio desconocido o no válido',
	'wikibase-linkitem-confirmitem-text' => 'La página que usted eligió ya está enlazada a un [$1 item on our central data repository]. Confirme que las páginas que se muestran a continuación son los que desea enlazar con esta página.',
	'wikibase-linkitem-confirmitem-button' => 'Confirmar',
	'wikibase-linkitem-not-loggedin-title' => 'Necesita haberse identificado',
	'wikibase-linkitem-not-loggedin' => 'Necesita haberse identificado en esta wiki y en el [$1 central data repository], para usar esta función.',
	'wikibase-linkitem-success-create' => 'Las páginas han sido enlazadas con éxito. Puedes encontrar encontrar el elemento recién creado que contiene los enlaces en nuestro [$1 depósito central de datos].',
	'wikibase-linkitem-success-link' => 'Las páginas han sido enlazadas con éxito. Puedes encontrar encontrar el elemento recién creado que contiene los enlaces en nuestro [$1 depósito central de datos].',
	'wikibase-property-notfound' => 'No se encuentra la propiedad "$1"',
	'wikibase-property-notsupportedyet' => 'Wikibase aún no soporta el tipo de propiedad de la propiedad "$1".',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Mostrar las modificaciones de Wikidata en los cambios recientes',
	'wikibase-watchlist-show-changes-pref' => 'Mostrar las modificaciones de Wikidata en tu lista de seguimiento',
	'wikibase-error-invalid-entity-id' => 'El ID ingresado es desconocido para el sistema. Utilice un identificador de entidad válido.',
	'special-unconnectedpages' => 'Páginas no conectados con elementos',
	'wikibase-unconnectedpages-legend' => 'Opciones para las páginas no conectadas',
	'wikibase-unconnectedpages-submit' => 'Continuar',
	'wikibase-unconnectedpages-invalid-language' => '"$1" no es un código de idioma válido.',
	'wikibase-unconnectedpages-page-warning' => 'El título de la página no se pudo usar para la búsqueda y se ignoró.',
	'wikibase-unconnectedpages-iwdata-label' => 'Solo páginas con enlaces interlingüísticos',
	'wikibase-unconnectedpages-format-row' => '($1 enlaces interlingüísticos en la página)',
);

/** Estonian (eesti)
 * @author Pikne
 */
$messages['et'] = array(
	'wikibase-client-desc' => 'Lisa Vikibaas klient',
	'specialpages-group-wikibaseclient' => 'Vikiandmete klient',
	'wikibase-after-page-move' => 'Võid [$1 värskendada] ka vastavat Vikiandmete üksust, et teisaldatud lehekülje keelelingid jääksid töökorda.',
	'wikibase-comment-remove' => 'Vastav Vikiandmete üksus on kustutatud. Keelelingid eemaldatud.',
	'wikibase-comment-linked' => 'Vikiandmete üksus viitab nüüd sellele leheküljele.',
	'wikibase-comment-unlink' => 'Vikiandmete üksus ei viita enam sellele leheküljele. Keelelingid eemaldatud.',
	'wikibase-comment-restore' => 'Vastav Vikiandmete üksus on taastatud. Keelelingid taastatud.',
	'wikibase-comment-update' => 'Muudetud Vikiandmete üksus',
	'wikibase-comment-sitelink-add' => 'Lisatud keelelink $1',
	'wikibase-comment-sitelink-change' => 'Keelelink $1 muudetud kujule $2',
	'wikibase-comment-sitelink-remove' => 'Eemaldatud keelelink $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|muudatus|muudatust}}',
	'wikibase-editlinks' => 'Redigeeri linke',
	'wikibase-editlinkstitle' => 'Redigeeri keeltevahelisi linke',
	'wikibase-linkitem-addlinks' => 'Lisa lingid',
	'wikibase-linkitem-alreadylinked' => 'Lehekülg, mida proovisid linkida on juba seotud keskse andmehoidla [$1 üksusega] ja viitab siin võrgukohas leheküljele $2. Üksusega saab siduda igast võrgukohast vaid ühe lehekülje. Palun vali muu lehekülg, millele linkida.',
	'wikibase-linkitem-close' => 'Sule dialoogikast ja laadi lehekülg uuesti',
	'wikibase-linkitem-failure' => 'Ette antud leheküljele linkimisel ilmnes tundmatu tõrge.',
	'wikibase-linkitem-title' => 'Leheküljega linkimine',
	'wikibase-linkitem-linkpage' => 'Lingi leheküljega',
	'wikibase-linkitem-selectlink' => 'Palun vali võrgukoht ja lehekülg, millega seda lehekülge linkida soovid.',
	'wikibase-linkitem-input-site' => 'Keel:',
	'wikibase-linkitem-input-page' => 'Lehekülg:',
	'wikibase-linkitem-invalidsite' => 'Valitud tundmatu või vigane võrgukoht',
	'wikibase-linkitem-confirmitem-text' => 'Lehekülg, mille valisid, on juba lingitud [$1 üksusega keskses andmehoidlas]. Palun kinnita, et alljärgnevad leheküljed on need, millega seda lehekülge soovid siduda.',
	'wikibase-linkitem-confirmitem-button' => 'Kinnita',
	'wikibase-linkitem-not-loggedin-title' => 'Pead olema sisse loginud',
	'wikibase-linkitem-not-loggedin' => 'Et kasutada seda funktsiooni, pead olema sisse loginud siia vikisse ja [$1 kesksesse andmehoidlasse].',
	'wikibase-linkitem-success-create' => 'Leheküljed on edukalt ühendatud. Linke sisaldava vastloodud üksuse leiad [$1 kesksest andmehoidlast].',
	'wikibase-linkitem-success-link' => 'Leheküljed on edukalt ühendatud. Linke sisaldava üksuse leiad [$1 kesksest andmehoidlast].',
	'wikibase-property-notfound' => 'Omadust $1 ei leitud.',
	'wikibase-property-notsupportedyet' => 'Vikibaas ei toeta veel omadusele $1 vastavat omaduse tüüpi.',
	'wikibase-rc-hide-wikidata' => 'Vikiandmed ($1)',
	'wikibase-rc-show-wikidata-pref' => 'Näita viimastes muudatustes Vikiandmete muudatusi',
	'wikibase-watchlist-show-changes-pref' => 'Näita jälgimisloendis Vikiandmete muudatusi',
	'wikibase-error-invalid-entity-id' => 'Sisestatud identifikaator on süsteemis tundmatu. Palun sisesta sobiv olemi identifikaator.',
	'special-unconnectedpages' => 'Üksustega ühendamata leheküljed',
	'wikibase-unconnectedpages-legend' => 'Ühendamata lehekülgede sätted',
	'wikibase-unconnectedpages-page' => 'Tulemused alates leheküljest:',
	'wikibase-unconnectedpages-submit' => 'Mine',
	'wikibase-unconnectedpages-invalid-language' => '"$1" pole sobiv keelekood.',
	'wikibase-unconnectedpages-page-warning' => 'Lehekülje pealkirja ei saanud päringus kasutada ja seda eiratakse.',
	'wikibase-unconnectedpages-iwdata-label' => 'Ainult keelelinkidega leheküljed',
	'wikibase-unconnectedpages-format-row' => '(leheküljel $1 keelelinki)',
);

/** Persian (فارسی)
 * @author Calak
 * @author Mehran
 * @author Pouyana
 * @author Reza1615
 * @author ZxxZxxZ
 * @author درفش کاویانی
 */
$messages['fa'] = array(
	'wikibase-client-desc' => 'کارخواه برای افزونهٔ ویکی‌بیس',
	'specialpages-group-wikibaseclient' => 'مشتری ویکی‌داده',
	'wikibase-after-page-move' => 'شما ممکن است در عین حال بخواهید آیتم وابستهٔ ویکی‌داده را نیز به [$1 روزرسانی] کنید، تا پیوند به صفحه منتقل شده باقی بماند.',
	'wikibase-comment-remove' => 'پیوند آیتم ویکی‌داده حذف گردید. پیوند زبان حذف شد.',
	'wikibase-comment-linked' => 'یک آیتم ویکی‌داده به این صفحه پیوند دارد.',
	'wikibase-comment-unlink' => 'این صفحه به ویکی‌داده پیوند ندارد. پیوند زبان حذف شد.',
	'wikibase-comment-restore' => 'پیوند آیتم ویکی‌داده بازیابی شد. پیوند زبان بازیابی شد.',
	'wikibase-comment-update' => 'آیتم ویکی‌داده تغییر یافت',
	'wikibase-comment-sitelink-add' => 'پیوند زبان اضافه شده:$1',
	'wikibase-comment-sitelink-change' => 'پیوند زبان از $1 به $2 تغییر کرده‌است.',
	'wikibase-comment-sitelink-remove' => 'پیوند زبان حذف شد: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|تغییر|تغییرها}}',
	'wikibase-editlinks' => 'ویرایش پیوندها',
	'wikibase-editlinkstitle' => 'ویرایش پیوندهای میان‌ویکی',
	'wikibase-linkitem-addlinks' => 'افزودن پیوند',
	'wikibase-linkitem-alreadylinked' => 'صفحه‌ای که شما به آن پیوند می‌دهید، از قبل به یک [$1 آیتم] در مخزن مرکزی داده که به $2 در این سایت مریوط می‌شود، متصل است. آیتم‌ها می‌توانند فقط به یک صفحه از وبگاه ارتباط داشته‌باشند. لطفا از یک صقحه برای پیوند استفاده کنید.',
	'wikibase-linkitem-close' => 'پنجره را ببند و صفحه را مجدداً بارگذاری کن',
	'wikibase-linkitem-failure' => 'یک خطای ناشناخته در هنگام بارگذاری صفحهٔ پیوند داده‌شده رخ داد.',
	'wikibase-linkitem-title' => 'پیوند به صفحه',
	'wikibase-linkitem-linkpage' => 'پیوند با صفحه',
	'wikibase-linkitem-selectlink' => 'لطفا سایت و صفحه‌ای که می‌خواهید به این صفحه پیوند دهید را انتخاب کنید.',
	'wikibase-linkitem-input-site' => 'زبان:',
	'wikibase-linkitem-input-page' => 'صفحه:',
	'wikibase-linkitem-invalidsite' => 'سایت‌های ناشناخته و یا غیر معتبر انتخاب شده است.',
	'wikibase-linkitem-confirmitem-text' => 'این صفحه که انتخاب کرده‌اید از قبل به [$1 یک آیتم روی مخزن مرکزی داده ما] متصل است. لطفا تایید کنید که صفحه‌های زیر همان‌هایی هستند که شما خواهان پیوند دادن به آن‌ها بودید.',
	'wikibase-linkitem-confirmitem-button' => 'تأیید',
	'wikibase-linkitem-not-loggedin-title' => 'باید وارد سیستم شوید.',
	'wikibase-linkitem-not-loggedin' => 'شما نیاز است که در این ویکی و [$1 مخزن اصلی داده] وارد شوید تا بتوانید از این امکان استفاده کنید.',
	'wikibase-linkitem-success-create' => 'این صفحه به‌درستی پیوند داده شد. شما می‌توانید آیتم حاوی پیوند را در [$1 مخزن مرکزی داده‌ها] ما بیابید.',
	'wikibase-linkitem-success-link' => 'این صفحه به‌درستی پیوند داده شد. شما می‌توانید آیتم حاوی پیوند را در [$1 central data repository] بیابید.',
	'wikibase-property-notfound' => 'خصوصیت $1 یافت نشد.',
	'wikibase-property-notsupportedyet' => 'ویکی‌بیس هنوز نوع خصوصیت برای خصوصیت $1 را پشتیبانی نمی‌کند.',
	'wikibase-rc-hide-wikidata' => '$1 ویکی‌داده',
	'wikibase-rc-show-wikidata-pref' => 'نمایش ویرایش‌های ویکی‌داده در تغییرات اخیر',
	'wikibase-watchlist-show-changes-pref' => 'نمایش ویرایش‌های ویکی‌داده در فهرست پیگیری‌ها',
	'wikibase-error-invalid-entity-id' => 'شناسهٔ واردشده برای سامانه ناشناخته است.لطفاً از یک شناسهٔ موضوع معتبر استفاده کنید.',
	'special-unconnectedpages' => 'صفحات به آیتم‌ها متصل نیستند',
	'wikibase-unconnectedpages-legend' => 'گزینه‌های صفحات غیرمرتبط',
	'wikibase-unconnectedpages-page' => 'فهرست نتایج با صفحهٔ:',
	'wikibase-unconnectedpages-submit' => 'برو',
	'wikibase-unconnectedpages-invalid-language' => '«$1» یک کد زبان نامعتبر است.',
	'wikibase-unconnectedpages-page-warning' => 'عنوان صفحه برای کوئری استفاده نمی‌شود و نادیده گرفته شده‌است.',
	'wikibase-unconnectedpages-iwdata-label' => 'فقط صفحات با پیوند میان‌ویکی',
	'wikibase-unconnectedpages-format-row' => '( $1  پیوند زبان بر روی صفحه)',
);

/** Finnish (suomi)
 * @author Crt
 * @author Harriv
 * @author Nike
 * @author Silvonen
 * @author Stryn
 * @author VezonThunder
 */
$messages['fi'] = array(
	'wikibase-client-desc' => 'Wikibase-laajennuksen asiakasohjelma',
	'specialpages-group-wikibaseclient' => 'Wikidata-asiakasohjelma',
	'wikibase-after-page-move' => 'Voit myös [$1 päivittää] sivuun liittyvän Wikidatan kohteen säilyttääksesi kielilinkit siirretyllä sivulla.',
	'wikibase-comment-remove' => 'Sivuun liittyvä Wikidata-kohde poistettu. Kielilinkit poistettu.',
	'wikibase-comment-linked' => 'Wikidata-kohde liitettiin tähän sivuun.',
	'wikibase-comment-unlink' => 'Tämä sivu ei ole enää liitettynä Wikidata-kohteeseen. Kielilinkit poistettu.',
	'wikibase-comment-restore' => 'Sivuun liittyvä Wikidata-kohde palautettu. Kielilinkit palautettu.',
	'wikibase-comment-update' => 'Wikidata-kohdetta muutettu',
	'wikibase-comment-sitelink-add' => 'Kielilinkki lisätty: $1',
	'wikibase-comment-sitelink-change' => 'Kielilinkki $1 muutettu muotoon $2',
	'wikibase-comment-sitelink-remove' => 'Kielilinkki poistettu: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|muutos|muutosta}}',
	'wikibase-editlinks' => 'Muokkaa linkkejä',
	'wikibase-editlinkstitle' => 'Muokkaa kieltenvälisiä linkkejä',
	'wikibase-linkitem-addlinks' => 'Lisää linkkejä',
	'wikibase-linkitem-alreadylinked' => 'Sivu, johon halusit linkittää, on jo liitetty keskustietovaraston [$1 kohteeseen], joka on linkitetty tämän sivuston sivuun $2. Kohteisiin voidaan liittää vain yksi sivu kustakin sivustosta. Linkitä johonkin muuhun sivuun.',
	'wikibase-linkitem-close' => 'Sulje ikkuna ja lataa sivu uudelleen',
	'wikibase-linkitem-failure' => 'Annettua sivua linkitettäessä tapahtui tuntematon virhe.',
	'wikibase-linkitem-title' => 'Linkitä sivuun',
	'wikibase-linkitem-linkpage' => 'Linkitä sivuun',
	'wikibase-linkitem-selectlink' => 'Valitse sivusto ja sivu, johon haluat linkittää tämän sivun.',
	'wikibase-linkitem-input-site' => 'Kieli',
	'wikibase-linkitem-input-page' => 'Sivu',
	'wikibase-linkitem-invalidsite' => 'Tuntematon tai virheellinen sivusto valittu',
	'wikibase-linkitem-confirmitem-text' => 'Valitsemasi sivu on jo liitetty [$1 keskustietovaraston kohteeseen]. Vahvista, että haluat linkittää tämän sivun alla näkyviin sivuihin.',
	'wikibase-linkitem-confirmitem-button' => 'Vahvista',
	'wikibase-linkitem-not-loggedin-title' => 'Sinun täytyy olla sisäänkirjautuneena',
	'wikibase-linkitem-not-loggedin' => 'Sinun täytyy olla kirjautuneena tässä wikissä ja [$1 keskustietovarastossa] käyttääksesi tätä toimintoa.',
	'wikibase-linkitem-success-create' => 'Sivujen linkittäminen onnistui. Uusi, linkit sisältävä kohde on [$1 keskustietovarastossa].',
	'wikibase-linkitem-success-link' => 'Sivujen linkittäminen onnistui. Linkit sisältävä kohde on [$1 keskustietovarastossa].',
	'wikibase-property-notfound' => 'Ominaisuutta $1 ei löydy.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Näytä Wikidata-muokkaukset tuoreissa muutoksissa',
	'wikibase-watchlist-show-changes-pref' => 'Näytä Wikidata-muokkaukset tarkkailulistallasi',
	'special-unconnectedpages' => 'Sivut, joita ei ole liitetty kohteisiin',
	'wikibase-unconnectedpages-legend' => 'Liittämättömien sivujen valinnat',
	'wikibase-unconnectedpages-page' => 'Aloita tulokset sivusta',
	'wikibase-unconnectedpages-submit' => 'Siirry',
	'wikibase-unconnectedpages-invalid-language' => '”$1” ei ole kelvollinen kielikoodi.',
	'wikibase-unconnectedpages-page-warning' => 'Sivun otsikkoa ei voitu käyttää kyselyssä ja se jätettiin huomiotta.',
	'wikibase-unconnectedpages-iwdata-label' => 'Sivut, joilla on kieltenvälisiä linkkejä',
	'wikibase-unconnectedpages-format-row' => '($1 kieltenvälistä linkkiä sivulla)',
);

/** French (français)
 * @author Crochet.david
 * @author Gomoko
 * @author Linedwell
 * @author Ltrlg
 * @author Peter17
 * @author Wyz
 */
$messages['fr'] = array(
	'wikibase-client-desc' => 'Client pour l’extension Wikibase',
	'specialpages-group-wikibaseclient' => 'Client Wikidata',
	'wikibase-after-page-move' => 'Vous pouvez aussi [$1 mettre à jour] l’élément Wikidata associé pour conserver les liens de langue sur la page déplacée.',
	'wikibase-comment-remove' => 'Élément Wikidata associé supprimé. Liens de langue supprimés.',
	'wikibase-comment-linked' => 'Un élément Wikidata a été lié à cette page.',
	'wikibase-comment-unlink' => 'Cette page a été dissociée de l’élément Wikidata. Liens de langue supprimés.',
	'wikibase-comment-restore' => 'Suppression de l’élément Wikidata associé annulée. Liens de langue rétablis.',
	'wikibase-comment-update' => "L'élément Wikidata a changé",
	'wikibase-comment-sitelink-add' => 'Lien de langue ajouté : $1',
	'wikibase-comment-sitelink-change' => 'Lien de langue modifié de $1 à $2',
	'wikibase-comment-sitelink-remove' => 'Lien de langue supprimé : $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|modification|modifications}}',
	'wikibase-editlinks' => 'Modifier les liens',
	'wikibase-editlinkstitle' => 'Modifier les liens interlangue',
	'wikibase-linkitem-addlinks' => 'Ajouter des liens',
	'wikibase-linkitem-alreadylinked' => 'La page à laquelle vous voulez vous lier est déjà attachée à un [$1 élément] du dépôt de données central qui se lie à $2 sur ce site. Les éléments ne peuvent avoir qu’une page attachée par site. Veuillez choisir une autre page pour vous lier avec.',
	'wikibase-linkitem-close' => 'Fermer la boîte de dialogue et recharger la page',
	'wikibase-linkitem-failure' => 'Une erreur inconnue est survenue en essayant de lier la page fournie.',
	'wikibase-linkitem-title' => 'Se lier avec la page',
	'wikibase-linkitem-linkpage' => 'Lien avec la page',
	'wikibase-linkitem-selectlink' => 'Veuillez sélectionner un site et une page avec laquelle vous voulez lier cette page.',
	'wikibase-linkitem-input-site' => 'Langue:',
	'wikibase-linkitem-input-page' => 'Page:',
	'wikibase-linkitem-invalidsite' => 'Site sélectionné inconnu ou invalide',
	'wikibase-linkitem-confirmitem-text' => 'La page que vous avez choisie est déjà liée à un [$1 élément dans notre dépôt de données central]. Veuillez confirmer que les pages affichées ci-dessous sont celles que vous voulez lier avec cette page.',
	'wikibase-linkitem-confirmitem-button' => 'Confirmer',
	'wikibase-linkitem-not-loggedin-title' => 'Vous devez être connecté',
	'wikibase-linkitem-not-loggedin' => 'Vous devez être connecté sur ce wiki et dans l’[$1 entrepôt central de données] pour utiliser cette fonctionnalité.',
	'wikibase-linkitem-success-create' => 'Les pages ont bien été liées. Vous pouvez trouver l’élément nouvellement créé contenant les liens dans notre [$1 entrepôt central de données].',
	'wikibase-linkitem-success-link' => 'Les pages ont bien été liées. Vous pouvez trouver l’élément contenant les liens dans notre [$1 entrepôt central de données].',
	'wikibase-property-notfound' => 'Propriété $1 non trouvée.',
	'wikibase-property-notsupportedyet' => 'Wikibase ne supporte pas encore de type de propriété pour la propriété $1.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Afficher les modifications de Wikidata dans les modifications récentes',
	'wikibase-watchlist-show-changes-pref' => 'Voir les modifications de Wikidata dans votre liste de suivi',
	'wikibase-error-invalid-entity-id' => 'L’ID saisi est inconnu du système. Veuillez utiliser un ID d’entité valide.',
	'special-unconnectedpages' => 'Pages non reliées à l’élément',
	'wikibase-unconnectedpages-legend' => 'Options de pages non reliées',
	'wikibase-unconnectedpages-page' => 'Début de la liste des résultats avec la page :',
	'wikibase-unconnectedpages-submit' => 'Démarrer',
	'wikibase-unconnectedpages-invalid-language' => '« $1 » n’est pas un code de langue valide.',
	'wikibase-unconnectedpages-page-warning' => 'Le titre de la page ne peut pas être utilisé pour la recherche et est ignoré',
	'wikibase-unconnectedpages-iwdata-label' => 'Uniquement les pages avec des liens inter-langues',
	'wikibase-unconnectedpages-format-row' => '($1 liens inter-langues sur la page)',
);

/** Franco-Provençal (arpetan)
 * @author ChrisPtDe
 */
$messages['frp'] = array(
	'wikibase-comment-update' => 'Lims de lengoua betâs a jorn.',
	'wikibase-comment-sitelink-add' => 'Lim de lengoua apondu : $1',
	'wikibase-comment-sitelink-change' => 'Lim de lengoua changiê de $1 a $2',
	'wikibase-comment-sitelink-remove' => 'Lim de lengoua enlevâ : $1',
	'wikibase-editlinks' => 'Changiér los lims',
	'wikibase-editlinkstitle' => 'Changiér los lims entèrlengoua',
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Montrar los changements de Wikidata dedens los dèrriérs changements',
);

/** Galician (galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'wikibase-client-desc' => 'Cliente para a extensión Wikibase',
	'specialpages-group-wikibaseclient' => 'Cliente do Wikidata',
	'wikibase-after-page-move' => 'Tamén pode [$1 actualizar] o elemento asociado do Wikidata para manter as ligazóns lingüísticas na páxina trasladada.',
	'wikibase-comment-remove' => 'Borrouse un elemento asociado do Wikidata. Elimináronse as ligazóns lingüísticas.',
	'wikibase-comment-linked' => 'Esta páxina foi ligada desde un elemento do Wikidata.',
	'wikibase-comment-unlink' => 'Esta páxina foi desligada do elemento asociado do Wikidata. Elimináronse as ligazóns lingüísticas.',
	'wikibase-comment-restore' => 'Restaurouse un elemento asociado do Wikidata. Recuperáronse as ligazóns lingüísticas.',
	'wikibase-comment-update' => 'Cambiou o elemento do Wikidata',
	'wikibase-comment-sitelink-add' => 'Engadiuse unha ligazón lingüística: $1',
	'wikibase-comment-sitelink-change' => 'Cambiouse unha ligazón lingüística de $1 a $2',
	'wikibase-comment-sitelink-remove' => 'Eliminouse unha ligazón lingüística: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|modificación|modificacións}}',
	'wikibase-editlinks' => 'Editar as ligazóns',
	'wikibase-editlinkstitle' => 'Editar as ligazóns interlingüísticas',
	'wikibase-linkitem-addlinks' => 'Engadir ligazóns',
	'wikibase-linkitem-alreadylinked' => 'A páxina coa que quería ligar xa está anexada a un [$1 elemento] no repositorio central de datos, que liga con "$2" neste sitio. Os elementos unicamente poden ter unha páxina por sitio. Escolla unha páxina diferente coa que ligar.',
	'wikibase-linkitem-close' => 'Pechar o diálogo e recargar a páxina',
	'wikibase-linkitem-failure' => 'Houbo un erro ao intentar ligar a páxina achegada.',
	'wikibase-linkitem-title' => 'Ligar coa páxina',
	'wikibase-linkitem-linkpage' => 'Ligar coa páxina',
	'wikibase-linkitem-selectlink' => 'Seleccione o sitio e a páxina coa que queira ligar esta páxina.',
	'wikibase-linkitem-input-site' => 'Lingua:',
	'wikibase-linkitem-input-page' => 'Páxina:',
	'wikibase-linkitem-invalidsite' => 'Seleccionouse un sitio descoñecido ou non válido',
	'wikibase-linkitem-confirmitem-text' => 'A páxina que escolleu xa está ligada cun [$1 elemento do noso respositorio central de datos]. Confirme que as páxinas que aparecen a continuación son aquelas que quere ligar con esta páxina.',
	'wikibase-linkitem-confirmitem-button' => 'Confirmar',
	'wikibase-linkitem-not-loggedin-title' => 'Cómpre acceder ao sistema',
	'wikibase-linkitem-not-loggedin' => 'Debe acceder ao sistema neste wiki e no [$1 repositorio central de datos] para utilizar esta característica.',
	'wikibase-linkitem-success-create' => 'As páxinas ligáronse correctamente. Pode atopar o novo elemento coas ligazóns no noso [$1 repositorio central de datos].',
	'wikibase-linkitem-success-link' => 'As páxinas ligáronse correctamente. Pode atopar o elemento coas ligazóns no noso [$1 repositorio central de datos].',
	'wikibase-property-notfound' => 'Non se atopou a propiedade "$1".',
	'wikibase-property-notsupportedyet' => 'Wikibase aínda non soporta o tipo de propiedade da propiedade "$1".',
	'wikibase-rc-hide-wikidata' => '$1 o Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Mostrar as modificacións do Wikidata nos cambios recentes',
	'wikibase-watchlist-show-changes-pref' => 'Mostrar as modificacións do Wikidata na súa lista de vixilancia',
	'wikibase-error-invalid-entity-id' => 'O sistema descoñece o ID inserido. Utilice un identificador de entidade válido.',
	'special-unconnectedpages' => 'Páxinas non conectadas con elementos',
	'wikibase-unconnectedpages-legend' => 'Opcións para as páxinas non conectadas',
	'wikibase-unconnectedpages-page' => 'Comezar a lista de resultados coa páxina:',
	'wikibase-unconnectedpages-submit' => 'Continuar',
	'wikibase-unconnectedpages-invalid-language' => '"$1" non é un código de lingua válido.',
	'wikibase-unconnectedpages-page-warning' => 'O título da páxina non se puido utilizar para a pescuda e ignorouse.',
	'wikibase-unconnectedpages-iwdata-label' => 'Só as páxinas con ligazóns interlingüísticas',
	'wikibase-unconnectedpages-format-row' => '($1 ligazóns interlingüísticas na páxina)',
);

/** Swiss German (Alemannisch)
 * @author Als-Holder
 */
$messages['gsw'] = array(
	'wikibase-client-desc' => 'Macht e Client fir d Erwyterig Wikibase megli',
	'wikibase-after-page-move' => 'Du chasch au s zuegordnet Wikidata-Elemänt [$1 aktualisiere], go Sprochlink vu verschobene Syte verwalte.',
	'wikibase-editlinks' => 'Links bearbeite',
	'wikibase-editlinkstitle' => 'Sprachibergryfigi Link bearbeite',
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
);

/** Gujarati (ગુજરાતી)
 * @author KartikMistry
 */
$messages['gu'] = array(
	'wikibase-unconnectedpages-submit' => 'જાઓ',
);

/** Hebrew (עברית)
 * @author Amire80
 * @author ExampleTomer
 * @author ערן
 */
$messages['he'] = array(
	'wikibase-client-desc' => 'לקוח להרחבה Wikibase',
	'specialpages-group-wikibaseclient' => 'לקוח ויקינתונים',
	'wikibase-after-page-move' => 'אפשר גם [$1 לעדכן] את הפריט המשויך בוויקינתונים כדי לתחזק את קישורי השפה בדף שהועבר.',
	'wikibase-comment-remove' => 'פריט הוויקינתונים המשויך נמחק. קישורי שפה הוסרו.',
	'wikibase-comment-linked' => 'פריט ויקינתונים קוּשר לדף הזה.',
	'wikibase-comment-unlink' => 'הדף הזה נותק מִפריט ויקינתונים. קישורי השפה הוסרו.',
	'wikibase-comment-restore' => 'פריט הוויקינתונים המשויך שוחזר. קישורי השפה שוחזרו.',
	'wikibase-comment-update' => 'פריט ויקינתונים השתנה',
	'wikibase-comment-sitelink-add' => 'קישור שפה נוסף: $1',
	'wikibase-comment-sitelink-change' => 'קישור השפה שוּנה מ־$1 אל $2',
	'wikibase-comment-sitelink-remove' => 'קישור השפה הוסר: $1',
	'wikibase-comment-multi' => '{{PLURAL:$1|שינוי אחד|$1 שינויים}}',
	'wikibase-editlinks' => 'עריכת קישורים',
	'wikibase-editlinkstitle' => 'עריכת קישורים בין־לשוניים',
	'wikibase-linkitem-addlinks' => 'הוספת קישורים',
	'wikibase-linkitem-alreadylinked' => 'הדף שניסית לקשר אליו כבר משויך ל[$1 פריט] במאגר הנתונים המרכזי שמקשר אל $2 באתר הזה. אפשר לשייך רק דף אחד לפריט. נא לבחור דף אחר.',
	'wikibase-linkitem-close' => 'סגירה ורענון',
	'wikibase-linkitem-failure' => 'שגיאה בלתי־ידועה אירעה בעת ניסיון לקשר את הדף הנתון.',
	'wikibase-linkitem-title' => 'קישור עם דף',
	'wikibase-linkitem-linkpage' => 'קישור עם דף',
	'wikibase-linkitem-selectlink' => 'נא לבחור אתר ודף שאליו ברצונכם לקשר את הדף הזה.',
	'wikibase-linkitem-input-site' => 'שפה:',
	'wikibase-linkitem-input-page' => 'דף:',
	'wikibase-linkitem-invalidsite' => 'בחרת אתר בלתי־ידוע או בלתי־תקין',
	'wikibase-linkitem-confirmitem-text' => 'הדף שבחרת כבר מקושר ל[$1 פריט במאגר הנתונים המרכזי]. נא לאשר שהדפים להלן הם אלה בשרצית לקשר אל הדף הזה.',
	'wikibase-linkitem-confirmitem-button' => 'אישור',
	'wikibase-linkitem-not-loggedin-title' => 'יש להיכנס לחשבון',
	'wikibase-linkitem-not-loggedin' => 'יש להיכנס לחשבון בוויקי הזה וב[$1 מאגר הנתונים המרכזי] כדי להשתמש באפשרות הזאת.',
	'wikibase-linkitem-success-create' => 'הדפים קושרו בהצלחה. אפשר למצוא את הפריט החדש שמכיל את הקישורים ב[$1 מאגר הנתונים המרכזי].',
	'wikibase-linkitem-success-link' => 'הדפים קושרו בהצלחה. אפשר למצוא את הפריט החדש שמכיל את הקישורים ב[$1 מאגר הנתונים המרכזי].',
	'wikibase-property-notfound' => 'המאפיין $1 לא נמצא.',
	'wikibase-property-notsupportedyet' => 'ההרחבה ויקיביסס עדיין לא תומכת בסוג המאפיין של המאפיין $1.',
	'wikibase-rc-hide-wikidata' => '$1 ויקינתונים',
	'wikibase-rc-show-wikidata-pref' => 'הצגת עריכות ויקינתונים בשינויים אחרונים',
	'wikibase-watchlist-show-changes-pref' => 'להציג עריכות בוויקינתונים ברשימת המעקב שלך',
	'wikibase-error-invalid-entity-id' => 'המזהה שהוזן אינו ידוע למערכת. נא להשתמש במזהה ישות תקין.',
	'special-unconnectedpages' => 'דפים שלא מקושרים לפריטים',
	'wikibase-unconnectedpages-legend' => 'אפשרויות לדפים לא מקושרים',
	'wikibase-unconnectedpages-page' => 'להתחיל את רשימת התוצאות עם הדף:',
	'wikibase-unconnectedpages-submit' => 'הרצה',
	'wikibase-unconnectedpages-invalid-language' => '"$1" אינו קוד שפה תקין.',
	'wikibase-unconnectedpages-page-warning' => 'לא ניתן היה להשתמש בכותרת הדף לשאילתה והמערכת תתעלם ממנה.',
	'wikibase-unconnectedpages-iwdata-label' => 'רק דפים עם קישורים לשפות אחרות',
	'wikibase-unconnectedpages-format-row' => '($1  קישורים לשפות אחרות בדף)',
	'wikibase-pageinfo-entity-id' => 'מזהה פריט בוויקינתונים',
	'wikibase-pageinfo-entity-id-none' => 'אין',
);

/** Hindi (हिन्दी)
 * @author Bill william compton
 */
$messages['hi'] = array(
	'wikibase-client-desc' => 'विकिबेस एक्सटेंशन के लिए क्लाइन्ट',
	'wikibase-after-page-move' => 'स्थान्तरित पृष्ठ पर भाषा कड़ियो को बनाए रखने के लिए आप सम्बन्धित विकिडेटा आइटम का भी [$1 अद्यतन] कर सकते हैं।',
	'wikibase-comment-remove' => 'सम्बन्धित विकिडेटा आइटम हटाया गया। भाषा कड़ियाँ हटाईं।',
	'wikibase-comment-linked' => 'विकिडेटा आइटम इस पृष्ठ के साथ जोड़ा गया।',
	'wikibase-comment-unlink' => 'इस पृष्ठ को विकिडेटा आइटम से हटा दिया गया। भाषा कड़ियाँ हटाईं।',
	'wikibase-comment-restore' => 'सम्बन्धित विकिडेटा आइटम अनष्ट किया गया। भाषा कड़ियाँ बहाल हुईं।',
	'wikibase-comment-update' => 'भाषा कड़ियाँ अद्यतन हुईं।',
	'wikibase-comment-sitelink-add' => 'भाषा कड़ी जोड़ी: $1',
	'wikibase-comment-sitelink-change' => 'भाषा कड़ी $1 से $2 में बदल दी गई',
	'wikibase-comment-sitelink-remove' => 'भाषा कड़ी हटा दी: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|परिवर्तन}}',
	'wikibase-editlinks' => 'कड़ी संपादित करें',
	'wikibase-editlinkstitle' => 'अन्तरभाषा कड़ी संपादित करें',
	'wikibase-linkitem-addlinks' => 'कड़ी जोड़ें',
	'wikibase-linkitem-alreadylinked' => 'पृष्ठ जिसे आप जोड़ना चाहते थे वह पहले से ही केन्द्रीय डेटा रिपोज़िटरी के [$1 आइटम] से जुड़ा हुआ है जो इस साइट के $2 से जुड़ता है। आइटम में एक साइट का केवल एक ही पृष्ठ जुड़ सकता है। कृपया किसी अन्य पृष्ठ को जोड़ने के लिए चुने।',
	'wikibase-linkitem-close' => 'संवाद बंद करें और पृष्ठ पुनः लोड करें',
	'wikibase-linkitem-failure' => 'दिए गए पृष्ठ को जोड़ते वक्त अज्ञात त्रुटि उत्पन्न हुई।',
	'wikibase-linkitem-title' => 'पृष्ठ के साथ कड़ी',
	'wikibase-linkitem-linkpage' => 'पृष्ठ के साथ कड़ी',
	'wikibase-linkitem-selectlink' => 'इस पृष्ठ के साथ जोड़ने के लिए कृपया साइट और पृष्ठ का चयन करें।',
	'wikibase-linkitem-input-site' => 'भाषा:',
	'wikibase-linkitem-input-page' => 'पृष्ठ:',
	'wikibase-linkitem-invalidsite' => 'अज्ञात या अमान्य साइट का चयन',
	'wikibase-linkitem-confirmitem-text' => 'जिस पृष्ठ को आपने चुना था वह [हमारी केन्द्रीय डेटा रिपोज़िटरी के $1 आइटम] के साथ पहले से ही जुड़ा हुआ है। कृपया सुनिश्चित करें कि नीचे दिखाए गए पृष्ठ वही हैं जिन्हें आप इस पृष्ठ के साथ जोड़ना चाहते है।',
	'wikibase-linkitem-confirmitem-button' => 'पुष्टि करें',
	'wikibase-linkitem-not-loggedin-title' => 'आप को लॉग इन होने की आवश्यकता है',
	'wikibase-linkitem-not-loggedin' => 'इस सुविधा के उपयोग के लिए आपका इस विकि और [$1 केन्द्रीय डेटा रिपोज़िटरी] पर लॉग इन होना आवश्यक है।',
	'wikibase-linkitem-success-create' => 'पृष्ठ सफलतापूर्वक जुड़ गया है। आप नव निर्मित कड़ियाँ युक्त आइटम को हमारी [$1 केन्द्रीय डेटा रिपोज़िटरी] में पा सकते हैं।',
	'wikibase-linkitem-success-link' => 'पृष्ठ सफलतापूर्वक जुड़ गया है। आप कड़ियाँ युक्त आइटम को हमारी [$1 केन्द्रीय डेटा रिपोज़िटरी] में पा सकते हैं।',
	'wikibase-rc-hide-wikidata' => '$1विकिडेटा',
	'wikibase-rc-show-wikidata-pref' => 'विकिडेटा संपादनो को हाल के परिवर्तनों में दिखाएँ',
);

/** Croatian (hrvatski)
 * @author Roberta F.
 * @author SpeedyGonsales
 */
$messages['hr'] = array(
	'wikibase-editlinks' => 'Uredi međuwikije',
	'wikibase-linkitem-addlinks' => 'Dodaj međuwikije',
	'wikibase-linkitem-alreadylinked' => 'Stranica koju ste željeli povezati već je povezana na [$1 međuwiki] na wikidati koja vodi na $2 na ovom projektu. Može postojati samo jedan međuwiki. Molimo izaberite drugu stranicu.',
	'wikibase-linkitem-close' => 'Zatvori dijalog i ponovno učitaj stranicu',
	'wikibase-linkitem-title' => 'Uređivanje međuwikipoveznica',
	'wikibase-linkitem-linkpage' => 'Snimi međuwikipoveznice',
	'wikibase-linkitem-selectlink' => 'Molimo odaberite projekt i stranicu koju želite povezati.',
	'wikibase-linkitem-input-site' => 'Jezik:',
	'wikibase-linkitem-input-page' => 'Ime stranice:',
	'wikibase-linkitem-confirmitem-button' => 'Potvrdi',
	'wikibase-linkitem-not-loggedin-title' => 'Morate biti prijavljeni',
	'wikibase-linkitem-success-create' => 'Stranice su uspješno povezane. Možete pronaći ove nove poveznice na projektu [$1 wikidata].',
);

/** Upper Sorbian (hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'wikibase-client-desc' => 'Klient za rozšěrjenje Wikibase',
	'wikibase-after-page-move' => 'Móžeš tež přirjadowany element Wikidata [$1 aktualizować], zo by mjezyrěčne wotkazy na přesunjenej stronje zarjadował.',
	'wikibase-comment-remove' => 'Přirjadowany element Wikidata zhašany. Mjezyrěčne wotkazy wotstronjene.',
	'wikibase-comment-linked' => 'Element z Wikidata je so z tutej stronu zwjazał.',
	'wikibase-comment-unlink' => 'Zwisk strony z elementom Wikidata je so wotstronił. Mjezyrěčne wotkazy wotstronjene.',
	'wikibase-comment-restore' => 'Přirjadowany element Wikidata zaso wobnowjeny. Mjezyrěčne wotkazy wobnowjene.',
	'wikibase-comment-update' => 'Mjezyrěčne wotkazy su so zaktualizowali.',
	'wikibase-comment-sitelink-add' => 'Mjezyrěčny wotkaz přidaty: $1',
	'wikibase-comment-sitelink-change' => 'Mjezyrěčny wotkaz změnjeny wot $1 do $2',
	'wikibase-comment-sitelink-remove' => 'Mjezyrěčny wotkaz wotstronjeny: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|změna|změnje|změny|změnow}}',
	'wikibase-editlinks' => 'Wotkazy wobdźěłać',
	'wikibase-editlinkstitle' => 'Mjezyrěčne wotkazy wobdźěłać',
	'wikibase-linkitem-addlinks' => 'Wotkazy přidać',
	'wikibase-linkitem-alreadylinked' => 'Strona, z kotrejž chceš zwjazać, słuša hižo k [$1 elementej] w centralnym datowym repozitoriju, kotryž  na $2 na tutym sydle pokazuje. Elementy móža jenož jednu stronu na sydło měć. Prošu wubjer druhu stronu, z kotrejž so zwjazuje.',
	'wikibase-linkitem-close' => 'Dialog začinić a stronu znowa začitać',
	'wikibase-linkitem-failure' => 'Při pospyće z datej stronu zwjazać, je njeznaty zmylk wustupił.',
	'wikibase-linkitem-title' => 'Ze stronu zwjazać',
	'wikibase-linkitem-linkpage' => 'Ze stronu zwjazać',
	'wikibase-linkitem-selectlink' => 'Prošu wubjer sydło a stronu, kotrejž chceš z tutej stronu zwjazać.',
	'wikibase-linkitem-input-site' => 'Rěč:',
	'wikibase-linkitem-input-page' => 'Strona:',
	'wikibase-linkitem-invalidsite' => 'Njeznate abo njepłaćiwe sydło wubrane',
	'wikibase-linkitem-confirmitem-text' => 'Strona, kotruž sy wubrał, je hižo z [$1 elementom w našim centralnym datowym repozitoriju] zwjazany. Prošu wobkruć, zo slědowace strony su te, kotrež chceš z tutej stronu zwjazać.',
	'wikibase-linkitem-confirmitem-button' => 'Wobkrućić',
	'wikibase-linkitem-not-loggedin-title' => 'Dyrbiš přizjewjeny być',
	'wikibase-linkitem-not-loggedin' => 'Dyrbiš w tutym wikiju a w [$1 centralnym datowym repozitoriju] přizjewjeny być, zo by tutu funkciju wužiwał.',
	'wikibase-linkitem-success-create' => 'Strony su so wuspěšnje zwjazali. Móžeš nowy element, kotryž wotkazy wobsahuje, w našim [$1 centralnym datowym repozitoriju] namakać.',
	'wikibase-linkitem-success-link' => 'Strony su so wuspěšnje zwjazali. Móžeš element, kotryž wotkazy wobsahuje, w našim [$1 centralnym datowym repozitoriju] namakać.',
	'wikibase-property-notfound' => 'Kajkosć $1 njeje so namakała.',
	'wikibase-property-notsupportedyet' => 'Wikibase hišće njepoděruje kajkostny typ za kajkosć $1.',
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Změny Wikidata w aktualnych změnach pokazać',
	'wikibase-watchlist-show-changes-pref' => 'Změny Wikidata w twojich wobkedźbowankach pokazać',
	'wikibase-error-invalid-entity-id' => 'Zapodaty ID je systemej njeznaty. Prošu wužij płaćiwy entitowy ID.',
);

/** Hungarian (magyar)
 * @author Tgr
 */
$messages['hu'] = array(
	'wikibase-client-desc' => 'Kliens a Wikibase kiterjesztéshez',
	'wikibase-after-page-move' => 'Ha azt akarod, hogy a nyelvközi hivatkozások megmaradjanak, [$1 frissítsd] a kapcsolt Wikidata elemet is.',
	'wikibase-comment-remove' => 'Nyelvközi hivatkozások eltávolítása – a kapcsolt Wikidata elemet törölték.',
	'wikibase-comment-linked' => 'Egy Wikidata elemet kapcsoltak ehhez az oldalhoz.',
	'wikibase-comment-unlink' => 'Nyelvközi hivatkozások eltávolítása – már nincs összekapcsolva a Wikidata elemmel.',
	'wikibase-comment-restore' => 'Nyelvközi hivatkozások visszaállítása – a hozzátartozó törölt Wikidata elemet visszaállították.',
	'wikibase-comment-update' => 'Nyelvközi hivatkozások frissítése.',
	'wikibase-comment-sitelink-add' => 'Nyelvközi hivatkozás hozzáadása: $1',
	'wikibase-comment-sitelink-change' => 'Nyelvközi hivatkozás módosítása (régi: $1, új: $2)',
	'wikibase-comment-sitelink-remove' => 'Nyelvközi hivatkozás törlése: $1',
	'wikibase-comment-multi' => '$1 változtatás', # Fuzzy
	'wikibase-editlinks' => 'szerkesztés',
	'wikibase-editlinkstitle' => 'Nyelvközi hivatkozások szerkesztése',
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Wikidata szerkesztések mutatása a friss változtatásokban',
);

/** Armenian (Հայերեն)
 * @author Vadgt
 */
$messages['hy'] = array(
	'wikibase-editlinks' => 'Փոխել հղումները',
	'wikibase-linkitem-addlinks' => 'Ստեղծել հղումը',
);

/** Interlingua (interlingua)
 * @author McDutchie
 */
$messages['ia'] = array(
	'wikibase-client-desc' => 'Cliente pro le extension Wikibase',
	'wikibase-editlinks' => 'Modificar ligamines',
	'wikibase-editlinkstitle' => 'Modificar ligamines a altere linguas',
);

/** Indonesian (Bahasa Indonesia)
 * @author Iwan Novirion
 * @author Kenrick95
 */
$messages['id'] = array(
	'wikibase-client-desc' => 'Klien untuk ekstensi Wikibase',
	'specialpages-group-wikibaseclient' => 'Klien Wikidata',
	'wikibase-after-page-move' => 'Anda juga perlu [$1 memperbarui item] yang terkait di Wikidata dalam hal interwiki saat pemindahan halaman',
	'wikibase-comment-remove' => 'Item Wikidata terkait dihapus. Interwiki dihapus.',
	'wikibase-comment-linked' => 'Item Wikidata telah tertaut ke halaman ini.',
	'wikibase-comment-unlink' => 'Halaman ini sudah tidak tertaut pada item Wikidata. Interwiki dihapus.',
	'wikibase-comment-restore' => 'Terkait pembatalan penghapusan item Wikidata, interwiki dipulihkan.',
	'wikibase-comment-update' => 'Item wikidata berubah',
	'wikibase-comment-sitelink-add' => 'Penambahan interwiki: $1',
	'wikibase-comment-sitelink-change' => 'Interwiki berubah dari $1 ke $2',
	'wikibase-comment-sitelink-remove' => 'Pengurangan interwiki: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|perubahan}}',
	'wikibase-editlinks' => 'Sunting interwiki',
	'wikibase-editlinkstitle' => 'Sunting pranala interwiki',
	'wikibase-linkitem-addlinks' => 'Tambah interwiki',
	'wikibase-linkitem-alreadylinked' => 'Halaman yang ingin Anda tautkan sudah tertaut ke [$1 item] pada pusat penyimpanan data yang terhubung pada $2 di situs ini. Item hanya dapat memiliki satu halaman per situs yang tertaut. Silakan pilih halaman lain untuk ditautkan.',
	'wikibase-linkitem-close' => 'Tutup dan muat ulang halaman',
	'wikibase-linkitem-failure' => 'Kesalahan tidak diketahui terjadi saat mencoba menautkan halaman.',
	'wikibase-linkitem-title' => 'Tautkan dengan halaman',
	'wikibase-linkitem-linkpage' => 'Tautkan dengan halaman',
	'wikibase-linkitem-selectlink' => 'Silakan pilih sebuah kode bahasa dan halaman yang ingin Anda tautkan dengan halaman ini.',
	'wikibase-linkitem-input-site' => 'Bahasa:',
	'wikibase-linkitem-input-page' => 'Halaman:',
	'wikibase-linkitem-invalidsite' => 'Wiki tidak diketahui atau tidak valid yang dipilih',
	'wikibase-linkitem-confirmitem-text' => 'Halaman yang anda pilih telah terpaut pada [$1 item di pusat penyimpanan data kami]. Silahkan dikonfirmasi halaman yang ditampilkan dibawah ini adalah halaman yang ingin anda tautkan.',
	'wikibase-linkitem-confirmitem-button' => 'Terima',
	'wikibase-linkitem-not-loggedin-title' => 'Anda harus masuk log',
	'wikibase-linkitem-not-loggedin' => 'Anda harus masuk log pada wiki ini dan pada [$1 pusat penyimpanan data] untuk menggunakan fitur ini.',
	'wikibase-linkitem-success-create' => 'Halaman telah ditautkan. Anda dapat menemukan item baru berisi tautan tersebut pada [$1 pusat penyimpanan data] kami.',
	'wikibase-linkitem-success-link' => 'Halaman telah berhasil ditautkan. Anda dapat menemukan item berisi tautan tersebut pada [$1 pusat penyimpanan data] kami.',
	'wikibase-property-notfound' => 'Properti $1 tidak ditemukan.',
	'wikibase-property-notsupportedyet' => 'Wikibase belum mendukung tipe properti untuk properti $1.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Tampilkan suntingan Wikidata di perubahan terbaru',
	'wikibase-watchlist-show-changes-pref' => 'Tampilkan suntingan Wikidata pada daftar pantauan Anda',
	'wikibase-error-invalid-entity-id' => 'ID yang dimasukkan tidak dikenal sistem. Gunakan entitas ID yang valid.',
	'special-unconnectedpages' => 'Halaman yang belum terhubung ke Wikidata',
	'wikibase-unconnectedpages-legend' => 'Pilihan halaman yang belum terhubung',
	'wikibase-unconnectedpages-page' => 'Daftar dimulai dengan artikel:',
	'wikibase-unconnectedpages-submit' => 'Lanjut',
	'wikibase-unconnectedpages-invalid-language' => '"$1" bukan kode bahasa yang valid.',
	'wikibase-unconnectedpages-page-warning' => 'Judul halaman tidak boleh digunakan untuk permintaan dan akan diabaikan.',
	'wikibase-unconnectedpages-iwdata-label' => 'Hanya halaman yang ada interwiki',
	'wikibase-unconnectedpages-format-row' => '($1 interwiki pada halaman)',
);

/** Iloko (Ilokano)
 * @author Lam-ang
 */
$messages['ilo'] = array(
	'wikibase-client-desc' => 'Kliente para iti Wikibase a pagpaatiddog',
	'specialpages-group-wikibaseclient' => 'Kliente ti Wikidata',
	'wikibase-after-page-move' => 'Mabalinmo pay a [$1 pabaruen] ti mainaig a banag ti Wikidata tapno mataripatu dagiti silpo ti pagsasao ti naiyalis a panid.',
	'wikibase-comment-remove' => 'Ti mainaig a banag ti Wikidata ket naikkaten. Dagiti silpo ti pagsasao ket naikkaten.',
	'wikibase-comment-linked' => 'Ti Wikidata a banag ket naisilpon iti daytoy a panid.',
	'wikibase-comment-unlink' => 'Daytoy a panid ket naikkat ti silpona manipud ti Wikidata a banag. Dagiti silpo ti pagsasao ket naikkaten.',
	'wikibase-comment-restore' => 'Ti mainaig a banag ti Wikidata ket naisubli ti pannakaikkatna. Dagiti silpo ti pagsasao ket naipasubli.',
	'wikibase-comment-update' => 'Nasukatan ti Widata a banag',
	'wikibase-comment-sitelink-add' => 'Nanayonan ti silpo ti pagsasao: $1',
	'wikibase-comment-sitelink-change' => 'Ti silpo ti pagsasao ket nasukatan manipud ti $1 iti $2',
	'wikibase-comment-sitelink-remove' => 'Naikkat ti silpo ti pagsasao: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|a sinukatan|a sinuksukatan}}',
	'wikibase-editlinks' => 'Urnosen dagiti silpo',
	'wikibase-editlinkstitle' => 'Urnosen dagiti sangkapagsasaoan a silpo',
	'wikibase-linkitem-addlinks' => 'Agnayon kadagiti silpo',
	'wikibase-linkitem-alreadylinked' => 'Ti panid a kinayatmo nga isilpo ket naikapeten ti maysa a [$1 banag] idiay sentral a repositorio ti datos a mangisilpo iti $2 iti daytoy a sitio. Dagiti banag mabalin laeng nga adda ti maysa a panid a naikapet ti tunggal maysa a sitio Pangngaasi nga agpili ti sabali a pangisilpuan a panid.',
	'wikibase-linkitem-close' => 'Irekep ti pagsaritaan ken ikarga manen ti panid',
	'wikibase-linkitem-failure' => 'Adda napasamak a maysa a di ammo a biddut bayat idi agisilsilpo ti naited a panid.',
	'wikibase-linkitem-title' => 'Isilpo iti panid',
	'wikibase-linkitem-linkpage' => 'Isilpo iti panid',
	'wikibase-linkitem-selectlink' => 'Pangngaasi nga agpili ti sitio ken ti maysa a panid a kayatmo nga isilpo ti daytoy a panid.',
	'wikibase-linkitem-input-site' => 'Pagsasao:',
	'wikibase-linkitem-input-page' => 'Panid:',
	'wikibase-linkitem-invalidsite' => 'Di ammo wenno saan nga umiso a sitio ti napili',
	'wikibase-linkitem-confirmitem-text' => 'Ti panid a napilim ket naisilpon iti [$1 a banag idiay sentro a pagipenpan ti datosmi]. Pangngaasi a pasingkedan a dagiti panid a naipakita dita baba ket dagiti kayatmo nga isilpo iti daytoy a panid.',
	'wikibase-linkitem-confirmitem-button' => 'Pasingkedan',
	'wikibase-linkitem-not-loggedin-title' => 'Masapul a nakastrekka',
	'wikibase-linkitem-not-loggedin' => 'Masapul a nakastrekka iti daytoy a wiki ken idiay [$1 sentro a resipotorio ti datos] tapno makausar ti daytoy a pagpilian.',
	'wikibase-linkitem-success-create' => 'Dagiti panid ket balligi a naisilpo. Mabalinmo a biruken ti baro a napartuat a banag nga aglaon kadagiti silpo idiay [$1 sentro a repositorio ti datos].',
	'wikibase-linkitem-success-link' => 'Dagiti panid ket balligi a naisilpo. Mabalinmo a biruken ti banag nga aglaon kadagiti silpo idiay [$1 sentro a resipotorio ti datos].',
	'wikibase-property-notfound' => 'Ti $1 a tagikua ket saan a nabirukan.',
	'wikibase-property-notsupportedyet' => 'Ti Wikibase ket saanna pay a suportaran ti tagikua a kita para iti $1 a tagikua.',
	'wikibase-rc-hide-wikidata' => '$1 ti Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Ipakita dagiti Wikidata nga inurnos idiay kinaudi a binalbaliwan',
	'wikibase-watchlist-show-changes-pref' => 'Ipakita dagiti Wikidata a panagurnos iti bukodmo a bambantayan',
	'wikibase-error-invalid-entity-id' => 'Ti ID a naikabil ket di ammo ti sistema. Pangngaasi nga agusar ti umiso nga entidad ti ID.',
	'special-unconnectedpages' => 'Dagiti panid a saan a naikapet kadagiti banag',
	'wikibase-unconnectedpages-legend' => 'Dagiti pagpilian kadagiti saan a naikapet a panid',
	'wikibase-unconnectedpages-page' => 'Rugian ti resulta ti listaan iti artikulo:',
	'wikibase-unconnectedpages-submit' => 'Inkan',
	'wikibase-unconnectedpages-invalid-language' => 'Ti "$1" ket saan nga umiso a kodigo ti pagsasao.',
	'wikibase-unconnectedpages-page-warning' => 'Ti titulo ti panid ket saan a mabalin a mausar iti panangbiruk ken saan a naikaskaso.',
	'wikibase-unconnectedpages-iwdata-label' => 'Dagiti laeng panid nga adda dagiti silpo ti pagsasao',
	'wikibase-unconnectedpages-format-row' => '($1 a silsilpo ti pagsasao iti daytoy a panid)',
);

/** Icelandic (íslenska)
 * @author Snævar
 */
$messages['is'] = array(
	'wikibase-client-desc' => 'Biðlari fyrir Wikibase viðbótina',
	'wikibase-after-page-move' => 'Þú mátt einnig [$1 uppfæra] viðeigandi Wikidata hlut til að viðhalda tungumálatenglum á færðu síðunni.',
	'wikibase-comment-remove' => 'Tengdum Wikidata hlut eytt. Tungumálatenglar fjarlægðir.',
	'wikibase-comment-linked' => 'Wikidata hlutur hefur tengst þessari síðu.',
	'wikibase-comment-unlink' => 'Þessi síða hefur verið aftengd Wikidata hlut. Tungumálatenglar fjarlægðir.',
	'wikibase-comment-restore' => 'Tengdur Wikidata hlutur endurvakinn. Tungumálatenglar endurvaktir.',
	'wikibase-comment-update' => 'Wikidata hlutinum var breytt.',
	'wikibase-comment-sitelink-add' => 'Tungumálatengli bætt við: $1',
	'wikibase-comment-sitelink-change' => 'Tungumálatengli breytt frá $1 í $2',
	'wikibase-comment-sitelink-remove' => 'Tungumálatengill fjarlægður: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|breyting|breytingar}}',
	'wikibase-editlinks' => 'Breyta tenglum',
	'wikibase-editlinkstitle' => 'Breyta tungumálatenglum',
	'wikibase-linkitem-addlinks' => 'Bæta við tenglum',
	'wikibase-linkitem-alreadylinked' => 'Síðan sem þú vildir tengja við er þegar tengd við [$1 hlut] á miðlægum gagnagrunni sem tengir á $2 á þessari síðu. Hlutir geta eingöngu haft eina síðu per vefsvæði. Vinsamlegast veldu aðra síðu til að tengja við.',
	'wikibase-linkitem-close' => 'Loka glugganum og endurhlaða síðunni',
	'wikibase-linkitem-failure' => 'Óþekkt villa kom upp þegar reynt var að tengja í síðuna.',
	'wikibase-linkitem-title' => 'Tengja í síðu',
	'wikibase-linkitem-linkpage' => 'Tengja í síðu',
	'wikibase-linkitem-selectlink' => 'Vinsamlegast veldu vefsvæði og síðu sem þú vilt tengja þessa síðu við.',
	'wikibase-linkitem-input-site' => 'Tungumál:',
	'wikibase-linkitem-input-page' => 'Síða:',
	'wikibase-linkitem-invalidsite' => 'Óþekkt eða ógild síða valin',
	'wikibase-linkitem-confirmitem-text' => 'Síðan sem þú valdir er þegar tengd við [$1 hlut á miðlægum gagnagrunni]. Vinsamlegast staðfestu að síðurnar fyrir neðan séu þær sem þú vilt tengja við þessa síðu.',
	'wikibase-linkitem-confirmitem-button' => 'Staðfesta',
	'wikibase-linkitem-not-loggedin-title' => 'Þú þarft að vera skráð/ur inn',
	'wikibase-linkitem-not-loggedin' => 'Þú þarft að vera skráð/ur inn á þennann wiki og á [$1 samnýtta þekkingargrunninn] til að nota þennan möguleika.',
	'wikibase-linkitem-success-create' => 'Síðurnar hafa verið tengdar saman. Þú getur fundið hlutinn, sem var nýlega búinn til með tenglunum, í [$1 samnýtta þekkingargrunninum].',
	'wikibase-linkitem-success-link' => 'Síðurnar hafa verið tengdar saman. Þú getur fundið hlutinn með tenglunum í [$1 samnýtta þekkingargrunninum].',
	'wikibase-property-notfound' => 'Eginleikinn „$1“ fannst ekki.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Sýna Wikidata breytingar í nýjustu breytingum',
	'wikibase-watchlist-show-changes-pref' => 'Sýna Wikidata breytingar í vaktlistanum þínum',
	'wikibase-error-invalid-entity-id' => 'Tilgreint auðkenni þekkist ekki. Vinsamlegast notaðu gilt auðkenni einingarinnar.',
	'special-unconnectedpages' => 'Síður sem tengjast ekki hlutum',
	'wikibase-unconnectedpages-legend' => 'Stillingar fyrir ótengdar síður',
	'wikibase-unconnectedpages-page' => 'Upphafsíða:', # Fuzzy
	'wikibase-unconnectedpages-submit' => 'Áfram',
	'wikibase-unconnectedpages-invalid-language' => '„$1” er ógildur tungumálakóði.',
	'wikibase-unconnectedpages-page-warning' => 'Síðutitilinn var ekki hægt að nota fyrir fyrirspurnina og hann var hunsaður.',
	'wikibase-unconnectedpages-iwdata-label' => 'Aðeins síður með tungumálatengla',
	'wikibase-unconnectedpages-format-row' => '($1 tungumálatenglar á síðunni)',
);

/** Italian (italiano)
 * @author Beta16
 * @author Gianfranco
 * @author Gloria sah
 * @author Raoli
 * @author Sannita
 */
$messages['it'] = array(
	'wikibase-client-desc' => "Client per l'estensione Wikibase",
	'specialpages-group-wikibaseclient' => 'Client Wikidata',
	'wikibase-after-page-move' => "Puoi anche [$1 aggiornare] l'elemento associato su Wikidata per trasferire gli interlink sulla nuova pagina.",
	'wikibase-comment-remove' => "L'elemento di Wikidata associato è stato cancellato. I collegamenti interlinguistici sono stati rimossi.",
	'wikibase-comment-linked' => 'Un elemento di Wikidata è stato collegato a questa pagina.',
	'wikibase-comment-unlink' => "Questa pagina è stata scollegata dall'elemento di Wikidata. I collegamenti interlinguistici sono stati rimossi.",
	'wikibase-comment-restore' => "L'elemento di Wikidata associato è stato recuperato. I collegamenti interlinguistici sono stati ripristinati.",
	'wikibase-comment-update' => 'Elemento Wikidata modificato',
	'wikibase-comment-sitelink-add' => 'Collegamento linguistico aggiunto: $1',
	'wikibase-comment-sitelink-change' => 'Collegamento linguistico modificato da $1 a $2',
	'wikibase-comment-sitelink-remove' => 'Collegamento linguistico rimosso: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|modifica|modifiche}}',
	'wikibase-editlinks' => 'Modifica link',
	'wikibase-editlinkstitle' => 'Modifica collegamenti interlinguistici',
	'wikibase-linkitem-addlinks' => 'Aggiungi link',
	'wikibase-linkitem-alreadylinked' => "La pagina che si vuole collegare è già associata ad un altro [$1 elemento] nell'archivio centrale dei dati che punta su $2 in questo sito. Gli elementi possono essere collegati ad una sola pagina per sito. Scegli una pagina diversa a cui collegarla.",
	'wikibase-linkitem-close' => 'Chiudi la finestra di dialogo e ricarica la pagina',
	'wikibase-linkitem-failure' => 'Si è verificato un errore sconosciuto durante il tentativo di collegare la pagina indicata.',
	'wikibase-linkitem-title' => "Collega con un'altra pagina",
	'wikibase-linkitem-linkpage' => 'Collega con la pagina',
	'wikibase-linkitem-selectlink' => 'Seleziona un sito e una pagina che vuoi collegare con questa.',
	'wikibase-linkitem-input-site' => 'Lingua:',
	'wikibase-linkitem-input-page' => 'Pagina:',
	'wikibase-linkitem-invalidsite' => 'Sito selezionato sconosciuto o non valido',
	'wikibase-linkitem-confirmitem-text' => 'La pagina che hai scelto è già collegata a un altro [$1 elemento nel nostro archivio centrale dei dati]. Conferma che le pagine mostrate qui sotto sono quelle che si desidera collegare con questa pagina.',
	'wikibase-linkitem-confirmitem-button' => 'Conferma',
	'wikibase-linkitem-not-loggedin-title' => "Devi aver effettuato l'accesso",
	'wikibase-linkitem-not-loggedin' => "Devi aver effettuato l'accesso su questo wiki e nell'[$1 archivio dati centralizzato] per utilizzare questa funzionalità.",
	'wikibase-linkitem-success-create' => "Le pagine sono state collegate correttamente. Puoi trovare l'elemento appena creato contenente i link nel nostro [$1 archivio dati centralizzato].",
	'wikibase-linkitem-success-link' => "Le pagine sono state collegate correttamente. Puoi trovare l'elemento contenente i link nel nostro [$1 archivio dati centralizzato].",
	'wikibase-property-notfound' => 'Proprietà $1 non trovata.',
	'wikibase-property-notsupportedyet' => 'Wikibase non supporta ancora il tipo di proprietà per la proprietà $1.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Mostra le modifiche di Wikidata nelle ultime modifiche',
	'wikibase-watchlist-show-changes-pref' => 'Mostra le modifiche su Wikidata nella tua lista degli osservati speciali',
	'wikibase-error-invalid-entity-id' => "L'ID specificato è sconosciuto al sistema. Utilizza un ID di entità valido.",
	'special-unconnectedpages' => 'Pagine non collegate agli elementi',
	'wikibase-unconnectedpages-legend' => 'Opzioni per le pagine non collegate',
	'wikibase-unconnectedpages-page' => "Inizia l'elenco dei risultati dalla pagina:",
	'wikibase-unconnectedpages-submit' => 'Vai',
	'wikibase-unconnectedpages-invalid-language' => '"$1" non è un codice di lingua valido.',
	'wikibase-unconnectedpages-page-warning' => 'Il titolo della pagina non può essere utilizzato per la query e viene ignorato.',
	'wikibase-unconnectedpages-iwdata-label' => 'Solo le pagine con collegamenti interlinguistici',
	'wikibase-unconnectedpages-format-row' => '($1 collegamenti interlinguistici nella pagina)',
	'wikibase-pageinfo-entity-id' => 'ID elemento Wikidata',
	'wikibase-pageinfo-entity-id-none' => 'Nessuno',
);

/** Japanese (日本語)
 * @author Fryed-peach
 * @author Shirayuki
 */
$messages['ja'] = array(
	'wikibase-client-desc' => 'Wikibase 拡張機能のクライアント',
	'specialpages-group-wikibaseclient' => 'ウィキデータのクライアント',
	'wikibase-after-page-move' => '移動されたページにある言語リンクを維持するために、関連付けられたウィキデータ項目を[$1 更新]することもできます。',
	'wikibase-comment-remove' => '関連付けられたウィキデータ項目を削除しました。言語リンクを除去しました。',
	'wikibase-comment-linked' => 'ウィキデータ項目をこのページにリンクしました。',
	'wikibase-comment-unlink' => 'このページをウィキデータ項目からリンク解除しました。言語リンクを除去しました。',
	'wikibase-comment-restore' => '関連付けられたウィキデータ項目を復元しました。言語リンクを復元しました。',
	'wikibase-comment-update' => 'ウィキデータ項目を更新しました',
	'wikibase-comment-sitelink-add' => '言語リンクを追加: $1',
	'wikibase-comment-sitelink-change' => '言語リンクを $1 から $2 に変更',
	'wikibase-comment-sitelink-remove' => '言語リンクを除去: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|件の変更}}',
	'wikibase-editlinks' => 'リンクを編集',
	'wikibase-editlinkstitle' => '言語間リンクを編集する',
	'wikibase-linkitem-addlinks' => 'リンクを追加',
	'wikibase-linkitem-alreadylinked' => 'あなたがリンクしようとしたページは既に中央データリポジトリの[$1 項目]と結びついており、このサイトの$2へリンクしています。項目には1つのサイトにつき1つのページしか結びつけることができません。別のページを選んでください。',
	'wikibase-linkitem-close' => 'ダイアログを閉じてページを再読み込み',
	'wikibase-linkitem-failure' => '指定したページをリンクする際に不明なエラーが発生しました。',
	'wikibase-linkitem-title' => 'ページとのリンク',
	'wikibase-linkitem-linkpage' => 'ページとリンク',
	'wikibase-linkitem-selectlink' => 'このページとリンクするサイトやページを選択してください。',
	'wikibase-linkitem-input-site' => '言語:',
	'wikibase-linkitem-input-page' => 'ページ:',
	'wikibase-linkitem-invalidsite' => '不明なサイトまたは無効なサイトを選択しました',
	'wikibase-linkitem-confirmitem-text' => '指定したページは既に[$1 中央データリポジトリ上の項目]とリンクされています。このページと以下に列挙したページをリンクしていいか確認してください。',
	'wikibase-linkitem-confirmitem-button' => '確認',
	'wikibase-linkitem-not-loggedin-title' => 'ログインする必要があります',
	'wikibase-linkitem-not-loggedin' => 'この機能を使用するには、このウィキおよび[$1 中央データリポジトリ]の両方にログインする必要があります。',
	'wikibase-linkitem-success-create' => 'ページのリンクに成功しました。リンクを含んで新しく作成された項目は[$1 中央データリポジトリ]にあります。',
	'wikibase-linkitem-success-link' => 'ページのリンクに成功しました。リンクを含んだ項目は[$1 中央データリポジトリ]にあります。',
	'wikibase-property-notfound' => 'プロパティ $1 が見つかりません。',
	'wikibase-property-notsupportedyet' => 'ウィキベースはプロパティ「$1」のプロパティ種別にまだ対応していません。',
	'wikibase-rc-hide-wikidata' => 'ウィキデータを$1',
	'wikibase-rc-show-wikidata-pref' => '最近の更新にウィキデータの編集を表示',
	'wikibase-watchlist-show-changes-pref' => 'ウィキデータの編集をウォッチリストに表示',
	'wikibase-error-invalid-entity-id' => '入力した ID はシステムに登録されていません。有効なエンティティ ID を使用してください。',
	'special-unconnectedpages' => '項目に関連付けられていないページ',
	'wikibase-unconnectedpages-legend' => '関連付けられていないページのオプション',
	'wikibase-unconnectedpages-page' => '検索結果の先頭にするページ:',
	'wikibase-unconnectedpages-submit' => '表示',
	'wikibase-unconnectedpages-invalid-language' => '「$1」は有効な言語コードではありません。',
	'wikibase-unconnectedpages-page-warning' => 'ページ名は検索に使用できなかったため無視します。',
	'wikibase-unconnectedpages-iwdata-label' => '言語間リンクがあるページのみ',
	'wikibase-unconnectedpages-format-row' => '(ページ内に $1 件の言語間リンク)',
	'wikibase-pageinfo-entity-id' => 'ウィキデータ項目 ID',
	'wikibase-pageinfo-entity-id-none' => 'なし',
);

/** Georgian (ქართული)
 * @author David1010
 */
$messages['ka'] = array(
	'wikibase-client-desc' => 'ვიკიბაზის გაფართოების კლიენტი',
	'specialpages-group-wikibaseclient' => 'ვიკიმონაცემების კლიენტი',
	'wikibase-after-page-move' => 'გადარქმეულ გვერდზე ენის ბმულების გასასწორებლად, თქვენ აგრეთვე შეგიძლიათ [$1 განაახლოთ] ვიკიმონაცემების დაკავშირებული ელემენტი.',
	'wikibase-comment-remove' => 'ვიკიმონაცემების დაკავშირებული ელემენტი წაშლილია. ენის ბმულები წაშლია.',
	'wikibase-comment-linked' => 'ვიკიმონაცემების ელემენტი დაკავშირებული იყო ამ გვერდთან.',
	'wikibase-comment-unlink' => 'ამ გვერდის კავშირი ვიკიმონაცემების ელემენტთან გაწყვეტილია. ენის ბმულები წაშლილია.',
	'wikibase-comment-restore' => 'ვიკიმონაცემებთან დაკავშირებული ელემენტის წაშლა გაუქმებულია. ენის ბმულები აღდგენილია.',
	'wikibase-comment-update' => 'ვიკიმონაცემების ელემენტი შეცვლილია',
	'wikibase-comment-sitelink-add' => 'ენის ბმული დამატებულია: $1',
	'wikibase-comment-sitelink-change' => 'ენის ბმული შეცვლილია $1-დან $2-ზე',
	'wikibase-comment-sitelink-remove' => 'ენის ბმული წაშლილია: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|ცვლილება|ცვლილება}}',
	'wikibase-editlinks' => 'ბმულების რედაქტირება',
	'wikibase-editlinkstitle' => 'ენათშორისი ბმულების რედაქტირება',
	'wikibase-linkitem-addlinks' => 'ბმულების დამატება',
	'wikibase-linkitem-alreadylinked' => 'გვერდი, რომელთან დაკავშირებასაც ცდილობდით, უკვე მიბმულია [$1 ელემენტთან] მონაცემების ცენტრალურ საცავში, რომელიც მისამართდება $2-ზე ამ საიტზე. ელემენტებს შესაძლოა ჰქონდეთ მხოლოდ თითო მიბმული გვერდი ყოველი საიტიდან. გთხოვთ, კავშირის დასამყარებლად აირჩიოთ სხვა გვერდი.',
	'wikibase-linkitem-close' => 'დიალოგის დახურვა და გვერდის გადატვირთვა',
	'wikibase-linkitem-failure' => 'აღნიშნული გვერდის მიბმის მცდელობისას მოხდა უცნობი შეცდომა.',
	'wikibase-linkitem-title' => 'კავშირი გვერდიდან',
	'wikibase-linkitem-linkpage' => 'კავშირი გვერდიდან',
	'wikibase-linkitem-selectlink' => 'გთხოვთ, აირჩიოთ საიტი და გვერდი, რომელზეც გსურთ აქედან ბმულის გაკეთება.',
	'wikibase-linkitem-input-site' => 'ენა:',
	'wikibase-linkitem-input-page' => 'გვერდი:',
	'wikibase-linkitem-invalidsite' => 'არჩეულია უცნობი ან არაკორექტული საიტი',
	'wikibase-linkitem-confirmitem-text' => 'თქვენ მიერ არჩეული გვერდი უკვე დაკავშირებულია [$1 ჩვენი მონაცემების ცენტრალური რეპოზიტორიის ელემენტთან]. გთხოვთ, დაადასტუროთ, რომ ქვემოთ ნაჩვენებ გვერდებს შორის არის ის, რომელზეც გსურთ ბმულის გაკეთება აქედან.',
	'wikibase-linkitem-confirmitem-button' => 'დადასტურება',
	'wikibase-linkitem-not-loggedin-title' => 'თქვენ უნდა გაიაროთ ავტორიზაცია',
	'wikibase-linkitem-not-loggedin' => 'ამ ფუნქციის გამოყენებისათვის, თქვენ უნდა გაიაროთ ავტორიზაცია ამ ვიკისა და [$1 მონაცემების ცენტრალურ რეპოზიტორიაში].',
	'wikibase-linkitem-success-create' => 'გვერდი წარმატებით დაუკავშირდა. კავშირის შემცველი ახლად შექმნილი ელემენტი, მდებარეობს ჩვენი [$1 მონაცემების ცენტრალურ საცავში].',
	'wikibase-linkitem-success-link' => 'გვერდები წარმატებით დაუკავშირდნენ. კავშირის შემცველი ელემენტი, მდებარეობს ჩვენი [$1 მონაცემების ცენტრალურ საცავში].',
	'wikibase-property-notfound' => 'თვისება „$1“ ვერ მოიძებნა.',
	'wikibase-property-notsupportedyet' => 'ვიკიბაზას ჯერ არ აქვს $1 თვისების ტიპის მხარდაჭერა.',
	'wikibase-rc-hide-wikidata' => 'ვიკიმონაცემების $1',
	'wikibase-rc-show-wikidata-pref' => 'ვიკიმონაცემების რედაქტირებების ჩვენება ბოლო ცვლილებების სიაში',
	'wikibase-watchlist-show-changes-pref' => 'ვიკიმონაცემების რედაქტირებების ჩვენება თქვენი კონტროლის სიაში',
	'wikibase-error-invalid-entity-id' => 'მითითებული ID სისტემისთვის უცნობია. გთხოვთ, გამოიყენოთ მოქმედი ID.',
	'special-unconnectedpages' => 'ელემენტებთან დაუკავშირებელი გვერდები',
	'wikibase-unconnectedpages-legend' => 'დაუკავშირებელი გვერდების პარამეტრები',
	'wikibase-unconnectedpages-page' => 'გვერდის დასაწყისი:',
	'wikibase-unconnectedpages-submit' => 'მიდი',
	'wikibase-unconnectedpages-invalid-language' => '„$1“ არ არის ენის სწორი კოდი.',
	'wikibase-unconnectedpages-iwdata-label' => 'მხოლოდ ენათშორისი ბმულების მქონე გვერდები',
	'wikibase-unconnectedpages-format-row' => '($1 ენათშორისი ბმული გვერდზე)',
);

/** Korean (한국어)
 * @author ChongDae
 * @author Kwj2772
 * @author 아라
 */
$messages['ko'] = array(
	'wikibase-client-desc' => '위키베이스 확장 기능을 위한 클라이언트',
	'specialpages-group-wikibaseclient' => '위키데이터 클라이언트',
	'wikibase-after-page-move' => '또한 이동한 문서에 언어 링크를 유지하기 위해 관련된 위키데이터 항목을 [$1 업데이트]할 수 있습니다.',
	'wikibase-comment-remove' => '연결한 위키데이터 항목을 삭제했습니다. 언어 링크를 제거했습니다.',
	'wikibase-comment-linked' => '위키데이터 항목을 이 문서에 연결했습니다.',
	'wikibase-comment-unlink' => '이 문서는 위키데이터 항목에 연결하지 않았습니다. 언어 링크를 제거했습니다.',
	'wikibase-comment-restore' => '연결한 위키데이터 항목을 복구했습니다. 언어 링크를 복구했습니다.',
	'wikibase-comment-update' => '위키데이터 항목을 바꿈',
	'wikibase-comment-sitelink-add' => '언어 링크를 추가함: $1',
	'wikibase-comment-sitelink-change' => '언어 링크를 $1에서 $2로 바꿈',
	'wikibase-comment-sitelink-remove' => '언어 링크를 제거함: $1',
	'wikibase-comment-multi' => '$1개 {{PLURAL:$1|바뀜}}',
	'wikibase-editlinks' => '링크 편집',
	'wikibase-editlinkstitle' => '인터언어 링크 편집',
	'wikibase-linkitem-addlinks' => '링크 추가',
	'wikibase-linkitem-alreadylinked' => '링크하고자 하는 문서는 이미 이 사이트에 $2(으)로 링크한 중앙 데이터 저장소의 [$1항목]에 연결되어 있습니다. 항목은 사이트마다 문서 하나만 연결할 수 있습니다. 링크할 다른 문서를 선택하세요.',
	'wikibase-linkitem-close' => '대화 상자를 닫고 문서를 다시 불러오기',
	'wikibase-linkitem-failure' => '주어진 문서에 링크하는 동안 알 수 없는 오류가 발생했습니다.',
	'wikibase-linkitem-title' => '문서에 링크',
	'wikibase-linkitem-linkpage' => '문서에 링크',
	'wikibase-linkitem-selectlink' => '이 문서로 링크할 사이트와 문서를 선택하세요.',
	'wikibase-linkitem-input-site' => '언어:',
	'wikibase-linkitem-input-page' => '문서:',
	'wikibase-linkitem-invalidsite' => '알 수 없거나 잘못된 사이트를 선택했습니다',
	'wikibase-linkitem-confirmitem-text' => '선택한 문서는 이미 [$1 중앙 데이터 저장소에 항목]에 링크되어 있습니다.
이 문서와 아래에 나타난 문서를 링크해야 할지 확인하세요.',
	'wikibase-linkitem-confirmitem-button' => '확인',
	'wikibase-linkitem-not-loggedin-title' => '로그인 필요',
	'wikibase-linkitem-not-loggedin' => '이 기능을 사용하려면 이 위키와 [$1 중앙 데이터 저장소]에 로그인해야 합니다.',
	'wikibase-linkitem-success-create' => '문서를 성공적으로 링크했습니다. [$1 중앙 데이터 저장소]에 링크를 포함하는 새로 만든 항목을 찾을 수 있습니다.',
	'wikibase-linkitem-success-link' => '문서를 성공적으로 링크했습니다. [$1 중앙 데이터 저장소]에 링크를 포함하는 항목을 찾을 수 있습니다.',
	'wikibase-property-notfound' => '$1 속성이 없습니다.',
	'wikibase-property-notsupportedyet' => '위키베이스에서 $1 속성에 대한 유형을 지원하지 않습니다.',
	'wikibase-rc-hide-wikidata' => '위키데이터 $1',
	'wikibase-rc-show-wikidata-pref' => '최근 바뀜에서 위키데이터 편집 보기',
	'wikibase-watchlist-show-changes-pref' => '주시문서 목록에서 위키데이터 편집을 보기',
	'wikibase-error-invalid-entity-id' => '입력한 ID는 시스템에서 알 수 없습니다. 올바른 개체 ID를 사용하세요.',
	'special-unconnectedpages' => '항목에 연결되지 않은 문서',
	'wikibase-unconnectedpages-legend' => '연결되지 않은 문서 옵션',
	'wikibase-unconnectedpages-page' => '시작 문서:', # Fuzzy
	'wikibase-unconnectedpages-submit' => '보기',
	'wikibase-unconnectedpages-invalid-language' => '"$1"(은)는 올바른 언어 코드가 아닙니다.',
	'wikibase-unconnectedpages-page-warning' => '문서 제목은 쿼리에 사용할 수 없고 무시됩니다.',
	'wikibase-unconnectedpages-iwdata-label' => '언어 인터위키 링크가 있는 문서만',
	'wikibase-unconnectedpages-format-row' => '(문서에 있는 언어 인터위키 링크 $1개)',
);

/** Karachay-Balkar (къарачай-малкъар)
 * @author Iltever
 */
$messages['krc'] = array(
	'wikibase-client-desc' => "Wikibase'ни кенгертирге клиент",
	'wikibase-after-page-move' => 'Аты тюрленнген бетдеги тил джибериулени тюзетир ючюн Викибилгилени байламлы элементин [$1 джангыртыргъа] да боллукъсуз.',
	'wikibase-comment-remove' => 'Викибилгилени байламлы элементин кетерилди. Тил джибериуле къоратылдыла.',
	'wikibase-comment-linked' => 'Викибилгилени элементи бу бет бла байламлы эди',
	'wikibase-comment-unlink' => 'Бу бетни Викибилгилени элементи бла байламы юзюлдю. Тил джибериуле кетерилдиле.',
	'wikibase-comment-restore' => 'Викибилгилени байламлы элементин кетериу ызына алынды. Тил джибериуле ызларына салындыла.',
	'wikibase-comment-update' => 'Тил джибериуле джангыртылдыла',
	'wikibase-comment-sitelink-add' => 'Интервики-джибериу къошулду: $1.',
	'wikibase-comment-sitelink-change' => '$1 интервики-джибериу $2 бла ауушулду',
	'wikibase-comment-sitelink-remove' => 'Интервики-джибериу кетерилди: $1.',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|тюрлениу|тюрлениу}}', # Fuzzy
	'wikibase-editlinks' => 'Джибериулени тюрлендир',
	'wikibase-editlinkstitle' => 'Тилле арасы джибериулени тюрлендириу',
	'wikibase-linkitem-addlinks' => 'Джибериулени къош',
	'wikibase-linkitem-close' => 'Диалогну джаб эм бетни джангырт',
	'wikibase-linkitem-failure' => 'Бу бетни байламлы этерге кюрешген сагъатда белгили болмагъан халат чыкъды.',
	'wikibase-linkitem-title' => 'Бет бла байлам',
	'wikibase-linkitem-linkpage' => 'Бет бла байла',
	'wikibase-linkitem-selectlink' => 'Былайдан аннга джибериу салыргъа излеген сайтны неда бетни айырыгъыз',
	'wikibase-linkitem-input-site' => 'Тил:',
	'wikibase-linkitem-input-page' => 'Бет:',
	'wikibase-linkitem-invalidsite' => 'Тюз неда белгили болмагъан сайт сайланнганды',
	'wikibase-linkitem-confirmitem-text' => 'Сиз сайлагъан бет [$1 билгилени ара репозиторийини элементи] бла алгъадан байламлыды. Тюбюнде кёргюзюлген бетлени ичинде сиз аннга джибериу салыргъа излеген болгъанын бегитигиз.',
	'wikibase-linkitem-confirmitem-button' => 'Къабыл эт',
	'wikibase-linkitem-not-loggedin-title' => 'Авторизацияны ётерге керексиз',
	'wikibase-linkitem-not-loggedin' => 'Бу функцияны хайырландырыр ючюн бу викиде эм [$1 билгилени ара репозиторийинде] авторизация ётерге керексиз.',
	'wikibase-linkitem-success-create' => 'Бу бет тыйыншлы байламлы этилди. Джибериулени тутхан джангы къуралгъан элементни [$1 билгилени ара репозиторийинде] табаргъа боллукъсуз.',
	'wikibase-linkitem-success-link' => 'Бу бет тыйыншлы байламлы этилди. Джибериулени тутхан элементни [$1 билгилени ара репозиторийинде] табаргъа боллукъсуз.',
	'wikibase-rc-hide-wikidata' => 'Викибилгилени $1',
	'wikibase-rc-show-wikidata-pref' => 'Викибилгилени тюрлениулерин джангы тюрлениулени тизмесинде кёргюз',
);

/** Colognian (Ripoarisch)
 * @author Purodha
 */
$messages['ksh'] = array(
	'wikibase-client-desc' => 'Mäd en Aanwendong vun däm Projrammzohsaz „Wikibase“ müjjelesch.',
	'wikibase-after-page-move' => 'Mer kann och dä zohjehüüreje Wikidata-Endraach [$1 aanpaße], öm de Lengks op Schprooche vun ömjenannte Sigge aschuur ze hallde.',
	'wikibase-comment-remove' => 'Dä verbonge Wikidata-Endraach wood fottjeschmeße. Alle Lengks op ander Schprooche woodte fottjenumme.',
	'wikibase-comment-linked' => 'Ene Wikidata-Endraach wood met heh dä Sigg verbonge.',
	'wikibase-comment-unlink' => 'Heh di Sigg wood uß däm verbonge Wikidata-Endraach jenumme. Alle Lengks op ander Schprooche woodte fottjenumme.',
	'wikibase-comment-restore' => 'Dä verbonge Wikidata-Endraach wood wider zerök jehollt. Alle Lengks op ander Schprooche woodte wider enejrescht.',
	'wikibase-comment-update' => 'De Lengks op ander Schprooche sin aanjepaß woode.', # Fuzzy
	'wikibase-comment-sitelink-add' => 'Dä Lengk $1 ob en ander Schprooch es derbei jedonn woode.',
	'wikibase-comment-sitelink-change' => 'Dä Lengk $1 ob en ander Schprooch es op $2 verändert woode.',
	'wikibase-comment-sitelink-remove' => 'Dä Lengk $1 ob en ander Schprooch es eruß jenomme woode.',
	'wikibase-comment-multi' => '{{PLURAL:$1|Ein Änderong|$1 Änderonge|Kein Änderong}}',
	'wikibase-editlinks' => 'Lengks ändere',
	'wikibase-editlinkstitle' => 'Donn de Lenks zwesche der Schprooche aanbränge udder aanpaße',
	'wikibase-linkitem-addlinks' => 'Lengks derbei donn',
	'wikibase-linkitem-alreadylinked' => 'Di Sigg, di De verlenke wells, es ald med enem [$1 Endraach] en zäntraale Daatebeschtand verbonge, un dä es ald met $2 heh em Wiki verbonge, Mer kann bloß ein esu en Verbendong pro Endraach han. Dröm söhk Der en ander Sigg uß för heh die Sigg dermet ze verbenge,',
	'wikibase-linkitem-close' => 'Finster zohmaache un Sigg neu laade',
	'wikibase-linkitem-failure' => 'Ene onbikannte Fähler es beim Verlengke op di aanjejovve Sigg opjetrodde.',
	'wikibase-linkitem-title' => 'Lengk met en ander Sigg',
	'wikibase-linkitem-linkpage' => 'Lohß Jonn!',
	'wikibase-linkitem-selectlink' => 'Donn en Wäbßait un en Sigg ußsöhkre, woh De heh di Sigg met verlengk han wells.',
	'wikibase-linkitem-input-site' => 'De Schprooch:',
	'wikibase-linkitem-input-page' => 'Sigg:',
	'wikibase-linkitem-invalidsite' => 'Dat es en onbikannte udder onjöltije ẞait!',
	'wikibase-linkitem-confirmitem-button' => 'Lohß jonn!',
	'wikibase-linkitem-not-loggedin-title' => 'Do moß enjelogg sin',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Donn Änderonge aan Wikidata en de „{{int:recentchanges}}“ zeije',
	'wikibase-unconnectedpages-format-row' => '(Mer {{PLURAL:$1|han eine Lenk|han $1 Lenks|hann_er kein Lenks}} op ander Schprooche en dä Sigg)',
);

/** Kurdish (Kurdî)
 * @author Ghybu
 */
$messages['ku'] = array(
	'wikibase-rc-hide-wikidata' => 'Wikidatayê $1',
);

/** Kurdish (Latin script) (Kurdî (latînî)‎)
 * @author George Animal
 * @author Ghybu
 * @author Gomada
 */
$messages['ku-latn'] = array(
	'wikibase-after-page-move' => 'Tu dikarî beşa têkilîdar a Wîkîdaneyê, ku girêdanên zimanan ên li ser rûpela gerandî têde ne, [$1 rojane bikî].',
	'wikibase-comment-update' => 'Girêdanên ziman hatin rojanekirin.', # Fuzzy
	'wikibase-comment-sitelink-add' => 'Girêdana ziman lê hat zêdekirin:$1',
	'wikibase-comment-sitelink-remove' => 'Girêdana ziman hat jêbirin:$1',
	'wikibase-editlinks' => 'Girêdanan biguherîne',
	'wikibase-linkitem-addlinks' => 'Girêdanan lê zêde bike',
	'wikibase-linkitem-input-site' => 'Ziman:',
	'wikibase-linkitem-input-page' => 'Rûpel:',
	'wikibase-rc-hide-wikidata' => 'Wikidatayê $1',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Les Meloures
 * @author Robby
 */
$messages['lb'] = array(
	'wikibase-client-desc' => "Client fir d'Wikibase Erweiderung",
	'specialpages-group-wikibaseclient' => 'Wikidata-Client',
	'wikibase-after-page-move' => "Dir kënnt och dat associéiert Wikidata-Element [$1 aktualiséiere] fir d'Interwikilinken op der geréckelter Säit bäizebehalen.",
	'wikibase-comment-remove' => 'Associéiert Wikidata-Element geläscht. Sproochlinken ewechgeholl.',
	'wikibase-comment-linked' => 'E Wikidata-Element gouf mat dëser Säit verlinkt.',
	'wikibase-comment-unlink' => 'Dës Säit gouf vum Wikidata-Element entlinkt. Interwiki-Linke goufen ewechgeholl.',
	'wikibase-comment-restore' => 'Associéiert Wikidata-Element restauréiert. Sproochlinken restauréiert.',
	'wikibase-comment-update' => 'Wikidata-Element geännert',
	'wikibase-comment-sitelink-add' => 'Sproochlink derbäigesat: $1',
	'wikibase-comment-sitelink-change' => 'Sproochlink vun $1 op $2 geännert',
	'wikibase-comment-sitelink-remove' => 'Sproochlink ewechgeholl: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|Ännerung|Ännerungen}}',
	'wikibase-editlinks' => 'Linken änneren',
	'wikibase-editlinkstitle' => 'Interwiki-Linken änneren',
	'wikibase-linkitem-addlinks' => 'Link derbäisetzen',
	'wikibase-linkitem-close' => "Maacht den Dialog zou a luet d'Säit nei",
	'wikibase-linkitem-failure' => 'En onbekannte Feeler ass beim Versuch fir déi Säit déi Dir uginn hutt ze verlinken.',
	'wikibase-linkitem-title' => 'Mat der Säit verlinken',
	'wikibase-linkitem-linkpage' => 'Mat der Säit verlinken',
	'wikibase-linkitem-selectlink' => 'Sicht w.e.g. e Site an eng Säit eraus mat där Dir dës Säit verlinke wëllt.',
	'wikibase-linkitem-input-site' => 'Sprooch:',
	'wikibase-linkitem-input-page' => 'Säit:',
	'wikibase-linkitem-invalidsite' => 'Onbekannten oder net valabele Site erausgesicht',
	'wikibase-linkitem-confirmitem-button' => 'Confirméieren',
	'wikibase-linkitem-not-loggedin-title' => 'Dir musst ageloggt sinn',
	'wikibase-linkitem-not-loggedin' => 'Dir musst op dëser Wiki an am [$1 zentralen Daterepertoire] ageloggt sinn fir dës Fonctioun ze benotzen.',
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Wikidata-Ännerungen an de rezenten Ännerunge weisen',
	'wikibase-watchlist-show-changes-pref' => 'Wikidata-Ännerungen an Ärer Iwwerwaachungslëscht weisen',
	'wikibase-error-invalid-entity-id' => "D'ID déi Dir aginn hutt ass am System net bekannt. Benotzt w.e.g. en valabel ID.",
	'special-unconnectedpages' => 'Säiten déi net mat Elementer verbonn sinn',
	'wikibase-unconnectedpages-page' => 'Lëscht vun de Resultater fänkt u mat der Säit:',
	'wikibase-unconnectedpages-submit' => 'Lass',
	'wikibase-unconnectedpages-invalid-language' => '"$1" ass kee valabele Sproochecode.',
	'wikibase-unconnectedpages-iwdata-label' => 'Nëmme Säite mat Linke mat anere Sproochen',
	'wikibase-unconnectedpages-format-row' => '($1 Linke mat anere Sproochen op der Säit)',
	'wikibase-pageinfo-entity-id-none' => 'Keng',
);

/** Lezghian (лезги)
 * @author Soul Train
 */
$messages['lez'] = array(
	'wikibase-rc-hide-wikidata' => '$1 Викималуматар',
);

/** Latvian (latviešu)
 * @author Papuass
 */
$messages['lv'] = array(
	'wikibase-editlinks' => 'Labot saites',
	'wikibase-editlinkstitle' => 'Labot starpvalodu saites',
	'wikibase-linkitem-addlinks' => 'Pievienot saites',
	'wikibase-linkitem-input-site' => 'Valoda:',
	'wikibase-linkitem-input-page' => 'Lapa:',
);

/** Malagasy (Malagasy)
 * @author Jagwar
 */
$messages['mg'] = array(
	'wikibase-editlinks' => 'Hanova ny rohy',
	'wikibase-editlinkstitle' => 'Hanova ny rohy iraisam-piteny',
);

/** Minangkabau (Baso Minangkabau)
 * @author Iwan Novirion
 */
$messages['min'] = array(
	'wikibase-client-desc' => 'Klien untuak ekstensi Wikibase',
	'specialpages-group-wikibaseclient' => 'Klien Wikidata',
	'wikibase-after-page-move' => 'Sanak paralu juo [$1 mampabarui item] nan tapauik di Wikidata dalam hal interwiki sangkek mamindahan laman.',
	'wikibase-comment-remove' => 'Item Wikidata takait dihapuih. Interwiki hilang.',
	'wikibase-comment-linked' => 'Item Wikidata alah tapauik ka laman iko.',
	'wikibase-comment-unlink' => 'Laman ko alah indak tapauik ka item Wikidata. Interwiki hilang.',
	'wikibase-comment-restore' => 'Dek pambatalan pahapuihan item Wikidata, interwiki dikambalian.',
	'wikibase-comment-update' => 'Item Wikidata barubah',
	'wikibase-comment-sitelink-add' => 'Panambahan interwiki: $1',
	'wikibase-comment-sitelink-change' => 'Interwiki barubah dari $1 ka $2',
	'wikibase-comment-sitelink-remove' => 'Pangurangan interwiki: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|parubahan}}',
	'wikibase-editlinks' => 'Suntiang interwiki',
	'wikibase-editlinkstitle' => 'Suntiang pranala interwiki',
	'wikibase-linkitem-addlinks' => 'Tambah interwiki',
	'wikibase-linkitem-alreadylinked' => 'Laman nan nio Sanak tauikkan alah tatauik ka [$1 item] pado pusek panyimpanan data nan tahubuang pado $2 di situs ko. Item hanyo dapek punyo satu laman per situs nan tatauik. Silakan piliah laman lain untuak ditauikkan.',
	'wikibase-linkitem-close' => 'Tutuik dan muek ulang laman',
	'wikibase-linkitem-failure' => 'Kasalahan indak dikatahui tajadi sangkek mancubo manauikkan laman.',
	'wikibase-linkitem-title' => 'Tauikkan jo laman',
	'wikibase-linkitem-linkpage' => 'Tauikkan jo laman',
	'wikibase-linkitem-selectlink' => 'Silakan piliah sabuah kode bahaso dan laman nan nio Sanak tauikkan jo laman ko.',
	'wikibase-linkitem-input-site' => 'Bahaso:',
	'wikibase-linkitem-input-page' => 'Laman:',
	'wikibase-linkitem-invalidsite' => 'Wiki indak dikatahui atau indak valid nan dipiliah',
	'wikibase-linkitem-confirmitem-text' => 'Laman nan Sanak piliah alah tapauik pado [$1 item di pusek panyimpanan data kami]. Silahkan dikonfirmasi laman nan ditampilan dibawah ko adolah laman nan nio Sanak tauikkan.',
	'wikibase-linkitem-confirmitem-button' => 'Tarimo',
	'wikibase-linkitem-not-loggedin-title' => 'Sanak musti masuak log',
	'wikibase-linkitem-not-loggedin' => 'Sanak musti masuak log pado wiki ko dan pado [$1 pusek panyimpanan data] untuak manggunoan fitur iko.',
	'wikibase-linkitem-success-create' => 'Laman alah ditauikkan. Sanak dapek manamuan item baru barisi tautan tasabuik pado [$1 pusek panyimpanan data] kami.',
	'wikibase-linkitem-success-link' => 'Laman alah barasil ditauikkan. Sanak dapek manamukan item barisi tautan tarsabuik pado [$1 pusek panyimpanan data] kami.',
	'wikibase-property-notfound' => 'Properti $1 indak basobok.',
	'wikibase-property-notsupportedyet' => 'Wikibase alun mandukuang tipe properti untuak properti $1.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Tunjuakan suntiangan Wikidata di parubahan baru',
	'wikibase-watchlist-show-changes-pref' => 'Tambahan suntiangan Wikidata pado daftar pantau',
	'wikibase-error-invalid-entity-id' => 'ID nan dimasuakan indak dikenal sistem. Gunoan entitas ID nan valid.',
	'special-unconnectedpages' => 'Laman nan alun tapauik ka Wikidata',
	'wikibase-unconnectedpages-legend' => 'Piliahan laman nan alun tapauik',
	'wikibase-unconnectedpages-page' => 'Daftar dimulai jo laman:',
	'wikibase-unconnectedpages-submit' => 'Lanjut',
	'wikibase-unconnectedpages-invalid-language' => '"$1" bukan kode bahaso nan sah.',
	'wikibase-unconnectedpages-page-warning' => 'Judul laman indak buliah digunoan untuak query dan akan diabaian.',
	'wikibase-unconnectedpages-iwdata-label' => 'Hanyo laman nan ado interwiki',
	'wikibase-unconnectedpages-format-row' => '($1 interwiki pado laman)',
);

/** Macedonian (македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'wikibase-client-desc' => 'Клиент за додатокот „Викибаза“',
	'specialpages-group-wikibaseclient' => 'Клиент на Википодатоците',
	'wikibase-after-page-move' => 'Можете и да го [$1 подновите] поврзаниот предмет на Википодатоци за да ги одржите јазичните врски на преместената страница.',
	'wikibase-comment-remove' => 'Здружениот предмет од Википодатоците е избришан. Јазичните врски се избришани.',
	'wikibase-comment-linked' => 'Со страницава е поврзан предмет од Википодатоците.',
	'wikibase-comment-unlink' => 'На оваа страница ѝ е раскината врската со елементот од Википодатоците. Јазичните врски се отстранети.',
	'wikibase-comment-restore' => 'Здружениот предмет од Википодатоците е повратен. Јазичните врски се повратени.',
	'wikibase-comment-update' => 'Изменет предмет во Википодатоците',
	'wikibase-comment-sitelink-add' => 'Додадена јазична врска: $1',
	'wikibase-comment-sitelink-change' => 'Изменета јазична врска од $1 на $2',
	'wikibase-comment-sitelink-remove' => 'Отстранета јазична врска: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|промена|промени}}',
	'wikibase-editlinks' => 'Уреди врски',
	'wikibase-editlinkstitle' => 'Уредување на меѓујазични врски',
	'wikibase-linkitem-addlinks' => 'Додај врски',
	'wikibase-linkitem-alreadylinked' => 'Страницата што сакате да ја сврзете е веќе поврзана со [$1 единица] во централното складиште, што води до $2 на ова вики. Единиците можат да имаат само една сврзана страница по мреж. место. Изберете друга страница за сврзување.',
	'wikibase-linkitem-close' => 'Затвори го дијалогот и превчитај ја страницата',
	'wikibase-linkitem-failure' => 'Се појави непозната грешка при обидот да ја сврзам дадената страница.',
	'wikibase-linkitem-title' => 'Сврзување со страница',
	'wikibase-linkitem-linkpage' => 'Сврзи со страница',
	'wikibase-linkitem-selectlink' => 'Одберете вики и страница што сакате да ја сврзете.',
	'wikibase-linkitem-input-site' => 'Јазици:',
	'wikibase-linkitem-input-page' => 'Страница:',
	'wikibase-linkitem-invalidsite' => 'Избран е непознато или неважечко мреж. место',
	'wikibase-linkitem-confirmitem-text' => 'Избраната страница е веќе сврзана со [$1 единица во нашето централно складиште]. Потврдете дека долуприкажаните страници се тие што сакате да ги сврзете со страницава.',
	'wikibase-linkitem-confirmitem-button' => 'Потврди',
	'wikibase-linkitem-not-loggedin-title' => 'Треба да сте најавени',
	'wikibase-linkitem-not-loggedin' => 'За да ја користите функцијава, треба да сте најавени на ова вики и на [$1 централното складиште на податоци].',
	'wikibase-linkitem-success-create' => 'Страниците се успешно сврзани. Новосоздадената единица со врските ќе ја најдете на нашето [$1 централно складиште на податоци].',
	'wikibase-linkitem-success-link' => 'Страниците се успешно сврзани. Новосоздадената единица со врските ќе ја најдете на нашето [$1 централно складиште на податоци].',
	'wikibase-property-notfound' => 'Својството „$1“ не е пронајдено.',
	'wikibase-property-notsupportedyet' => 'Викибазата сè уште не поддржува својства од типот „$1“.',
	'wikibase-rc-hide-wikidata' => '$1 Википодатоци',
	'wikibase-rc-show-wikidata-pref' => 'Прикажувај ги уредувањата на Википодатоците во скорешните промени',
	'wikibase-watchlist-show-changes-pref' => 'Нема уредувања на Википодатоци во набљудувањата',
	'wikibase-error-invalid-entity-id' => 'Назнаката не му е позната на системот. Внесете важечка назнака.',
	'special-unconnectedpages' => 'Страници што не се врзани со предмети',
	'wikibase-unconnectedpages-legend' => 'Можности за неврзани страници',
	'wikibase-unconnectedpages-page' => 'Почни го списокот на резултати со страницата:',
	'wikibase-unconnectedpages-submit' => 'Оди',
	'wikibase-unconnectedpages-invalid-language' => '„$1“ не претставува важечки јазичен код.',
	'wikibase-unconnectedpages-page-warning' => 'Насловот на страницата не може да се употреби во барање, па затоа е занемарен.',
	'wikibase-unconnectedpages-iwdata-label' => 'Само страници со меѓујазични врски',
	'wikibase-unconnectedpages-format-row' => '($1 меѓујазични врски на страницата)',
	'wikibase-pageinfo-entity-id' => 'Предметна назнака од Википодатоци',
	'wikibase-pageinfo-entity-id-none' => 'нема',
);

/** Malayalam (മലയാളം)
 * @author Praveenp
 * @author Santhosh.thottingal
 */
$messages['ml'] = array(
	'wikibase-client-desc' => 'വിക്കിബേസ് അനുബന്ധത്തിനുള്ള ക്ലയന്റ്',
	'specialpages-group-wikibaseclient' => 'വിക്കിഡേറ്റ ക്ലയന്റ്',
	'wikibase-after-page-move' => 'മാറ്റിയ താളിലെ ഭാഷാ കണ്ണികൾ പരിപാലിക്കുന്നതിനായി ബന്ധപ്പെട്ട വിക്കിഡേറ്റ ഇനം താങ്കൾക്ക് [$1 പുതുക്കുകയും] ചെയ്യാവുന്നതാണ്.',
	'wikibase-comment-remove' => 'ബന്ധപ്പെട്ട വിക്കിഡേറ്റ ഇനം മായ്ക്കപ്പെട്ടിരിക്കുന്നു. ഭാഷാ കണ്ണികൾ നീക്കം ചെയ്തു.',
	'wikibase-comment-linked' => 'ഒരു വിക്കിഡേറ്റ ഇനം ഈ താളിൽ കണ്ണി ചേർത്തിരിക്കുന്നു.',
	'wikibase-comment-unlink' => 'ഈ താൾ വിക്കിഡേറ്റാ ഇനത്തിൽ നിന്നും കണ്ണി മാറ്റിയിരിക്കുന്നു. ഭാഷാ കണ്ണികൾ നീക്കം ചെയ്തു.',
	'wikibase-comment-restore' => 'ബന്ധപ്പെട്ട വിക്കിഡേറ്റ ഇനം പുനഃസ്ഥാപിച്ചിരിക്കുന്നു. ഭാഷാ കണ്ണികൾ പുനഃസ്ഥാപിച്ചു.',
	'wikibase-comment-update' => 'വിക്കിഡേറ്റ ഇനം മാറിയിരിക്കുന്നു',
	'wikibase-comment-sitelink-add' => 'ഭാഷാ കണ്ണി ചേർത്തു: $1',
	'wikibase-comment-sitelink-change' => 'ഭാഷാ കണ്ണി $1 എന്നതിൽ നിന്ന് $2 എന്നാക്കി മാറ്റിയിരിക്കുന്നു',
	'wikibase-comment-sitelink-remove' => 'ഭാഷാ കണ്ണി നീക്കം ചെയ്തു: $1',
	'wikibase-comment-multi' => '{{PLURAL:$1|ഒരു മാറ്റം|$1 മാറ്റങ്ങൾ}}',
	'wikibase-editlinks' => 'കണ്ണികൾ തിരുത്തുക',
	'wikibase-editlinkstitle' => 'അന്തർഭാഷാ കണ്ണികൾ തിരുത്തുക',
	'wikibase-linkitem-addlinks' => 'കണ്ണികൾ ചേർക്കുക',
	'wikibase-linkitem-alreadylinked' => 'കേന്ദ്രീകൃത വിവര ശേഖരത്തിൽ നിന്ന് ഈ താളുമായി ബന്ധപ്പെടുത്താൻ താങ്കൾ ശ്രമിക്കുന്ന ഒരു [$1 ഇനം], ഈ സൈറ്റിൽ $2 എന്നതിലേയ്ക്ക് കണ്ണിചേർക്കപ്പെട്ടിരിക്കുന്നു. ഒരു സൈറ്റിൽ ഒരൊറ്റ താളിലേയ്ക്കേ ഇനങ്ങൾ ചേർക്കപ്പെടാവൂ. ദയവായി കണ്ണി ചേർക്കാൻ വ്യത്യസ്തമായ ഒരു താൾ തിരഞ്ഞെടുക്കുക.',
	'wikibase-linkitem-close' => 'അറിയിപ്പ് അടച്ച ശേഷം താൾ റീലോഡ് ചെയ്യുക',
	'wikibase-linkitem-failure' => 'തന്നിരിക്കുന്ന താളിലേയ്ക്ക് കണ്ണി ചേർക്കാൻ ശ്രമിച്ചപ്പോൾ അപരിചിതമായ ഒരു പിഴവുണ്ടായി.',
	'wikibase-linkitem-title' => 'താളുമായി കണ്ണി ചേർക്കുക',
	'wikibase-linkitem-linkpage' => 'താളുമായി കണ്ണി ചേർക്കുക',
	'wikibase-linkitem-selectlink' => 'ദയവായി ഈ താളുമായി കണ്ണി ചേർക്കേണ്ട സൈറ്റും അതിലെ താളും തിരഞ്ഞെടുക്കുക.',
	'wikibase-linkitem-input-site' => 'ഭാഷ:',
	'wikibase-linkitem-input-page' => 'താൾ:',
	'wikibase-linkitem-invalidsite' => 'അപരിചിതമോ അസാധുവോ ആയ സൈറ്റാണ് തിരഞ്ഞെടുത്തത്',
	'wikibase-linkitem-confirmitem-text' => 'താങ്കൾ തിരഞ്ഞെടുത്ത താൾ മുമ്പേതന്നെ  [$1 കേന്ദ്രീകൃത വിവര ശേഖരത്തിലെ ഒരിനവുമായി] കണ്ണിചേർക്കപ്പെട്ടിരിക്കുന്നു. ഈ താളുമായി കണ്ണിചേർക്കേണ്ട താളുകളാണ് താഴെ പ്രദർശിപ്പിച്ചിരിക്കുന്നതെന്ന് സ്ഥിരീകരിക്കുക.',
	'wikibase-linkitem-confirmitem-button' => 'സ്ഥിരീകരിക്കുക',
	'wikibase-linkitem-not-loggedin-title' => 'താങ്കൾ പ്രവേശിച്ചിരിക്കേണ്ടതാവശ്യമാണ്',
	'wikibase-linkitem-not-loggedin' => 'ഈ സൗകര്യം ഉപയോഗിക്കുന്നതിനായി ഈ വിക്കിയിലും [$1 കേന്ദ്രീകൃത വിവര ശേഖരത്തിലും] താങ്കൾ പ്രവേശിച്ചിരിക്കേണ്ടതുണ്ട്.',
	'wikibase-linkitem-success-create' => 'താളുകൾ വിജയകരമായി കണ്ണിചേർത്തിരിക്കുന്നു. താങ്കൾക്ക് പുതിയതായി സൃഷ്ടിച്ച കണ്ണികളുള്ള ഇനം ഞങ്ങളുടെ [$1 കേന്ദ്രീകൃത വിവര ശേഖരത്തിൽ] കാണാവുന്നതാണ്.',
	'wikibase-linkitem-success-link' => 'താളുകൾ വിജയകരമായി കണ്ണിചേർത്തിരിക്കുന്നു. താങ്കൾക്ക് ഇനം ഞങ്ങളുടെ [$1 കേന്ദ്രീകൃത വിവര ശേഖരത്തിൽ] കാണാവുന്നതാണ്.',
	'wikibase-property-notfound' => '$1 എന്ന ഗുണം കണ്ടെത്താനായില്ല.',
	'wikibase-property-notsupportedyet' => 'വിക്കിബേസിൽ ഇതുവരെ $1 തരത്തിലുള്ള ഗുണം പിന്തുണയ്ക്കുന്നില്ല.',
	'wikibase-rc-hide-wikidata' => 'വിക്കിഡേറ്റ $1',
	'wikibase-rc-show-wikidata-pref' => 'സമീപകാല മാറ്റങ്ങളിൽ വിക്കിഡേറ്റാ തിരുത്തലുകളും പ്രദർശിപ്പിക്കുക',
	'wikibase-watchlist-show-changes-pref' => 'താങ്കൾ ശ്രദ്ധിക്കുന്നവയുടെ പട്ടികയിൽ വിക്കിഡേറ്റ തിരുത്തലുകളും പ്രദർശിപ്പിക്കുക',
	'wikibase-error-invalid-entity-id' => 'നൽകിയ ഐ.ഡി. വ്യവസ്ഥയിൽ അപരിചിതമാണ്. ദയവായി സാധുവായ ഐ.ഡി. നൽകുക.',
	'special-unconnectedpages' => 'താളുകൾ ഇനങ്ങളുമായി ബന്ധപ്പെടുത്തിയിട്ടില്ല',
	'wikibase-unconnectedpages-legend' => 'ബന്ധപ്പെടാത്ത താളുകളുടെ ഐച്ഛികങ്ങൾ',
	'wikibase-unconnectedpages-page' => 'ഫലങ്ങളുടെ പട്ടിക ഈ താളിൽ തുടങ്ങുക:',
	'wikibase-unconnectedpages-submit' => 'പോകൂ',
	'wikibase-unconnectedpages-invalid-language' => '"$1" സാധുവായ ഒരു ഭാഷാ കോഡ് അല്ല.',
	'wikibase-unconnectedpages-page-warning' => 'താളിന്റെ തലക്കെട്ട് ക്വറിയ്ക്കായി ഉപയോഗിക്കാൻ കഴിയില്ലാത്തതിനാൽ അവഗണിച്ചിരിക്കുന്നു.',
	'wikibase-unconnectedpages-iwdata-label' => 'അന്തർഭാഷാകണ്ണികളുള്ള താളുകൾ മാത്രം',
	'wikibase-unconnectedpages-format-row' => '(താളിൽ $1 അന്തർഭാഷാകണ്ണികളുണ്ട്)',
);

/** Marathi (मराठी)
 * @author Ydyashad
 * @author संतोष दहिवळ
 */
$messages['mr'] = array(
	'wikibase-comment-linked' => 'या पानाला विकिडाटातील कलम दुवा जोडला.',
	'wikibase-comment-update' => 'विकिडाटा कलम बदलली',
	'wikibase-comment-sitelink-add' => 'भाषा दुवा जोडला: $1',
	'wikibase-comment-sitelink-change' => 'भाषा दुवा बदलला $1 पासून $2 कडे',
	'wikibase-comment-sitelink-remove' => 'भाषा दुवा काढला: $1',
	'wikibase-rc-hide-wikidata' => 'विकिडाटा $1',
	'wikibase-rc-show-wikidata-pref' => 'विकिडाटा संपादने अलीकडील बदल मध्ये दाखवा',
);

/** Malay (Bahasa Melayu)
 * @author Anakmalaysia
 */
$messages['ms'] = array(
	'wikibase-client-desc' => 'Pelanggan sambungan Wikibase',
	'specialpages-group-wikibaseclient' => 'Klien Wikidata',
	'wikibase-after-page-move' => 'Anda juga boleh [$1 mengemaskinikan] perkara Wikidata yang berkenaan untuk memelihara pautan bahasa pada halaman yang dipindahkan.',
	'wikibase-comment-remove' => 'Perkara Wikidata yang berkenaan dihapuskan. Pautan bahasa dipadamkan.',
	'wikibase-comment-linked' => 'Satu perkara Wikidata telah dipautkan ke halaman ini.',
	'wikibase-comment-unlink' => 'Halaman ini telah dinyahpautkan dari perkara Wikidata. Pautan bahasa dibuang.',
	'wikibase-comment-restore' => 'Perkara Wikidata yang berkenaan dinyahhapus. Pautan bahasa dipulihkan.',
	'wikibase-comment-update' => 'Perkara wikidata diubah',
	'wikibase-comment-sitelink-add' => 'Pautan bahasa dibubuh: $1',
	'wikibase-comment-sitelink-change' => 'Pautan bahasa diubah daripada $1 kepada $2',
	'wikibase-comment-sitelink-remove' => 'Pautan bahasa dibuang: $1',
	'wikibase-comment-multi' => '$1 perubahan',
	'wikibase-editlinks' => 'Sunting pautan',
	'wikibase-editlinkstitle' => 'Sunting pautan antara bahasa',
	'wikibase-linkitem-addlinks' => 'Tambah pautan',
	'wikibase-linkitem-alreadylinked' => 'Halaman yang anda ingin pautkan itu sudah dilampirkan dengan satu [$1 perkara] di repositori data pusat yang berpaut dengan $2 di tapak ini. Setiap perkara hanya boleh berlampirkan satu halaman setapak. Sila pilih halaman yang lain untuk dipautkan.',
	'wikibase-linkitem-close' => 'Tutup dialog dan muat semula halaman',
	'wikibase-linkitem-failure' => 'Ralat di luar jangkaan berlaku apabila cuba memautkan halaman yang diberikan.',
	'wikibase-linkitem-title' => 'Pautan dengan halaman',
	'wikibase-linkitem-linkpage' => 'Pautan dengan halaman',
	'wikibase-linkitem-selectlink' => 'Sila pilih tapak dan halaman yang mana ingin anda pautkan halaman ini.',
	'wikibase-linkitem-input-site' => 'Bahasa:',
	'wikibase-linkitem-input-page' => 'Halaman:',
	'wikibase-linkitem-invalidsite' => 'Tapak yang tidak dikenali atau tidak sah terpilih',
	'wikibase-linkitem-confirmitem-text' => 'Halaman yang telah anda pilih itu sudah dipautkan dengan satu [$1 perkara di repositori pusat kami]. Sila sahkan bahawa halaman-halaman yang ditunjukkan seperti berikut adalah yang ingin anda pautkan dengan halaman ini.',
	'wikibase-linkitem-confirmitem-button' => 'Sahkan',
	'wikibase-linkitem-not-loggedin-title' => 'Anda perlu log masuk',
	'wikibase-linkitem-not-loggedin' => 'Anda perlu log masuk ke dalam wiki ini dan juga ke dalam [$1 repositori data pusat] untuk menggunakan ciri ini.',
	'wikibase-linkitem-success-create' => 'Halaman-halaman ini telah berjaya dipautkan. Anda boleh mendapati perkara baru diwujudkan yang mengandungi pautan-pautannya di dalam [$1 repositori data pusat] kami.',
	'wikibase-linkitem-success-link' => 'Halaman-halaman ini telah berjaya dipautkan. Anda boleh mendapati perkara yang mengandungi pautan-pautannya di dalam [$1 repositori data pusat] kami.',
	'wikibase-property-notfound' => 'Sifat $1 tidak dijumpai.',
	'wikibase-property-notsupportedyet' => 'Wikibase belum menyokong jenis sifat $1.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Paparkan suntingan Wikidata dalam perubahan terkini',
	'wikibase-watchlist-show-changes-pref' => 'Paparkan suntingan Wikidata dalam senarai pantau anda',
	'wikibase-error-invalid-entity-id' => 'ID yang diberikan itu tidak dikenali oleh sistem. Sila gunakan ID entiti yang sah.',
	'special-unconnectedpages' => 'Halaman yang tidak bersambung dengan perkara',
	'wikibase-unconnectedpages-legend' => 'Pilihan untuk halaman tak tersambung',
	'wikibase-unconnectedpages-page' => 'Mulakan senarai hasil dengan halaman:',
	'wikibase-unconnectedpages-submit' => 'Pergi',
	'wikibase-unconnectedpages-invalid-language' => '"$1" bukan kod bahasa yang sah.',
	'wikibase-unconnectedpages-page-warning' => 'Tajuk halaman tidak dapat digunakan untuk pertanyaan, oleh itu diabaikan.',
	'wikibase-unconnectedpages-iwdata-label' => 'Hanya halaman dengan pautan antara bahasa',
	'wikibase-unconnectedpages-format-row' => '($1 pautan antara bahasa pada halaman ini)',
);

/** Maltese (Malti)
 * @author Chrisportelli
 */
$messages['mt'] = array(
	'wikibase-client-desc' => 'Klijent għall-estensjoni Wikibase',
	'wikibase-comment-update' => 'Ħoloq għal-lingwi aġġornati.',
	'wikibase-editlinks' => 'Immodifika l-ħoloq',
	'wikibase-editlinkstitle' => 'Immodifika l-ħoloq interlingwi',
	'wikibase-linkitem-addlinks' => 'Żid ħoloq',
	'wikibase-linkitem-not-loggedin-title' => 'Trid tkun dħalt fil-kont tiegħek',
	'wikibase-linkitem-not-loggedin' => "Sabiex tuża' din il-funzjoni trid tkun dħalt fil-kont tiegħek u fir-[$1repożitorju tad-data ċentralizzata].",
);

/** Norwegian Bokmål (norsk bokmål)
 * @author Danmichaelo
 * @author Event
 * @author Jeblad
 */
$messages['nb'] = array(
	'wikibase-client-desc' => 'Klientutvidelse for Wikibase, det strukturerte datalageret',
	'wikibase-after-page-move' => 'Du kan også [$1 oppdatere] det tilknyttede Wikidata-datasettet for å bevare språklenkene til den flyttede siden.',
	'wikibase-comment-remove' => 'Det tilknyttede Wikidata-datasettet har blitt slettet. Språklenker har blitt fjernet.',
	'wikibase-comment-linked' => 'Et Wikidata-datasett har blitt knyttet til denne siden.',
	'wikibase-comment-unlink' => 'Denne siden har blitt fraknyttet et Wikidata-datasett. Språklenker har blitt fjernet.',
	'wikibase-comment-restore' => 'Det tilknyttede Wikidata-datasettet har blitt gjenopprettet. Språklenker har blitt gjenopprettet.',
	'wikibase-comment-update' => 'Språklenker har blitt oppdatert.',
	'wikibase-comment-sitelink-add' => 'Språklenke tilført: $1',
	'wikibase-comment-sitelink-change' => 'Språklenke endret fra $1 til $2',
	'wikibase-comment-sitelink-remove' => 'Språklenke fjernet: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|endring|endringer}}',
	'wikibase-editlinks' => 'Rediger lenker',
	'wikibase-editlinkstitle' => 'Rediger språklenker – lenker til artikkelen på andre språk',
	'wikibase-linkitem-addlinks' => 'Legg til lenke',
	'wikibase-linkitem-alreadylinked' => 'Siden du vil lenke med er allerede koblet til et [$1 datasett] på det sentrale datalageret, som lenker til $2 på dette nettstedet. Datasett kan bare koble til én side per nettområde. Vennligst velg en annen side å lenke med.',
	'wikibase-linkitem-close' => 'Lukk dialogboksen og last side på nytt',
	'wikibase-linkitem-failure' => 'Det oppstod en ukjent feil under forsøket på å lenke med angitt side.',
	'wikibase-linkitem-title' => 'Lenk til side',
	'wikibase-linkitem-linkpage' => 'Lenk til side',
	'wikibase-linkitem-selectlink' => 'Vennligst velg et nettsted og en side som du vil lenke med denne siden.',
	'wikibase-linkitem-input-site' => 'Språk:',
	'wikibase-linkitem-input-page' => 'Side:',
	'wikibase-linkitem-invalidsite' => 'Ukjent eller ugyldig nettsted er valgt',
	'wikibase-linkitem-confirmitem-text' => 'Siden du valgte, er allerede lenket til [$1 element på vårt sentrale dataregister]. Bekreft at siden(e) som er vist nedenfor er de(n) du vil lenke med valgt side.',
	'wikibase-linkitem-confirmitem-button' => 'Bekreft',
	'wikibase-linkitem-not-loggedin-title' => 'Du må være logget inn',
	'wikibase-linkitem-not-loggedin' => 'Du må være logget inn på denne wikien og på det [$1 sentrale dataregister] for å bruke denne funksjonen.',
	'wikibase-linkitem-success-create' => 'Sidene er lenket. Du kan finne det nyopprettede datasettet med nettstedlenkene i vårt [$1 sentrale dataregister].',
	'wikibase-linkitem-success-link' => 'Sidene er koblet. Du kan finne datasettet med nettstedlenkene i vårt [$1 sentrale dataregister].',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Vis Wikidata-redigeringer i siste endringer',
);

/** Dutch (Nederlands)
 * @author Konovalov
 * @author Siebrand
 */
$messages['nl'] = array(
	'wikibase-client-desc' => 'Client voor de uitbreiding Wikibase',
	'specialpages-group-wikibaseclient' => 'Wikidataclient',
	'wikibase-after-page-move' => 'U kunt ook het gekoppelde Wikidataitem [$1 bijwerken] om de taalkoppelingen op de hernoemde pagina te kunnen beheren.',
	'wikibase-comment-remove' => 'Het gekoppelde Wikidataitem is verwijderd. De taalkoppelingen zijn verwijderd.',
	'wikibase-comment-linked' => 'Er is een Wikidataitem gekoppeld aan deze pagina.',
	'wikibase-comment-unlink' => 'Deze pagina is ontkoppeld van het Wikidataitem. De taalkoppelingen zijn verwijderd.',
	'wikibase-comment-restore' => 'Het gekoppelde Wikidataitem is teruggeplaatst. De taalkoppelingen zijn hersteld.',
	'wikibase-comment-update' => 'Wikidataitem bijgewerkt',
	'wikibase-comment-sitelink-add' => 'Taalkoppeling toegevoegd: $1',
	'wikibase-comment-sitelink-change' => 'Taalkoppeling gewijzigd van $1 naar $2',
	'wikibase-comment-sitelink-remove' => 'Taalkoppeling verwijderd: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|wijziging|wijzigingen}}',
	'wikibase-editlinks' => 'Koppelingen bewerken',
	'wikibase-editlinkstitle' => 'Intertaalkoppelingen bewerken',
	'wikibase-linkitem-addlinks' => 'Koppelingen toevoegen',
	'wikibase-linkitem-alreadylinked' => 'De pagina waar u naar wilde koppelen is al gekoppeld aan een [$1 item] in de centrale gegevensrepository die koppelt naar $2 op deze site. Items kunnen slechts naar één pagina per site verwijzen. Kies een andere pagina om mee te koppelen.',
	'wikibase-linkitem-close' => 'Venster sluiten en de pagina opnieuw laden',
	'wikibase-linkitem-failure' => 'Er is een onbekende fout opgetreden tijdens het maken van een koppeling naar de opgegeven pagina.',
	'wikibase-linkitem-title' => 'Koppelen met pagina',
	'wikibase-linkitem-linkpage' => 'Koppelen met pagina',
	'wikibase-linkitem-selectlink' => 'Selecteer en site en een pagina waar u deze pagina mee wilt koppelen.',
	'wikibase-linkitem-input-site' => 'Taal:',
	'wikibase-linkitem-input-page' => 'Pagina:',
	'wikibase-linkitem-invalidsite' => 'Er is een onbekende of ongeldige site geselecteerd',
	'wikibase-linkitem-confirmitem-text' => "De pagina die u hebt gekozen is al gekoppeld aan een [$1 item in onze centrale gegevensrepository]. Bevestig dat de onderstaande pagina's inderdaad de pagina's zijn die u met deze pagina wilt koppelen.",
	'wikibase-linkitem-confirmitem-button' => 'Bevestigen',
	'wikibase-linkitem-not-loggedin-title' => 'U moet aangemeld zijn',
	'wikibase-linkitem-not-loggedin' => 'U moet aangemeld zijn bij deze wiki en de [$1 centrale gegevensrepository] om deze functie te kunnen gebruiken.',
	'wikibase-linkitem-success-create' => "De pagina's zijn gekoppeld. U kunt het nieuw aangemaakte item met de koppelingen vinden in de [$1 centrale gegevensrepository].",
	'wikibase-linkitem-success-link' => "De pagina's zijn gekoppeld. U kunt het item met de koppelingen vinden in de [$1 centrale gegevensrepository].",
	'wikibase-property-notfound' => 'Eigenschap $1 niet gevonden.',
	'wikibase-property-notsupportedyet' => 'Wikibase ondersteunt het eigenschapstype voor de eigenschap $1 nog niet.',
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Wikidatabewerkingen weergeven in recente wijzigingen',
	'wikibase-watchlist-show-changes-pref' => 'Wikidata bewerkingen weergeven in uw volglijst',
	'wikibase-error-invalid-entity-id' => 'Het opgegeven ID is niet bekend in het systeem. Gebruik een geldig entiteits-ID.',
	'special-unconnectedpages' => "Pagina's niet niet gekoppeld zijn aan items",
	'wikibase-unconnectedpages-legend' => "Instellingen voor ongekoppelde pagina's",
	'wikibase-unconnectedpages-page' => 'Lijst beginnen met pagina:',
	'wikibase-unconnectedpages-submit' => 'OK',
	'wikibase-unconnectedpages-invalid-language' => '"$1" is geen geldige taalcode.',
	'wikibase-unconnectedpages-page-warning' => 'De paginanaam kan niet worden gebruikt voor de zoekopdracht en wordt genegeerd.',
	'wikibase-unconnectedpages-iwdata-label' => "Alleen pagina's met intertaalkoppelingen",
	'wikibase-unconnectedpages-format-row' => '($1 intertaalkoppeling{{PLURAL:$1||en}} op de pagina)',
	'wikibase-pageinfo-entity-id' => 'Wikidata Item-ID',
	'wikibase-pageinfo-entity-id-none' => 'Geen',
);

/** Norwegian Nynorsk (norsk nynorsk)
 * @author Jeblad
 * @author Njardarlogar
 */
$messages['nn'] = array(
	'wikibase-client-desc' => 'Klient for Wikibase-utvidinga',
	'specialpages-group-wikibaseclient' => 'Wikidata-klient',
	'wikibase-after-page-move' => 'Du kan òg [$1 oppdatera] det tilknytte Wikidata-settet for å halda språklenkjene på den flytte sida ved like.',
	'wikibase-comment-remove' => 'Tilknytt Wikidata-sett sletta. Språklenkjer fjerna.',
	'wikibase-comment-linked' => 'Eit Wikidata-sett har vorte lenkja til sida.',
	'wikibase-comment-unlink' => 'Lenkinga til sida har vorte fjerna frå Wikidata-settet. Språklenkjer fjerna.',
	'wikibase-comment-restore' => 'Tilknytt Wikidata-sett attoppretta. Språklenkjer lagde inn att.',
	'wikibase-comment-update' => 'Wikidata-datasett vart endra',
	'wikibase-comment-sitelink-add' => 'Språklenkje lagd til: $1',
	'wikibase-comment-sitelink-change' => 'Språklenkje endra frå $1 til $2',
	'wikibase-comment-sitelink-remove' => 'Språklenkje fjerna: $1',
	'wikibase-comment-multi' => '{{PLURAL:$1|éi endring|$1 endringar}}',
	'wikibase-editlinks' => 'Endra lenkjer',
	'wikibase-editlinkstitle' => 'Endra mellomspråklege lenkjer',
	'wikibase-linkitem-addlinks' => 'Legg til lenkjer',
	'wikibase-linkitem-alreadylinked' => 'Sida du ynskte å lenkja med er alt knytt til eit [$1 datasett] på det sentrale datalageret som lenkjer til $2 på denne nettstaden. Datasett kan berre ha tilknytt éi side per nettstad. Vel ei anna side å lenkja med.',
	'wikibase-linkitem-close' => 'Lat att dialog og last sida på nytt',
	'wikibase-linkitem-failure' => 'Ein ukjend feil oppstod under lenkinga av sida.',
	'wikibase-linkitem-title' => 'Lenk til side',
	'wikibase-linkitem-linkpage' => 'Lenk til side',
	'wikibase-linkitem-selectlink' => 'Vel ein nettstad og ei side du ynskjer å lenkja til denne sida.',
	'wikibase-linkitem-input-site' => 'Språk:',
	'wikibase-linkitem-input-page' => 'Side:',
	'wikibase-linkitem-invalidsite' => 'Ukjend eller ugild nettstad er vald.',
	'wikibase-linkitem-confirmitem-text' => 'Sida du valde er alt lenkja til eit [$1 datasett på det sentrale datalageret vårt]. Stadfest at sidene viste under er dei du ynskjer at skal lenkjast til denne sida.',
	'wikibase-linkitem-confirmitem-button' => 'Stadfest',
	'wikibase-linkitem-not-loggedin-title' => 'Du lyt vera innlogga',
	'wikibase-linkitem-not-loggedin' => 'Du lyt vera innlogga på denne wikien og på det [$1 sentrale datalageret] for å nytta denne funksjonen.',
	'wikibase-linkitem-success-create' => 'Sidene vart lenkja til kvarandre. Du kan finna det nyoppretta objektet som inneheld lenkjene i det [$1 sentrale datalageret] vårt.',
	'wikibase-linkitem-success-link' => 'Sidene vart lenkja til kvarandre. Du kan finna objektet som inneheld lenkjene i det [$1 sentrale datalageret] vårt.',
	'wikibase-property-notfound' => 'Eigenskapen $1 vart ikkje funnen.',
	'wikibase-property-notsupportedyet' => 'Wikibase stør enno ikkje eigenskapstypen for eigenskapen $1.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Vis Wikidata-endringar i siste endringane',
	'wikibase-watchlist-show-changes-pref' => 'Vis Wikidata-endringar på overvakingslista di',
	'wikibase-error-invalid-entity-id' => 'Den oppgjevne ID-en er ukjend for systemet. Bruk ein gild einings-ID.',
	'special-unconnectedpages' => 'Sider som ikkje er kopla til datasett',
	'wikibase-unconnectedpages-legend' => 'Val for ukopla sider',
	'wikibase-unconnectedpages-page' => 'Byrja resultatlista med denne sida:',
	'wikibase-unconnectedpages-submit' => 'Gå',
	'wikibase-unconnectedpages-invalid-language' => '«$1» er ikkje ein gild språkkode.',
	'wikibase-unconnectedpages-page-warning' => 'Sidetittelen kunne ikkje brukast med spørjinga og er ignorert.',
	'wikibase-unconnectedpages-iwdata-label' => 'Berre sider med mellomspråklege lenkjer',
	'wikibase-unconnectedpages-format-row' => '($1 mellomspråklege lenkjer på sida)',
);

/** Polish (polski)
 * @author BeginaFelicysym
 * @author Lazowik
 * @author Maćko
 * @author Odie2
 * @author WTM
 */
$messages['pl'] = array(
	'wikibase-client-desc' => 'Klient rozszerzenia Wikibase',
	'specialpages-group-wikibaseclient' => 'Klient Wikidanych',
	'wikibase-after-page-move' => 'Możesz także [$1 zaktualizować] powiązany element Wikidanych w celu zachowania linków do innych wersji językowych na przeniesionej stronie.',
	'wikibase-comment-remove' => 'Powiązany element Wikidanych skasowany. Linki do innych wersji językowych usunięte.',
	'wikibase-comment-linked' => 'Element Wikidanych został powiązany z tą stroną.',
	'wikibase-comment-unlink' => 'Zostało zerwane powiązanie elementu Wikidanych z tą stroną. Linki do innych wersji językowych usunięte.',
	'wikibase-comment-restore' => 'Powiązany element Wikidanych odtworzony. Linki do innych wersji językowych przywrócone.',
	'wikibase-comment-update' => 'Element Wikidanych zmieniony',
	'wikibase-comment-sitelink-add' => 'Dodano link interwiki: $1',
	'wikibase-comment-sitelink-change' => 'Zmieniono link interwiki z $1 na $2',
	'wikibase-comment-sitelink-remove' => 'Usunięto link interwiki: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|zmiana|zmiany|zmian}}',
	'wikibase-editlinks' => 'Edytuj linki',
	'wikibase-editlinkstitle' => 'Edytuj linki wersji językowych',
	'wikibase-linkitem-addlinks' => 'Dodaj linki',
	'wikibase-linkitem-alreadylinked' => 'Wybrana strona jest już wykorzystywana w naszym centralnym repozytorium danych przez [$1 element], który odsyła do strony $2 tutaj. Każdy element może być powiązany tylko z jedną stroną w witrynie. Wybierz, proszę, inną stronę.',
	'wikibase-linkitem-close' => 'Zamknij okno i odśwież stronę',
	'wikibase-linkitem-failure' => 'Wystąpił nieznany błąd podczas próby powiązania podanej strony.',
	'wikibase-linkitem-title' => 'Powiąż ze stroną',
	'wikibase-linkitem-linkpage' => 'Powiąż ze stroną',
	'wikibase-linkitem-selectlink' => 'Wybierz serwis i stronę z którą chcesz powiązać tę stronę.',
	'wikibase-linkitem-input-site' => 'Język:',
	'wikibase-linkitem-input-page' => 'Strona:',
	'wikibase-linkitem-invalidsite' => 'Wybrano nieznaną lub niepoprawną stronę',
	'wikibase-linkitem-confirmitem-text' => 'Wybrana strona jest już powiązana z [$1 elementem w naszym centralnym repozytorium danych]. Potwierdź, że strony wymienione poniżej są tymi, z którymi chcesz powiązać tę stronę.',
	'wikibase-linkitem-confirmitem-button' => 'Potwierdź',
	'wikibase-linkitem-not-loggedin-title' => 'Musisz być zalogowany.',
	'wikibase-linkitem-not-loggedin' => 'Aby użyć tej opcji musisz być zalogowany na tej wiki oraz [$1 centralnym repozytorium danych].',
	'wikibase-linkitem-success-create' => 'Strony zostały pomyślnie powiązane. Utworzony element zawierający linki możesz znaleźć w naszym [$1 centralnym repozytorium danych].',
	'wikibase-linkitem-success-link' => 'Strony zostały pomyślnie powiązane. Element zawierający linki możesz znaleźć w naszym [$1 centralnym repozytorium danych].',
	'wikibase-property-notfound' => 'Właściwość $1 nie została znaleziona.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidane',
	'wikibase-rc-show-wikidata-pref' => 'Pokazuj edycje na Wikidanych w ostatnich zmianach',
	'wikibase-watchlist-show-changes-pref' => 'Pokazuj edycje na Wikidanych w obserwowanych',
	'wikibase-error-invalid-entity-id' => 'Wprowadzony identyfikator jest nieznany w systemie. Użyj prawidłowego identyfikatora encji.',
	'special-unconnectedpages' => 'Nie powiązane strony',
	'wikibase-unconnectedpages-legend' => 'Opcje stron niepowiązanych',
	'wikibase-unconnectedpages-page' => 'Rozpocznij wyświetlanie wyników od strony:',
	'wikibase-unconnectedpages-submit' => 'Pokaż',
	'wikibase-unconnectedpages-invalid-language' => '"$1" jest nie prawidłowym kodem języka.',
	'wikibase-unconnectedpages-page-warning' => 'Wpisany tytuł strony nie może być użyty do wyszukiwania, dlatego został zignorowany.',
	'wikibase-unconnectedpages-iwdata-label' => 'Tylko strony z odnośnikami interwiki w treści',
	'wikibase-unconnectedpages-format-row' => '(zawiera $1 {{PLURAL:$1|odnośnik interwiki|odnośniki interwiki|odnośników interwiki}})',
);

/** Piedmontese (Piemontèis)
 * @author Borichèt
 * @author Dragonòt
 */
$messages['pms'] = array(
	'wikibase-client-desc' => "Client për l'estension Wikibase",
	'wikibase-after-page-move' => "A peul ëdcò [$1 agiorné] j'element Wikidata associà për goerné le liure ëd lenga an sla pàgina tramudà.",
	'wikibase-comment-remove' => 'Element Wikidata associà scancelà. Liura ëd lenga gavà.',
	'wikibase-comment-linked' => "N'element Wikidata a l'é stàit colegà a sta pàgina.",
	'wikibase-comment-unlink' => "Sta pàgina a l'é stàita dëscolegà da l'element Wikidata. Liure ëd lenga gavà.",
	'wikibase-comment-restore' => 'Element associà Wikidata ripristinà. Liure ëd lenga ripristinà.',
	'wikibase-comment-update' => 'Liure ëd lenga agiornà.', # Fuzzy
	'wikibase-comment-sitelink-add' => 'Liure ëd lenga giontà: $1',
	'wikibase-comment-sitelink-change' => 'Liure ëd lenga modificà da $1 a $2',
	'wikibase-comment-sitelink-remove' => 'Liure ëd lenga gavà: $1',
	'wikibase-editlinks' => "Modifiché j'anliure",
	'wikibase-editlinkstitle' => 'Modifiché le liure antërlenga',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => "Smon-e le modìfiche ëd Wikidata ant j'ùltime modìfiche",
);

/** Portuguese (português)
 * @author Helder.wiki
 * @author Lijealso
 * @author Malafaya
 * @author SandroHc
 */
$messages['pt'] = array(
	'wikibase-client-desc' => 'Cliente para a extensão Wikibase',
	'wikibase-after-page-move' => 'Também pode [$1 actualizar] o item do Wikidata associado para manter os links de idioma na página movida.',
	'wikibase-comment-remove' => 'O item associado no Wikidata foi eliminado. Foram removidos os links para outros idiomas.',
	'wikibase-comment-linked' => 'Um item do Wikidata foi ligado a esta página.',
	'wikibase-comment-unlink' => 'O link desta página foi retirado do item do Wikidata. Os links para outros idiomas foram removidos.',
	'wikibase-comment-restore' => 'O item associado no Wikidata foi restaurado. Foram restaurados os links para outros idiomas.',
	'wikibase-comment-update' => 'Foram atualizados os links para outros idiomas',
	'wikibase-comment-sitelink-add' => 'Link de idioma adicionado:$1',
	'wikibase-comment-sitelink-change' => 'Link de idioma alterado de  $1 para $2',
	'wikibase-comment-sitelink-remove' => 'Link de idioma removido: $1',
	'wikibase-editlinks' => 'Editar links',
	'wikibase-editlinkstitle' => 'Editar links interlínguas',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Mostrar as edições no Wikidata nas mudanças recentes',
);

/** Brazilian Portuguese (português do Brasil)
 * @author Helder.wiki
 * @author Jaideraf
 * @author TheGabrielZaum
 * @author Tuliouel
 */
$messages['pt-br'] = array(
	'wikibase-client-desc' => 'Cliente para a extensão Wikibase',
	'specialpages-group-wikibaseclient' => 'Cliente Wikidata',
	'wikibase-after-page-move' => 'Você também pode [$1 atualizar] o item associado ao Wikidata para manter os links de idioma na página movida.',
	'wikibase-comment-remove' => 'O item associado no Wikidata foi eliminado. Os links para os outros idiomas foram removidos.',
	'wikibase-comment-linked' => 'Um item do Wikidata foi associado a esta página.',
	'wikibase-comment-unlink' => 'O link desta página foi retirado do item do Wikidata. Os links para os outros idiomas foram removidos.',
	'wikibase-comment-restore' => 'O item associado no Wikidata foi restaurado. Os links para os outros idiomas foram restaurados.',
	'wikibase-comment-update' => 'O item no Wikidata foi modificado',
	'wikibase-comment-sitelink-add' => 'Link de idioma adicionado: $1',
	'wikibase-comment-sitelink-change' => 'Link de idioma alterado de $1 para $2',
	'wikibase-comment-sitelink-remove' => 'Link de idioma removido: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|mudança|mudanças}}',
	'wikibase-editlinks' => 'Editar links',
	'wikibase-editlinkstitle' => 'Editar links para outros idiomas',
	'wikibase-linkitem-addlinks' => 'Adicionar links',
	'wikibase-linkitem-alreadylinked' => 'A página que você queria vincular já está anexada a um [$1 item] no repositório central de dados que vincula a $2 neste site. Itens só podem possuir uma página por site anexado. Por favor, escolha uma página diferente para vincular.',
	'wikibase-linkitem-close' => 'Feche a caixa de diálogo e recarrega a página',
	'wikibase-linkitem-failure' => 'Ocorreu um erro desconhecido ao tentar vincular a determinada página.',
	'wikibase-linkitem-title' => 'Link com a página',
	'wikibase-linkitem-linkpage' => 'Link com a página',
	'wikibase-linkitem-selectlink' => 'Por favor, selecione um site e uma página que você deseja vincular a esta página.',
	'wikibase-linkitem-input-site' => 'Idioma:',
	'wikibase-linkitem-input-page' => 'Página:',
	'wikibase-linkitem-invalidsite' => 'Site desconhecido ou inválido selecionado',
	'wikibase-linkitem-confirmitem-text' => 'A página que você escolheu já está vinculada a um [$1 item em nosso repositório central de dados]. Por favor, confirme que as páginas exibidas abaixo são as que você deseja vincular com esta página.',
	'wikibase-linkitem-confirmitem-button' => 'Confirmar',
	'wikibase-linkitem-not-loggedin-title' => 'Você precisa estar logado',
	'wikibase-linkitem-not-loggedin' => 'Você precisa estar logado neste wiki e no [$1 repositório central de dados] para utilizar esse recurso.',
	'wikibase-linkitem-success-create' => 'As páginas foram vinculadas com êxito. Você pode encontrar o item recém criado contendo os links em nosso [$1 repositório central de dados].',
	'wikibase-linkitem-success-link' => 'As páginas foram vinculadas com êxito. Você pode encontrar o item que contém os links em nosso [$1 repositório central de dados].',
	'wikibase-property-notfound' => 'A propriedade $1 não foi encontrada.',
	'wikibase-property-notsupportedyet' => 'O WikiBase ainda não suporta o tipo de propriedade para a propriedade $1.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Mostrar as edições do Wikidata nas mudanças recentes',
	'wikibase-watchlist-show-changes-pref' => 'Mostrar as edições do Wikidata em sua lista de vigiados',
	'wikibase-error-invalid-entity-id' => 'O ID inserido é desconhecido pelo sistema. Por favor, utilize um ID de entidade válido.',
	'special-unconnectedpages' => 'Páginas não conectadas a itens',
	'wikibase-unconnectedpages-legend' => 'Opções de páginas não conectadas',
	'wikibase-unconnectedpages-page' => 'Iniciar lista de resultados com o artículo:', # Fuzzy
	'wikibase-unconnectedpages-submit' => 'Ir',
	'wikibase-unconnectedpages-invalid-language' => '"$1" não é um código de idioma válido.',
	'wikibase-unconnectedpages-page-warning' => 'O título da página não pôde ser utilizado para a consulta e foi ignorado.',
	'wikibase-unconnectedpages-iwdata-label' => 'Apenas páginas com links interlínguas',
	'wikibase-unconnectedpages-format-row' => '($1 links interlínguas na página)',
);

/** Quechua (Runa Simi)
 * @author AlimanRuna
 */
$messages['qu'] = array(
	'wikibase-after-page-move' => "T'inkisqa Wikidata qallawatapas [$1 musuqchaytam] atinki astasqa p'anqapi rimay t'inkikunata hat'allinaykipaq.",
	'wikibase-editlinks' => "T'inkikunata llamk'apuy",
	'wikibase-editlinkstitle' => "Wikipura t'inkikunata llamk'apuy",
	'wikibase-linkitem-addlinks' => "T'inkikunata yapay",
	'wikibase-linkitem-title' => "P'anqawan t'inkiy",
	'wikibase-linkitem-linkpage' => "P'anqawan t'inkiy",
	'wikibase-linkitem-selectlink' => "Ama hina kaspa, llika tiyayta p'anqatapas akllay kay p'anqawan t'inkinanpaq.",
	'wikibase-linkitem-input-site' => 'Rimay:',
	'wikibase-linkitem-input-page' => "P'anqa:",
);

/** Romansh (rumantsch)
 * @author Kazu89
 */
$messages['rm'] = array(
	'wikibase-editlinks' => 'Modifitgar colliaziuns',
	'wikibase-linkitem-addlinks' => 'Agiunscher colliaziuns',
	'wikibase-linkitem-input-site' => 'Lingua',
	'wikibase-linkitem-input-page' => 'Pagina',
);

/** Romanian (română)
 * @author Minisarm
 * @author Stelistcristi
 */
$messages['ro'] = array(
	'wikibase-client-desc' => 'Client pentru extensia Wikibase',
	'specialpages-group-wikibaseclient' => 'Client Wikidata',
	'wikibase-after-page-move' => 'Puteți, de asemenea, [$1 să actualizați] elementul Wikidata asociat pentru a menține legăturile linguale pe pagina redenumită.',
	'wikibase-comment-remove' => 'Elementul Wikidata asociat a fost șters. Legăturile linguale au fost înlăturate.',
	'wikibase-comment-linked' => 'Un element Wikidata a fost legat către această pagină.',
	'wikibase-comment-update' => 'Elementul Wikidata a fost modificat',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|modificare|modificări|de modificări}}',
	'wikibase-editlinks' => 'Modifică legăturile',
	'wikibase-editlinkstitle' => 'Modifică legăturile interlinguale',
	'wikibase-linkitem-addlinks' => 'Adaugă legături',
	'wikibase-linkitem-close' => 'Închide caseta de dialog și reîncarcă pagina',
	'wikibase-linkitem-input-site' => 'Limbă:',
	'wikibase-linkitem-input-page' => 'Pagină:',
	'wikibase-linkitem-confirmitem-button' => 'Confirmare',
	'wikibase-linkitem-not-loggedin-title' => 'Trebuie să fiți autentificat',
	'wikibase-property-notfound' => 'Proprietatea $1 nu a fost găsită.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-unconnectedpages-submit' => 'Du-te',
	'wikibase-unconnectedpages-invalid-language' => '„$1” nu este un cod de limbă valid.',
	'wikibase-unconnectedpages-iwdata-label' => 'Doar pagini cu legături interlinguale',
);

/** tarandíne (tarandíne)
 * @author Joetaras
 */
$messages['roa-tara'] = array(
	'wikibase-comment-update' => 'Vôsce de Uicchidata cangiate',
	'wikibase-comment-sitelink-add' => "Collegamende d'a lènghe aggiunde: $1",
	'wikibase-comment-sitelink-remove' => "Collegamende d'a lènghe luate: $1",
	'wikibase-editlinks' => 'Cange le collegaminde',
	'wikibase-linkitem-addlinks' => 'Aggiunge le collegaminde',
	'wikibase-linkitem-title' => "Collegate cu 'a pàgene",
	'wikibase-linkitem-linkpage' => "Collegate cu 'a pàgene",
	'wikibase-linkitem-input-site' => 'Lènghe:',
	'wikibase-linkitem-input-page' => 'Pàgene:',
	'wikibase-unconnectedpages-submit' => 'Véje',
	'wikibase-unconnectedpages-invalid-language' => '"$1" non g\'è \'nu codece de lènghe valide.',
);

/** Russian (русский)
 * @author Ignatus
 * @author Kaganer
 * @author Ole Yves
 * @author Александр Сигачёв
 */
$messages['ru'] = array(
	'wikibase-client-desc' => 'Клиент для расширения Wikibase',
	'specialpages-group-wikibaseclient' => 'Клиент Викиданных',
	'wikibase-after-page-move' => 'Чтобы исправить на переименованной странице языковые ссылки, вы можете также [$1  обновить] связанный элемент Викиданных.',
	'wikibase-comment-remove' => 'Связанный элемент Викиданных удалён. Языковые ссылки ликвидированы.',
	'wikibase-comment-linked' => 'Элемент Викиданных был связан с данной страницей.',
	'wikibase-comment-unlink' => 'Связь этой страницы с элементом Викиданных была разорвана. Языковые ссылки удалены.',
	'wikibase-comment-restore' => 'Удаление связанного элемента Викиданных отменено. Языковые ссылки восстановлены.',
	'wikibase-comment-update' => 'Элемент Викиданных изменён',
	'wikibase-comment-sitelink-add' => 'Интервики-ссылка добавлена: $1.',
	'wikibase-comment-sitelink-change' => 'Интервики-ссылка изменена с $1 на $2',
	'wikibase-comment-sitelink-remove' => 'Интервики-ссылка удалена: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|изменение|изменения|изменений}}',
	'wikibase-editlinks' => 'Править ссылки',
	'wikibase-editlinkstitle' => 'Править межъязыковые ссылки',
	'wikibase-linkitem-addlinks' => 'Добавить ссылки',
	'wikibase-linkitem-alreadylinked' => 'Cтранице, c которую вы хотели установить связь, уже прикреплена к [$1 элементу] в центральном хранилище данных, который ссылается на $2 на этом сайте. Элементы могут иметь только по одной прикреплённой странице с каждого сайта. Пожалуйста, выберите другую страницу для установления связи.',
	'wikibase-linkitem-close' => 'Закрыть диалог и перезагрузить страницу',
	'wikibase-linkitem-failure' => 'При попытке привязать данную страницу произошла неизвестная ошибка.',
	'wikibase-linkitem-title' => 'Связь со страницей',
	'wikibase-linkitem-linkpage' => 'Связать со страницей',
	'wikibase-linkitem-selectlink' => 'Пожалуйста, выберите сайт и страницу, на которую вы хотите поставить ссылку отсюда.',
	'wikibase-linkitem-input-site' => 'Язык:',
	'wikibase-linkitem-input-page' => 'Страница:',
	'wikibase-linkitem-invalidsite' => 'Выбран неизвестный или некорректный сайт',
	'wikibase-linkitem-confirmitem-text' => 'Выбранная вами страница уже связана с [$1 элементом нашего центрального репозитория данных]. Пожалуйста, подтвердите, что среди показанных ниже страниц есть та, на которую вы хотели поставить ссылку отсюда.',
	'wikibase-linkitem-confirmitem-button' => 'Подтвердить',
	'wikibase-linkitem-not-loggedin-title' => 'Вы должны авторизоваться',
	'wikibase-linkitem-not-loggedin' => 'Чтобы воспользоваться этой функцией, вы должны быть авторизованы в этой вики и в [$1 центральном репозитории данных].',
	'wikibase-linkitem-success-create' => 'Страница успешно связана. Вновь созданный элемент, содержащий связи, находится в нашем [$1 центральном хранилище данных].',
	'wikibase-linkitem-success-link' => 'Страницы были успешно связаны. Элемент, содержащий связи, находится в нашем [$1 центральном хранилище данных].',
	'wikibase-property-notfound' => 'Свойство «$1» не найдено',
	'wikibase-property-notsupportedyet' => 'Wikibase пока не поддерживает тип свойства «$1».',
	'wikibase-rc-hide-wikidata' => '$1 Викиданные',
	'wikibase-rc-show-wikidata-pref' => 'Показать изменения Викиданных в списке свежих правок',
	'wikibase-watchlist-show-changes-pref' => 'Показать изменения на Викиданных в вашем списке наблюдения',
	'wikibase-error-invalid-entity-id' => 'Введённый идентификатор не известен системе. Используйте один из действующих идентификаторов сущностей.',
	'special-unconnectedpages' => 'Страницы, не связанные с элементами',
	'wikibase-unconnectedpages-legend' => 'Параметры несвязанных страниц',
	'wikibase-unconnectedpages-page' => 'Начало списка результатов со страницей:',
	'wikibase-unconnectedpages-submit' => 'Применить',
	'wikibase-unconnectedpages-invalid-language' => '«$1» — неправильный код языка.',
	'wikibase-unconnectedpages-page-warning' => 'Это название страницы не может быть использовано в данном запросе и игнорируется.',
	'wikibase-unconnectedpages-iwdata-label' => 'Только страницы с межъязыковыми ссылками:',
	'wikibase-unconnectedpages-format-row' => '($1 межъязыков{{PLURAL:$1|ая ссылка|ые ссылки|ых ссылок}} на странице)',
);

/** Sinhala (සිංහල)
 * @author පසිඳු කාවින්ද
 */
$messages['si'] = array(
	'wikibase-client-desc' => 'විකිපාදක දිගුව සඳහා සේවාදායකයා',
	'wikibase-comment-update' => 'භාෂා සබැඳි යාවත්කාලීන කරන ලදී.',
	'wikibase-comment-sitelink-add' => 'භාෂා සබැඳිය එක් කරන ලදී: $1',
	'wikibase-comment-sitelink-change' => 'භාෂා සබැඳිය $1 ගෙන් $2 වෙත වෙනස් වෙන ලදී',
	'wikibase-comment-sitelink-remove' => 'භාෂා සබැඳිය ඉවත් කරන ලදී: $1',
	'wikibase-editlinks' => 'සබැඳි සංස්කරණය කරන්න',
	'wikibase-editlinkstitle' => 'අන්තර්භාෂාමය සබැඳි සංස්කරණය කරන්න',
	'wikibase-rc-hide-wikidata' => '$1 විකිදත්ත',
	'wikibase-rc-show-wikidata-pref' => 'මෑත වෙනස්කම්වල විකිදත්ත සංස්කරණ පෙන්වන්න',
);

/** Slovak (slovenčina)
 * @author JAn Dudík
 * @author KuboF
 */
$messages['sk'] = array(
	'wikibase-client-desc' => 'Klient pre rozšírenie Wikibase',
	'wikibase-comment-sitelink-add' => 'Pridaný medzijazykový odkaz: $1',
	'wikibase-editlinks' => 'Upraviť odkazy',
	'wikibase-editlinkstitle' => 'Upraviť medzijazykové odkazy',
	'wikibase-linkitem-addlinks' => 'Pridať odkazy',
	'wikibase-linkitem-input-site' => 'Jazyk:',
	'wikibase-linkitem-input-page' => 'Stránka:',
	'wikibase-linkitem-confirmitem-button' => 'Potvrdiť',
	'wikibase-linkitem-not-loggedin-title' => 'Musíš byť prihlásený',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Zobraziť úpravy Wikidat v posledných zmienách',
);

/** Serbian (Cyrillic script) (српски (ћирилица)‎)
 * @author Nikola Smolenski
 * @author Rancher
 * @author Милан Јелисавчић
 */
$messages['sr-ec'] = array(
	'wikibase-client-desc' => 'Клијент за проширење Викибаза',
	'specialpages-group-wikibaseclient' => 'Клијент Википодатака',
	'wikibase-after-page-move' => 'Можете такође [$1 ажурирати] придружене ставке на Википодацима ради одржавања језичких веза на премештеној страници.',
	'wikibase-comment-remove' => 'Придружена ставка на Википодацима је избрисана. Језичке везе уклоњене.',
	'wikibase-comment-linked' => 'Ставка на Википодацима је повезана са овом страницом.',
	'wikibase-comment-unlink' => 'Веза до ове странице је уклоњена са ставке на Википодацима. Језичке везе уклоњене.',
	'wikibase-comment-restore' => 'Придружена ставка на Википодацима је опозвана. Језичке везе поново успостављене.',
	'wikibase-comment-update' => 'Измењена ставка на Википодацима',
	'wikibase-comment-sitelink-add' => 'Додата веза за језик: $1',
	'wikibase-comment-sitelink-change' => 'Веза на језику промењена са $1 на $2',
	'wikibase-comment-sitelink-remove' => 'Уклоњена веза за језик: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|измена}}',
	'wikibase-editlinks' => 'Уреди везе',
	'wikibase-editlinkstitle' => 'Уређивање међујезичких веза',
	'wikibase-linkitem-addlinks' => 'Додај везе',
	'wikibase-linkitem-alreadylinked' => 'Страница коју сте желели да повежете је већ прикачена на [$1 ставку] на централном спремишту података који води до $2 на овом сајту. Ставке могу да имају само једну прикачену страницу по сајту. Изаберите другу страницу да бисте је повезали.',
	'wikibase-linkitem-close' => 'Затвори дијалог и поново учитај страницу',
	'wikibase-linkitem-failure' => 'Непозната грешка се појавила при покушају повезивања дате странице.',
	'wikibase-linkitem-title' => 'Веза са страницом',
	'wikibase-linkitem-linkpage' => 'Повежи са страном',
	'wikibase-linkitem-selectlink' => 'Изаберите сајт и страницу са којом желите да повежете ову страницу.',
	'wikibase-linkitem-input-site' => 'Језик:',
	'wikibase-linkitem-input-page' => 'Страница:',
	'wikibase-linkitem-invalidsite' => 'Изабран непознато или неважећи сајт',
	'wikibase-linkitem-confirmitem-text' => 'Страница коју сте изабрали је већ повезана са [$1 ставком на нашој централној бази података]. Молимо вас потврдите да су странице испод приказане оне које желите да повежете са овом страницом.',
	'wikibase-linkitem-confirmitem-button' => 'Потврди',
	'wikibase-linkitem-not-loggedin-title' => 'Морате бити пријављени',
	'wikibase-linkitem-not-loggedin' => 'Морате бити пријављени на овом вики-сајту и на [$1 главном складишту података] како бисте користили ову могућност.',
	'wikibase-linkitem-success-create' => 'Странице су успешно повезане. Можете наћи новоуспостављену ставку која садржи везе на нашој [$1 централној бази података].',
	'wikibase-linkitem-success-link' => 'Странице су успешно повезане. Можете пронаћи ставку која садржи везе на нашој [$1 централној бази података].',
	'wikibase-property-notfound' => '$1 својство није пронађено.',
	'wikibase-property-notsupportedyet' => 'Викибаза још не подржава врсту својства за $1 својство.',
	'wikibase-rc-hide-wikidata' => '$1 Википодаци',
	'wikibase-rc-show-wikidata-pref' => 'Прикажи измене на Википодацима у скорашњим изменама',
	'wikibase-watchlist-show-changes-pref' => 'Прикажи измене на Википодацима у списку надгледања',
	'wikibase-error-invalid-entity-id' => 'Унети идентификатор је непознат систему. Молимо унесите важећи ИД.',
	'special-unconnectedpages' => 'Странице нису повезане са ставкама',
	'wikibase-unconnectedpages-legend' => 'Могућности неповезаних страница',
	'wikibase-unconnectedpages-page' => 'Почетак списка резултата страницом:',
	'wikibase-unconnectedpages-submit' => 'Иди',
	'wikibase-unconnectedpages-invalid-language' => '„$1“ није исправан код за језик.',
	'wikibase-unconnectedpages-page-warning' => 'Наслов странице не може се користи за упит и биће игнорисан.',
	'wikibase-unconnectedpages-iwdata-label' => 'Само странице са међујезичким везама',
	'wikibase-unconnectedpages-format-row' => '($1 међујезичких веза на страници)',
);

/** Serbian (Latin script) (srpski (latinica)‎)
 */
$messages['sr-el'] = array(
	'wikibase-client-desc' => 'Klijent za proširenje Vikibaza',
	'wikibase-editlinks' => 'Uredi veze',
	'wikibase-editlinkstitle' => 'Uređivanje međujezičkih veza',
);

/** Swedish (svenska)
 * @author Ainali
 * @author Lokal Profil
 * @author Stryn
 * @author WikiPhoenix
 */
$messages['sv'] = array(
	'wikibase-client-desc' => 'Klient för tillägget Wikibase',
	'specialpages-group-wikibaseclient' => 'Wikidata-klient',
	'wikibase-after-page-move' => 'Du kan också [$1 uppdatera] det associerade objektet på Wikidata för att bevara språklänkarna i den flyttade sidan.',
	'wikibase-comment-remove' => 'Tillhörande Wikidata objekt togs bort. Språklänkar togs bort.',
	'wikibase-comment-linked' => 'Ett Wikidata-objekt har länkats till den här sidan.',
	'wikibase-comment-unlink' => 'Denna sida har gjorts olänkad från Wikidata-objektet. Språklänkar togs bort.',
	'wikibase-comment-restore' => 'Tillhörande Wikidata-objekt togs bort. Språklänkar togs bort.',
	'wikibase-comment-update' => 'Wikidataobjekt ändrades',
	'wikibase-comment-sitelink-add' => 'Språklänk lades till: $1',
	'wikibase-comment-sitelink-change' => 'Språklänk ändrades från $1 till $2',
	'wikibase-comment-sitelink-remove' => 'Språklänk togs bort: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|ändring|ändringar}}',
	'wikibase-editlinks' => 'Redigera länkar',
	'wikibase-editlinkstitle' => 'Redigera interwikilänkar',
	'wikibase-linkitem-addlinks' => 'Lägg till länkar',
	'wikibase-linkitem-alreadylinked' => 'Sidan du vill länka med är redan kopplad till ett [$1 objekt] i den centrala databasen som länkar till $2 på denna webbplats. Objekt kan endast ha en bifogad sida per webbplats. Välj en annan sida att länka med.',
	'wikibase-linkitem-close' => 'Stäng dialogruta och uppdatera sidan',
	'wikibase-linkitem-failure' => 'Ett okänt fel uppstod när den angivna sidan skulle länkas.',
	'wikibase-linkitem-title' => 'Länka med sida',
	'wikibase-linkitem-linkpage' => 'Länka med sida',
	'wikibase-linkitem-selectlink' => 'Var god välj en webbplats och en sida du vill länka denna sida med.',
	'wikibase-linkitem-input-site' => 'Språk:',
	'wikibase-linkitem-input-page' => 'Sida:',
	'wikibase-linkitem-invalidsite' => 'Okänd eller ogiltig webbplats har valts',
	'wikibase-linkitem-confirmitem-text' => 'Sidan du valde är redan kopplad till ett [$1 objekt på vår centrala databas]. Var god bekräfta att sidorna nedan är dem du vill länka med denna sida.',
	'wikibase-linkitem-confirmitem-button' => 'Bekräfta',
	'wikibase-linkitem-not-loggedin-title' => 'Du måste vara inloggad',
	'wikibase-linkitem-not-loggedin' => 'Du måste vara inloggad på denna wiki och i [$1 den centrala databasen] att använda denna funktion.',
	'wikibase-linkitem-success-create' => 'Sidorna har länkats. Du kan hitta de nyskapade objekten som innehåller länkarna i vår [$1 centrala databas].',
	'wikibase-linkitem-success-link' => 'Sidorna har länkats. Du kan hitta objektet som innehåller länkarna i vår [$1 centrala databas].',
	'wikibase-property-notfound' => 'Egenskapen $1 hittades inte.',
	'wikibase-property-notsupportedyet' => 'Wikibase stöder ännu inte egenskapstypen för egenskapen $1.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Visa Wikidataredigeringar i senaste ändringar',
	'wikibase-watchlist-show-changes-pref' => 'Visa Wikidata-redigeringar i din bevakningslista',
	'wikibase-error-invalid-entity-id' => 'Det angivna ID:et är okänt för systemet. Var god använd ett giltigt enhets-ID.',
	'special-unconnectedpages' => 'Sidor som inte är anslutna till objekt',
	'wikibase-unconnectedpages-legend' => 'Alternativ för sidor som inte är anslutna',
	'wikibase-unconnectedpages-page' => 'Börja resultatlistan med sidan:',
	'wikibase-unconnectedpages-submit' => 'Gå',
	'wikibase-unconnectedpages-invalid-language' => '"$1" är inte en giltig språkkod.',
	'wikibase-unconnectedpages-page-warning' => 'Sidtiteln kunde inte användas till förfrågan och ignoreras.',
	'wikibase-unconnectedpages-iwdata-label' => 'Endast sidor med interspråklänkar',
	'wikibase-unconnectedpages-format-row' => '($1 interspråklänkar på sidan)',
);

/** Tamil (தமிழ்)
 * @author Shanmugamp7
 * @author மதனாஹரன்
 */
$messages['ta'] = array(
	'wikibase-editlinks' => 'இணைப்புக்களைத் தொகு',
	'wikibase-rc-hide-wikidata' => '$1 விக்கித்தரவு',
	'wikibase-rc-show-wikidata-pref' => 'விக்கித்தரவு தொகுப்புகளை அண்மைய மாற்றங்களில் காண்பி',
);

/** Telugu (తెలుగు)
 * @author Veeven
 */
$messages['te'] = array(
	'wikibase-editlinks' => 'లంకెలను మార్చు',
	'wikibase-linkitem-addlinks' => 'లంకెలను చేర్చండి',
	'wikibase-rc-hide-wikidata' => 'వికీడాటాను $1',
	'wikibase-rc-show-wikidata-pref' => 'వికీడామా మార్పులను ఇటీవలి మార్పులలో చూపించు',
);

/** Tagalog (Tagalog)
 * @author AnakngAraw
 */
$messages['tl'] = array(
	'wikibase-client-desc' => 'Kliyente para sa dugtong na Wikibase',
	'wikibase-editlinks' => 'Baguhin ang mga kawing',
	'wikibase-editlinkstitle' => 'Baguhin ang mga kawing na para sa interwika',
);

/** Turkish (Türkçe)
 * @author Sadrettin
 */
$messages['tr'] = array(
	'specialpages-group-wikibaseclient' => 'Vikiveri istemcisi',
	'wikibase-rc-hide-wikidata' => 'Vikiveri değişikliklerini $1',
	'wikibase-rc-show-wikidata-pref' => 'Vikiveri düzenlemelerini son değişikliklerde göster',
	'special-unconnectedpages' => 'Öğelerine bağlanmamış sayfalar',
	'wikibase-unconnectedpages-legend' => 'Bağlanmamış sayfa seçenekleri',
	'wikibase-unconnectedpages-submit' => 'Git',
	'wikibase-unconnectedpages-iwdata-label' => 'Sadece dillerarası bağlantıları olan sayfalar',
);

/** Ukrainian (українська)
 * @author AS
 * @author Base
 * @author Lystopad
 * @author Microcell
 * @author Ата
 */
$messages['uk'] = array(
	'wikibase-client-desc' => 'Клієнт для розширення Wikibase',
	'specialpages-group-wikibaseclient' => 'Клієнт Вікіданих',
	'wikibase-after-page-move' => "Щоб виправити мовні посилання на перейменованій сторінці, Ви також можете [$1 оновити] пов'язаний елемент Вікіданих.",
	'wikibase-comment-remove' => "Пов'язаний елемент Вікіданих видалений. Мовні посилання видалені.",
	'wikibase-comment-linked' => 'Елемент Вікіданих посилався на цю сторінку.',
	'wikibase-comment-unlink' => "Ця сторінка була від'єднана від елемента Вікіданих. Мовні посилання видалені.",
	'wikibase-comment-restore' => "Пов'язаний елемент Вікіданих відновлений. Мовні посилання відновлені.",
	'wikibase-comment-update' => 'Елемент Вікіданих змінено',
	'wikibase-comment-sitelink-add' => 'Додано мовне посилання: $1',
	'wikibase-comment-sitelink-change' => 'Мовне посилання змінено з $1 на $2',
	'wikibase-comment-sitelink-remove' => 'Мовне посилання видалено: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|зміна|зміни|змін}}',
	'wikibase-editlinks' => 'Редагувати посилання',
	'wikibase-editlinkstitle' => 'Редагувати міжмовні посилання',
	'wikibase-linkitem-addlinks' => 'Додати посилання',
	'wikibase-linkitem-alreadylinked' => "Сторінка, до якої Ви хотіли прив'язатись, уже прикріплена до [$1 елемента] у центральному сховищі даних, що посилається на сторінку $2 цього сайту. До елемента можна прикріпити тільки одну сторінку з сайту. Будь ласка, оберіть іншу сторінку для зв'язку.",
	'wikibase-linkitem-close' => 'Закрити діалог і оновити сторінку',
	'wikibase-linkitem-failure' => "При спробі прив'язати вибрану сторінку сталася невідома помилка.",
	'wikibase-linkitem-title' => "Прив'язати до сторінки",
	'wikibase-linkitem-linkpage' => "Прив'язати до сторінки",
	'wikibase-linkitem-selectlink' => "Виберіть сайт і сторінку, яку треба прив'язати до активної сторінки.",
	'wikibase-linkitem-input-site' => 'Мова:',
	'wikibase-linkitem-input-page' => 'Сторінка:',
	'wikibase-linkitem-invalidsite' => 'Вибрано невідомий або недопустимий сайт',
	'wikibase-linkitem-confirmitem-text' => "Сторінка, до якої Ви хотіли прив'язатись, уже прикріплена до [$1 елемента у центральному сховищі даних]. Підтвердіть, що наведений нижче список сторінок до прив'язання складено правильно.",
	'wikibase-linkitem-confirmitem-button' => 'Підтвердити',
	'wikibase-linkitem-not-loggedin-title' => 'Вам необхідно увійти в систему',
	'wikibase-linkitem-not-loggedin' => 'Вам необхідно увійти в систему на цій вікі і на [$1 центральному сховищі даних], щоб скористатись цією функцією.',
	'wikibase-linkitem-success-create' => "Сторінки було успішно пов'язано. Ви можете знайти новостворений елемент, який містить ці посилання, у нашому [$1 центральному сховищі даних].",
	'wikibase-linkitem-success-link' => "Сторінки було успішно пов'язано. Ви можете знайти елемент, який містить ці посилання, у нашому [$1 центральному сховищі даних].",
	'wikibase-property-notfound' => 'Властивість $1 не знайдено.',
	'wikibase-property-notsupportedyet' => 'Вікідані поки що не підтримують тип властивості для властивості $1.',
	'wikibase-rc-hide-wikidata' => '$1 Вікідані',
	'wikibase-rc-show-wikidata-pref' => 'Показати зміни Вікіданих у списку нових редагувань',
	'wikibase-watchlist-show-changes-pref' => 'Показувати редагування Вікіданих у Вашому списку спостереження',
	'wikibase-error-invalid-entity-id' => 'Введений ідентифікатор не відомий системі. Будь ласка, введіть коректний ідентифікатор сутності.',
	'special-unconnectedpages' => "Сторінки не пов'язані з елементами",
	'wikibase-unconnectedpages-legend' => "Параметри непов'язаних сторінок",
	'wikibase-unconnectedpages-page' => 'Почати список результатів із сторінки:',
	'wikibase-unconnectedpages-submit' => 'Виконати',
	'wikibase-unconnectedpages-invalid-language' => '«$1» — неправильний код мови.',
	'wikibase-unconnectedpages-page-warning' => 'Ця назва сторінки не може бути використаною у запиті й ігнорована.',
	'wikibase-unconnectedpages-iwdata-label' => 'Лише сторінки із міжмовними інтервікі-посиланнями',
	'wikibase-unconnectedpages-format-row' => '($1 {{PLURAL:$1|міжмовне інтервікі-посилання|міжмовні інтервікі-посилання|міжмовних інтервікі-посилань}} на сторінці)',
);

/** Urdu (اردو)
 * @author Noor2020
 */
$messages['ur'] = array(
	'wikibase-unconnectedpages-page' => 'نتائج فہرست اس صفحہ سے شروع کریں:',
);

/** Uzbek (oʻzbekcha)
 * @author CoderSI
 * @author Nataev
 */
$messages['uz'] = array(
	'wikibase-editlinks' => 'Ishoratlarni tahrirla',
	'wikibase-editlinkstitle' => 'Tillararo havolalarni tahrirlash',
);

/** vèneto (vèneto)
 * @author Candalua
 * @author Frigotoni
 */
$messages['vec'] = array(
	'wikibase-client-desc' => "Client par l'estension Wikibase",
	'specialpages-group-wikibaseclient' => 'Client Wikidata',
	'wikibase-after-page-move' => "Te pol anca [$1 ajornar] l'elemento asocià su Wikidata par trasferir i interlink su la pagina nova.",
	'wikibase-comment-remove' => "L'elemento de Wikidata asocià el xe stà scancelà. I colegamenti interlenguìsteghi i xe stà cavà.",
	'wikibase-comment-linked' => 'Un elemento de Wikidata el xe stà colegà a sta pagina.',
	'wikibase-comment-unlink' => "Sta pagina la xe stà destacà da l'elemento de Wikidata. I colegamenti interlenguìsteghi i xe stà cavà.",
	'wikibase-comment-restore' => "L'elemento de Wikidata asocià el xe stà recuperà. I colegamenti interlenguìsteghi i xe stà ripristinà.",
	'wikibase-comment-update' => 'Elemento Wikidata modificà',
	'wikibase-comment-sitelink-add' => 'Zontà colegamento lenguìstego: $1',
	'wikibase-comment-sitelink-change' => 'Colegamento lenguìstego canbià da $1 a $2',
	'wikibase-comment-sitelink-remove' => 'Cavà colegamento lenguìstego: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|canbiamento|canbiamenti}}',
	'wikibase-editlinks' => 'Canbia link',
	'wikibase-editlinkstitle' => 'Canbia colegamenti interlenguìsteghi',
	'wikibase-linkitem-addlinks' => 'Zonta link',
	'wikibase-linkitem-alreadylinked' => "La pagina che te vol colegar la xe zà asocià a n'altro [$1 elemento] in te l'archivio sentrale dei dati che ponta su $2 in sto sito. I elementi i se pol colegar a na pagina sola par sito. Siegli na pagina difarente a cui colegarla.",
	'wikibase-linkitem-close' => 'Sèra la finestra de dialogo e ricarga la pagina',
	'wikibase-linkitem-failure' => 'Xe capità un eror sconossùo sercando de colegar la pagina indicà.',
	'wikibase-linkitem-title' => "Colega co n'altra pagina",
	'wikibase-linkitem-linkpage' => 'Colega co la pagina',
	'wikibase-linkitem-selectlink' => 'Selesiona un sito e na pagina che te vol colegar co sta qua.',
	'wikibase-linkitem-input-site' => 'Lengua:',
	'wikibase-linkitem-input-page' => 'Pagina:',
	'wikibase-linkitem-invalidsite' => 'Sito selessionà sconossùo o mia valido',
	'wikibase-linkitem-confirmitem-text' => "La pagina che te ghè sielto la xe zà colegà a n'altro [$1 elemento nel nostro archivio sentrale dei dati]. Conferma che le pagine mostrà qua soto le xe quele che te vol colegar co sta pagina.",
	'wikibase-linkitem-confirmitem-button' => 'Va ben',
	'wikibase-linkitem-not-loggedin-title' => 'Te ghè da ver fato el login.',
	'wikibase-linkitem-not-loggedin' => "Te ghè da ver fato el login su sta wiki e su l'[$1 archivio dati sentralizà] par doparar sta funsionalità.",
	'wikibase-linkitem-success-create' => "Le pagine le xe stà colegà coretamente. Te pol catar l'elemento pena creà contegnente i link nel nostro [$1 archivio dati sentralizà].",
	'wikibase-linkitem-success-link' => "Le pagine le xe stà colegà coretamente. Te pol catar l'elemento contegnente i link nel nostro [$1 archivio dati sentralizà].",
	'wikibase-property-notfound' => 'Proprietà $1 mia catà.',
	'wikibase-property-notsupportedyet' => "Wikibase no'l suporta gnancora el tipo de proprietà par la proprietà $1.",
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Mostra le modifiche de Wikidata tra i ultimi canbiamenti',
	'wikibase-watchlist-show-changes-pref' => 'Mostra le modifiche de Wikidata sui osservati speciali',
	'wikibase-error-invalid-entity-id' => "L'ID specificà el xe sconossùo al sistema. Dòpara un ID de entità valido.",
	'special-unconnectedpages' => 'Pagine mia colegà ai elementi',
	'wikibase-unconnectedpages-legend' => 'Opzioni par le pagine mia colegà',
	'wikibase-unconnectedpages-page' => "Taca l'elenco dei risultati da la pagina:",
	'wikibase-unconnectedpages-submit' => 'Và',
	'wikibase-unconnectedpages-invalid-language' => '"$1" no xe un còdese de lengua valido.',
	'wikibase-unconnectedpages-page-warning' => 'El titolo de la pagina no se pol dopararlo par la riserca e quindi el vien ignorà.',
	'wikibase-unconnectedpages-iwdata-label' => 'Solo le pagine con colegamenti interlenguìsteghi',
	'wikibase-unconnectedpages-format-row' => '($1 colegamenti interlinguìsteghi in te la pagina)',
);

/** Vietnamese (Tiếng Việt)
 * @author Cheers!
 * @author Minh Nguyen
 */
$messages['vi'] = array(
	'wikibase-client-desc' => 'Trình khách của phần mở rộng Wikibase',
	'specialpages-group-wikibaseclient' => 'Trình khách Wikidata',
	'wikibase-after-page-move' => 'Bạn cũng có thể [$1 cập nhật] khoản mục Wikidata liên kết để duy trì các liên kết ngôn ngữ trên trang được di chuyển.',
	'wikibase-comment-remove' => 'Đã xóa khoản mục liên kết Wikidata. Đã loại bỏ các liên kết ngôn ngữ.',
	'wikibase-comment-linked' => 'Một khoản mục Wikidata đã được liên kết đến trang này.',
	'wikibase-comment-unlink' => 'Đã gỡ liên kết đến khoản mục Wikidata khỏi trang này. Đã dời các liên kết ngôn ngữ.',
	'wikibase-comment-restore' => 'Đã phục hồi khoản mục liên kết Wikidata. Đã phục hồi các liên kết ngôn ngữ.',
	'wikibase-comment-update' => 'Khoản mục Wikidata đã thay đổi',
	'wikibase-comment-sitelink-add' => 'Đã thêm liên kết ngôn ngữ: $1',
	'wikibase-comment-sitelink-change' => 'Đã đổi liên kết ngôn ngữ từ $1 thành $2',
	'wikibase-comment-sitelink-remove' => 'Đã loại bỏ liên kết ngôn ngữ: $1',
	'wikibase-comment-multi' => '$1 thay đổi',
	'wikibase-editlinks' => 'Sửa liên kết',
	'wikibase-editlinkstitle' => 'Sửa liên kết giữa ngôn ngữ',
	'wikibase-linkitem-addlinks' => 'Thêm liên kết',
	'wikibase-linkitem-alreadylinked' => 'Bạn không thể đặt liên kết đến trang được chọn vì nó đã được liên kết đến một [$1 khoản mục] trong kho dữ liệu chung, và khoản mục đó đã liên kết đến $2 tại site này. Các khoản mục chỉ có thể có liên kết đến mỗi site một trang. Xin vui lòng chọn một trang khác để liên kết.',
	'wikibase-linkitem-close' => 'Đóng hộp thoại và tải lại trang',
	'wikibase-linkitem-failure' => 'Đã xuất hiện lỗi bất ngờ khi đặt liên kết đến trang chỉ định.',
	'wikibase-linkitem-title' => 'Đặt liên kết với trang',
	'wikibase-linkitem-linkpage' => 'Đặt liên kết với trang',
	'wikibase-linkitem-selectlink' => 'Xin hãy chọn site và trang để liên kết với trang này.',
	'wikibase-linkitem-input-site' => 'Ngôn ngữ:',
	'wikibase-linkitem-input-page' => 'Trang:',
	'wikibase-linkitem-invalidsite' => 'Đã chọn site không rõ hoặc không hợp lệ',
	'wikibase-linkitem-confirmitem-text' => 'Bạn đã chọn một trang đã được liên kết đến một [$1 khoản mục trong kho dữ liệu chung]. Xin vui lòng xác nhận rằng bạn muốn liên kết trang này với các trang ở dưới.',
	'wikibase-linkitem-confirmitem-button' => 'Xác nhận',
	'wikibase-linkitem-not-loggedin-title' => 'Bạn cần đăng nhập',
	'wikibase-linkitem-not-loggedin' => 'Bạn cần đăng nhập vào cả wiki này lẫn [$1 kho dữ liệu chung] để sử dụng tính năng này.',
	'wikibase-linkitem-success-create' => 'Các trang đã được liên kết với nhau thành công. Một khoản mục chứa các liên kết mới được tạo ra trong [$1 kho dữ liệu chung].',
	'wikibase-linkitem-success-link' => 'Các trang đã được liên kết với nhau thành công. Xem khoản mục chứa các liên kết trong [$1 kho dữ liệu chung].',
	'wikibase-property-notfound' => 'Không tìm thấy thuộc tính $1.',
	'wikibase-property-notsupportedyet' => 'Wikibase chưa hỗ trợ kiểu của thuộc tính $1.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Hiện các sửa đổi Wikidata trong thay đổi gần đây',
	'wikibase-watchlist-show-changes-pref' => 'Hiện các sửa đổi tại Wikidata trong danh sách theo dõi của bạn',
	'wikibase-error-invalid-entity-id' => 'ID nhập vào không có trên hệ thống. Xin vui lòng sử dụng ID đã được cấp.',
	'special-unconnectedpages' => 'Trang không kết nối với khoản mục',
	'wikibase-unconnectedpages-legend' => 'Tùy chọn danh sách trang không kết nối',
	'wikibase-unconnectedpages-page' => 'Bắt đầu danh sách kết quả tìm kiếm từ trang:',
	'wikibase-unconnectedpages-submit' => 'Xem',
	'wikibase-unconnectedpages-invalid-language' => '“$1” không phải là mã ngôn ngữ hợp lệ.',
	'wikibase-unconnectedpages-page-warning' => 'Tên trang không thể sử dụng để truy vấn nên đã được bỏ qua.',
	'wikibase-unconnectedpages-iwdata-label' => 'Chỉ những trang có liên kết ngôn ngữ',
	'wikibase-unconnectedpages-format-row' => '($1 liên kết ngôn ngữ trên trang)',
	'wikibase-pageinfo-entity-id' => 'ID khoản mục Wikidata',
	'wikibase-pageinfo-entity-id-none' => 'Không có',
);

/** Volapük (Volapük)
 * @author Iketsi
 * @author Malafaya
 */
$messages['vo'] = array(
	'wikibase-editlinks' => 'Redakön yümis',
	'wikibase-editlinkstitle' => 'Redakön yümis bevüpükik',
	'wikibase-linkitem-addlinks' => 'Läükön yümis',
	'wikibase-linkitem-input-site' => 'Pük:',
	'wikibase-linkitem-input-page' => 'Pad:',
	'wikibase-rc-hide-wikidata' => '$1 eli Wikidata',
);

/** Yiddish (ייִדיש)
 * @author פוילישער
 */
$messages['yi'] = array(
	'wikibase-client-desc' => 'קליענט פארן Wikibase פארברייטערונג',
	'specialpages-group-wikibaseclient' => 'וויקידאטן קליענט',
	'wikibase-after-page-move' => 'איר קענט אויך [$1 דערהיינטיקן] דעם אסאציאירטן וויקידאטן־עלעמענט כדי אויסהאלטן שפראכלינקען אויפן באוועגטן בלאט.',
	'wikibase-comment-remove' => 'אסאציאירטער וויקידאטן־עלעמענט אויסגעמעקט. שפראכלינקען אראפגענומען.',
	'wikibase-comment-linked' => "מ'האט פארלינקט א וויקידאטן איינס צו דעם בלאט",
	'wikibase-comment-unlink' => 'מען האט אויפגעלינקט דעם בלאט פון וויקידאטן־אביעקט. שפראכלינקען אראפגענומען.',
	'wikibase-comment-restore' => 'אסאציאירטער וויקידאטן־עלעמענט צוריקגעשטעלט. שפראכלינקען צוריקגעשטעלט.',
	'wikibase-comment-update' => 'וויקידאטן־עלעמענט געענדערט.',
	'wikibase-comment-sitelink-add' => 'שפראכלינק צוגעלייגט: $1',
	'wikibase-comment-sitelink-change' => 'שפראכלינק געענדערט פון $1 אויף $2',
	'wikibase-comment-sitelink-remove' => 'שפראך לינק אראפגענומען: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|ענדערונג|ענדערונגען}}',
	'wikibase-editlinks' => 'רעדאקטירן לינקען',
	'wikibase-editlinkstitle' => 'רעדאקטירן אינטערשפראך לינקען',
	'wikibase-linkitem-addlinks' => 'צולייגן לינקען',
	'wikibase-linkitem-alreadylinked' => 'דער בלאט מיט וואס איר ווילט פארלינקען איז שוין צוגעבונדן צו אן [$1 אביעקט] אויפן צענטראלן רעפאזיטאריום וואס פארלינקט מיט $2 אויף דעם וועבזייטל. אן אביעקט קען נאר זיין צוגעבונדן צו איין בלאט אויף א געוויסן וועבזייטל. זייט אזוי גוט קלויבט אויס אן אנדער בלאט צו פארלינקען.',
	'wikibase-linkitem-close' => 'צומאכן דיאלאג און ווידערלאדן בלאט',
	'wikibase-linkitem-failure' => "אן אומבאוואוסטער גרייז האט פאסירט ווען מ'האט פראבירט פארלינקען מיט יענעם בלאט.",
	'wikibase-linkitem-title' => 'פארלינקען מיט בלאט',
	'wikibase-linkitem-linkpage' => 'פארלינקען מיט בלאט',
	'wikibase-linkitem-selectlink' => 'זייט אזוי גוט קלויבט אויס אן וועבזייטל און א בלאט מיט וואס איר ווילט פארלינקען דעם דאזיקן בלאט.',
	'wikibase-linkitem-input-site' => 'שפּראַך:',
	'wikibase-linkitem-input-page' => 'בלאַט:',
	'wikibase-linkitem-invalidsite' => 'אומבאוואוסט אדער אומגילטיק זייטל אויסגעוויילט',
	'wikibase-linkitem-confirmitem-button' => 'באַשטעטיקן',
	'wikibase-linkitem-not-loggedin-title' => 'איר דארפט זיין אריינלאגירט',
	'wikibase-rc-hide-wikidata' => '$1 וויקידאטן',
	'wikibase-rc-show-wikidata-pref' => 'ווייזן וויקידאטן רעדאקטירונגען אין לעצטע ענדערונגען',
	'wikibase-unconnectedpages-page' => 'אנהייבן רעזולטאט ליסטע מיט בלאט:',
	'wikibase-unconnectedpages-submit' => 'גיין',
	'wikibase-unconnectedpages-invalid-language' => '"$1" איז נישט קיין גילטיקער שפראך־קאד.',
	'wikibase-unconnectedpages-iwdata-label' => 'נאר בלעטער מיט אינטערשפראך לינקען',
	'wikibase-unconnectedpages-format-row' => '($1 אינטערשפראך לינקען אויפן בלאט)',
);

/** Cantonese (粵語)
 * @author Waihorace
 */
$messages['yue'] = array(
	'wikibase-client-desc' => 'Wikibase擴展客戶端',
	'wikibase-after-page-move' => '你仲可以[$1 更新]有關嘅維基數據項目，將佢連結去搬咗之後嘅頁面。',
	'wikibase-editlinks' => '修改連結',
	'wikibase-editlinkstitle' => '修改跨語言連結',
	'wikibase-linkitem-addlinks' => '加新連結',
	'wikibase-linkitem-input-site' => '語言:',
	'wikibase-linkitem-input-page' => '頁面：',
	'wikibase-linkitem-confirmitem-button' => '確認',
	'wikibase-linkitem-not-loggedin-title' => '你要登入先',
);

/** Simplified Chinese (中文（简体）‎)
 * @author Hydra
 * @author Li3939108
 * @author Liangent
 * @author Linforest
 * @author Shizhao
 * @author Stevenliuyi
 * @author Yfdyh000
 * @author 乌拉跨氪
 */
$messages['zh-hans'] = array(
	'wikibase-client-desc' => 'Wikibase扩展客户端',
	'specialpages-group-wikibaseclient' => '维基数据客户端',
	'wikibase-after-page-move' => '您还可以[$1 更新]关联的维基数据项，使其链接至移动后的页面。',
	'wikibase-comment-remove' => '关联的维基数据项已删除。跨语言链接已移除。',
	'wikibase-comment-linked' => '一个维基数据项已链接至此页面。',
	'wikibase-comment-unlink' => '本页已解除至维基数据项的链接。跨语言链接已移除。',
	'wikibase-comment-restore' => '关联的维基数据项已还原。跨语言链接已恢复。',
	'wikibase-comment-update' => '维基数据项已变更',
	'wikibase-comment-sitelink-add' => '语言链接已添加：$1',
	'wikibase-comment-sitelink-change' => '语言链接从$1更改为$2',
	'wikibase-comment-sitelink-remove' => '删除语言链接：$1',
	'wikibase-comment-multi' => '$1个更改',
	'wikibase-editlinks' => '编辑链接',
	'wikibase-editlinkstitle' => '编辑跨语言链接',
	'wikibase-linkitem-addlinks' => '添加链接',
	'wikibase-linkitem-alreadylinked' => '您想要链接的页面已经在中央数据存储库中的一个[$1 项]中，并且已经链接到了本站的$2。一个项只能链接每个站点的一个页面。请选择其他的页面来链接。',
	'wikibase-linkitem-close' => '关闭对话框并刷新该页面',
	'wikibase-linkitem-failure' => '在链接页面时出现了一个未知的问题。',
	'wikibase-linkitem-title' => '链接该页面',
	'wikibase-linkitem-linkpage' => '链接该页面',
	'wikibase-linkitem-selectlink' => '请选择您想要与该页面链接的一个站点和一个页面。',
	'wikibase-linkitem-input-site' => '语言：',
	'wikibase-linkitem-input-page' => '页面：',
	'wikibase-linkitem-invalidsite' => '选择了一个未知或无效的网站',
	'wikibase-linkitem-confirmitem-text' => '您所选的页面已与[$1 中央数据库中的其他项]链接。请确认如下所示的都是您想要与此页面相链接的页面。',
	'wikibase-linkitem-confirmitem-button' => '确认',
	'wikibase-linkitem-not-loggedin-title' => '您必须登录',
	'wikibase-linkitem-not-loggedin' => '您必须在此维基和[$1 中央数据存储库]上登录才能使用此功能。',
	'wikibase-linkitem-success-create' => '页面已成功地被链接了。您可以在我们的[$1 中央数据存储库]找到包含该链接的新建项。',
	'wikibase-linkitem-success-link' => '页面已成功链接。您可以在我们的[$1 中央数据库]中找到包含该链接的项。',
	'wikibase-property-notfound' => '$1属性未找到。',
	'wikibase-property-notsupportedyet' => 'Wikibase对$1属性不支持属性类型。',
	'wikibase-rc-hide-wikidata' => '$1维基数据',
	'wikibase-rc-show-wikidata-pref' => '在最近更改中显示维基数据的编辑',
	'wikibase-watchlist-show-changes-pref' => '在您的监视列表中显示维基数据的编辑',
	'wikibase-error-invalid-entity-id' => '输入的ID在系统中是未知的。请使用一个有效的实体ID。',
	'special-unconnectedpages' => '未连接到项的页面',
	'wikibase-unconnectedpages-legend' => '未连接页面的选项',
	'wikibase-unconnectedpages-page' => '从此页面开始显示结果列表：',
	'wikibase-unconnectedpages-submit' => '提交',
	'wikibase-unconnectedpages-invalid-language' => '“$1”不是有效的语言代码。',
	'wikibase-unconnectedpages-page-warning' => '该页面标题不能被用于查询并已被忽略。',
	'wikibase-unconnectedpages-iwdata-label' => '仅含有跨语言链接的页面',
	'wikibase-unconnectedpages-format-row' => '（该页面有$1个跨语言链接）',
);

/** Traditional Chinese (中文（繁體）‎)
 * @author Bencmq
 * @author Justincheng12345
 * @author Li3939108
 * @author Simon Shek
 * @author Stevenliuyi
 * @author Tntchn
 * @author 乌拉跨氪
 */
$messages['zh-hant'] = array(
	'wikibase-client-desc' => 'Wikibase擴展客戶端',
	'specialpages-group-wikibaseclient' => '維基數據客戶端',
	'wikibase-after-page-move' => '您還可以[$1 更新]關聯的維基數據項，使其連結至移動後的頁面。',
	'wikibase-comment-remove' => '關聯的維基數據項目已刪除。語言連結已移除。',
	'wikibase-comment-linked' => '一個維基數據項已連結至此頁面。',
	'wikibase-comment-unlink' => '本頁已解除維基數據項目的連結。語言連結已移除。',
	'wikibase-comment-restore' => '關聯的維基數據項已還原。語言連結已恢復。',
	'wikibase-comment-update' => '維基數據項目已變更。',
	'wikibase-comment-sitelink-add' => '添加語言連結：$1',
	'wikibase-comment-sitelink-change' => '語言連結從$1更改為$2',
	'wikibase-comment-sitelink-remove' => '刪除語言連結：$1',
	'wikibase-comment-multi' => '$1次{{PLURAL:$1|更改|更改}}',
	'wikibase-editlinks' => '編輯連結',
	'wikibase-editlinkstitle' => '編輯跨語言連結',
	'wikibase-linkitem-addlinks' => '添加連結',
	'wikibase-linkitem-alreadylinked' => '你要連接的頁面已於中央數據庫中的一個[$1 項目]裏，並且已連接到了本站點的$2。站點中的每個頁面只能在一個項目裏。請選擇其他頁面作連接。',
	'wikibase-linkitem-close' => '關閉對話框，並刷新頁面',
	'wikibase-linkitem-failure' => '連接頁面時出現未知錯誤。',
	'wikibase-linkitem-title' => '頁面該連結',
	'wikibase-linkitem-linkpage' => '連結該頁面',
	'wikibase-linkitem-selectlink' => '請輸入你想連接的項目及其頁面。',
	'wikibase-linkitem-input-site' => '語言：',
	'wikibase-linkitem-input-page' => '頁面：',
	'wikibase-linkitem-invalidsite' => '所選的網站未知或無效。',
	'wikibase-linkitem-confirmitem-text' => '你選擇的頁面已連至[$1 中央數據庫的項目]。請確認以下項目亦是你想連接到的頁面。',
	'wikibase-linkitem-confirmitem-button' => '確認',
	'wikibase-linkitem-not-loggedin-title' => '您必須登入。',
	'wikibase-linkitem-not-loggedin' => '你必需於本維基及[$1 中央數據庫]中登入才可使用此功能。',
	'wikibase-linkitem-success-create' => '頁面已成功連接，你可以於[$1 中央數據庫]中找到包含此連結的項目。',
	'wikibase-linkitem-success-link' => '貢面已成功連接，你可以於[$1 中央數據庫]中找到包含此連結的項目。',
	'wikibase-property-notfound' => '$1屬性未找到。',
	'wikibase-property-notsupportedyet' => 'Wikibase尚未支援$1屬性。',
	'wikibase-rc-hide-wikidata' => '$1維基數據',
	'wikibase-rc-show-wikidata-pref' => '在最近更改中顯示維基數據的編輯',
	'wikibase-watchlist-show-changes-pref' => '於監視列表中顯示維基數據的編輯',
	'wikibase-error-invalid-entity-id' => '系統無法辨認你輸入的ID。請使用一個有效的ID。',
	'special-unconnectedpages' => '未連接到任一項目的頁面',
	'wikibase-unconnectedpages-legend' => '未連接頁面選項',
	'wikibase-unconnectedpages-page' => '由此頁面開始：',
	'wikibase-unconnectedpages-submit' => '前往',
	'wikibase-unconnectedpages-invalid-language' => '"$1"不是有效的語言代碼。',
	'wikibase-unconnectedpages-page-warning' => '該頁面標題無法用於查詢並已忽略。',
	'wikibase-unconnectedpages-iwdata-label' => '僅有跨語言連結的頁面',
	'wikibase-unconnectedpages-format-row' => '（該頁面有$1個跨語言連結）',
	'wikibase-pageinfo-entity-id-none' => '無',
);
