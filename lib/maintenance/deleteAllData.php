<?php

namespace Wikibase;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for deleting all Wikibase data.
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
 * @ingroup WikibaseLib
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
		if ( !defined( 'WBL_VERSION' ) ) {
			$this->output( "You need to have WikibaseLib enabled in order to use this maintenance script!\n\n" );
			exit;
		}

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
