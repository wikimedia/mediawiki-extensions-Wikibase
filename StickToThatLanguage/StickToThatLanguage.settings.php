<?php

/**
 * File defining the settings for the 'Stick to That Language' extension.
 * More info can be found at https://www.mediawiki.org/wiki/Extension:Stick_to_That_Language#Configuration
 *
 * NOTICE:
 * =======
 * Changing one of these settings can be done by copying and placing
 * it in LocalSettings.php, AFTER the inclusion of the extension.
 *
 * @file StickToThatLanguage.settings.php
 * @ingroup STTLanguage
 * @since 0.1
 *
 * @author Daniel Werner
 */

/**
 * Allows to define whether the language selector should be used or not.
 *
 * @since 0.1
 *
 * @var bool
 */
$egSTTLanguageDisplaySelector = true;

/**
 * preferred languages (displayed on top) for the language switcher for non-logged-in users. Logged-
 * in users can choose their preferred languages, these will be used instead.
 * By default this is set to the busiest languages on Wikipedia.
 *
 * NOTE: These languages are not used as defaults for the preferred languages user option.
 */
$egSTTLanguageTopLanguages = array( 'en', 'de', 'fr', 'nl', 'it', 'pl', 'es', 'ru', 'ja', 'pt' );
