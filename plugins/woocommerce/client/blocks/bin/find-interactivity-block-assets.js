const path = require( 'path' );
const fs = require( 'fs' );
const glob = require( 'glob' );

function findInteractivityBlockAssets( dir, additionalPatterns = [] ) {
	let results = [];
	const ents = fs.readdirSync( dir, { withFileTypes: true } );

	for ( const entry of ents ) {
		const fullPath = path.join( dir, entry.name );
		if ( entry.isDirectory() ) {
			results = results.concat(
				findInteractivityBlockAssets( fullPath, additionalPatterns )
			);
		} else if ( entry.isFile() && entry.name === 'block.json' ) {
			// parse the json file and determine if its a block that supports interactivity.
			const blockJson = JSON.parse( fs.readFileSync( fullPath, 'utf8' ) );

			if ( blockJson.supports && blockJson.supports.interactivity ) {
				const blockDir = path.dirname( fullPath );
				const assets = additionalPatterns.flatMap( ( pattern ) =>
					glob.sync( pattern, { cwd: blockDir, absolute: true } )
				);
				results.push( {
					blockName: blockJson.name,
					blockJson: fullPath,
					assets,
				} );
			}
		}
	}

	return results;
}

module.exports = {
	findInteractivityBlockAssets,
};
