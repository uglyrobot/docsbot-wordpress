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

	var reconnect = document.querySelector( '.docsbot-reconnect' );
	if ( reconnect ) {
		reconnect.addEventListener( 'click', function () {
			var panel = document.querySelector( '.docsbot-reconnect-panel' );
			if ( ! panel ) {
				return;
			}
			var opening = panel.hidden;
			panel.hidden = ! opening;
			reconnect.setAttribute( 'aria-expanded', opening ? 'true' : 'false' );
			if ( opening ) {
				document.getElementById( 'docsbot-api-key' ).focus();
			}
		} );
	}

	var disconnectForm = document.querySelector( '.docsbot-disconnect-form' );
	if ( disconnectForm ) {
		disconnectForm.addEventListener( 'submit', function ( event ) {
			var button = disconnectForm.querySelector( '[data-confirm]' );
			if ( button && ! window.confirm( button.dataset.confirm ) ) {
				event.preventDefault();
			}
		} );
	}

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

	document.querySelectorAll( '[data-depends-on]' ).forEach( function ( panel ) {
		var controller = document.querySelector( '[name="' + panel.dataset.dependsOn + '"]' );
		if ( ! controller ) {
			return;
		}
		var updatePanel = function () {
			panel.hidden = ! controller.checked;
		};
		controller.addEventListener( 'change', updatePanel );
		updatePanel();
	} );

	document.querySelectorAll( '[data-custom-icon-for]' ).forEach( function ( panel ) {
		var choices = document.querySelectorAll( '[name="' + panel.dataset.customIconFor + '"]' );
		var updatePanel = function () {
			var selected = document.querySelector( '[name="' + panel.dataset.customIconFor + '"]:checked' );
			panel.hidden = ! selected || selected.value !== 'custom';
		};
		choices.forEach( function ( choice ) {
			choice.addEventListener( 'change', updatePanel );
		} );
		updatePanel();
	} );

	var bookingActions = document.querySelector( '[data-booking-actions]' );
	if ( bookingActions ) {
		var bookingToggles = bookingActions.querySelectorAll( '[data-booking-toggle]' );
		var updateBooking = function ( changed ) {
			if ( changed && changed.checked ) {
				bookingToggles.forEach( function ( toggle ) {
					if ( toggle !== changed ) {
						toggle.checked = false;
					}
				} );
			}
			bookingActions.querySelectorAll( '[data-booking-action]' ).forEach( function ( editor ) {
				var toggle = editor.querySelector( '[data-booking-toggle]' );
				var enabled = Boolean( toggle && toggle.checked );
				var body = editor.querySelector( '.docsbot-action-editor__body' );
				editor.classList.toggle( 'is-enabled', enabled );
				if ( body ) {
					body.hidden = ! enabled;
				}
			} );
		};
		bookingToggles.forEach( function ( toggle ) {
			toggle.addEventListener( 'change', function () {
				updateBooking( toggle );
			} );
		} );
		updateBooking();
	}

	var leadCollection = document.querySelector( '[data-lead-collection]' );
	if ( leadCollection ) {
		var leadToggle = leadCollection.querySelector( '[data-lead-collection-toggle]' );
		var leadBody = leadCollection.querySelector( '[data-lead-collection-body]' );
		var leadList = leadCollection.querySelector( '[data-lead-fields]' );
		var leadTemplate = document.getElementById( 'docsbot-lead-field-template' );
		var leadAddType = leadCollection.querySelector( '[data-lead-add-type]' );
		var leadAdd = leadCollection.querySelector( '[data-lead-add-field]' );
		var leadDragged = null;
		var nextLeadIndex = leadList ? leadList.children.length : 0;
		var leadFieldDefaults = function ( field, type ) {
			var key = type === 'datetime-local' ? 'datetime' : type;
			var used = Array.prototype.map.call( leadList.querySelectorAll( '[data-lead-key]' ), function ( input ) { return input.value; } );
			var suffix = 2;
			while ( used.indexOf( key ) !== -1 ) { key = type + suffix; suffix += 1; }
			var labels = { tel: 'Phone', url: 'Website', textarea: 'Notes', 'datetime-local': 'Date & Time' };
			var label = labels[ type ] || type.charAt( 0 ).toUpperCase() + type.slice( 1 );
			field.querySelector( '[data-lead-key]' ).value = key;
			field.querySelector( '[data-lead-label]' ).value = label;
			field.querySelector( '[data-lead-type]' ).value = type;
			if ( type === 'url' ) { field.querySelector( '[name$="[placeholder]"]' ).value = 'https://example.com'; }
			if ( type === 'select' ) { updateLeadFieldType( field ); }
		};
		var updateLeadTitle = function ( field ) {
			var title = field.querySelector( '[data-lead-field-title]' );
			var label = field.querySelector( '[data-lead-label]' );
			var key = field.querySelector( '[data-lead-key]' );
			if ( title ) { title.textContent = ( label && label.value.trim() ) || ( key && key.value.trim() ) || 'Untitled field'; }
		};
		var updateLeadFieldType = function ( field ) {
			var type = field.querySelector( '[data-lead-type]' );
			var options = field.querySelector( '[data-lead-options]' );
			if ( options ) {
				options.hidden = ! type || type.value !== 'select';
				if ( type && type.value === 'select' && ! options.querySelector( '.docsbot-lead-option' ) ) {
					addLeadOption( field );
				}
			}
		};
		var addLeadOption = function ( field ) {
			var list = field.querySelector( '[data-lead-option-list]' );
			if ( ! list ) { return; }
			var prefix = field.querySelector( '[data-lead-key]' ).name.replace( '[key]', '' );
			var index = list.children.length;
			var row = document.createElement( 'div' );
			row.className = 'docsbot-lead-option';
			row.innerHTML = '<input type="text" name="' + prefix + '[options][' + index + '][value]" placeholder="Value"><input type="text" name="' + prefix + '[options][' + index + '][label]" placeholder="Label"><button type="button" class="button-link-delete" data-lead-remove-option>Remove</button>';
			list.appendChild( row );
		};
		var bindLeadField = function ( field ) {
			field.querySelectorAll( '[data-lead-key], [data-lead-label]' ).forEach( function ( input ) { input.addEventListener( 'input', function () { updateLeadTitle( field ); } ); } );
			var type = field.querySelector( '[data-lead-type]' );
			if ( type ) { type.addEventListener( 'change', function () { updateLeadFieldType( field ); } ); }
			field.addEventListener( 'click', function ( event ) {
				if ( event.target.closest( '[data-lead-remove]' ) ) { if ( leadList.children.length > 1 ) { field.remove(); } return; }
				if ( event.target.closest( '[data-lead-add-option]' ) ) { addLeadOption( field ); return; }
				var option = event.target.closest( '[data-lead-remove-option]' ); if ( option && field.querySelector( '[data-lead-option-list]' ).children.length > 1 ) { option.closest( '.docsbot-lead-option' ).remove(); }
			} );
			field.addEventListener( 'dragstart', function () { leadDragged = field; field.classList.add( 'is-dragging' ); } );
			field.addEventListener( 'dragend', function () { leadDragged = null; field.classList.remove( 'is-dragging' ); } );
			field.addEventListener( 'dragover', function ( event ) { if ( leadDragged && leadDragged !== field ) { event.preventDefault(); } } );
			field.addEventListener( 'drop', function ( event ) { if ( leadDragged && leadDragged !== field ) { event.preventDefault(); leadList.insertBefore( leadDragged, field ); } } );
			updateLeadTitle( field ); updateLeadFieldType( field );
		};
		var updateLeadCollection = function () { var enabled = Boolean( leadToggle && leadToggle.checked ); leadCollection.classList.toggle( 'is-enabled', enabled ); if ( leadBody ) { leadBody.hidden = ! enabled; } };
		leadList.querySelectorAll( '[data-lead-field]' ).forEach( bindLeadField );
		if ( leadToggle ) { leadToggle.addEventListener( 'change', updateLeadCollection ); }
		if ( leadAdd ) { leadAdd.addEventListener( 'click', function () { if ( ! leadTemplate || ! leadAddType || ! leadAddType.value ) { return; } var wrapper = document.createElement( 'div' ); wrapper.innerHTML = leadTemplate.innerHTML.split( '__INDEX__' ).join( String( nextLeadIndex++ ) ); var field = wrapper.firstElementChild; if ( field ) { leadList.appendChild( field ); leadFieldDefaults( field, leadAddType.value ); bindLeadField( field ); leadAddType.value = ''; } } ); }
		updateLeadCollection();
	}

	var customButtons = document.querySelector( '[data-custom-buttons]' );
	var customButtonTemplate = document.getElementById( 'docsbot-custom-button-template' );
	var addCustomButton = document.querySelector( '[data-add-custom-button]' );
	var customButtonPrompt = document.querySelector( '[data-custom-button-prompt]' );
	if ( customButtons ) {
		var bindCustomButton = function ( editor ) {
			var remove = editor.querySelector( '.docsbot-remove-custom-button' );
			var name = editor.querySelector( '[data-custom-button-name]' );
			var title = editor.querySelector( '[data-custom-button-title]' );
			var toggle = editor.querySelector( '[name$="[enabled]"]' );
			var body = editor.querySelector( '.docsbot-action-editor__body' );
			var updateState = function () {
				var enabled = Boolean( toggle && toggle.checked );
				editor.classList.toggle( 'is-enabled', enabled );
				if ( body ) {
					body.hidden = ! enabled;
					body.querySelectorAll( 'input:not([type="hidden"]), textarea' ).forEach( function ( field ) {
						field.required = enabled && field.name.indexOf( '[icon]' ) === -1;
					} );
				}
			};
			if ( remove ) {
				remove.addEventListener( 'click', function () {
					var wasUnsavedDraft = editor.dataset.unsavedDraft === 'true';
					editor.remove();
					if ( wasUnsavedDraft && addCustomButton ) {
						addCustomButton.disabled = false;
						addCustomButton.hidden = false;
					}
				} );
			}
			if ( name && title ) {
				name.addEventListener( 'input', function () {
					title.textContent = name.value.trim() || editor.dataset.newTitle;
				} );
			}
			if ( toggle ) {
				toggle.addEventListener( 'change', updateState );
			}
			updateState();
		};
		var appendCustomButton = function ( draft ) {
			var nextIndex = parseInt( customButtons.dataset.nextIndex || '0', 10 );
			var baseKey = draft.functionKey || '';
			var functionKey = baseKey;
			var suffix = 2;
			var usedKeys = Array.prototype.map.call(
				customButtons.querySelectorAll( '[name$="[functionKey]"]' ),
				function ( input ) {
					return input.value;
				}
			);
			while ( functionKey && usedKeys.indexOf( functionKey ) !== -1 ) {
				functionKey = baseKey + '_' + suffix;
				suffix += 1;
			}
			customButtons.dataset.nextIndex = String( nextIndex + 1 );
			var wrapper = document.createElement( 'div' );
			wrapper.innerHTML = customButtonTemplate.innerHTML.split( '__INDEX__' ).join( String( nextIndex ) );
			var editor = wrapper.firstElementChild;
			if ( ! editor ) {
				return null;
			}
			var values = {
				'name': draft.name || '',
				'functionKey': functionKey,
				'instructions': draft.instructions || '',
				'buttonText': draft.buttonText || '',
				'icon': draft.icon || 'LinkIcon',
				'url': draft.url || ''
			};
			Object.keys( values ).forEach( function ( field ) {
				var input = editor.querySelector( '[name$="[' + field + ']"]' );
				if ( input ) {
					input.value = values[ field ];
				}
			} );
			var generatedIcon = editor.querySelector( '[data-custom-button-icon]' );
			if ( generatedIcon ) {
				editor.querySelectorAll( '[data-custom-button-header-icon] use' ).forEach( function ( use ) {
					var href = use.getAttribute( 'href' ) || '';
					use.setAttribute( 'href', href.split( '#' )[ 0 ] + '#' + generatedIcon.value );
				} );
			}
			var enabled = editor.querySelector( '[name$="[enabled]"]' );
			if ( enabled ) {
				enabled.checked = draft.enabled !== false;
			}
			customButtons.appendChild( editor );
			bindCustomButton( editor );
			var name = editor.querySelector( '[data-custom-button-name]' );
			if ( name ) {
				name.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			}
			return editor;
		};
		customButtons.querySelectorAll( '.docsbot-custom-button-editor' ).forEach( bindCustomButton );
		if ( addCustomButton && customButtonTemplate && customButtonPrompt ) {
			var promptInput = customButtonPrompt.querySelector( '[data-custom-button-prompt-input]' );
			var promptError = customButtonPrompt.querySelector( '[data-custom-button-prompt-error]' );
			var promptStatus = customButtonPrompt.querySelector( '[data-custom-button-prompt-status]' );
			var generateButton = customButtonPrompt.querySelector( '[data-generate-custom-button]' );
			var cancelButton = customButtonPrompt.querySelector( '[data-cancel-custom-button]' );
			var updateGenerateButton = function () {
				if ( generateButton && promptInput ) {
					generateButton.disabled = ! promptInput.value.trim();
				}
			};
			var closePrompt = function ( clearInput, restoreFocus ) {
				customButtonPrompt.hidden = true;
				customButtonPrompt.setAttribute( 'aria-busy', 'false' );
				addCustomButton.hidden = false;
				addCustomButton.setAttribute( 'aria-expanded', 'false' );
				if ( promptError ) {
					promptError.hidden = true;
				}
				if ( promptStatus ) {
					promptStatus.textContent = '';
				}
				if ( clearInput && promptInput ) {
					promptInput.value = '';
					updateGenerateButton();
				}
				if ( restoreFocus ) {
					addCustomButton.focus();
				}
			};
			addCustomButton.addEventListener( 'click', function () {
				var maxButtons = parseInt( customButtons.dataset.maxButtons || '20', 10 );
				if ( customButtons.querySelectorAll( '.docsbot-custom-button-editor' ).length >= maxButtons ) {
					customButtonPrompt.hidden = false;
					addCustomButton.hidden = true;
					addCustomButton.setAttribute( 'aria-expanded', 'true' );
					if ( promptInput ) {
						promptInput.disabled = true;
					}
					if ( generateButton ) {
						generateButton.disabled = true;
					}
					if ( promptError ) {
						promptError.textContent = customButtonPrompt.dataset.limitLabel;
						promptError.hidden = false;
					}
					return;
				}
				addCustomButton.hidden = true;
				addCustomButton.setAttribute( 'aria-expanded', 'true' );
				customButtonPrompt.hidden = false;
				if ( promptInput ) {
					promptInput.disabled = false;
					updateGenerateButton();
					promptInput.focus();
				}
			} );
			if ( cancelButton ) {
				cancelButton.addEventListener( 'click', function () {
					closePrompt( true, true );
				} );
			}
			if ( generateButton && promptInput ) {
				promptInput.addEventListener( 'input', updateGenerateButton );
				updateGenerateButton();
				generateButton.addEventListener( 'click', function () {
					var maxButtons = parseInt( customButtons.dataset.maxButtons || '20', 10 );
					if ( customButtons.querySelectorAll( '.docsbot-custom-button-editor' ).length >= maxButtons ) {
						if ( promptError ) {
							promptError.textContent = customButtonPrompt.dataset.limitLabel;
							promptError.hidden = false;
						}
						return;
					}
					var input = promptInput.value.trim();
					if ( ! input ) {
						promptInput.focus();
						return;
					}
					var originalLabel = generateButton.textContent;
					generateButton.disabled = true;
					generateButton.textContent = customButtonPrompt.dataset.loadingLabel;
					customButtonPrompt.setAttribute( 'aria-busy', 'true' );
					if ( promptStatus ) {
						promptStatus.textContent = customButtonPrompt.dataset.loadingLabel;
					}
					promptInput.disabled = true;
					if ( cancelButton ) {
						cancelButton.disabled = true;
					}
					if ( promptError ) {
						promptError.hidden = true;
					}
					var body = new window.URLSearchParams();
					body.set( 'action', 'docsbot_custom_button_draft' );
					body.set( 'nonce', customButtonPrompt.dataset.nonce );
					body.set( 'input', input );
					window.fetch( customButtonPrompt.dataset.ajaxUrl, {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
						body: body.toString()
					} ).then( function ( response ) {
						return response.text().then( function ( raw ) {
							var data;
							try {
								data = JSON.parse( raw );
							} catch ( error ) {
								throw new Error( customButtonPrompt.dataset.errorLabel );
							}
							if ( ! response.ok || ! data.success ) {
								throw new Error( data.data && data.data.message ? data.data.message : customButtonPrompt.dataset.errorLabel );
							}
							return data.data;
						} );
					} ).then( function ( draft ) {
						var editor = appendCustomButton( draft );
						closePrompt( true, false );
						if ( editor ) {
							editor.dataset.unsavedDraft = 'true';
							addCustomButton.disabled = true;
							addCustomButton.hidden = true;
							var url = editor.querySelector( '[name$="[url]"]' );
							if ( url ) {
								url.focus();
							}
						}
					} ).catch( function ( error ) {
						if ( promptError ) {
							promptError.textContent = error.message;
							promptError.hidden = false;
						}
					} ).finally( function () {
						customButtonPrompt.setAttribute( 'aria-busy', 'false' );
						if ( promptStatus ) {
							promptStatus.textContent = '';
						}
						promptInput.disabled = false;
						if ( cancelButton ) {
							cancelButton.disabled = false;
						}
						generateButton.textContent = originalLabel;
						updateGenerateButton();
					} );
				} );
			}
		}
	}

	var colorPicker = document.getElementById( 'docsbot-color-picker' );
	var colorText = document.getElementById( 'docsbot-color' );
	if ( colorPicker && colorText ) {
		colorPicker.addEventListener( 'input', function () {
			colorText.value = colorPicker.value;
			colorText.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		} );
		colorText.addEventListener( 'input', function () {
			if ( /^#[0-9a-f]{6}$/i.test( colorText.value ) ) {
				colorPicker.value = colorText.value;
			}
		} );
	}

	var preview = document.querySelector( '[data-docsbot-preview]' );
	if ( preview ) {
		var updateText = function ( fieldName, targetName, fallback ) {
			var field = document.querySelector( '[name="' + fieldName + '"]' );
			var target = preview.querySelector( '[data-preview="' + targetName + '"]' );
			if ( ! field || ! target ) {
				return;
			}
			var update = function () {
				target.textContent = field.value.trim() || fallback;
			};
			field.addEventListener( 'input', update );
		};

		updateText( 'name', 'name', 'DocsBot' );
		updateText( 'description', 'description', 'Ask me anything about this site.' );
		updateText( 'first_message', 'first-message', 'Hi! How can I help?' );
		updateText( 'input_placeholder', 'placeholder', 'Send a message…' );
		updateText( 'floating_button', 'button-label', 'Chat with us' );
		var footerField = document.querySelector( '[name="footer_message"]' );
		var footerTarget = preview.querySelector( '[data-preview="footer-message"]' );
		if ( footerField && footerTarget ) {
			var updateFooter = function () {
				footerTarget.textContent = footerField.value.trim();
				footerTarget.hidden = ! footerField.value.trim();
			};
			footerField.addEventListener( 'input', updateFooter );
		}

		var bindToggle = function ( name, inverse ) {
			var input = document.querySelector( '[name="' + name + '"]' );
			var selector = inverse ? '[data-preview-toggle-inverse="' + name + '"]' : '[data-preview-toggle="' + name + '"]';
			preview.querySelectorAll( selector ).forEach( function ( target ) {
				if ( ! input ) {
					return;
				}
				input.addEventListener( 'change', function () {
					target.hidden = inverse ? input.checked : ! input.checked;
				} );
			} );
		};

		[
			'use_feedback',
			'use_escalation',
			'use_web_search',
			'use_calendly',
			'use_calcom',
			'use_tidycal',
			'use_custom_buttons',
			'show_agent_activity',
			'use_image_upload',
			'use_audio_upload',
			'show_copy_button',
			'link_safety_enabled'
		].forEach( function ( name ) {
			bindToggle( name, false );
		} );
		bindToggle( 'hide_sources', true );

		var showButtonLabel = document.querySelector( '[name="show_button_label"]' );
		var buttonLabel = preview.querySelector( '[data-preview="button-label"]' );
		if ( showButtonLabel && buttonLabel ) {
			var setButtonLabel = function () {
				buttonLabel.hidden = ! showButtonLabel.checked;
				preview.querySelector( '.docsbot-widget-preview__launcher' ).classList.toggle( 'has-label', showButtonLabel.checked );
			};
			showButtonLabel.addEventListener( 'change', setButtonLabel );
			setButtonLabel();
		}

		if ( colorText ) {
			var updateColor = function () {
				if ( /^#[0-9a-f]{6}$/i.test( colorText.value ) ) {
					preview.style.setProperty( '--docsbot-preview-color', colorText.value );
					var red = parseInt( colorText.value.slice( 1, 3 ), 16 );
					var green = parseInt( colorText.value.slice( 3, 5 ), 16 );
					var blue = parseInt( colorText.value.slice( 5, 7 ), 16 );
					var luminance = ( red * 299 + green * 587 + blue * 114 ) / 1000;
					preview.style.setProperty( '--docsbot-preview-foreground', luminance > 155 ? '#0f172a' : '#ffffff' );
					var avatarRed = Math.round( red + ( 255 - red ) * 0.6 );
					var avatarGreen = Math.round( green + ( 255 - green ) * 0.6 );
					var avatarBlue = Math.round( blue + ( 255 - blue ) * 0.6 );
					var avatarLuminance = ( avatarRed * 299 + avatarGreen * 587 + avatarBlue * 114 ) / 1000;
					preview.style.setProperty( '--docsbot-preview-avatar-bg', 'rgb(' + avatarRed + ', ' + avatarGreen + ', ' + avatarBlue + ')' );
					preview.style.setProperty( '--docsbot-preview-avatar-foreground', avatarLuminance > 155 ? '#0f172a' : '#ffffff' );
				}
			};
			colorText.addEventListener( 'input', updateColor );
			updateColor();
		}

		var alignment = document.querySelector( '[name="alignment"]' );
		if ( alignment ) {
			var setAlignment = function () {
				preview.classList.toggle( 'is-left', alignment.value === 'left' );
			};
			alignment.addEventListener( 'change', setAlignment );
			setAlignment();
		}

		var headerAlignment = document.querySelector( '[name="header_alignment"]' );
		if ( headerAlignment ) {
			var setHeaderAlignment = function () {
				preview.classList.toggle( 'has-left-header', headerAlignment.value === 'left' );
			};
			headerAlignment.addEventListener( 'change', setHeaderAlignment );
			setHeaderAlignment();
		}

		var branding = document.querySelector( '[name="branding"]' );
		if ( branding ) {
			var setBranding = function () {
				preview.querySelector( '.docsbot-widget-preview__footer' ).hidden = ! branding.checked;
			};
			branding.addEventListener( 'change', setBranding );
			setBranding();
		}

		var iconPaths = {
			'default': { viewBox: '0 0 512 512', path: 'M512 240c0 114.9-114.6 208-256 208c-37.1 0-72.3-6.4-104.1-17.9c-11.9 8.7-31.3 20.6-54.3 30.6C73.6 471.1 44.7 480 16 480c-6.5 0-12.3-3.9-14.8-9.9c-2.5-6-1.1-12.8 3.4-17.4c8.4-8.4 38.5-44.4 43.1-91.9C17.7 326.8 0 285.1 0 240C0 125.1 114.6 32 256 32s256 93.1 256 208z' },
			'comment': { viewBox: '0 0 512 512', path: 'M512 240c0 114.9-114.6 208-256 208c-37.1 0-72.3-6.4-104.1-17.9c-11.9 8.7-31.3 20.6-54.3 30.6C73.6 471.1 44.7 480 16 480c-6.5 0-12.3-3.9-14.8-9.9c-2.5-6-1.1-12.8 3.4-17.4c8.4-8.4 38.5-44.4 43.1-91.9C17.7 326.8 0 285.1 0 240C0 125.1 114.6 32 256 32s256 93.1 256 208z' },
			'comments': { viewBox: '0 0 640 512', path: 'M208 352c114.9 0 208-78.8 208-176S322.9 0 208 0S0 78.8 0 176c0 38.6 14.7 74.3 39.6 103.4c-7.9 21.1-24.9 35.3-39 45.7C-4.9 329.2-1.4 352 16 352c31 0 62.5-11.3 87.3-23.9C134.1 343.3 169.8 352 208 352zM448 176c0 112.3-99.1 196.9-216.5 207C255.8 457.4 336.4 512 432 512c38.2 0 73.9-8.7 104.7-23.9c24.8 12.6 56.3 23.9 87.3 23.9c17.4 0 20.9-22.8 15.4-26.9c-14.1-10.4-31.1-24.6-39-45.7c24.9-29 39.6-64.7 39.6-103.4c0-92.8-84.9-168.9-192.6-175.5c.4 5.1 .6 10.3 .6 15.5z' },
			'robot': { viewBox: '0 0 640 512', path: 'M320 0c17.7 0 32 14.3 32 32v64h120c39.8 0 72 32.2 72 72v272c0 39.8-32.2 72-72 72H168c-39.8 0-72-32.2-72-72V168c0-39.8 32.2-72 72-72h120V32c0-17.7 14.3-32 32-32zM208 384c-21.3 0-21.3 32 0 32h32c21.3 0 21.3-32 0-32h-32zm96 0c-21.3 0-21.3 32 0 32h32c21.3 0 21.3-32 0-32h-32zm96 0c-21.3 0-21.3 32 0 32h32c21.3 0 21.3-32 0-32h-32zM264 256a40 40 0 1 0-80 0a40 40 0 1 0 80 0zm152 40a40 40 0 1 0 0-80a40 40 0 1 0 0 80zM48 224h16v192H48c-26.5 0-48-21.5-48-48v-96c0-26.5 21.5-48 48-48zm544 0c26.5 0 48 21.5 48 48v96c0 26.5-21.5 48-48 48h-16V224h16z' },
			'life-ring': { viewBox: '0 0 512 512', path: 'M367.2 412.5C335.9 434.9 297.5 448 256 448s-79.9-13.1-111.2-35.5l58-58c15.8 8.6 34 13.5 53.3 13.5s37.4-4.9 53.3-13.5l58 58zM457.9 413.3c33.8-43.4 54-98 54-157.3s-20.2-113.9-54-157.3C477.8 71 441 34.2 413.3 54C369.9 20.2 315.3 0 256 0S142.1 20.2 98.7 54C71 34.2 34.2 71 54 98.7C20.2 142.1 0 196.7 0 256s20.2 113.9 54 157.3C34.2 441 71 477.8 98.7 458c43.4 33.8 98 54 157.3 54s113.9-20.2 157.3-54c27.7 19.8 64.5-17 44.6-44.7zM412.5 367.2l-58-58c8.6-15.8 13.5-34 13.5-53.3s-4.9-37.4-13.5-53.3l58-58C434.9 176.1 448 214.5 448 256s-13.1 79.9-35.5 111.2zM367.2 99.5l-58 58c-15.8-8.6-34-13.5-53.3-13.5s-37.4 4.9-53.3 13.5l-58-58C176.1 77.1 214.5 64 256 64s79.9 13.1 111.2 35.5zM157.5 309.3l-58 58C77.1 335.9 64 297.5 64 256s13.1-79.9 35.5-111.2l58 58c-8.6 15.8-13.5 34-13.5 53.3s4.9 37.4 13.5 53.3zM208 256a48 48 0 1 1 96 0a48 48 0 1 1-96 0z' },
			'question': { viewBox: '0 0 320 512', path: 'M80 160c0-35.3 28.7-64 64-64h32c35.3 0 64 28.7 64 64v3.6c0 21.8-11.1 42.1-29.4 53.8l-42.2 27.1c-25.2 16.2-40.4 44.1-40.4 74V320c0 17.7 14.3 32 32 32s32-14.3 32-32v-1.4c0-8.2 4.2-15.8 11-20.2l42.2-27.1c36.6-23.6 58.8-64.1 58.8-107.7V160c0-70.7-57.3-128-128-128h-32C73.3 32 16 89.3 16 160c0 42.7 64 42.7 64 0zm80 320a40 40 0 1 0 0-80a40 40 0 1 0 0 80z' },
			'book': { viewBox: '0 0 448 512', path: 'M96 0C43 0 0 43 0 96v320c0 53 43 96 96 96h320c42.7 0 42.7-64 0-64v-64c17.7 0 32-14.3 32-32V32c0-17.7-14.3-32-32-32H96zm0 384h256v64H96c-42.7 0-42.7-64 0-64zm32-240c0-8.8 7.2-16 16-16h192c21.3 0 21.3 32 0 32H144c-8.8 0-16-7.2-16-16zm16 48h192c21.3 0 21.3 32 0 32H144c-21.3 0-21.3-32 0-32z' },
			'info': { viewBox: '0 0 192 512', path: 'M48 80a48 48 0 1 1 96 0a48 48 0 1 1-96 0zM0 224c0-17.7 14.3-32 32-32h64c17.7 0 32 14.3 32 32v224h32c42.7 0 42.7 64 0 64H32c-42.7 0-42.7-64 0-64h32V256H32c-17.7 0-32-14.3-32-32z' }
		};
		var launcherIcons = document.querySelectorAll( '[name="icon"]' );
		if ( launcherIcons.length ) {
			var updateLauncherIcon = function () {
				var launcherIcon = document.querySelector( '[name="icon"]:checked' ) || launcherIcons[ 0 ];
				var customField = document.querySelector( '[name="icon_custom"]' );
				var isCustom = launcherIcon.value === 'custom' && customField && /^https:\/\//i.test( customField.value.trim() );
				var icon = iconPaths[ launcherIcon.value ] || iconPaths.default;
				preview.querySelectorAll( '[data-preview-launcher-svg]' ).forEach( function ( svg ) {
					svg.hidden = isCustom;
					svg.setAttribute( 'viewBox', icon.viewBox );
				} );
				preview.querySelectorAll( '[data-preview-launcher-path]' ).forEach( function ( path ) {
					path.setAttribute( 'd', icon.path );
				} );
				preview.querySelectorAll( '[data-preview-launcher-image]' ).forEach( function ( image ) {
					image.hidden = ! isCustom;
					if ( isCustom ) {
						image.src = customField.value.trim();
					}
				} );
			};
			launcherIcons.forEach( function ( launcherIcon ) {
				launcherIcon.addEventListener( 'change', updateLauncherIcon );
			} );
			var launcherCustom = document.querySelector( '[name="icon_custom"]' );
			if ( launcherCustom ) {
				launcherCustom.addEventListener( 'input', updateLauncherIcon );
			}
			updateLauncherIcon();
		}

		var botIconFields = document.querySelectorAll( '[name="bot_icon"]' );
		if ( botIconFields.length ) {
			var updateBotIcon = function () {
				var botIconField = document.querySelector( '[name="bot_icon"]:checked' ) || botIconFields[ 0 ];
				var customField = document.querySelector( '[name="bot_icon_custom"]' );
				var isCustom = botIconField.value === 'custom' && customField && /^https:\/\//i.test( customField.value.trim() );
				var hasBotIcon = !! botIconField.value;
				var icon = iconPaths[ botIconField.value ] || iconPaths.default;
				preview.querySelectorAll( '.docsbot-preview-avatar' ).forEach( function ( avatar ) {
					avatar.hidden = ! hasBotIcon;
				} );
				preview.querySelectorAll( '[data-preview-icon-svg]' ).forEach( function ( svg ) {
					svg.hidden = isCustom;
					svg.setAttribute( 'viewBox', icon.viewBox );
				} );
				preview.querySelectorAll( '[data-preview-icon-path]' ).forEach( function ( path ) {
					path.setAttribute( 'd', icon.path );
				} );
				preview.querySelectorAll( '[data-preview-bot-image]' ).forEach( function ( image ) {
					image.hidden = ! isCustom;
					if ( isCustom ) {
						image.src = customField.value.trim();
					}
				} );
			};
			botIconFields.forEach( function ( botIconField ) {
				botIconField.addEventListener( 'change', updateBotIcon );
			} );
			var botIconCustom = document.querySelector( '[name="bot_icon_custom"]' );
			if ( botIconCustom ) {
				botIconCustom.addEventListener( 'input', updateBotIcon );
			}
			updateBotIcon();
		}

		var logoField = document.querySelector( '[name="logo"]' );
		var logoImage = preview.querySelector( '[data-preview="logo"]' );
		var headerCopy = preview.querySelector( '[data-preview="header-copy"]' );
		if ( logoField && logoImage && headerCopy ) {
			var updateLogo = function () {
				var logoUrl = logoField.value.trim();
				var hasLogo = /^https:\/\//i.test( logoUrl );
				logoImage.hidden = ! hasLogo;
				headerCopy.hidden = hasLogo;
				if ( hasLogo ) {
					logoImage.src = logoUrl;
				} else {
					logoImage.removeAttribute( 'src' );
				}
			};
			logoField.addEventListener( 'input', updateLogo );
			updateLogo();
		}

		document.querySelectorAll( '.docsbot-choose-image' ).forEach( function ( chooseImage ) {
			chooseImage.addEventListener( 'click', function () {
				if ( ! window.wp || ! window.wp.media ) {
					return;
				}
				var target = document.getElementById( chooseImage.dataset.mediaTarget );
				if ( ! target ) {
					return;
				}
				var frame = window.wp.media( {
					title: chooseImage.dataset.mediaTitle,
					button: { text: chooseImage.dataset.mediaButton },
					multiple: false,
					library: { type: 'image' }
				} );
				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					target.value = attachment.url || '';
					if ( chooseImage.dataset.mediaChoice ) {
						var customChoice = document.querySelector( '[name="' + chooseImage.dataset.mediaChoice + '"][value="custom"]' );
						if ( customChoice ) {
							customChoice.checked = true;
							customChoice.dispatchEvent( new Event( 'change', { bubbles: true } ) );
						}
					}
					var customPreview = document.querySelector( '[data-custom-preview-for="' + target.id + '"]' );
					if ( customPreview && target.value ) {
						customPreview.innerHTML = '';
						var image = document.createElement( 'img' );
						image.src = target.value;
						image.alt = '';
						customPreview.appendChild( image );
					}
					target.dispatchEvent( new Event( 'input', { bubbles: true } ) );
				} );
				frame.open();
			} );
		} );

		preview.querySelectorAll( '[data-preview-mode]' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				preview.querySelectorAll( '[data-preview-mode]' ).forEach( function ( item ) {
					item.classList.toggle( 'is-active', item === button );
					item.setAttribute( 'aria-pressed', item === button ? 'true' : 'false' );
				} );
				preview.classList.toggle( 'is-embed', button.dataset.previewMode === 'embed' );
			} );
		} );
	}
}() );
