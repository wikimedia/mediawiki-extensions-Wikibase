import DataBridgeConfig from '@/@types/wikibase/DataBridgeConfig';
import WbRepo from '@/@types/wikibase/WbRepo';

interface MwConfigValues {
	wbDataBridgeConfig: DataBridgeConfig;
	wbRepo: WbRepo;
	wgUserName: string;
	wgPageContentLanguage: string;
}

interface MwConfig {
	get<K extends keyof MwConfigValues>( key: K ): MwConfigValues[ K ];
}

export default MwConfig;
