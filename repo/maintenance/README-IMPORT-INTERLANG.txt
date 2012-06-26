My setup is with a number of servers and symlinks to catalogs used for development.
Repository server is located in /var/www/repo and there are symlinks for maintenenance
pointing to ~/Workspace/core/maintenance and ~/Workspace/Wikibase/repo/maintenance

There are also an environment variable in .bashrc
export MW_INSTALL_PATH="/var/www/repo"

Settings for LocalSettings.php that seems to work
$egWBSettings['apiUseKeys'] = false;
$egWBSettings['apiInDebug'] = true;
$egWBSettings['apiInTest'] = true;
$egWBSettings['apiDebugWithPost'] = false;
$egWBSettings['apiDebugWithTokens'] = false;
$egWBSettings['apiDebugWithRights'] = false;

A call that seems to work is like
php maintenance/importInterlang.php --conf LocalSettings.php no ~/Workspace/Wikibase/repo/maintenance/simple-elements.csv "http://localhost/repo/api.php"
 