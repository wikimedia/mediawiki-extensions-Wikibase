import { UlsData } from '@/@types/mediawiki/MwWindow';
import DirectionalityRepository from '@/definitions/data-access/DirectionalityRepository';

export default class UlsDirectionalityRepository implements DirectionalityRepository {
	private resolver: ( languageCode?: string ) => 'ltr'|'rtl';

	public constructor( ulsDirectionality: UlsData ) {
		this.resolver = ulsDirectionality.getDir;
	}

	public resolve( languageCode: string ): 'ltr'|'rtl' {
		return this.resolver( languageCode );
	}
}
