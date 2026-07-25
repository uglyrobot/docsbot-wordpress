( function () {
	'use strict';

	document.querySelectorAll( '.docsbot-reveal' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var input = document.getElementById( button.dataset.reveal );
			if ( ! input ) {
				return;
			}
			var reveal = input.type === 'password';
			input.type = reveal ? 'text' : 'password';
			button.textContent = reveal ? button.dataset.hideLabel : button.dataset.showLabel;
			button.setAttribute( 'aria-pressed', reveal ? 'true' : 'false' );
		} );
	} );

	var team = document.getElementById( 'docsbot-team' );
	if ( team ) {
		team.addEventListener( 'change', function () {
			var bot = document.getElementById( 'docsbot-bot' );
			if ( bot ) {
				bot.value = '';
			}
		} );
	}

	var deployForm = document.querySelector( '.docsbot-deploy-form' );
	if ( deployForm ) {
		var saveBar = deployForm.querySelector( '.docsbot-sticky-save' );
		var dirty = false;
		var markDirty = function () {
			dirty = true;
			if ( saveBar ) {
				saveBar.hidden = false;
			}
		};

		deployForm.addEventListener( 'input', markDirty );
		deployForm.addEventListener( 'change', markDirty );
		deployForm.addEventListener( 'submit', function () {
			dirty = false;
		} );
		document.querySelectorAll( '.docsbot-tabs a' ).forEach( function ( link ) {
			link.addEventListener( 'click', function ( event ) {
				if ( dirty ) {
					if ( ! window.confirm( deployForm.dataset.unsavedMessage ) ) {
						event.preventDefault();
					} else {
						dirty = false;
					}
				}
			} );
		} );
		window.addEventListener( 'beforeunload', function ( event ) {
			if ( dirty ) {
				event.preventDefault();
				event.returnValue = '';
			}
		} );
	}

	var colorPicker = document.getElementById( 'docsbot-color-picker' );
	var colorText = document.getElementById( 'docsbot-color' );
	if ( colorPicker && colorText ) {
		colorPicker.addEventListener( 'input', function () {
			colorText.value = colorPicker.value;
		} );
		colorText.addEventListener( 'input', function () {
			if ( /^#[0-9a-f]{6}$/i.test( colorText.value ) ) {
				colorPicker.value = colorText.value;
			}
		} );
	}
}() );
