import DataBridgeConfig from '@/@types/wikibase/DataBridgeConfig';
import WbRepo from '@/@types/wikibase/WbRepo';

interface MwConfigValues {
	wbDataBridgeConfig: DataBridgeConfig;
	wbRepo: WbRepo;
	wgPageContentLanguage: string;
	wgPageName: string;
	wgUserName: string|null;
}

interface MwConfig {
	get<K extends keyof MwConfigValues>( key: K ): MwConfigValues[ K ];
}

export default MwConfig;
