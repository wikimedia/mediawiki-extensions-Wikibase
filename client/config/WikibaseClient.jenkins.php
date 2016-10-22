<?php

/**
 * Configuration overrides for use in a Jenkins CI based testing environment.
 *
 * NOTE: Ideally, no such overrides should be needed, as tests should be self-contained.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */

// Force the siteGroup name, so we do not rely on looking up the
// group in the sites table during testing.
// NOTE: This can be removed once T126596 is implemented.
$wgWBClientSettings['siteGroup'] = "mywikigroup";

// TODO: Remove the feature flag when not needed any more!
$wgWBClientSettings['enableStatementsParserFunction'] = true;
