<?php

/**
 * PHPUnit test bootstrap file for the Wikibase DataModel component.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

require_once( __DIR__ . '/../../../DataValues/DataValues/DataValues.php' );

require_once( __DIR__ . '/../../../Diff/Diff.php' );

require_once( __DIR__ . '/../DataModel.php' );

require_once( __DIR__ . '/testLoader.php' );

// If something needs to change here, a reflecting change needs to be added to ../dependencies.txt.