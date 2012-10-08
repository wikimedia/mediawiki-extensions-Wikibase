<?php

namespace Wikibase;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for rebuilding all secondary Wikibase data (ie indexes and caches).
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class RebuildAllData extends \Maintenance {

	public function __construct() {
		$this->mDescription = 'Rebuild the Wikidata data';

		parent::__construct();
	}

	public function execute() {
		$quick = $_SERVER['argc'] > 1 && $_SERVER['argv'][1] == '--yes-im-sure-maybe';

		if ( !$quick ) {
			echo "Are you really really sure you want to rebuild all the Wikibase data?? If so, type YES\n";

			if ( $this->readconsole() !== 'YES' ) {
				return;
			}
		}

		$report = function( $message ) {
			echo $message;
		};

		wfRunHooks( 'WikibaseDeleteData', array( $report ) );

		echo exec('php ../../lib/maintenance/pollForChanges.php  --all');

		$report( <<<EOT

	                 ......                             ..          ,,
                 ..=~..                             ZD.   ....:=,.
                 ..:++=..     .,.     .:        .....M....,=+++...
                 ...=+++~......:~.....~= ..   ...~==.7.:+++++=.. .
                  . :+++++~.. .,+=...,++...  ..~++=..:++++++~...
                    .+++++++~..:++=..~++,.. .:++++~===+++++=.
                    .~++++++++~:++++:=++=..~++++++++:=++++:..
           ....     ..++++++++++++=+M7IM~+++++=+=+++8++++:.
           .=:...... .,++++++++++:M:::::~D~~:M...I++M+++:.
          ...,=+=,... .=++++++++++++?N ......M. .?++I++=..
            ...:+++=,...+++++++++O.M..  .O= ,. +..++~+=.
        .MD. ....+++++==~+++++++:...M,M,NMMMMM, ...~=+,..           ....
     .=.$:::~.....+++++++++++++++M.7M.MMMMMMMMM.   Z+~.......      ..,==..
     .M::::::M... .~+++++++++++++++.7MMMMMMMMMM.   M+,,~==+,.....~=++=.
     .M:::::::?..:=+++++++++++++++=, .:MMMMMMMM  ..8+++++:...,==+++=...
       M~::::::M...=+++++++++++++++=,...MMMMMMN ..?7=+++,.~+++++=:...
       .M:::~~~8....,++++++++++++++=M   MMMMMMM...77N+++++++++=,..
       ...:+=NOM. ....~+++++++++==I++Z  .?MMMN  .Z7I7=++++++=,..
            ..?OZ~++++++++++++~N==++++?,.......=O77I7O+++++~......
        ......,~OO?+++++++++I++++++++++DIZ8DDZ7777777O++++,..........
       ..~++++=::8ON=++++=N=+++++++++++=77777777777777~+++++++++++++++=:..
          ,+++++++DZ8=+8=+++++++++++++++D7I77777777777N+++++++++++++++++:.
          .~+++++++:8O?++++++++++++++++++II777777777777=++++++++++=:......
          ..=+++++++=DON=++++++++++++++++D77777777777I7=++++++~,..
          ...=++++++++8O8=+++++++++++++++OI777777777777=++=:......
            ..:++++++++~OO??++++++++++++~?I777777777777=++=,.
......... .....,++++++++~8ZN++++++++++++~77777777777777=++++:...................

                                  REBUILD
                             ALL OF THE DATAS!

EOT
		);
	}

}

$maintClass = 'Wikibase\RebuildAllData';
require_once( RUN_MAINTENANCE_IF_MAIN );
