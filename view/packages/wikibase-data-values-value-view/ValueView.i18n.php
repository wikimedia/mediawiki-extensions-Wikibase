<?php

/**
 * Internationalization file for the "ValueView" extension.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup ValueView
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

$messages = array();

/** English
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
$messages['en'] = array(
	'valueview-desc' => 'UI components for displaying and editing data values',

	'valueview-expert-advancedadjustments' => 'advanced adjustments',

	// UnsupportedValue expert:
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Handling of "$1" values is not yet supported.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Handling of values for "$1" data type is not yet supported.',

	// EmptyValue expert:
	'valueview-expert-emptyvalue-empty' => 'empty',

	// CoordinateInput expert:
	'valueview-expert-globecoordinateinput-precision' => 'Precision:',

	// TimeValue expert:
	'valueview-expert-timevalue-calendar-gregorian' => 'Gregorian',
	'valueview-expert-timevalue-calendar-julian' => 'Julian',

	// TimeInput expert:
	'valueview-expert-timeinput-precision' => 'Precision:',
	'valueview-expert-timeinput-calendar' => 'Calendar:',
	'valueview-expert-timeinput-calendarhint-gregorian' => '(Gregorian calendar)',
	'valueview-expert-timeinput-calendarhint-julian' => '(Julian calendar)',
	'valueview-expert-timeinput-calendarhint-switch-gregorian' => '&rarr; change to Gregorian',
	'valueview-expert-timeinput-calendarhint-switch-julian' => '&rarr; change to Julian',

	'valueview-preview-label' => 'will be displayed as:',
	'valueview-preview-novalue' => 'no valid value recognized',

	'valueview-listrotator-auto' => 'auto',
);

/** Message documentation (Message documentation)
 * @author Amire80
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 * @author Shirayuki
 */
$messages['qqq'] = array(
	'valueview-desc' => '{{desc|name=ValueView|url=http://www.mediawiki.org/wiki/Extension:ValueView}}',
	'valueview-expert-advancedadjustments' => 'Label of the link to unfold advanced adjustments regarding the data type (see [[d:Wikidata:Glossary]]) the user is about to enter a value of (e.g. specifying the precision of a time value).',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Error shown if a data value of a certain data value type (see [[d:Wikidata:Glossary]]) should be displayed or a form for creating one should be offered while this is not yet possible from a technical point of view (e.g. because a valueview widget expert handling data values of that type has not yet been implemented).

Parameters:
* $1 - the name of the data value type which lacks support',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Error shown if a data value for a certain data type (see [[d:Wikidata:Glossary]]) should be displayed or a form for creating one should be offered while this is not yet possible from a technical point of view (e.g. because a valueview widget expert handling data values for that data type has not yet been implemented).

Parameters:
* $1 - the name of the data type which lacks support',
	'valueview-expert-emptyvalue-empty' => 'Message expressing that there is currently no value set in a jQuery valueview.
{{Identical|Empty}}',
	'valueview-expert-globecoordinateinput-precision' => 'Label for the user interface element used to set a specific precision (e.g. 1, 0.1, 0.001) when entering a coordinate value.',
	'valueview-expert-timevalue-calendar-gregorian' => 'Label of the GREGORIAN calendar. The label is used for selecting the GREGORIAN calendar model when entering a date and is displayed with dates that refer to that calendar model.

See also:
* {{msg-mw|Valueview-expert-timeinput-calendarhint-gregorian}}
* {{msg-mw|Valueview-expert-timeinput-calendarhint-switch-gregorian}}',
	'valueview-expert-timevalue-calendar-julian' => 'Label of the JULIAN calendar. The label is used for selecting the JULIAN calendar model when entering a date and is displayed with dates that refer to that calendar model.

See also:
* {{msg-mw|Valueview-expert-timeinput-calendarhint-julian}}
* {{msg-mw|Valueview-expert-timeinput-calendarhint-switch-julian}}',
	'valueview-expert-timeinput-precision' => 'Label for the user interface element used to set a specific precision (e.g. hour, day, month, year) when entering a time value.',
	'valueview-expert-timeinput-calendar' => 'Label for the user interface element used to select a specific calendar (e.g. Gregorian, Julian) entering a time value.

The calendar is not localized at this time.
{{Identical|Calendar}}',
	'valueview-expert-timeinput-calendarhint-gregorian' => 'Message informing that the GREGORIAN calendar has been detected automatically while specifying a date. The message is shown only when the specified date lies within a time frame when multiple calendars had been in use.

See also:
* {{msg-mw|Valueview-expert-timevalue-calendar-gregorian}}
* {{msg-mw|Valueview-expert-timeinput-calendarhint-switch-gregorian}}',
	'valueview-expert-timeinput-calendarhint-julian' => 'Message informing that the JULIAN calendar has been detected automatically while specifying a date. The message is shown only when the specified date lies within a time frame when multiple calendars had been in use.

See also:
* {{msg-mw|Valueview-expert-timevalue-calendar-julian}}
* {{msg-mw|Valueview-expert-timeinput-calendarhint-switch-julian}}',
	'valueview-expert-timeinput-calendarhint-switch-gregorian' => 'Label of the link manually switching to the GREGORIAN calendar. The link is located directly at the preview (in combination with the calendar hint message).

See also:
* {{msg-mw|Valueview-expert-timevalue-calendar-gregorian}}
* {{msg-mw|Valueview-expert-timeinput-calendarhint-gregorian}}',
	'valueview-expert-timeinput-calendarhint-switch-julian' => 'Label of the link manually switching to the JULIAN calendar. The link is located directly at the preview (in combination with the calendar hint message).

See also:
* {{msg-mw|Valueview-expert-timevalue-calendar-julian}}
* {{msg-mw|Valueview-expert-timeinput-calendarhint-julian}}',
	'valueview-preview-label' => "Label displayed above the preview of a value that is being entered by the user. The preview is the system's interpretation of the specified value and - since there is no strict definition for a user how to specify values - visualizes how the value will be displayed later on after the value has been saved.",
	'valueview-preview-novalue' => "Message displayed instead of an input value's preview when no value is specified yet or when the specified value could not be interpreted by the system.",
	'valueview-listrotator-auto' => 'Label of the link to have the system automatically select the most appropriate value from a "listrotator" widget. The "listrotator" basically is a façade for a drop-down select box allowing to pick a value from a list of values. In addition to the defined values, an "automatic" option may be selected that makes the system pick the most appropriate value according to an associated input element.
{{Identical|Automatic}}',
);

/** Asturian (asturianu)
 * @author Xuacu
 */
$messages['ast'] = array(
	'valueview-desc' => "Componentes de la interfaz p'amosar y editar valores de datos",
	'valueview-expert-advancedadjustments' => 'axustes avanzaos',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Inda nun hai sofitu pa xestionar valores «$1».',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Inda nun hai sofitu pa xestionar valores de datos de tipu «$1».',
	'valueview-expert-emptyvalue-empty' => 'balero',
	'valueview-expert-globecoordinateinput-precision' => 'Precisión:',
	'valueview-expert-timeinput-precision' => 'Precisión:',
	'valueview-expert-timeinput-calendar' => 'Calendariu:',
	'valueview-expert-timeinput-calendarhint' => '(calendariu $1)',
	'valueview-expert-timeinput-calendarhint-switch' => '&rarr; cambiar al $1',
	'valueview-preview-label' => 'veráse como:',
	'valueview-preview-novalue' => 'nun se reconoció dengún valor válidu',
	'valueview-listrotator-auto' => 'automáticu',
);

/** Belarusian (Taraškievica orthography) (беларуская (тарашкевіца)‎)
 * @author Wizardist
 */
$messages['be-tarask'] = array(
	'valueview-desc' => 'Кампанэнты карыстальніцкага інтэрфэйсу для адлюстраваньня і рэдагаваньня зьвестак',
	'valueview-expert-advancedadjustments' => 'пашыраныя налады',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Апрацоўка значэньняў «$1» пакуль не падтрымліваецца.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Апрацоўка значэньняў тыпу «$1» яшчэ не падтрымліваецца.',
	'valueview-expert-emptyvalue-empty' => 'пуста',
	'valueview-expert-globecoordinateinput-precision' => 'Дакладнасьць:',
	'valueview-expert-timeinput-precision' => 'Дакладнасьць:',
	'valueview-expert-timeinput-calendar' => 'Каляндар:',
	'valueview-preview-label' => 'будзе адлюстроўвацца як:',
	'valueview-preview-novalue' => 'слушнае значэньне не распазнанае',
	'valueview-listrotator-auto' => 'аўтаматычна',
);

/** Bengali (বাংলা)
 * @author Aftab1995
 * @author Bellayet
 */
$messages['bn'] = array(
	'valueview-expert-emptyvalue-empty' => 'খালি',
	'valueview-expert-timeinput-calendarhint' => '($1 ক্যালেন্ডার)',
	'valueview-expert-timeinput-calendarhint-switch' => '&rarr; $1-এ পরিবর্তন',
	'valueview-preview-label' => 'যা হিসাবে প্রদর্শন করা হবে:',
);

/** Catalan (català)
 * @author Pitort
 */
$messages['ca'] = array(
	'valueview-expert-emptyvalue-empty' => 'buit',
);

/** Danish (dansk)
 * @author Byrial
 */
$messages['da'] = array(
	'valueview-desc' => 'Brugergrænsefladekomponenter til at vise og redigere dataværdier',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Håndtering af værdier af $1 understøttes ikke endnu.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Håndtering af værdier for datatypen $1 understøttes ikke endnu.',
	'valueview-expert-emptyvalue-empty' => 'tom',
);

/** German (Deutsch)
 * @author Kghbln
 * @author Metalhead64
 */
$messages['de'] = array(
	'valueview-desc' => 'Ergänzt die Benutzeroberfläche um Komponenten zum Anzeigen und Bearbeiten von Datenwerten',
	'valueview-expert-advancedadjustments' => 'Erweiterte Anpassungen',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Das Bearbeiten von Werten mit dem Typ „$1“ wird noch nicht unterstützt.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Das Bearbeiten von Werten für den Datentyp „$1“ wird noch nicht unterstützt.',
	'valueview-expert-emptyvalue-empty' => 'leer',
	'valueview-expert-globecoordinateinput-precision' => 'Genauigkeit:',
	'valueview-expert-timevalue-calendar-gregorian' => 'Gregorianisch',
	'valueview-expert-timevalue-calendar-julian' => 'Julianisch',
	'valueview-expert-timeinput-precision' => 'Genauigkeit:',
	'valueview-expert-timeinput-calendar' => 'Kalender:',
	'valueview-expert-timeinput-calendarhint-gregorian' => '(Gregorianischer Kalender)',
	'valueview-expert-timeinput-calendarhint-julian' => '(Julianischer Kalender)',
	'valueview-expert-timeinput-calendarhint-switch-gregorian' => '&rarr; ändern in Gregorianisch',
	'valueview-expert-timeinput-calendarhint-switch-julian' => '&rarr; ändern in Julianisch',
	'valueview-preview-label' => 'wird angezeigt als:',
	'valueview-preview-novalue' => 'keinen gültigen Wert erkannt',
	'valueview-listrotator-auto' => 'automatisch',
);

/** Lower Sorbian (dolnoserbski)
 */
$messages['dsb'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Wobźěłowanje datowych gódnotow "$1" hyšći se njepódpěra.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Wobźěłowanje gódnotow za datowy typ "$1" hyšći se njepódpěra.', # Fuzzy
);

/** Greek (Ελληνικά)
 * @author Nikosguard
 */
$messages['el'] = array(
	'valueview-expert-timevalue-calendar-gregorian' => 'Γρηγοριανό',
	'valueview-expert-timevalue-calendar-julian' => 'Ιουλιανό',
	'valueview-expert-timeinput-calendarhint-gregorian' => '(Γρηγοριανό ημερολόγιο)',
	'valueview-expert-timeinput-calendarhint-julian' => '(Ιουλιανό ημερολόγιο)',
	'valueview-expert-timeinput-calendarhint-switch-gregorian' => '&rarr; αλλαγή σε Γρηγοριανό',
	'valueview-expert-timeinput-calendarhint-switch-julian' => '&rarr; αλλαγή σε Ιουλιανό',
);

/** British English (British English)
 */
$messages['en-gb'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Handling of "$1" data values is not yet supported.', # Fuzzy
);

/** Spanish (español)
 * @author Fitoschido
 * @author Invadinado
 */
$messages['es'] = array(
	'valueview-expert-advancedadjustments' => 'ajustes avanzados',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'La manipulación de valores para el tipo de datos "$1" aún no está soportada.', # Fuzzy
	'valueview-expert-emptyvalue-empty' => 'vacío',
	'valueview-expert-globecoordinateinput-precision' => 'Precisión:',
	'valueview-expert-timeinput-precision' => 'Precisión:',
	'valueview-expert-timeinput-calendar' => 'Calendario:',
	'valueview-preview-label' => 'se mostrará como:',
);

/** Persian (فارسی)
 */
$messages['fa'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'دستکاری داده "$1" فعلا امکان پذیر نیست', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'پشتیبانی از مقدار نوع دادهٔ «$1» هنوز پشتیبانی نشده‌است.', # Fuzzy
);

/** Finnish (suomi)
 * @author Nike
 * @author Silvonen
 * @author Stryn
 */
$messages['fi'] = array(
	'valueview-expert-advancedadjustments' => 'lisäsäädöt',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Datatyypin "$1" arvojen käsittelyä ei vielä tueta.',
	'valueview-expert-emptyvalue-empty' => 'tyhjä',
	'valueview-expert-timeinput-precision' => 'Tarkkuus:',
	'valueview-expert-timeinput-calendar' => 'Kalenteri:',
	'valueview-expert-timeinput-calendarhint' => '(kalenteri: $1)',
	'valueview-expert-timeinput-calendarhint-switch' => '&rarr; vaihda kalenteriin $1',
	'valueview-preview-label' => 'näytetään muodossa:',
	'valueview-preview-novalue' => 'kelvollista arvoa ei tunnistettu',
	'valueview-listrotator-auto' => 'auto',
);

/** French (français)
 * @author Ayack
 * @author Gomoko
 * @author Hello71
 * @author Metroitendo
 * @author Peter17
 * @author Urhixidur
 */
$messages['fr'] = array(
	'valueview-desc' => 'Composants graphiques pour l’affichage et la modification des données',
	'valueview-expert-advancedadjustments' => 'réglages avancés',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'La manipulation des valeurs de données « $1 » n’est pas encore supportée.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'La gestion des valeurs pour le type de données « $1 » n’est pas encore pris en charge.',
	'valueview-expert-emptyvalue-empty' => 'vide',
	'valueview-expert-globecoordinateinput-precision' => 'Précision :',
	'valueview-expert-timevalue-calendar-gregorian' => 'Grégorien',
	'valueview-expert-timevalue-calendar-julian' => 'Julien',
	'valueview-expert-timeinput-precision' => 'Précision :',
	'valueview-expert-timeinput-calendar' => 'Calendrier :',
	'valueview-expert-timeinput-calendarhint-gregorian' => '(Calendrier grégorien)',
	'valueview-expert-timeinput-calendarhint-julian' => '(Calendrier julien)',
	'valueview-expert-timeinput-calendarhint-switch-gregorian' => '&rarr; passer en Grégorien',
	'valueview-expert-timeinput-calendarhint-switch-julian' => '&rarr; passer en Julien',
	'valueview-preview-label' => 'affiché comme :',
	'valueview-preview-novalue' => 'aucune valeur valide reconnu',
	'valueview-listrotator-auto' => 'auto',
);

/** Galician (galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'valueview-desc' => 'Compoñentes da interface para mostrar e editar valores de datos',
	'valueview-expert-advancedadjustments' => 'axustes avanzados',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'A manipulación de valores "$1" aínda non está soportada.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'A manipulación de valores para o tipo de datos "$1" aínda non está soportada.',
	'valueview-expert-emptyvalue-empty' => 'baleiro',
	'valueview-expert-globecoordinateinput-precision' => 'Precisión:',
	'valueview-expert-timevalue-calendar-gregorian' => 'Gregoriano',
	'valueview-expert-timevalue-calendar-julian' => 'Xuliano',
	'valueview-expert-timeinput-precision' => 'Precisión:',
	'valueview-expert-timeinput-calendar' => 'Calendario:',
	'valueview-expert-timeinput-calendarhint-gregorian' => '(Calendario gregoriano)',
	'valueview-expert-timeinput-calendarhint-julian' => '(Calendario xuliano)',
	'valueview-expert-timeinput-calendarhint-switch-gregorian' => '&rarr; cambiar a gregoriano',
	'valueview-expert-timeinput-calendarhint-switch-julian' => '&rarr; cambiar a xuliano',
	'valueview-preview-label' => 'vaise mostrar así:',
	'valueview-preview-novalue' => 'non se recoñeceu ningún valor válido',
	'valueview-listrotator-auto' => 'automático',
);

/** Gujarati (ગુજરાતી)
 * @author Ashok modhvadia
 * @author KartikMistry
 */
$messages['gu'] = array(
	'valueview-expert-emptyvalue-empty' => 'ખાલી',
	'valueview-expert-timeinput-calendarhint' => '($1 પંચાંગ)',
	'valueview-listrotator-auto' => 'સ્વયં',
);

/** Hebrew (עברית)
 * @author Amire80
 * @author Orsa
 */
$messages['he'] = array(
	'valueview-desc' => 'רכיבי ממשק להצגה ועריכה של ערכי נתונים',
	'valueview-expert-advancedadjustments' => 'כוונונים מתקדמים',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'טיפול בערכים מסוג "$1" אינו נתמך עדיין.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'טיפול בערכים מסוג נתונים "$1" אינו נתמך עדיין.',
	'valueview-expert-emptyvalue-empty' => 'ריק',
	'valueview-expert-timeinput-precision' => 'דיוק:',
	'valueview-expert-timeinput-calendar' => 'לוח שנה:',
	'valueview-expert-timeinput-calendarhint' => '(לוח שנה $1 מקדים)', # Fuzzy
	'valueview-expert-timeinput-calendarhint-switch' => '&larr; שינוי ל$1',
	'valueview-preview-label' => 'יוצג בתור:',
	'valueview-preview-novalue' => 'לא הוכר שום ערך תקין',
	'valueview-listrotator-auto' => 'אוטומטי',
);

/** Upper Sorbian (hornjoserbsce)
 */
$messages['hsb'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Wobdźěłowanje datowych hódnotow "$1" so hišće njepodpěruje.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Wobdźěłowanje hódnotow za datowy typ "$1" hišće so njepodpěruje.', # Fuzzy
);

/** Hungarian (magyar)
 */
$messages['hu'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'A(z) „$1” típusú adatokat még nem támogatjuk.', # Fuzzy
);

/** Indonesian (Bahasa Indonesia)
 */
$messages['id'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Penanganan "$1" nilai data belum didukung.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Penanganan nilai untuk "$1" tipe data belum didukung.', # Fuzzy
);

/** Iloko (Ilokano)
 */
$messages['ilo'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Ti panagtengngel ti "$1" a patpateg ti datos ket saan pay a nasuportaran.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Ti panagtengngel kadagiti pateg para iti "$1" a kita ti datos ket saan pay a nasuportaran.', # Fuzzy
);

/** Icelandic (íslenska)
 */
$messages['is'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Gagnagerðin „$1” er ekki enn studd.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Gildi af gagnagerð „$1” eru ekki studd ennþá.', # Fuzzy
);

/** Italian (italiano)
 * @author Beta16
 */
$messages['it'] = array(
	'valueview-desc' => "Componenti dell'interfaccia utente per la visualizzazione e la modifica dei valori dei dati",
	'valueview-expert-advancedadjustments' => 'regolazioni avanzate',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'La gestione dei valori "$1" non è ancora supportata.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'La gestione dei valori per il tipo di dati "$1" non è ancora supportata.',
	'valueview-expert-emptyvalue-empty' => 'vuoto',
	'valueview-expert-globecoordinateinput-precision' => 'Precisione:',
	'valueview-expert-timevalue-calendar-gregorian' => 'Gregoriano',
	'valueview-expert-timevalue-calendar-julian' => 'Giuliano',
	'valueview-expert-timeinput-precision' => 'Precisione:',
	'valueview-expert-timeinput-calendar' => 'Calendario:',
	'valueview-expert-timeinput-calendarhint-gregorian' => '(calendario gregoriano)',
	'valueview-expert-timeinput-calendarhint-julian' => '(calendario giuliano)',
	'valueview-expert-timeinput-calendarhint-switch-gregorian' => '&rarr; modifica in gregoriano',
	'valueview-expert-timeinput-calendarhint-switch-julian' => '&rarr; modifica in giuliano',
	'valueview-preview-label' => 'verrà visualizzato come:',
	'valueview-preview-novalue' => 'nessun valore valido riconosciuto',
	'valueview-listrotator-auto' => 'automatico',
);

/** Japanese (日本語)
 * @author Fryed-peach
 * @author Shirayuki
 */
$messages['ja'] = array(
	'valueview-desc' => 'データ値を表示/編集するユーザーインターフェイスコンポーネント',
	'valueview-expert-advancedadjustments' => '高度な調整',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => '「$1」の値の処理にはまだ対応していません。',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'データ型「$1」の値の処理にはまだ対応していません。',
	'valueview-expert-emptyvalue-empty' => '空',
	'valueview-expert-globecoordinateinput-precision' => '精度:',
	'valueview-expert-timevalue-calendar-gregorian' => 'グレゴリオ暦',
	'valueview-expert-timevalue-calendar-julian' => 'ユリウス暦',
	'valueview-expert-timeinput-precision' => '精度:',
	'valueview-expert-timeinput-calendar' => '暦:',
	'valueview-expert-timeinput-calendarhint-gregorian' => '(グレゴリオ暦)',
	'valueview-expert-timeinput-calendarhint-julian' => '(ユリウス暦)',
	'valueview-expert-timeinput-calendarhint-switch-gregorian' => '&rarr; グレゴリオ暦に変更',
	'valueview-expert-timeinput-calendarhint-switch-julian' => '&rarr; ユリウス暦に変更',
	'valueview-preview-label' => 'プレビュー:',
	'valueview-preview-novalue' => '有効な値を認識できませんでした',
	'valueview-listrotator-auto' => '自動',
);

/** Korean (한국어)
 * @author Kwj2772
 * @author 아라
 */
$messages['ko'] = array(
	'valueview-desc' => '데이터 값 보이기와 편집을 위한 사용자 인터페이스 구성 요소',
	'valueview-expert-advancedadjustments' => '고급 조정',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => '"$1" 값의 처리는 아직 지원하지 않습니다.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => '"$1" 데이터 유형에 대한 값의 처리는 아직 지원하지 않습니다.',
	'valueview-expert-emptyvalue-empty' => '비었음',
	'valueview-expert-globecoordinateinput-precision' => '정밀도:',
	'valueview-expert-timeinput-precision' => '정밀도:',
	'valueview-expert-timeinput-calendar' => '달력:',
	'valueview-expert-timeinput-calendarhint' => '($1력)',
	'valueview-expert-timeinput-calendarhint-switch' => '&rarr; $1로 바꾸기',
	'valueview-preview-label' => '다음과 같이 보이기:',
	'valueview-preview-novalue' => '올바른 값이 인식되지 않음',
	'valueview-listrotator-auto' => '자동',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'valueview-expert-emptyvalue-empty' => 'eidel',
	'valueview-expert-globecoordinateinput-precision' => 'Präzisioun:',
	'valueview-expert-timevalue-calendar-gregorian' => 'Gregorianesch',
	'valueview-expert-timevalue-calendar-julian' => 'Julianesch',
	'valueview-expert-timeinput-precision' => 'Präzisioun:',
	'valueview-expert-timeinput-calendar' => 'Kalenner:',
	'valueview-expert-timeinput-calendarhint-gregorian' => '(Gregorianesche Kalenner)',
	'valueview-expert-timeinput-calendarhint-julian' => '(Julianesche Kalenner)',
	'valueview-preview-label' => 'gëtt gewisen als:',
	'valueview-preview-novalue' => 'kee valabele Wäert erkannt',
	'valueview-listrotator-auto' => 'auto',
);

/** Macedonian (македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'valueview-desc' => 'Посреднички компоненти за приказ и уредување на податочни вредности',
	'valueview-expert-advancedadjustments' => 'напредни прилагодувања',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Работата со вредности од типот „$1“ сè уште не е поддржана.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Работата со вредности за податочниот тип „$1“ сè уште не е поддржана.',
	'valueview-expert-emptyvalue-empty' => 'празно',
	'valueview-expert-globecoordinateinput-precision' => 'Уточнетост:',
	'valueview-expert-timevalue-calendar-gregorian' => 'грегоријански',
	'valueview-expert-timevalue-calendar-julian' => 'јулијански',
	'valueview-expert-timeinput-precision' => 'Уточнетост:',
	'valueview-expert-timeinput-calendar' => 'Календар:',
	'valueview-expert-timeinput-calendarhint-gregorian' => '(грегоријански календар)',
	'valueview-expert-timeinput-calendarhint-julian' => '(јулијански календар)',
	'valueview-expert-timeinput-calendarhint-switch-gregorian' => '&rarr; смени во грегоријански',
	'valueview-expert-timeinput-calendarhint-switch-julian' => '&rarr; смени во јулијански',
	'valueview-preview-label' => 'ќе се прикажува како:',
	'valueview-preview-novalue' => 'не препознав важечка вредност',
	'valueview-listrotator-auto' => 'автоматски',
);

/** Malay (Bahasa Melayu)
 * @author Anakmalaysia
 */
$messages['ms'] = array(
	'valueview-desc' => 'Komponen-komponen UI untuk memaparkan dan menyunting nilai data',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Pengelolaan nilai "$1" belum disokong.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Pengelolaan nilai untuk jenis data "$1" belum disokong.',
	'valueview-expert-emptyvalue-empty' => 'kosong',
	'valueview-expert-globecoordinateinput-precision' => 'Kepersisan:',
	'valueview-expert-timevalue-calendar-gregorian' => 'Gregory',
	'valueview-expert-timevalue-calendar-julian' => 'Julius',
	'valueview-expert-timeinput-precision' => 'Kepersisan:',
	'valueview-expert-timeinput-calendar' => 'Kalendar:',
	'valueview-expert-timeinput-calendarhint-gregorian' => '(kalendar Gregory)',
	'valueview-expert-timeinput-calendarhint-julian' => '(kalendar Julius)',
	'valueview-expert-timeinput-calendarhint-switch-gregorian' => '&rarr; beralih kepada kalendar Gregory',
	'valueview-expert-timeinput-calendarhint-switch-julian' => '&rarr; beralih kepada kalendar Julius',
	'valueview-preview-label' => 'akan dipaparkan sebagai:',
	'valueview-preview-novalue' => 'tiada nilai sah yang dikenali',
	'valueview-listrotator-auto' => 'auto',
);

/** Norwegian Bokmål (norsk bokmål)
 */
$messages['nb'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Håndtering av verdier for datatypen «$1» støttes ikke ennå.', # Fuzzy
);

/** Dutch (Nederlands)
 * @author Siebrand
 */
$messages['nl'] = array(
	'valueview-desc' => 'Gebruikersinterface-elementen voor het weergeven en bewerken van gegevenswaarden',
	'valueview-expert-advancedadjustments' => 'geavanceerde aanpassingen',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Het verwerken van waarden van het type "$1" wordt nog niet ondersteund.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Het verwerken van waarden van het gegevenstype "$1" wordt nog niet ondersteund.',
	'valueview-expert-emptyvalue-empty' => 'leeg',
	'valueview-expert-globecoordinateinput-precision' => 'Precisie:',
	'valueview-expert-timeinput-precision' => 'Precisie:',
	'valueview-expert-timeinput-calendar' => 'Kalender:',
	'valueview-expert-timeinput-calendarhint' => '(kalendersysteem $1)',
	'valueview-expert-timeinput-calendarhint-switch' => '&rarr; wijzigen naar $1',
	'valueview-preview-label' => 'wordt weergegeven als:',
	'valueview-preview-novalue' => 'geen geldige waarde herkend',
	'valueview-listrotator-auto' => 'automatisch',
);

/** Norwegian Nynorsk (norsk nynorsk)
 * @author Njardarlogar
 */
$messages['nn'] = array(
	'valueview-expert-emptyvalue-empty' => 'tom',
	'valueview-expert-timeinput-precision' => 'Presisjon',
	'valueview-expert-timeinput-calendar' => 'Kalender:',
	'valueview-preview-label' => 'vil visast som:',
	'valueview-preview-novalue' => 'ingen gild verdi vart attkjend',
	'valueview-listrotator-auto' => 'auto',
);

/** Polish (polski)
 * @author Ty221
 */
$messages['pl'] = array(
	'valueview-expert-emptyvalue-empty' => 'pusty',
);

/** Piedmontese (Piemontèis)
 * @author Borichèt
 */
$messages['pms'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => "La gestion dij valor për la sòrt ëd dat «$1» a l'é ancor nen mantnùa.",
);

/** Pashto (پښتو)
 * @author Ahmed-Najib-Biabani-Ibrahimkhel
 */
$messages['ps'] = array(
	'valueview-expert-emptyvalue-empty' => 'تش',
);

/** tarandíne (tarandíne)
 * @author Joetaras
 */
$messages['roa-tara'] = array(
	'valueview-desc' => 'Combonende UI pe fà vedè e cangià le volre de le date',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => '\'A gestione de "$1" valore non g\'è angore mandenute.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => '\'A gestione de valore pu tipe de date "$1" non g\'è angore mandenute.',
	'valueview-expert-emptyvalue-empty' => 'vacande',
);

/** Russian (русский)
 */
$messages['ru'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Обработка значений типа «$1» пока не поддерживается.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Обработка значений для типа данных «$1» пока не поддерживается.', # Fuzzy
);

/** Serbo-Croatian (srpskohrvatski / српскохрватски)
 */
$messages['sh'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Korištenje vrijednosti za tip podatka "$1" još nije podržano.', # Fuzzy
);

/** Serbian (Cyrillic script) (српски (ћирилица)‎)
 * @author Milicevic01
 * @author Милан Јелисавчић
 */
$messages['sr-ec'] = array(
	'valueview-desc' => 'УИ компоненте за приказ и уређивање података',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Начин управљања вредностима за „$1“ врсту података још није подржан.', # Fuzzy
	'valueview-expert-emptyvalue-empty' => 'празно',
	'valueview-expert-timeinput-calendar' => 'Календар:',
);

/** Swedish (svenska)
 * @author Jopparn
 */
$messages['sv'] = array(
	'valueview-expert-advancedadjustments' => 'avancerade inställningar',
	'valueview-expert-emptyvalue-empty' => 'tom',
	'valueview-expert-globecoordinateinput-precision' => 'Precision:',
	'valueview-expert-timeinput-precision' => 'Precision:',
	'valueview-expert-timeinput-calendar' => 'Kalender:',
	'valueview-preview-label' => 'kommer att visas som:',
	'valueview-preview-novalue' => 'inget giltigt värde erkänns',
	'valueview-listrotator-auto' => 'automatisk',
);

/** Ukrainian (українська)
 * @author Ата
 */
$messages['uk'] = array(
	'valueview-desc' => 'Компоненти користувацького інтерфейсу для відображення і редагування значень даних',
	'valueview-expert-advancedadjustments' => 'розширені налаштування',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Обробка значень «$1» ще не підтримується.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Обробка значень типу даних «$1» ще не підтримується.',
	'valueview-expert-emptyvalue-empty' => 'пусто',
	'valueview-expert-timeinput-precision' => 'Точність:',
	'valueview-expert-timeinput-calendar' => 'Календар:',
	'valueview-expert-timeinput-calendarhint' => '(пролептичний $1 календар)', # Fuzzy
	'valueview-expert-timeinput-calendarhint-switch' => '&rarr; змінити на $1',
	'valueview-preview-label' => 'буде відображатися як:',
	'valueview-preview-novalue' => 'не розпізнано допустимого значення',
	'valueview-listrotator-auto' => 'автоматично',
);

/** Vietnamese (Tiếng Việt)
 */
$messages['vi'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Chưa hỗ trợ các giá trị dữ liệu “$1”.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Chưa hỗ trợ các giá trị có kiểu dữ liệu “$1”.', # Fuzzy
);

/** Simplified Chinese (中文（简体）‎)
 * @author Li3939108
 * @author Linforest
 * @author Stevenliuyi
 */
$messages['zh-hans'] = array(
	'valueview-expert-advancedadjustments' => '高级调整',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => '尚不支持对“$1”取值的操作。',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => '尚不支持对“$1”数据类型取值的操作。',
	'valueview-expert-emptyvalue-empty' => '空白',
	'valueview-expert-globecoordinateinput-precision' => '精度：',
	'valueview-expert-timevalue-calendar-gregorian' => '格里历',
	'valueview-expert-timevalue-calendar-julian' => '儒略历',
	'valueview-expert-timeinput-precision' => '精度：',
	'valueview-expert-timeinput-calendar' => '日历：',
	'valueview-expert-timeinput-calendarhint-gregorian' => '（格里历）',
	'valueview-expert-timeinput-calendarhint-julian' => '（儒略历）',
	'valueview-expert-timeinput-calendarhint-switch-gregorian' => '&rarr; 更改为格里历',
	'valueview-expert-timeinput-calendarhint-switch-julian' => '&rarr; 更改为儒略历',
	'valueview-preview-label' => '将显示为：',
	'valueview-preview-novalue' => '无法识别',
	'valueview-listrotator-auto' => '自动',
);

/** Traditional Chinese (中文（繁體）‎)
 * @author Simon Shek
 * @author Stevenliuyi
 */
$messages['zh-hant'] = array(
	'valueview-desc' => '顯示和編輯數據值的用戶介面',
	'valueview-expert-advancedadjustments' => '高級調整',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => '尚未支援「$1」值的操作。',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => '尚未支援「$1」數據值的操作。',
	'valueview-expert-emptyvalue-empty' => '空',
	'valueview-expert-globecoordinateinput-precision' => '精度：',
	'valueview-expert-timevalue-calendar-gregorian' => '格里曆',
	'valueview-expert-timevalue-calendar-julian' => '儒略曆',
	'valueview-expert-timeinput-precision' => '精度：',
	'valueview-expert-timeinput-calendar' => '日曆：',
	'valueview-expert-timeinput-calendarhint-gregorian' => '（格里曆）',
	'valueview-expert-timeinput-calendarhint-julian' => '（儒略曆）',
	'valueview-expert-timeinput-calendarhint-switch-gregorian' => '&rarr; 更改為格里曆',
	'valueview-expert-timeinput-calendarhint-switch-julian' => '&rarr; 更改為儒略曆',
	'valueview-preview-label' => '將顯示為：',
	'valueview-preview-novalue' => '無法識別',
	'valueview-listrotator-auto' => '自動',
);
