/* Slotwise booking form. No dependencies. */
( function () {
	'use strict';
	var cfg = window.SLOTWISE;
	document.querySelectorAll( 'form.slotwise' ).forEach( function ( form ) {
		var slots = form.querySelector( '.sw-slots' );
		var msg = form.querySelector( '.sw-msg' );
		var chosen = '';

		function service() {
			var r = form.querySelector( 'input[name=service]:checked' );
			return r ? r.value : '';
		}

		function load() {
			chosen = '';
			var date = form.date.value;
			if ( ! service() || ! date ) {
				slots.textContent = '';
				return;
			}
			slots.textContent = cfg.i18n.loading;
			fetch( cfg.root + 'slots?service=' + encodeURIComponent( service() ) + '&date=' + encodeURIComponent( date ) )
				.then( function ( r ) { return r.json().then( function ( j ) { return { ok: r.ok, j: j }; } ); } )
				.then( function ( res ) {
					slots.textContent = '';
					if ( ! res.ok ) {
						slots.textContent = res.j.message || cfg.i18n.error;
						return;
					}
					if ( ! res.j.slots.length ) {
						slots.textContent = cfg.i18n.none;
						return;
					}
					res.j.slots.forEach( function ( t ) {
						var b = document.createElement( 'button' );
						b.type = 'button';
						b.className = 'sw-slot';
						b.setAttribute( 'role', 'radio' );
						b.setAttribute( 'aria-checked', 'false' );
						b.textContent = t;
						b.addEventListener( 'click', function () {
							chosen = t;
							slots.querySelectorAll( '.sw-slot' ).forEach( function ( x ) { x.setAttribute( 'aria-checked', String( x === b ) ); } );
						} );
						slots.appendChild( b );
					} );
				} )
				.catch( function () { slots.textContent = cfg.i18n.error; } );
		}

		form.addEventListener( 'change', function ( e ) {
			if ( e.target.name === 'service' || e.target.name === 'date' ) {
				load();
			}
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			if ( ! form.checkValidity() || ! chosen ) {
				msg.textContent = chosen ? '' : 'Please choose a time.';
				form.reportValidity();
				return;
			}
			var btn = form.querySelector( '.sw-submit' );
			btn.disabled = true;
			var data = {
				service: parseInt( service(), 10 ),
				date: form.date.value,
				time: chosen,
				name: form.name.value,
				email: form.email.value,
				phone: form.phone.value,
				notes: form.notes.value,
				website: form.website.value
			};
			fetch( cfg.root + 'bookings', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify( data )
			} )
				.then( function ( r ) { return r.json().then( function ( j ) { return { ok: r.ok, j: j }; } ); } )
				.then( function ( res ) {
					btn.disabled = false;
					msg.textContent = res.j.message || ( res.ok ? '' : cfg.i18n.error );
					if ( res.ok ) {
						form.reset();
						slots.textContent = '';
					} else if ( res.j.code === 'sw_taken' ) {
						load();
					}
				} )
				.catch( function () { btn.disabled = false; msg.textContent = cfg.i18n.error; } );
		} );
	} );
}() );
