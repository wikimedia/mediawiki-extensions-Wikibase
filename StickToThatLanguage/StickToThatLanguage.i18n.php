<?php
/**
 * Internationalization file for the 'Stick to That Language' extension.
 *
 * @since 0.1
 *
 * @file StickToThatLanguage.i18n.php
 * @ingroup STTLanguage
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */

$messages = array();

/** English
 * @author Daniel Werner
 */
$messages['en'] = array(
	'sticktothatlanguage-desc' => 'Provides functionality to choose a language persistently',
	'sttl-setting-languages' => 'Additional languages<br />(as fallback when displaying data not available in the main language)',
	'sttl-languages-more-link' => 'more languages',
);

/** Message documentation (Message documentation)
 * @author Daniel Werner
 */
$messages['qqq'] = array(
	'sttl-setting-languages' => 'Label for the user settings where the user can choose several languages he is considering interested. These languages will be displayed on top of any language selector and can be considered special by other extensions.',
	'sttl-languages-more-link' => 'Link to show all languages other than the top 10 languages. The link sits within the "In other languages" section below the top 10 languages that are always displayed. Clicking the link unfolds all the links to other languages just below it.',
);
