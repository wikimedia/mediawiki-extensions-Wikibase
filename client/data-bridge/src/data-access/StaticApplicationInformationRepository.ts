import AppInformation from '@/definitions/AppInformation';
import ApplicationInformationRepository from '@/definitions/data-access/ApplicationInformationRepository';

export default class StaticApplicationInformationRepository implements ApplicationInformationRepository {
	private readonly information: AppInformation;

	public constructor( information: AppInformation ) {
		this.information = information;
	}

	public getInformation(): Promise<AppInformation> {
		return Promise.resolve( this.information );
	}
}
