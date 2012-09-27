<?php

namespace Wikibase;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for deleting all Wikibase data.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DeleteAllData extends \Maintenance {

	public function __construct() {
		$this->mDescription = 'Delete the Wikidata data';

		parent::__construct();
	}

	public function execute() {
		$quick = $_SERVER['argc'] > 1 && $_SERVER['argv'][1] == '--yes-im-sure-maybe';

		if ( !$quick ) {
			echo "Are you really really sure you want to delete all the Wikibase data?? If so, type DELETE\n";

			if ( $this->readconsole() !== 'DELETE' ) {
				return;
			}
		}

		$report = function( $message ) {
			echo $message;
		};

		wfRunHooks( 'WikibaseDeleteData', array( $report ) );

		$dbw = wfGetDB( DB_MASTER );

		$tables = array(
			'wb_changes',
		);

		// TODO: put in client
		if ( defined( 'WBC_VERSION' ) ) {
			$tables = array_merge( $tables, array(
				'wbc_item_usage',
				'wbc_query_usage',
				'wbc_entity_cache',
				'wbc_items_per_site',
			) );
		}

		foreach ( $tables as $table ) {
			echo "Emptying table $table...";

			$dbw->delete( $dbw->tableName( $table ), '*', __METHOD__ );

			echo "done!\n";
		}

		$report( <<<EOT
Some tasty bits there... omnomnom...

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

                                  DELETED
                             ALL OF THE DATAS!

EOT
		);
	}

}

$maintClass = 'Wikibase\DeleteAllData';
require_once( RUN_MAINTENANCE_IF_MAIN );
