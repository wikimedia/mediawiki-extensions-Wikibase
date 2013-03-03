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
 */
$messages['en'] = array(
	'wikibase-client-desc' => 'Client for the Wikibase extension',
	'wikibase-after-page-move' => 'You may also [$1 update] the associated Wikidata item to maintain language links on moved page.',
	'wikibase-comment-remove' => 'Associated Wikidata item deleted. Language links removed.',
	'wikibase-comment-linked' => 'A Wikidata item has been linked to this page.',
	'wikibase-comment-unlink' => 'This page has been unlinked from Wikidata item. Language links removed.',
	'wikibase-comment-restore' => 'Associated Wikidata item undeleted. Language links restored.',
	'wikibase-comment-update' => 'Language links updated.',
	'wikibase-comment-sitelink-add' => 'Language link added: $1',
	'wikibase-comment-sitelink-change' => 'Language link changed from $1 to $2',
	'wikibase-comment-sitelink-remove' => 'Language link removed: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|change|changes}}',
	'wikibase-nolanglinks' => 'none',
	'wikibase-editlinks' => 'Edit links',
	'wikibase-editlinkstitle' => 'Edit interlanguage links',
	'wikibase-linkitem-addlinks' => 'Add links',
	'wikibase-linkitem-alreadylinked' => 'The page you wanted to link with is already attached to an [$1 item] on the central data repository which links to $2 on this site. Items can only have one page per site attached. Please choose a different page to link with.',
	'wikibase-linkitem-close' => 'Close dialog and reload page',
	'wikibase-linkitem-failure' => 'An unknown error occured while trying to link the given page.',
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
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Show Wikidata edits in recent changes',
);

/** Message documentation (Message documentation)
 * @author Jeblad
 * @author Katie Filbert
 * @author Marius Hoch
 * @author Raymond
 * @author Shirayuki
 */
$messages['qqq'] = array(
	'wikibase-client-desc' => '{{desc|name=Wikibase Client|url=http://www.mediawiki.org/wiki/Extension:Wikibase_Client}}
See also [[m:Wikidata/Glossary#Wikidata|Wikidata]].',
	'wikibase-after-page-move' => 'Message on [[Special:MovePage]] on submit and successfully move, inviting user to update associated Wikibase repository item to maintain language links on the moved page on the client.

Parameters:
* $1 - the link for the associated Wikibase item.',
	'wikibase-comment-remove' => 'Autocomment message for client (e.g. Wikipedia) recent changes when a Wikidata item connected to a page gets deleted. This results in all the language links being removed from the page on the client.',
	'wikibase-comment-linked' => 'Autocomment message in the client for when a Wikidata item is linked to a page in the client.',
	'wikibase-comment-unlink' => 'Autocomment message for client (e.g. Wikipedia) recent changes when a site link to a page gets removed. This results in the associated item being disconnected from the client page and all the language links being removed.',
	'wikibase-comment-restore' => 'Autocomment message for client (e.g. Wikipedia) recent changes when a Wikidata item gets undeleted and has a site link to this page. Language links get readded to the client page.',
	'wikibase-comment-update' => 'Autocomment message for client (e.g. Wikipedia) recent changes when site links for a linked Wikidata item get changed. This results in language links being updated on the client page.',
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
	'wikibase-nolanglinks' => 'Shown in the "Languages" box in case the page exists but hasn\'t got any langlinks.
{{Identical|None}}',
	'wikibase-editlinks' => '[[Image:InterlanguageLinks-Sidebar-Monobook.png|right]]
	This is a link to the page on Wikidata where interlanguage links of the current page can be edited. See the image on the right for how it looks.
{{Identical|Edit link}}',
	'wikibase-editlinkstitle' => 'This is the text on a link in the sidebar that opens a wizard to edit interlanguage links.',
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
	'wikibase-rc-hide-wikidata' => 'This refers to a toggle to hide or show edits (revisions) that come from Wikidata. If set to "hide", it hides edits made to the connected item in the Wikidata repository.

Parameters:
* $1 - a link with the text {{msg-mw|show}} or {{msg-mw|hide}}',
	'wikibase-rc-show-wikidata-pref' => 'Option in the Recent changes section of preferences to show wikibase changes by default in recent changes',
);

/** Arabic (العربية)
 * @author Ali1
 * @author Peadara
 * @author Tarawneh
 */
$messages['ar'] = array(
	'wikibase-client-desc' => 'عميل امتداد ويكيبيس',
	'wikibase-after-page-move' => 'يمكنك أيضا [ $1  تحديث] بند ويكيبيانات المرتبط بها للحفاظ على روابط اللغة ضمن الصفحة المنقولة.',
	'wikibase-comment-remove' => 'تم حذف بند ويكيبيانات المرتبطة. و تم إزالة ارتباطات اللغة.',
	'wikibase-comment-linked' => 'تم ربط عنصر ويكيبيانات مع هذه الصفحة.',
	'wikibase-comment-unlink' => 'تم فصل ارتباط هذه الصفحة من البند ويكيبيانات. تم إزالة روابط اللغة.',
	'wikibase-comment-restore' => 'تم استرجاع بند ويكيبيانات المرتبط. روابط اللغة أعيدت.',
	'wikibase-comment-update' => 'روابط اللغة حُدثت.',
	'wikibase-comment-sitelink-add' => 'وصلة اللغة المُضافة:$1',
	'wikibase-comment-sitelink-change' => 'تم تعديل وصلة اللغة من $1 إلى $2',
	'wikibase-comment-sitelink-remove' => 'وصلة اللغة المُلغاة:$1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|تعديل|تعديلات}}',
	'wikibase-nolanglinks' => 'لا وصلات',
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

/** Asturian (asturianu)
 * @author Xuacu
 */
$messages['ast'] = array(
	'wikibase-client-desc' => 'Cliente pa la estensión Wikibase',
	'wikibase-editlinks' => 'Editar los enllaces',
	'wikibase-editlinkstitle' => "Editar los enllaces d'interllingua",
);

/** Belarusian (Taraškievica orthography) (беларуская (тарашкевіца)‎)
 * @author Wizardist
 */
$messages['be-tarask'] = array(
	'wikibase-client-desc' => 'Кліент для пашырэньня Wikibase',
	'wikibase-after-page-move' => 'Каб захаваць міжмоўныя спасылкі на перанесеную старонку, вы можаце [$1 абнавіць] злучаны аб’ект у Вікізьвестках.',
	'wikibase-comment-remove' => 'Злучаны аб’ект выдалены зь Вікізьвестак. Моўныя спасылкі былі выдаленыя.',
	'wikibase-comment-linked' => 'Аб’ект Вікізьвестак быў злучаны з гэтай старонкай.',
	'wikibase-comment-unlink' => 'Гэтая старонка была адлучаная ад аб’екта Вікізьвестак. Моўныя спасылкі выдаленыя.',
	'wikibase-comment-restore' => 'Выдаленьне злучанага аб’екта Вікізьвестак скасавана. Моўныя спасылкі адноўленыя.',
	'wikibase-comment-update' => 'Моўныя спасылкі абноўленыя.',
	'wikibase-comment-sitelink-add' => 'Дададзеная моўная спасылка: $1',
	'wikibase-comment-sitelink-change' => 'Моўная спасылка зьмененая з $1 на $2',
	'wikibase-comment-sitelink-remove' => 'Моўная спасылка выдаленая: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|зьмена|зьмены|зьменаў}}',
	'wikibase-nolanglinks' => 'няма',
	'wikibase-editlinks' => 'Зьмяніць спасылкі',
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
	'wikibase-rc-hide-wikidata' => '$1 Вікізьвесткі',
	'wikibase-rc-show-wikidata-pref' => 'Паказваць праўкі Вікізьвестак у сьпісе апошніх зьменаў',
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
 * @author Sankarshan
 */
$messages['bn'] = array(
	'wikibase-linkitem-input-site' => 'ভাষা:',
	'wikibase-rc-hide-wikidata' => '$1 উইকিডাটা',
);

/** Breton (brezhoneg)
 * @author Fulup
 */
$messages['br'] = array(
	'wikibase-editlinks' => 'Kemmañ al liammoù',
	'wikibase-editlinkstitle' => 'Kemmañ al liammoù etreyezhel',
);

/** Bosnian (bosanski)
 * @author Edinwiki
 */
$messages['bs'] = array(
	'wikibase-client-desc' => 'Klijent za proširenje Wikibaza',
	'wikibase-after-page-move' => 'Možete također [$1 ažurirati] asociranu Wikidata stavku za održavanje jezičnih veza na premještenoj stranici.',
	'wikibase-comment-remove' => 'Asocirana Wikidata stavka je izbrisana. Jezične veze su uklonjene.',
	'wikibase-comment-linked' => 'Neka Wikidata stavka je povezana prema ovoj stranici.',
	'wikibase-comment-unlink' => 'Ova stranica je odvojena od Wikidata stavke. Jezične veze su uklonjene.',
	'wikibase-comment-restore' => 'Asocirana Wikidata stavka je vraćena. Jezične veze su sada isto vraćene.',
	'wikibase-comment-update' => 'Jezične veze su ažurirane.',
	'wikibase-comment-sitelink-add' => 'Jezična veza dodana: $1',
	'wikibase-comment-sitelink-change' => 'Jezična veza izmjenjena sa $1 u $2',
	'wikibase-comment-sitelink-remove' => 'Jezična veza uklonjena: $1',
	'wikibase-editlinks' => 'Izmjeni veze',
	'wikibase-editlinkstitle' => 'Izmjeni međujezične veze',
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Pokaži Wikidata izmjene u nedavnim izmjenama',
);

/** Catalan (català)
 * @author Arnaugir
 * @author Grondin
 * @author Vriullop
 * @author Àlex
 */
$messages['ca'] = array(
	'wikibase-client-desc' => "Client per l'extensió Wikibase",
	'wikibase-after-page-move' => "Podeu també [$1 actualitzar] l'element associat de Wikidata per a mantenir els enllaços d'idioma a la pàgina moguda.",
	'wikibase-comment-remove' => 'Element associat de Wikidata eliminat. Enllaços de llengua suprimits.',
	'wikibase-comment-linked' => 'Un element de Wikidata ha estat enllaçat a aquesta pàgina.',
	'wikibase-comment-unlink' => "Aquesta pàgina ha estat deslligada de l'element Wikidata. Enllaços de llengua suprimits.",
	'wikibase-comment-restore' => 'Element associat de Wikidata recuperat. Enllaços de llengua restaurats.',
	'wikibase-comment-update' => 'Enllaços de llengua actualitzats.',
	'wikibase-comment-sitelink-add' => 'Afegit enllaç de llengua: $1',
	'wikibase-comment-sitelink-change' => 'Enllaç de llengua canviat de $1 a $2',
	'wikibase-comment-sitelink-remove' => 'Tret enllaç de llengua: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|canvi|canvis}}',
	'wikibase-nolanglinks' => 'cap',
	'wikibase-editlinks' => 'Modifica els enllaços',
	'wikibase-editlinkstitle' => 'Modifica enllaços interlingües',
	'wikibase-linkitem-addlinks' => 'Afegeix enllaços',
	'wikibase-linkitem-alreadylinked' => 'La pàgina amb la que voleu enllaçar ja està definida en un [$1 element] del repositori central de dades que enllaça a $2 en aquest lloc. Els elements només poden estar relacionats amb una pàgina per lloc. Escolliu una pàgina diferent per enllaçar-hi.',
	'wikibase-linkitem-close' => 'Tanca la caixa de diàleg i recarrega la pàgina',
	'wikibase-linkitem-failure' => "S'ha produït un error desconegut en intentar enllaçar a la pàgina indicada.",
	'wikibase-linkitem-linkpage' => 'Enllaça amb la pàgina',
	'wikibase-linkitem-selectlink' => 'Seleccioneu un lloc i una pàgina que vulgueu lligar a aquesta pàgina.',
	'wikibase-linkitem-input-site' => 'Llengua:',
	'wikibase-linkitem-input-page' => 'Pàgina:',
	'wikibase-linkitem-invalidsite' => 'Lloc seleccionat no conegut o no vàlid',
	'wikibase-linkitem-confirmitem-text' => 'La pàgina que heu indicat ja està enllaçada a un [$1 element del repositori central de dades]. Confirmeu que les pàgines que es mostren a continuació són les que voleu enllaçar amb aquesta pàgina.',
	'wikibase-linkitem-confirmitem-button' => 'Confirmat',
	'wikibase-linkitem-not-loggedin-title' => 'Cal iniciar una sessió',
	'wikibase-linkitem-not-loggedin' => 'Cal que inicieu una sessió en aquest wiki i en el [$1 repositori central de dades] per utilitzar aquesta funcionalitat.',
	'wikibase-linkitem-success-create' => 'Les pàgines han estat lligades correctament. Podeu trobar el nou element creat amb els enllaços en el [$1 repositori central de dades].',
	'wikibase-linkitem-success-link' => "Les pàgines han estat lligades correctament. Podeu trobar l'element que conté els enllaços en el [$1 repositori central de dades].",
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Mostra les modificacions de Wikidata en els canvis recents',
);

/** Sorani Kurdish (کوردی)
 * @author Calak
 */
$messages['ckb'] = array(
	'wikibase-after-page-move' => 'باشترە ھەروەھا برگەی ویکیدراوی پەیوەندیداریش [$1 نوێ بکەیەوە] ھەتا پەیوەندەکانی زمان لە پەڕە گوازراوەکەدا بمێنێتەوە.',
	'wikibase-comment-remove' => 'بڕگەی ویکیدراوەی پەیوەندیدار سڕایەوە. بەستەرەکانی زمان لابران.',
	'wikibase-comment-linked' => 'بڕگەیەکی ویکیدراوە بەم پەڕەیە بەستەر دراوە.',
	'wikibase-comment-unlink' => 'ئەم پەڕەیە بە بڕگەی ویکیدراوە بەستەر نەدراوە. بەستەرەکانی زمان لابران.',
	'wikibase-comment-restore' => 'بڕگەی ویکیدراوەی پەیوەندیدار گەڕایەوە. بەستەرەکانی زمان ھێنرایەوە.',
	'wikibase-rc-hide-wikidata' => '$1 ویکیدراوه',
	'wikibase-rc-show-wikidata-pref' => 'دەستکارییەکانی ویکیدراوە لە دوایین گۆڕانکارییەکاندا نیشان بدە',
);

/** Czech (česky)
 * @author JAn Dudík
 */
$messages['cs'] = array(
	'wikibase-client-desc' => 'Klient pro rozšíření Wikibase',
	'wikibase-after-page-move' => 'Můžete také [ $1  aktualizovat] související položku Wikidat pro údržbu mezijazykových odkazů na přesunuté stránce.',
	'wikibase-comment-remove' => 'Související položka Wikidat odstraněna. Mezijazykové odkazy odstraněny.',
	'wikibase-comment-linked' => 'Položka Wikidat odkazovala na tuto stránku.',
	'wikibase-comment-unlink' => 'Odkaz na tuto stránku byl odstraněn z Wikidat. Mezijazykové odkazy odstraněny.',
	'wikibase-comment-restore' => 'Související položka Wikidat obnovena. Mezijazykové odkazy obnoveny.',
	'wikibase-comment-update' => 'Aktualizovány mezijazykové odkazy.',
	'wikibase-comment-sitelink-add' => 'Přidán mezijazykový odkaz:$1',
	'wikibase-comment-sitelink-change' => 'Změněn mezijazykový odkaz z $1 na $2',
	'wikibase-comment-sitelink-remove' => 'Odstraněn mezijazykový odkaz:$1',
	'wikibase-editlinks' => 'Upravit odkazy',
	'wikibase-editlinkstitle' => 'Editovat mezijazykové odkazy',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Zobrazit změny Wikidat v posledních změnách',
);

/** Danish (dansk)
 * @author Christian List
 * @author Poul G
 */
$messages['da'] = array(
	'wikibase-client-desc' => 'Klient til Wikibase udvidelse',
	'wikibase-after-page-move' => 'Du kan også [ $1  opdatere] det tilknyttede Wikidata emne for at bevare sprog-link til den flyttede side.',
	'wikibase-comment-remove' => 'Det tilknyttede Wikidata emne er slettet. Sprog-links fjernet.',
	'wikibase-comment-linked' => 'Et Wikidata emne er blevet knyttet til denne side.',
	'wikibase-comment-unlink' => 'Denne side er ikke længere linket fra et Wikidata emne. Sprog-links fjernet.',
	'wikibase-comment-restore' => 'Det tilknyttede Wikidata emne er genskabt. Sprog-links gendannet.',
	'wikibase-comment-update' => 'Sprog-link opdateret.',
	'wikibase-comment-sitelink-add' => 'Sprog-link tilføjet: $1',
	'wikibase-comment-sitelink-change' => 'Sprog-link ændret fra $1 til $2',
	'wikibase-comment-sitelink-remove' => 'Sprog-link fjernet: $1',
	'wikibase-editlinks' => 'Rediger links',
	'wikibase-editlinkstitle' => 'Rediger sprog-link',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Vis Wikidata redigeringer i de seneste ændringer',
);

/** German (Deutsch)
 * @author Kghbln
 * @author Metalhead64
 * @author Purodha
 */
$messages['de'] = array(
	'wikibase-client-desc' => 'Ermöglicht einen Client für die Erweiterung Wikibase',
	'wikibase-after-page-move' => 'Du kannst auch das zugeordnete Wikidata-Element [$1 aktualisieren], um Sprachlinks von verschobenen Seiten zu verwalten.',
	'wikibase-comment-remove' => 'Zugeordnetes Wikidata-Objekt wurde gelöscht. Sprachlinks wurden entfernt.',
	'wikibase-comment-linked' => 'Ein Wikidata-Objekt wurde mit dieser Seite verknüpft.',
	'wikibase-comment-unlink' => 'Diese Seite wurde vom Wikidata-Objekt entlinkt. Sprachlinks wurden entfernt.',
	'wikibase-comment-restore' => 'Zugeordnetes Wikidata-Objekt wurde wiederhergestellt. Sprachlinks wurden wiederhergestellt.',
	'wikibase-comment-update' => 'Sprachlinks wurden aktualisiert',
	'wikibase-comment-sitelink-add' => 'Sprachlink hinzugefügt: $1',
	'wikibase-comment-sitelink-change' => 'Sprachlink $1 geändert in $2',
	'wikibase-comment-sitelink-remove' => 'Sprachlink entfernt: $1',
	'wikibase-comment-multi' => '{{PLURAL:$1|Eine Änderung|$1 Änderungen}}',
	'wikibase-nolanglinks' => 'keine',
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
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Wikidata-Bearbeitungen in den „Letzten Änderungen“ anzeigen',
);

/** Zazaki (Zazaki)
 * @author Erdemaslancan
 */
$messages['diq'] = array(
	'wikibase-editlinks' => 'Gri bıvurnê',
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
	'wikibase-nolanglinks' => 'žeden',
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
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Změny Wikidata w aktualnych změnach pokazaś',
);

/** Esperanto (Esperanto)
 * @author ArnoLagrange
 */
$messages['eo'] = array(
	'wikibase-client-desc' => 'Kliento por la Vikidatuma etendaĵo',
	'wikibase-after-page-move' => 'Vi povas [$1 ĝisdatigi] la ligitan Vikidatuman eron por pluteni la lingvan ligilon al la la movita paĝo.',
	'wikibase-comment-remove' => 'Ligita Vikidatuma ero etis forigita. La lingvaj ligiloj estas forviŝitaj.',
	'wikibase-comment-linked' => 'Vikidatuma ero estis ligita al ĉi tiu paĝo.',
	'wikibase-comment-unlink' => 'Ĉi tiu paĝo estis malligita de la Vikidatuma ero. La lingvaj ligiloj estas forigitaj.',
	'wikibase-comment-restore' => 'Ligita vikidatuma ero estis restarigita. La lingvaj ligiloj ankaŭ estis restarigitaj.',
	'wikibase-comment-update' => 'Lingvaj ligiloj ĝisdatigitaj.',
	'wikibase-comment-sitelink-add' => 'Lingva ligilo aldonita: $1',
	'wikibase-comment-sitelink-change' => 'Lingva ligilo ŝanĝita de $1 al $2',
	'wikibase-comment-sitelink-remove' => 'Lingva ligilo forigita: $1',
	'wikibase-editlinks' => 'Redaktu ligilojn',
	'wikibase-editlinkstitle' => 'Redaktu interlingvajn ligilojn',
	'wikibase-rc-hide-wikidata' => '$1 Vikidatumoj',
	'wikibase-rc-show-wikidata-pref' => 'Montru Vikidatumaj redaktoj en la lastaj ŝanĝoj',
);

/** Spanish (español)
 * @author Armando-Martin
 * @author Dalton2
 * @author Hazard-SJ
 * @author Pegna
 */
$messages['es'] = array(
	'wikibase-client-desc' => 'Cliente para la extensión Wikibase',
	'wikibase-after-page-move' => 'También puedes [$1 actualizar] el elemento Wikidata asociado para mantener los vínculos de idioma en la página que se ha movido.',
	'wikibase-comment-remove' => 'Se ha borrado un elemento asociado a Wikidata. Se han eliminado los enlaces lingüísticos.',
	'wikibase-comment-linked' => 'Un artículo de Wikidata ha sido enlazado a esta página.',
	'wikibase-comment-unlink' => 'Esta página ha sido desenlazada de un elemento de Wikidata. Se han eliminado los enlaces lingüísticos.',
	'wikibase-comment-restore' => 'Se ha restaurado un elemento asociado a Wikidata. Se han restaurado los enlaces de idioma.',
	'wikibase-comment-update' => 'Los enlaces de idioma se han actualizado.',
	'wikibase-comment-sitelink-add' => 'Se ha añadido un enlace de idioma: $1',
	'wikibase-comment-sitelink-change' => 'Se ha cambiado el enlace de idioma de $1 a $2',
	'wikibase-comment-sitelink-remove' => 'Se ha eliminado el enlace de idioma: $1',
	'wikibase-comment-multi' => '$1 cambios', # Fuzzy
	'wikibase-nolanglinks' => 'ninguno',
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
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Mostrar las ediciones Wikidata en la lista de cambios recientes',
);

/** Estonian (eesti)
 * @author Pikne
 */
$messages['et'] = array(
	'wikibase-comment-update' => 'Keelelingid uuendatud.',
	'wikibase-comment-sitelink-add' => 'Keelelink lisatud: $1',
	'wikibase-comment-sitelink-change' => 'Keelelink $1 muudetud kujule $2',
	'wikibase-comment-sitelink-remove' => 'Keelelink eemaldatud: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|muudatus|muudatust}}',
	'wikibase-nolanglinks' => 'puudub',
	'wikibase-editlinks' => 'Redigeeri linke',
	'wikibase-editlinkstitle' => 'Redigeeri keeltevahelisi linke',
	'wikibase-linkitem-addlinks' => 'Lisa lingid',
	'wikibase-linkitem-input-site' => 'Keel:',
	'wikibase-linkitem-input-page' => 'Lehekülg:',
	'wikibase-linkitem-invalidsite' => 'Valitud tundmatu või vigane võrgukoht',
	'wikibase-linkitem-not-loggedin-title' => 'Pead olema sisse loginud',
	'wikibase-linkitem-not-loggedin' => 'Et kasutada seda funktsiooni, pead olema sisse loginud siia vikisse ja [$1 kesksesse andmehoidlasse].',
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
	'wikibase-after-page-move' => 'شما ممکن است در عین حال بخواهید آیتم وابستهٔ ویکی‌داده را نیز به [$1 روزرسانی] کنید، تا پیوند به صفحه منتقل شده باقی بماند.',
	'wikibase-comment-remove' => 'پیوند آیتم ویکی‌داده حذف گردید. پیوند زبان حذف شد.',
	'wikibase-comment-linked' => 'یک آیتم ویکی‌داده به این صفحه پیوند دارد.',
	'wikibase-comment-unlink' => 'این صفحه به ویکی‌داده پیوند ندارد. پیوند زبان حذف شد.',
	'wikibase-comment-restore' => 'پیوند آیتم ویکی‌داده بازیابی شد. پیوند زبان بازیابی شد.',
	'wikibase-comment-update' => 'پیوندهای زبانی به‌روز شد.',
	'wikibase-comment-sitelink-add' => 'پیوند زبان اضافه شده:$1',
	'wikibase-comment-sitelink-change' => 'پیوند زبان از $1 به $2 تغییر کرده‌است.',
	'wikibase-comment-sitelink-remove' => 'پیوند زبان حذف شد: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|تغییر|تغییرها}}',
	'wikibase-nolanglinks' => 'هیچ',
	'wikibase-editlinks' => 'ویرایش پیوندها',
	'wikibase-editlinkstitle' => 'ویرایش پیوندهای میان‌ویکی',
	'wikibase-linkitem-addlinks' => 'افزودن پیوند',
	'wikibase-linkitem-alreadylinked' => 'صفحه‌ای که شما به آن پیوند می‌دهید، از قبل به یک [$1 آیتم] در مخزن مرکزی داده که به $2 در این سایت مریوط می‌شود، متصل است. آیتم‌ها می‌توانند فقط به یک صفحه از وبگاه ارتباط داشته‌باشند. لطفا از یک صقحه برای پیوند استفاده کنید.',
	'wikibase-linkitem-close' => 'گفتگو را ببندید و صفحه را مجدداً بارگذاری نمایید.',
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
	'wikibase-rc-hide-wikidata' => '$1 ویکی‌داده',
	'wikibase-rc-show-wikidata-pref' => 'نمایش ویرایش‌های ویکی‌داده در تغییرات اخیر',
);

/** Finnish (suomi)
 * @author Nike
 * @author Stryn
 * @author VezonThunder
 */
$messages['fi'] = array(
	'wikibase-client-desc' => 'Wikibase-laajennuksen asiakasohjelma',
	'wikibase-after-page-move' => 'Voit myös [$1 päivittää] sivuun liittyvän Wikidatan kohteen säilyttääksesi kielilinkit siirretyllä sivulla.',
	'wikibase-comment-remove' => 'Sivuun liittyvä Wikidata-kohde poistettu. Kielilinkit poistettu.',
	'wikibase-comment-linked' => 'Wikidata-kohde liitettiin tähän sivuun.',
	'wikibase-comment-unlink' => 'Tämä sivu ei ole enää liitettynä Wikidata-kohteeseen. Kielilinkit poistettu.',
	'wikibase-comment-restore' => 'Sivuun liittyvä Wikidata-kohde palautettu. Kielilinkit palautettu.',
	'wikibase-comment-update' => 'Kielilinkit päivitetty.',
	'wikibase-comment-sitelink-add' => 'Kielilinkki lisätty: $1',
	'wikibase-comment-sitelink-change' => 'Kielilinkki $1 muutettu muotoon $2',
	'wikibase-comment-sitelink-remove' => 'Kielilinkki poistettu: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|muutos|muutosta}}',
	'wikibase-editlinks' => 'Muokkaa linkkejä',
	'wikibase-editlinkstitle' => 'Muokkaa kieltenvälisiä linkkejä',
	'wikibase-linkitem-addlinks' => 'Lisää linkkejä',
	'wikibase-linkitem-close' => 'Sulje ikkuna ja lataa sivu uudelleen',
	'wikibase-linkitem-input-site' => 'Kieli',
	'wikibase-linkitem-input-page' => 'Sivu',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Näytä Wikidata-muokkaukset tuoreissa muutoksissa',
);

/** French (français)
 * @author Crochet.david
 * @author Gomoko
 * @author Ltrlg
 * @author Wyz
 */
$messages['fr'] = array(
	'wikibase-client-desc' => 'Client pour l’extension Wikibase',
	'wikibase-after-page-move' => 'Vous pouvez aussi [$1 mettre à jour] l’élément Wikidata associé pour conserver les liens de langue sur la page déplacée.',
	'wikibase-comment-remove' => 'Élément Wikidata associé supprimé. Liens de langue supprimés.',
	'wikibase-comment-linked' => 'Un élément Wikidata a été lié à cette page.',
	'wikibase-comment-unlink' => 'Cette page a été dissociée de l’élément Wikidata. Liens de langue supprimés.',
	'wikibase-comment-restore' => 'Suppression de l’élément Wikidata associé annulée. Liens de langue rétablis.',
	'wikibase-comment-update' => 'Liens de langue mis à jour.',
	'wikibase-comment-sitelink-add' => 'Lien de langue ajouté : $1',
	'wikibase-comment-sitelink-change' => 'Lien de langue modifié de $1 à $2',
	'wikibase-comment-sitelink-remove' => 'Lien de langue supprimé : $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|modification|modifications}}',
	'wikibase-nolanglinks' => 'aucun',
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
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Afficher les modifications de Wikidata dans les modifications récentes',
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
	'wikibase-after-page-move' => 'Tamén pode [$1 actualizar] o elemento de Wikidata asociado para manter as ligazóns lingüísticas na páxina trasladada.',
	'wikibase-comment-remove' => 'Borrouse un elemento de Wikidata asociado. Elimináronse as ligazóns lingüísticas.',
	'wikibase-comment-linked' => 'Esta páxina foi ligada desde un elemento de Wikidata.',
	'wikibase-comment-unlink' => 'Esta páxina foi desligada do elemento de Wikidata asociado. Elimináronse as ligazóns lingüísticas.',
	'wikibase-comment-restore' => 'Restaurouse un elemento de Wikidata asociado. Recuperáronse as ligazóns lingüísticas.',
	'wikibase-comment-update' => 'Actualizáronse as ligazóns lingüísticas.',
	'wikibase-comment-sitelink-add' => 'Engadiuse unha ligazón lingüística: $1',
	'wikibase-comment-sitelink-change' => 'Cambiouse unha ligazón lingüística de $1 a $2',
	'wikibase-comment-sitelink-remove' => 'Eliminouse unha ligazón lingüística: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|modificación|modificacións}}',
	'wikibase-nolanglinks' => 'ningunha',
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
	'wikibase-rc-hide-wikidata' => '$1 o Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Mostrar as modificacións de Wikidata nos cambios recentes',
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

/** Hebrew (עברית)
 * @author Amire80
 */
$messages['he'] = array(
	'wikibase-client-desc' => 'לקוח להרחבה Wikibase',
	'wikibase-after-page-move' => 'אפשר גם [$1 לעדכן] את הפריט המשויך בוויקיפדיה כדי לתחזק את קישורי השפה בדף שהועבר.',
	'wikibase-comment-remove' => 'פריט הוויקינתונים המשויך נמחק. קישורי שפה הוסרו.',
	'wikibase-comment-linked' => 'פריט ויקינתונים קוּשר לדף הזה.',
	'wikibase-comment-unlink' => 'הדף הזה נותק מִפריט ויקינתונים. קישורי השפה הוסרו.',
	'wikibase-comment-restore' => 'פריט הוויקינתונים המשויך שוחזר. קישורי השפה שוחזרו.',
	'wikibase-comment-update' => 'קישורי השפה עודכנו.',
	'wikibase-comment-sitelink-add' => 'קישור שפה הוסף: $1',
	'wikibase-comment-sitelink-change' => 'קישור השפה שוּנה מ־$1 אל $2',
	'wikibase-comment-sitelink-remove' => 'קישור השפה הוסר: $1',
	'wikibase-comment-multi' => '{{PLURAL:$1|שינוי אחד|$1 שינויים}}',
	'wikibase-nolanglinks' => 'אין',
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
	'wikibase-rc-hide-wikidata' => '$1 ויקינתונים',
	'wikibase-rc-show-wikidata-pref' => 'הצגת עריכות ויקינתונים בשינויים אחרונים',
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
	'wikibase-nolanglinks' => 'žadyn',
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
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Změny Wikidata w aktualnych změnach pokazać',
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

/** Interlingua (interlingua)
 * @author McDutchie
 */
$messages['ia'] = array(
	'wikibase-client-desc' => 'Cliente pro le extension Wikibase',
	'wikibase-editlinks' => 'Modificar ligamines',
	'wikibase-editlinkstitle' => 'Modificar ligamines interlingua',
);

/** Indonesian (Bahasa Indonesia)
 * @author Iwan Novirion
 */
$messages['id'] = array(
	'wikibase-client-desc' => 'Klien untuk ekstensi Wikibase',
);

/** Iloko (Ilokano)
 * @author Lam-ang
 */
$messages['ilo'] = array(
	'wikibase-client-desc' => 'Kliente para iti Wikibase a pagpaatiddog',
	'wikibase-after-page-move' => 'Mabalinmo pay a [$1 pabaruen] ti mainaig a banag ti Wikidata tapno mataripatu dagiti silpo ti pagsasao ti naiyalis a panid.',
	'wikibase-comment-remove' => 'Ti mainaig a banag ti Wikidata ket naikkaten. Dagiti silpo ti pagsasao ket naikkaten.',
	'wikibase-comment-linked' => 'Ti Wikidata a banag ket naisilpon iti daytoy a panid.',
	'wikibase-comment-unlink' => 'Daytoy a panid ket naikkat ti silpona manipud ti Wikidata a banag. Dagiti silpo ti pagsasao ket naikkaten.',
	'wikibase-comment-restore' => 'Ti mainaig a banag ti Wikidata ket naisubli ti pannakaikkatna. Dagiti silpo ti pagsasao ket naipasubli.',
	'wikibase-comment-update' => 'Naipabaro dagiti silpo ti pagsasao.',
	'wikibase-comment-sitelink-add' => 'Nanayonan ti silpo ti pagsasao: $1',
	'wikibase-comment-sitelink-change' => 'Ti silpo ti pagsasao ket nasukatan manipud ti $1 iti $2',
	'wikibase-comment-sitelink-remove' => 'Naikkat ti silpo ti pagsasao: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|a sinukatan|a sinuksukatan}}',
	'wikibase-nolanglinks' => 'awan',
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
	'wikibase-linkitem-confirmitem-text' => 'Pangngaasi a pasingkedan dagiti naipakita a panid dita baba ket isu dagiti kaytamo nga isilpo ti daytoy a panid.', # Fuzzy
	'wikibase-linkitem-confirmitem-button' => 'Pasingkedan',
	'wikibase-linkitem-not-loggedin-title' => 'Masapul a nakastrekka',
	'wikibase-linkitem-not-loggedin' => 'Masapul a nakastrekka iti daytoy a wiki ken idiay [$1 sentro a resipotorio ti datos] tapno makausar ti daytoy a pagpilian.',
	'wikibase-linkitem-success-create' => 'Dagiti panid ket balligi a naisilpo. Mabalinmo a biruken ti baro a napartuat a banag nga aglaon kadagiti silpo idiay [$1 sentro a resipotorio ti datos].',
	'wikibase-linkitem-success-link' => 'Dagiti panid ket balligi a naisilpo. Mabalinmo a biruken ti banag nga aglaon kadagiti silpo idiay [$1 sentro a resipotorio ti datos].',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Ipakita dagiti Wikidata nga inurnos idiay kinaudi a binalbaliwan',
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
	'wikibase-comment-update' => 'Vefsvæðis tenglar uppfærðir.',
	'wikibase-comment-sitelink-add' => 'Tungumálatengli bætt við: $1',
	'wikibase-comment-sitelink-change' => 'Tungumálatengli breytt frá $1 í $2',
	'wikibase-comment-sitelink-remove' => 'Tungumálatengill fjarlægður: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|breyting|breytingar}}',
	'wikibase-nolanglinks' => 'engir',
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
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Sýna Wikidata breytingar í nýjustu breytingum',
);

/** Italian (italiano)
 * @author Beta16
 * @author Gianfranco
 * @author Raoli
 * @author Sannita
 */
$messages['it'] = array(
	'wikibase-client-desc' => "Client per l'estensione Wikibase",
	'wikibase-after-page-move' => "Puoi anche [$1 aggiornare] l'elemento associato su Wikidata per trasferire gli interlink sulla nuova pagina.",
	'wikibase-comment-remove' => "L'elemento di Wikidata associato è stato cancellato. I collegamenti interlinguistici sono stati rimossi.",
	'wikibase-comment-linked' => 'Un elemento di Wikidata è stato collegato a questa pagina.',
	'wikibase-comment-unlink' => "Questa pagina è stata scollegata dall'elemento di Wikidata. I collegamenti interlinguistici sono stati rimossi.",
	'wikibase-comment-restore' => "L'elemento di Wikidata associato è stato recuperato. I collegamenti interlinguistici sono stati ripristinati.",
	'wikibase-comment-update' => 'Collegamento linguistico aggiornato.',
	'wikibase-comment-sitelink-add' => 'Collegamento linguistico aggiunto: $1',
	'wikibase-comment-sitelink-change' => 'Collegamento linguistico modificato da $1 a $2',
	'wikibase-comment-sitelink-remove' => 'Collegamento linguistico rimosso: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|modifica|modifiche}}',
	'wikibase-nolanglinks' => 'nessuna',
	'wikibase-editlinks' => 'Modifica link',
	'wikibase-editlinkstitle' => 'Modifica collegamenti interlinguistici',
	'wikibase-linkitem-addlinks' => 'Aggiungi link',
	'wikibase-linkitem-close' => 'Chiudi la finestra di dialogo e ricarica la pagina',
	'wikibase-linkitem-failure' => 'Si è verificato un errore sconosciuto durante il tentativo di collegare la pagina indicata.',
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
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Mostra le modifiche di Wikidata nelle ultime modifiche',
);

/** Japanese (日本語)
 * @author Fryed-peach
 * @author Shirayuki
 */
$messages['ja'] = array(
	'wikibase-client-desc' => 'Wikibase 拡張機能のクライアント',
	'wikibase-after-page-move' => '移動されたページにある言語リンクを維持するために、関連付けられたウィキデータ項目を[$1 更新]することもできます。',
	'wikibase-comment-remove' => '関連付けられたウィキデータ項目を削除しました。言語リンクを除去しました。',
	'wikibase-comment-linked' => 'ウィキデータ項目をこのページにリンクしました。',
	'wikibase-comment-unlink' => 'このページをウィキデータ項目からリンク解除しました。言語リンクを除去しました。',
	'wikibase-comment-restore' => '関連付けられたウィキデータ項目を復元しました。言語リンクを復元しました。',
	'wikibase-comment-update' => '言語リンクを更新しました。',
	'wikibase-comment-sitelink-add' => '言語リンクを追加: $1',
	'wikibase-comment-sitelink-change' => '言語リンクを $1 から $2 に変更',
	'wikibase-comment-sitelink-remove' => '言語リンクを除去: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|件の変更}}',
	'wikibase-nolanglinks' => 'なし',
	'wikibase-editlinks' => 'リンクを編集',
	'wikibase-editlinkstitle' => '言語間リンクを編集',
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
	'wikibase-rc-hide-wikidata' => 'ウィキデータを$1',
	'wikibase-rc-show-wikidata-pref' => '最近の更新にウィキデータの編集を表示',
);

/** Georgian (ქართული)
 * @author David1010
 */
$messages['ka'] = array(
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|ცვლილება|ცვლილება}}',
	'wikibase-nolanglinks' => 'არა',
	'wikibase-editlinks' => 'ბმულების რედაქტირება',
	'wikibase-linkitem-input-site' => 'ენა:',
	'wikibase-linkitem-input-page' => 'გვერდი:',
);

/** Korean (한국어)
 * @author 아라
 */
$messages['ko'] = array(
	'wikibase-client-desc' => '위키베이스 확장 기능을 위한 클라이언트',
	'wikibase-after-page-move' => '또한 이동한 문서에 언어 링크를 유지하기 위해 관련된 위키데이터 항목을 [$1 업데이트]할 수 있습니다.',
	'wikibase-comment-remove' => '연결한 위키데이터 항목을 삭제했습니다. 언어 링크를 제거했습니다.',
	'wikibase-comment-linked' => '위키데이터 항목을 이 문서에 연결했습니다.',
	'wikibase-comment-unlink' => '이 문서는 위키데이터 항목에 연결하지 않았습니다. 언어 링크를 제거했습니다.',
	'wikibase-comment-restore' => '연결한 위키데이터 항목을 복구했습니다. 언어 링크를 복구했습니다.',
	'wikibase-comment-update' => '언어 링크를 업데이트했습니다.',
	'wikibase-comment-sitelink-add' => '언어 링크를 추가함: $1',
	'wikibase-comment-sitelink-change' => '언어 링크를 $1에서 $2로 바꿈',
	'wikibase-comment-sitelink-remove' => '언어 링크를 제거함: $1',
	'wikibase-comment-multi' => '$1개 {{PLURAL:$1|바뀜}}',
	'wikibase-nolanglinks' => '없음',
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
	'wikibase-rc-hide-wikidata' => '위키데이터 $1',
	'wikibase-rc-show-wikidata-pref' => '최근 바뀜에서 위키데이터 편집 보기',
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
	'wikibase-comment-update' => 'De Lengks op ander Schprooche sin aanjepaß woode.',
	'wikibase-comment-sitelink-add' => 'Dä Lengk $1 ob en ander Schprooch es derbei jedonn woode.',
	'wikibase-comment-sitelink-change' => 'Dä Lengk $1 ob en ander Schprooch es op $2 verändert woode.',
	'wikibase-comment-sitelink-remove' => 'Dä Lengk $1 ob en ander Schprooch es eruß jenomme woode.',
	'wikibase-comment-multi' => '{{PLURAL:$1|Ein Änderong|$1 Änderonge|Kein Änderong}}',
	'wikibase-nolanglinks' => 'keine',
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
);

/** Kurdish (Latin script) (Kurdî (latînî)‎)
 * @author George Animal
 */
$messages['ku-latn'] = array(
	'wikibase-editlinks' => 'Girêdanan biguherîne',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'wikibase-client-desc' => "Client fir d'Wikibase Erweiderung",
);

/** Macedonian (македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'wikibase-client-desc' => 'Клиент за додатокот „Викибаза“',
	'wikibase-after-page-move' => 'Можете и да го [$1 подновите] поврзаниот предмет на Википодатоци за да ги одржите јазичните врски на преместената страница.',
	'wikibase-comment-remove' => 'Здружениот предмет од Википодатоците е избришан. Јазичните врски се избришани.',
	'wikibase-comment-linked' => 'Со страницава е поврзан предмет од Википодатоците.',
	'wikibase-comment-unlink' => 'На оваа страница ѝ е раскината врската со елементот од Википодатоците. Јазичните врски се отстранети.',
	'wikibase-comment-restore' => 'Здружениот предмет од Википодатоците е повратен. Јазичните врски се повратени.',
	'wikibase-comment-update' => 'Јазичните врски се подновени',
	'wikibase-comment-sitelink-add' => 'Додадена јазична врска: $1',
	'wikibase-comment-sitelink-change' => 'Изменета јазична врска од $1 на $2',
	'wikibase-comment-sitelink-remove' => 'Отстранета јазична врска: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|промена|промени}}',
	'wikibase-nolanglinks' => 'нема',
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
	'wikibase-rc-hide-wikidata' => '$1 Википодатоци',
	'wikibase-rc-show-wikidata-pref' => 'Прикажувај ги уредувањата на Википодатоците во скорешните промени',
);

/** Malayalam (മലയാളം)
 * @author Praveenp
 * @author Santhosh.thottingal
 */
$messages['ml'] = array(
	'wikibase-client-desc' => 'വിക്കിബേസ് അനുബന്ധത്തിനുള്ള ക്ലയന്റ്',
	'wikibase-after-page-move' => 'മാറ്റിയ താളിലെ ഭാഷാ കണ്ണികൾ പരിപാലിക്കുന്നതിനായി ബന്ധപ്പെട്ട വിക്കിഡേറ്റ ഇനം താങ്കൾക്ക് [$1 പുതുക്കുകയും] ചെയ്യാവുന്നതാണ്.',
	'wikibase-comment-remove' => 'ബന്ധപ്പെട്ട വിക്കിഡേറ്റ ഇനം മായ്ക്കപ്പെട്ടിരിക്കുന്നു. ഭാഷാ കണ്ണികൾ നീക്കം ചെയ്തു.',
	'wikibase-comment-linked' => 'ഒരു വിക്കിഡേറ്റ ഇനം ഈ താളിൽ കണ്ണി ചേർത്തിരിക്കുന്നു.',
	'wikibase-comment-unlink' => 'ഈ താൾ വിക്കിഡേറ്റാ ഇനത്തിൽ നിന്നും കണ്ണി മാറ്റിയിരിക്കുന്നു. ഭാഷാ കണ്ണികൾ നീക്കം ചെയ്തു.',
	'wikibase-comment-restore' => 'ബന്ധപ്പെട്ട വിക്കിഡേറ്റ ഇനം പുനഃസ്ഥാപിച്ചിരിക്കുന്നു. ഭാഷാ കണ്ണികൾ പുനഃസ്ഥാപിച്ചു.',
	'wikibase-comment-update' => 'ഭാഷാ കണ്ണികൾ പുതുക്കപ്പെട്ടു.',
	'wikibase-comment-sitelink-add' => 'ഭാഷാ കണ്ണി ചേർത്തു: $1',
	'wikibase-comment-sitelink-change' => 'ഭാഷാ കണ്ണി $1 എന്നതിൽ നിന്ന് $2 എന്നാക്കി മാറ്റിയിരിക്കുന്നു',
	'wikibase-comment-sitelink-remove' => 'ഭാഷാ കണ്ണി നീക്കം ചെയ്തു: $1',
	'wikibase-nolanglinks' => 'ഒന്നുമില്ല',
	'wikibase-editlinks' => 'കണ്ണികൾ തിരുത്തുക',
	'wikibase-editlinkstitle' => 'അന്തർഭാഷാ കണ്ണികൾ തിരുത്തുക',
	'wikibase-linkitem-addlinks' => 'കണ്ണികൾ ചേർക്കുക',
	'wikibase-linkitem-input-site' => 'ഭാഷ:',
	'wikibase-linkitem-input-page' => 'താൾ:',
	'wikibase-linkitem-invalidsite' => 'അപരിചിതമോ അസാധുവോ ആയ സൈറ്റാണ് തിരഞ്ഞെടുത്തത്',
	'wikibase-linkitem-confirmitem-button' => 'സ്ഥിരീകരിക്കുക',
	'wikibase-rc-hide-wikidata' => 'വിക്കിഡേറ്റ $1',
	'wikibase-rc-show-wikidata-pref' => 'സമീപകാല മാറ്റങ്ങളിൽ വിക്കിഡേറ്റാ തിരുത്തലുകളും പ്രദർശിപ്പിക്കുക',
);

/** Marathi (मराठी)
 * @author Ydyashad
 */
$messages['mr'] = array(
	'wikibase-rc-hide-wikidata' => '$१ विकिमाहिती',
);

/** Malay (Bahasa Melayu)
 * @author Anakmalaysia
 */
$messages['ms'] = array(
	'wikibase-client-desc' => 'Pelanggan sambungan Wikibase',
	'wikibase-after-page-move' => 'Anda juga boleh [$1 mengemaskinikan] perkara Wikidata yang berkenaan untuk memelihara pautan bahasa pada halaman yang dipindahkan.',
	'wikibase-comment-remove' => 'Perkara Wikidata yang berkenaan dihapuskan. Pautan bahasa dipadamkan.',
	'wikibase-comment-linked' => 'Satu perkara Wikidata telah dipautkan ke halaman ini.',
	'wikibase-comment-unlink' => 'Halaman ini telah dinyahpautkan dari perkara Wikidata. Pautan bahasa dibuang.',
	'wikibase-comment-restore' => 'Perkara Wikidata yang berkenaan dinyahhapus. Pautan bahasa dipulihkan.',
	'wikibase-comment-update' => 'Pautan bahasa dikemaskinikan.',
	'wikibase-comment-sitelink-add' => 'Pautan bahasa dibubuh: $1',
	'wikibase-comment-sitelink-change' => 'Pautan bahasa diubah daripada $1 kepada $2',
	'wikibase-comment-sitelink-remove' => 'Pautan bahasa dibuang: $1',
	'wikibase-comment-multi' => '$1 perubahan',
	'wikibase-nolanglinks' => 'tiada',
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
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Paparkan suntingan Wikidata dalam perubahan terkini',
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
	'wikibase-nolanglinks' => 'ingen',
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
 * @author Siebrand
 */
$messages['nl'] = array(
	'wikibase-client-desc' => 'Client voor de uitbreiding Wikibase',
	'wikibase-after-page-move' => 'U kunt ook het gekoppelde Wikidataitem [$1 bijwerken] om de taalkoppelingen op de hernoemde pagina te kunnen beheren.',
	'wikibase-comment-remove' => 'Het gekoppelde Wikidataitem is verwijderd. De taalkoppelingen zijn verwijderd.',
	'wikibase-comment-linked' => 'Er is een Wikidataitem gekoppeld aan deze pagina.',
	'wikibase-comment-unlink' => 'Deze pagina is ontkoppeld van het Wikidataitem. De taalkoppelingen zijn verwijderd.',
	'wikibase-comment-restore' => 'Het gekoppelde Wikidataitem is teruggeplaatst. De taalkoppelingen zijn hersteld.',
	'wikibase-comment-update' => 'De taalkoppelingen zijn bijgewerkt.',
	'wikibase-comment-sitelink-add' => 'Taalkoppeling toegevoegd: $1',
	'wikibase-comment-sitelink-change' => 'Taalkoppeling gewijzigd van $1 naar $2',
	'wikibase-comment-sitelink-remove' => 'Taalkoppeling verwijderd: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|wijziging|wijzigingen}}',
	'wikibase-nolanglinks' => 'geen',
	'wikibase-editlinks' => 'Koppelingen bewerken',
	'wikibase-editlinkstitle' => 'Intertaalkoppelingen bewerken',
	'wikibase-linkitem-addlinks' => 'Koppelingen toevoegen',
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
	'wikibase-rc-hide-wikidata' => 'Wikidata $1',
	'wikibase-rc-show-wikidata-pref' => 'Wikidatabewerkingen weergeven in recente wijzigingen',
);

/** Norwegian Nynorsk (norsk nynorsk)
 * @author Jeblad
 * @author Njardarlogar
 */
$messages['nn'] = array(
	'wikibase-client-desc' => 'Klient for Wikibase-utvidinga',
	'wikibase-after-page-move' => 'Du kan òg [$1 oppdatera] det tilknytte Wikidata-settet for å halda språklenkjene på den flytte sida ved like.',
	'wikibase-comment-remove' => 'Tilknytt Wikidata-sett sletta. Språklenkjer fjerna.',
	'wikibase-comment-linked' => 'Eit Wikidata-sett har vorte lenkja til sida.',
	'wikibase-comment-unlink' => 'Lenkinga til sida har vorte fjerna frå Wikidata-settet. Språklenkjer fjerna.',
	'wikibase-comment-restore' => 'Tilknytt Wikidata-sett attoppretta. Språklenkjer lagde inn att.',
	'wikibase-comment-update' => 'Språklenkjer oppdaterte.',
	'wikibase-comment-sitelink-add' => 'Språklenkje lagd til: $1',
	'wikibase-comment-sitelink-change' => 'Språklenkje endra frå $1 til $2',
	'wikibase-comment-sitelink-remove' => 'Språklenkje fjerna: $1',
	'wikibase-comment-multi' => '{{PLURAL:$1|éi endring|$1 endringar}}',
	'wikibase-nolanglinks' => 'ingen',
	'wikibase-editlinks' => 'Endra lenkjer',
	'wikibase-editlinkstitle' => 'Endra mellomspråklege lenkjer',
	'wikibase-linkitem-addlinks' => 'Legg til lenkjer',
	'wikibase-linkitem-close' => 'Lat att dialog og last sida på nytt',
	'wikibase-linkitem-failure' => 'Ein ukjend feil oppstod under lenkinga av sida.',
	'wikibase-linkitem-title' => 'Lenk til side',
	'wikibase-linkitem-linkpage' => 'Lenk til side',
	'wikibase-linkitem-selectlink' => 'Vel ein nettstad og ei side du ynskjer å lenkja til denne sida.',
	'wikibase-linkitem-input-site' => 'Språk:',
	'wikibase-linkitem-input-page' => 'Side:',
	'wikibase-linkitem-invalidsite' => 'Ukjend eller ugild nettstad er vald.',
	'wikibase-linkitem-confirmitem-text' => 'Stadfest at sidene viste under er dei du ynskjer at skal lenkjast til denne sida.', # Fuzzy
	'wikibase-linkitem-confirmitem-button' => 'Stadfest',
	'wikibase-linkitem-not-loggedin-title' => 'Du lyt vera innlogga',
	'wikibase-linkitem-not-loggedin' => 'Du lyt vera innlogga på denne wikien og på det [$1 sentrale datalageret] for å nytta denne funksjonen.',
	'wikibase-linkitem-success-create' => 'Sidene vart lenkja til kvarandre. Du kan finna det nyoppretta objektet som inneheld lenkjene i det [$1 sentrale datalageret] vårt.',
	'wikibase-linkitem-success-link' => 'Sidene vart lenkja til kvarandre. Du kan finna objektet som inneheld lenkjene i det [$1 sentrale datalageret] vårt.',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Vis Wikidata-endringar i siste endringane',
);

/** Polish (polski)
 * @author BeginaFelicysym
 * @author Lazowik
 * @author Maćko
 * @author Odie2
 */
$messages['pl'] = array(
	'wikibase-client-desc' => 'Klient rozszerzenia Wikibase',
	'wikibase-comment-sitelink-add' => 'Łącze języka dodane: $1',
	'wikibase-comment-sitelink-change' => 'Łącze języka zmienione z $1 na $2',
	'wikibase-comment-sitelink-remove' => 'Łącze języka usunięte: $1',
	'wikibase-editlinks' => 'Edytuj linki',
	'wikibase-editlinkstitle' => 'Edytuj linki wersji językowych',
);

/** Piedmontese (Piemontèis)
 * @author Borichèt
 * @author Dragonòt
 */
$messages['pms'] = array(
	'wikibase-client-desc' => "Client për l'estension Wikibase",
	'wikibase-after-page-move' => "It peule ëdcò [$1 modifiché] j'element Wikidata associà për goerné le ëd lenga an sla pàgine tramudà.",
	'wikibase-comment-remove' => 'Element Wikidata associà scancelà. Liura ëd lenga gavà.',
	'wikibase-comment-linked' => "N'element Wikidata a l'é stàit colegà a sta pàgina.",
	'wikibase-comment-unlink' => "Sta pàgina a l'é stàita dëscolegà da l'element Wikidata. Liure ëd lenga gavà.",
	'wikibase-comment-restore' => 'Element associà Wikidata ripristinà. Liure ëd lenga ripristinà.',
	'wikibase-comment-update' => 'Liure ëd lenga agiornà.',
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
 * @author Tuliouel
 */
$messages['pt-br'] = array(
	'wikibase-client-desc' => 'Cliente para a extensão Wikibase',
	'wikibase-after-page-move' => 'Você também pode [$1 atualizar] o item associado ao Wikidata para manter os links de idioma na página movida.',
	'wikibase-comment-remove' => 'O item associado no Wikidata foi eliminado. Os links para os outros idiomas foram removidos.',
	'wikibase-comment-linked' => 'Um item do Wikidata foi associado a esta página.',
	'wikibase-comment-unlink' => 'O link desta página foi retirado do item do Wikidata. Os links para os outros idiomas foram removidos.',
	'wikibase-comment-restore' => 'O item associado no Wikidata foi restaurado. Os links para os outros idiomas foram restaurados.',
	'wikibase-comment-update' => 'Os links para outros idiomas foram atualizados.',
	'wikibase-comment-sitelink-add' => 'Link de idioma adicionado: $1',
	'wikibase-comment-sitelink-change' => 'Link de idioma alterado de $1 para $2',
	'wikibase-comment-sitelink-remove' => 'Link de idioma removido: $1',
	'wikibase-comment-multi' => '$1 alterações',
	'wikibase-editlinks' => 'Editar links',
	'wikibase-editlinkstitle' => 'Editar links para outros idiomas',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Mostrar as edições do Wikidata nas mudanças recentes',
);

/** Romanian (română)
 * @author Stelistcristi
 */
$messages['ro'] = array(
	'wikibase-editlinks' => 'Editează legăturile',
	'wikibase-editlinkstitle' => 'Editează legăturile interlingvistice',
);

/** tarandíne (tarandíne)
 * @author Joetaras
 */
$messages['roa-tara'] = array(
	'wikibase-nolanglinks' => 'ninde',
	'wikibase-editlinks' => 'Cange le collegaminde',
	'wikibase-linkitem-title' => "Collegate cu 'a pàgene",
	'wikibase-linkitem-linkpage' => "Collegate cu 'a pàgene",
	'wikibase-linkitem-input-site' => 'Lènghe:',
	'wikibase-linkitem-input-page' => 'Pàgene:',
);

/** Russian (русский)
 * @author Kaganer
 * @author Ole Yves
 * @author Александр Сигачёв
 */
$messages['ru'] = array(
	'wikibase-client-desc' => 'Клиент для расширения Wikibase',
	'wikibase-after-page-move' => 'Чтобы исправить на переименованной странице языковые ссылки, вы можете также [$1  обновить] связанный элемент Викиданных.',
	'wikibase-comment-remove' => 'Связанный элемент Викиданных удалён. Языковые ссылки ликвидированы.',
	'wikibase-comment-linked' => 'Элемент Викиданных был связан с данной страницей.',
	'wikibase-comment-unlink' => 'Связь этой страницы с элементом Викиданных была разорвана. Языковые ссылки удалены.',
	'wikibase-comment-restore' => 'Удаление связанного элемента Викиданных отменено. Языковые ссылки восстановлены.',
	'wikibase-comment-update' => 'Языковые ссылки обновлены.',
	'wikibase-comment-sitelink-add' => 'Интервики-ссылка добавлена: $1.',
	'wikibase-comment-sitelink-change' => 'Интервики-ссылка изменена с $1 на $2',
	'wikibase-comment-sitelink-remove' => 'Интервики-ссылка удалена: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|изменение|изменения|изменений}}',
	'wikibase-nolanglinks' => 'нет',
	'wikibase-editlinks' => 'Править ссылки',
	'wikibase-editlinkstitle' => 'Править межъязыковые ссылки',
	'wikibase-linkitem-addlinks' => 'Добавить ссылки',
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
	'wikibase-rc-hide-wikidata' => '$1 Викиданные',
	'wikibase-rc-show-wikidata-pref' => 'Показать изменения Викиданных в списке свежих правок',
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
 */
$messages['sk'] = array(
	'wikibase-client-desc' => 'Klient pre rozšírenie Wikibase',
	'wikibase-editlinks' => 'Upraviť odkazy',
	'wikibase-editlinkstitle' => 'Upraviť medzijazykové odkazy',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Zobraziť úpravy Wikidat v posledných zmienách',
);

/** Serbian (Cyrillic script) (српски (ћирилица)‎)
 * @author Nikola Smolenski
 * @author Rancher
 */
$messages['sr-ec'] = array(
	'wikibase-client-desc' => 'Клијент за проширење Викибаза',
	'wikibase-editlinks' => 'Уреди везе',
	'wikibase-editlinkstitle' => 'Уређивање међујезичких веза',
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
 */
$messages['sv'] = array(
	'wikibase-client-desc' => 'Klient för tillägget Wikibase',
	'wikibase-comment-remove' => 'Tillhörande Wikidata objekt togs bort. Språklänkar togs bort.',
	'wikibase-comment-linked' => 'Ett Wikidata-objekt har länkats till den här sidan.',
	'wikibase-comment-unlink' => 'Denna sida har gjorts olänkad från Wikidata-objektet. Språklänkar togs bort.',
	'wikibase-comment-restore' => 'Tillhörande Wikidata-objekt togs bort. Språklänkar togs bort.',
	'wikibase-comment-update' => 'Språklänkar uppdaterades.',
	'wikibase-comment-sitelink-add' => 'Språklänken lades till: $1',
	'wikibase-comment-sitelink-change' => 'Språklänken ändrades från $1 till $2',
	'wikibase-comment-sitelink-remove' => 'Språklänken togs bort: $1',
	'wikibase-editlinks' => 'Redigera länkar',
	'wikibase-editlinkstitle' => 'Redigera interwikilänkar',
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Visa Wikidataredigeringar i senaste ändringar',
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

/** Ukrainian (українська)
 * @author AS
 * @author Ата
 */
$messages['uk'] = array(
	'wikibase-client-desc' => 'Клієнт для розширення Wikibase',
	'wikibase-after-page-move' => "Щоб виправити мовні посилання на перейменованій сторінці, Ви також можете [$1 оновити] пов'язаний елемент Вікіданих.",
	'wikibase-comment-remove' => "Пов'язаний елемент Вікіданих видалений. Мовні посилання видалені.",
	'wikibase-comment-linked' => 'Елемент Вікіданих посилався на цю сторінку.',
	'wikibase-comment-unlink' => "Ця сторінка була від'єднана від елемента Вікіданих. Мовні посилання видалені.",
	'wikibase-comment-restore' => "Пов'язаний елемент Вікіданих відновлений. Мовні посилання відновлені.",
	'wikibase-comment-update' => 'Мовні посилання оновлені.',
	'wikibase-comment-sitelink-add' => 'Додано мовне посилання: $1',
	'wikibase-comment-sitelink-change' => 'Мовне посилання змінено з $1 на $2',
	'wikibase-comment-sitelink-remove' => 'Мовне посилання видалено: $1',
	'wikibase-comment-multi' => '$1 {{PLURAL:$1|зміна|зміни|змін}}',
	'wikibase-nolanglinks' => 'не вказано',
	'wikibase-editlinks' => 'Редагувати посилання',
	'wikibase-editlinkstitle' => 'Редагувати міжмовні посилання',
	'wikibase-linkitem-addlinks' => 'Додати посилання',
	'wikibase-linkitem-close' => 'Закрити діалог і оновити сторінку',
	'wikibase-linkitem-failure' => "При спробі прив'язати вибрану сторінку сталася невідома помилка.",
	'wikibase-linkitem-title' => "Прив'язати до сторінки",
	'wikibase-linkitem-linkpage' => "Прив'язати до сторінки",
	'wikibase-linkitem-selectlink' => "Виберіть сайт і сторінку, яку треба прив'язати до активної сторінки.",
	'wikibase-linkitem-input-site' => 'Мова:',
	'wikibase-linkitem-input-page' => 'Сторінка:',
	'wikibase-linkitem-invalidsite' => 'Вибрано невідомий або недопустимий сайт',
	'wikibase-linkitem-confirmitem-text' => "Підтвердіть, що наведений нижче список сторінок до прив'язання складено правильно.", # Fuzzy
	'wikibase-linkitem-confirmitem-button' => 'Підтвердити',
	'wikibase-rc-hide-wikidata' => '$1 Вікідані',
	'wikibase-rc-show-wikidata-pref' => 'Показати зміни Вікіданих у списку нових редагувань',
);

/** Vietnamese (Tiếng Việt)
 * @author Minh Nguyen
 */
$messages['vi'] = array(
	'wikibase-client-desc' => 'Trình khách của phần mở rộng Wikibase',
	'wikibase-after-page-move' => 'Bạn cũng có thể [$1 cập nhật] khoản mục Wikidata liên kết để duy trì các liên kết ngôn ngữ trên trang được di chuyển.',
	'wikibase-comment-remove' => 'Đã xóa khoản mục liên kết Wikidata. Đã loại bỏ các liên kết ngôn ngữ.',
	'wikibase-comment-linked' => 'Một khoản mục Wikidata đã được liên kết đến trang này.',
	'wikibase-comment-unlink' => 'Đã gỡ liên kết đến khoản mục Wikidata khỏi trang này. Đã dời các liên kết ngôn ngữ.',
	'wikibase-comment-restore' => 'Đã phục hồi khoản mục liên kết Wikidata. Đã phục hồi các liên kết ngôn ngữ.',
	'wikibase-comment-update' => 'Đã cập nhật các liên kết ngôn ngữ.',
	'wikibase-comment-sitelink-add' => 'Đã thêm liên kết ngôn ngữ: $1',
	'wikibase-comment-sitelink-change' => 'Đã đổi liên kết ngôn ngữ từ $1 thành $2',
	'wikibase-comment-sitelink-remove' => 'Đã loại bỏ liên kết ngôn ngữ: $1',
	'wikibase-comment-multi' => '$1 thay đổi',
	'wikibase-nolanglinks' => 'không có',
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
	'wikibase-rc-hide-wikidata' => '$1 Wikidata',
	'wikibase-rc-show-wikidata-pref' => 'Hiện các sửa đổi Wikidata trong thay đổi gần đây',
);

/** Simplified Chinese (中文（简体）‎)
 * @author Hydra
 * @author Linforest
 * @author Shizhao
 * @author Stevenliuyi
 * @author Yfdyh000
 */
$messages['zh-hans'] = array(
	'wikibase-client-desc' => 'Wikibase扩展客户端',
	'wikibase-after-page-move' => '您还可以[$1 更新]关联的维基数据项目，使其链接至移动后的页面。',
	'wikibase-comment-remove' => '关联的维基数据项目已删除。语言链接已移除。',
	'wikibase-comment-linked' => '一个维基数据项目已链接至此页面。',
	'wikibase-comment-unlink' => '本页已解除维基数据项目的链接。语言链接已移除。',
	'wikibase-comment-restore' => '关联的维基数据项目已还原。语言链接已恢复。',
	'wikibase-comment-update' => '语言链接已更新。',
	'wikibase-comment-sitelink-add' => '添加语言链接：$1',
	'wikibase-comment-sitelink-change' => '语言链接从$1更改为$2',
	'wikibase-comment-sitelink-remove' => '删除语言链接：$1',
	'wikibase-comment-multi' => '$1 个更改',
	'wikibase-nolanglinks' => '无',
	'wikibase-editlinks' => '编辑链接',
	'wikibase-editlinkstitle' => '编辑跨语言链接',
	'wikibase-linkitem-addlinks' => '添加链接',
	'wikibase-linkitem-alreadylinked' => '你要链接的这个页面已经在中央数据知识库中的一个[$1 项目]里，并且已经链接到了本站的$2。每个链接的站点上的页面只能在一个项目里。请选择其他的页面来链接。',
	'wikibase-linkitem-close' => '关闭视窗和刷新页面',
	'wikibase-linkitem-failure' => '在链接页面时出现了一个未知的问题。',
	'wikibase-linkitem-title' => '与页面链接',
	'wikibase-linkitem-linkpage' => '与页面链接',
	'wikibase-linkitem-selectlink' => '请选择一个您想链接这个页面的网站与页面。',
	'wikibase-linkitem-input-site' => '语言：',
	'wikibase-linkitem-input-page' => '页面：',
	'wikibase-linkitem-invalidsite' => '选择了一个未知或无效的网站',
	'wikibase-linkitem-confirmitem-text' => '您选择的页面已链接到[ $1 我们中央数据知识库中的项目]。请确认如下所示的页面都是您想要与此页面链接的。',
	'wikibase-linkitem-confirmitem-button' => '确认',
	'wikibase-linkitem-not-loggedin-title' => '您必须要登入',
	'wikibase-linkitem-not-loggedin' => '您必须要在此维基上和[$1 中央数据知识库]登入才能使用此功能。',
	'wikibase-linkitem-success-create' => '页面以成功地被链接了。您可以在我们的[$1 中央数据知识库]找到包含该链接的新项目。',
	'wikibase-linkitem-success-link' => '页面已成功地被链接了。您可以在我们的[$1 中央数据知识库]找到包含该链接的项目。',
	'wikibase-rc-hide-wikidata' => '$1维基数据',
	'wikibase-rc-show-wikidata-pref' => '在最近更改中显示维基数据的编辑',
);

/** Traditional Chinese (中文（繁體）‎)
 * @author Stevenliuyi
 */
$messages['zh-hant'] = array(
	'wikibase-client-desc' => 'Wikibase擴展客戶端',
	'wikibase-after-page-move' => '您還可以[$1 更新]關聯的維基數據項目，使其連結至移動後的頁面。',
	'wikibase-comment-remove' => '關聯的維基數據項目已刪除。語言連結已移除。',
	'wikibase-comment-linked' => '一個維基數據項目已連結至此頁面。',
	'wikibase-comment-unlink' => '本頁已解除維基數據項目的連結。語言連結已移除。',
	'wikibase-comment-restore' => '關聯的維基數據項目已還原。語言連結已恢復。',
	'wikibase-comment-update' => '語言連結已更新。',
	'wikibase-comment-sitelink-add' => '添加語言連結：$1',
	'wikibase-comment-sitelink-change' => '語言連結從$1更改為$2',
	'wikibase-comment-sitelink-remove' => '刪除語言連結：$1',
	'wikibase-editlinkstitle' => '編輯跨語言鏈接',
	'wikibase-rc-hide-wikidata' => '$1維基數據',
	'wikibase-rc-show-wikidata-pref' => '在最近更改中顯示維基數據的編輯',
);
