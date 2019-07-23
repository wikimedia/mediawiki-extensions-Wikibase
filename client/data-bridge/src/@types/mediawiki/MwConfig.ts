import DataBridgeConfig from '@/@types/wikibase/DataBridgeConfig';

interface MwConfigValues {
	wbDataBridgeConfig: DataBridgeConfig;
}

interface MwConfig {
	get<K extends keyof MwConfigValues>( key: K ): MwConfigValues[ K ];
}

export default MwConfig;
