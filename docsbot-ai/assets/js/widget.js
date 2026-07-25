( function () {
	'use strict';

	var bootstrap = window.docsbotAIWordPress;

	if ( ! bootstrap || ! bootstrap.endpoint || ! bootstrap.path || ! bootstrap.ticket ) {
		return;
	}

	var headers = { Accept: 'application/json' };
	if ( bootstrap.nonce ) {
		headers['X-WP-Nonce'] = bootstrap.nonce;
	}

	fetch( bootstrap.endpoint + '?path=' + encodeURIComponent( bootstrap.path ) + '&ticket=' + encodeURIComponent( bootstrap.ticket ), {
		credentials: 'same-origin',
		headers: headers,
		cache: 'no-store'
	} )
		.then( function ( response ) {
			if ( ! response.ok ) {
				throw new Error( 'Widget unavailable' );
			}
			return response.json();
		} )
		.then( function ( config ) {
			return new Promise( function ( resolve, reject ) {
				var script = document.createElement( 'script' );
				script.src = 'https://widget.docsbot.ai/chat.js';
				script.async = true;
				script.onload = resolve;
				script.onerror = reject;
				document.head.appendChild( script );
			} ).then( function () {
				if ( window.DocsBotAI && typeof window.DocsBotAI.mount === 'function' ) {
					return window.DocsBotAI.mount( config );
				}
				throw new Error( 'DocsBot widget did not initialize' );
			} );
		} )
		.catch( function () {
			// Access denials and network failures stay silent on public pages.
		} );
}() );
