export default class TitleInvalid extends Error {
	public readonly title: string;
	public constructor( title: string ) {
		super( `The title '${title}' is invalid.` );
		this.title = title;
	}
}
