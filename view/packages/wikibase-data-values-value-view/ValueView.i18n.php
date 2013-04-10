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
 */
$messages['en'] = array(
	'valueview-desc' => 'UI components for displaying and editing data values',

	// UnsupportedValue expert:
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Handling of "$1" values is not yet supported.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Handling of values for "$1" data type is not yet supported.',

	// EmptyValue expert:
	'valueview-expert-emptyvalue-empty' => 'empty'
);

/** Message documentation (Message documentation)
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author Shirayuki
 */
$messages['qqq'] = array(
	'valueview-desc' => '{{desc|name=ValueView|url=http://www.mediawiki.org/wiki/Extension:ValueView}}',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Error shown if a data value of a certain data value type (see [[d:Wikidata:Glossary]]) should be displayed or a form for creating one should be offered while this is not yet possible from a technical point of view (e.g. because a valueview widget expert handling data values of that type has not yet been implemented). $1 is the name of the data value type which lacks support.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Error shown if a data value for a certain data type (see [[d:Wikidata:Glossary]]) should be displayed or a form for creating one should be offered while this is not yet possible from a technical point of view (e.g. because a valueview widget expert handling data values for that data type has not yet been implemented). Parameter $1 is the name of the data type which lacks support',
	'valueview-expert-emptyvalue-empty' => 'Message expressing that there is currently no value set in a jQuery valueview.
{{Identical|Empty}}',
);

/** Belarusian (Taraškievica orthography) (беларуская (тарашкевіца)‎)
 */
$messages['be-tarask'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Апрацоўка зьвестак тыпу «$1» пакуль не падтрымліваецца.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Апрацоўка значэньняў тыпу «$1» яшчэ не падтрымліваецца.', # Fuzzy
);

/** Danish (dansk)
 */
$messages['da'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Håndtering af "$1" dataværdier understøttes ikke endnu.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Håndtering af værdier for "$1" datatype understøttes ikke endnu.', # Fuzzy
);

/** German (Deutsch)
 * @author Metalhead64
 */
$messages['de'] = array(
	'valueview-desc' => 'Ergänzt Komponenten zur Benutzeroberfläche zum Anzeigen und Bearbeiten von Datenwerten',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Das Bearbeiten von Werten mit dem Typ „$1“ wird noch nicht unterstützt.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Das Bearbeiten von Werten für den Datentyp „$1“ wird noch nicht unterstützt.',
	'valueview-expert-emptyvalue-empty' => 'leer',
);

/** Lower Sorbian (dolnoserbski)
 */
$messages['dsb'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Wobźěłowanje datowych gódnotow "$1" hyšći se njepódpěra.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Wobźěłowanje gódnotow za datowy typ "$1" hyšći se njepódpěra.', # Fuzzy
);

/** Spanish (español)
 */
$messages['es'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'La manipulación de valores para el tipo de datos "$1" aún no está soportada.', # Fuzzy
);

/** Persian (فارسی)
 */
$messages['fa'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'دستکاری داده "$1" فعلا امکان پذیر نیست', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'پشتیبانی از مقدار نوع دادهٔ «$1» هنوز پشتیبانی نشده‌است.', # Fuzzy
);

/** Finnish (suomi)
 */
$messages['fi'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Datatyypin "$1" arvojen käsittelyä ei vielä tueta.', # Fuzzy
);

/** French (français)
 * @author Metroitendo
 * @author Peter17
 * @author Urhixidur
 */
$messages['fr'] = array(
	'valueview-desc' => 'Composants graphiques pour l’affichage et la modification des données',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'La manipulation des valeurs de données « $1 » n’est pas encore supportée.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'La gestion des valeurs pour le type de données « $1 » n’est pas encore pris en charge.',
	'valueview-expert-emptyvalue-empty' => 'vide',
);

/** Galician (galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'valueview-desc' => 'Compoñentes da interface para mostrar e editar valores de datos',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'A manipulación de valores "$1" aínda non está soportada.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'A manipulación de valores para o tipo de datos "$1" aínda non está soportada.',
	'valueview-expert-emptyvalue-empty' => 'baleiro',
);

/** Gujarati (ગુજરાતી)
 * @author KartikMistry
 */
$messages['gu'] = array(
	'valueview-expert-emptyvalue-empty' => 'ખાલી',
);

/** Hebrew (עברית)
 * @author Orsa
 */
$messages['he'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'טיפול בערכי נתונים מסוג "$1" אינו נתמך עדיין.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'עדיין אין תמיכה בטיפול בערכים מסוג "$1".', # Fuzzy
	'valueview-expert-emptyvalue-empty' => 'ריק',
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
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'La gestione dei valori "$1" non è ancora supportata.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'La gestione dei valori per il tipo di dati "$1" non è ancora supportata.',
	'valueview-expert-emptyvalue-empty' => 'vuoto',
);

/** Japanese (日本語)
 * @author Shirayuki
 */
$messages['ja'] = array(
	'valueview-desc' => 'データ値を表示/編集するユーザーインターフェイスコンポーネント',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => '「$1」の値の処理にはまだ対応していません。',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'データ型「$1」の値の処理にはまだ対応していません。',
	'valueview-expert-emptyvalue-empty' => '空',
);

/** Korean (한국어)
 */
$messages['ko'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => '"$1" 데이터 값의 처리는 아직 지원하지 않습니다.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => '"$1" 데이터 유형에 대한 값의 처리는 아직 지원하지 않습니다.', # Fuzzy
);

/** Macedonian (македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'valueview-desc' => 'Посреднички компоненти за приказ и уредување на податочни вредности',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Работата со вредности од типот „$1“ сè уште не е поддржана.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Работата со вредности за податочниот тип „$1“ сè уште не е поддржана.',
	'valueview-expert-emptyvalue-empty' => 'празно',
);

/** Malay (Bahasa Melayu)
 */
$messages['ms'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Pengelolaan nilai-nilai untuk jenis data "$1" belum disokong.', # Fuzzy
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
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Het verwerken van gegevenswaarden van het type "$1" wordt nog niet ondersteund.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Het verwerken van waarden van het gegevenstype "$1" wordt nog niet ondersteund.', # Fuzzy
);

/** Polish (polski)
 * @author Ty221
 */
$messages['pl'] = array(
	'valueview-expert-emptyvalue-empty' => 'pusty',
);

/** Piedmontese (Piemontèis)
 */
$messages['pms'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'La gestion dij valor për la sòrt ëd dat "$1" a l\'é anco\' pa apogià.', # Fuzzy
);

/** tarandíne (tarandíne)
 * @author Joetaras
 */
$messages['roa-tara'] = array(
	'valueview-desc' => 'Combonende UI pe fà vedè e cangià le volre de le date',
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
 * @author Милан Јелисавчић
 */
$messages['sr-ec'] = array(
	'valueview-desc' => 'УИ компоненте за приказ и уређивање података',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Начин управљања вредностима за „$1“ врсту података још није подржан.', # Fuzzy
	'valueview-expert-emptyvalue-empty' => 'празно',
);

/** Ukrainian (українська)
 */
$messages['uk'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Обробка значень типу «$1» не підтримується.', # Fuzzy
);

/** Vietnamese (Tiếng Việt)
 */
$messages['vi'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Chưa hỗ trợ các giá trị dữ liệu “$1”.', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Chưa hỗ trợ các giá trị có kiểu dữ liệu “$1”.', # Fuzzy
);

/** Simplified Chinese (中文（简体）‎)
 */
$messages['zh-hans'] = array(
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => '“$1”数据值的操作尚不支持。', # Fuzzy
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => '对“$1”数据类型的值的操作尚不支持。', # Fuzzy
);

/** Traditional Chinese (中文（繁體）‎)
 * @author Simon Shek
 */
$messages['zh-hant'] = array(
	'valueview-desc' => '顯示和編輯數據值的用戶介面',
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => '尚未支援「$1」值的操作。',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => '尚未支援「$1」數據值的操作。',
	'valueview-expert-emptyvalue-empty' => '空',
);
