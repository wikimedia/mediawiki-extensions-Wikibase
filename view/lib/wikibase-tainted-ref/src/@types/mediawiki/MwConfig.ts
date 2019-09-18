import WbRepo from '@/@types/wikibase/WbRepo';

interface MwConfigValues {
	wbRepo: WbRepo;
	wgUserName: string;
	wgPageContentLanguage: string;
	wbTaintedReferencesEnabled: boolean;
}

interface MwConfig {
	get<K extends keyof MwConfigValues>( key: K ): MwConfigValues[ K ];
}

export default MwConfig;
