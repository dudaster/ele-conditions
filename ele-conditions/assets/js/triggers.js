( function() {
	'use strict';

	// ── Safe localStorage wrapper (throws in Safari private browsing) ──
	var store = {
		get: function( k ) { try { return localStorage.getItem( k ); } catch ( e ) { return null; } },
		set: function( k, v ) { try { localStorage.setItem( k, v ); } catch ( e ) {} }
	};

	// ── Visit tracking (per URL path) ────────────────────────
	var visitKey   = 'elecond_v_' + window.location.pathname;
	var visitCount = parseInt( store.get( visitKey ) || '0', 10 ) + 1;
	store.set( visitKey, visitCount );

	// ── A/B group helper ─────────────────────────────────────
	function getAbGroup( name ) {
		var key   = 'elecond_ab_' + ( name || 'default' );
		var group = store.get( key );
		if ( ! group ) {
			group = Math.random() < 0.5 ? 'a' : 'b';
			store.set( key, group );
		}
		return group;
	}

	// ── Exit-intent (document-level, fires once) ─────────────
	var exitCallbacks        = [];
	var exitListenerAttached = false;

	function onExitIntent( cb ) {
		exitCallbacks.push( cb );
		if ( exitListenerAttached ) return;
		exitListenerAttached = true;
		document.addEventListener( 'mouseleave', function( e ) {
			if ( e.clientY > 0 ) return;
			var cbs = exitCallbacks.slice();
			exitCallbacks = [];
			cbs.forEach( function( fn ) { fn(); } );
		} );
	}

	// ── Target resolution ────────────────────────────────────
	function getTarget( self, selector ) {
		if ( ! selector || selector.trim() === '' ) return self;
		return document.querySelector( selector.trim() ) || self;
	}

	// ── Action executor ──────────────────────────────────────
	function executeAction( self, trigger ) {
		var action = trigger.action_type;
		var cls    = ( trigger.action_class || '' ).trim();
		var target = getTarget( self, trigger.action_target );

		switch ( action ) {
			case 'show':
				target.style.removeProperty( 'display' );
				target.style.removeProperty( 'visibility' );
				break;

			case 'hide':
				target.style.display = 'none';
				break;

			case 'toggle':
				if ( window.getComputedStyle( target ).display === 'none' ) {
					target.style.removeProperty( 'display' );
				} else {
					target.style.display = 'none';
				}
				break;

			case 'add_class':
				if ( cls ) target.classList.add( cls );
				break;

			case 'remove_class':
				if ( cls ) target.classList.remove( cls );
				break;

			case 'toggle_class':
				if ( cls ) target.classList.toggle( cls );
				break;

			case 'scroll_to':
				target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				break;

			case 'focus':
				target.focus();
				break;

			case 'close_others':
				var groupClass = ( trigger.action_group_class || '' ).trim();
				if ( ! groupClass ) break;
				document.querySelectorAll( '.' + groupClass ).forEach( function( el ) {
					if ( el !== self ) el.style.display = 'none';
				} );
				break;
		}
	}

	// ── Trigger attacher ─────────────────────────────────────
	function attachTrigger( el, trigger ) {
		var type = trigger.trigger_type;

		switch ( type ) {
			case 'click':
				el.addEventListener( 'click', function( e ) {
					e.stopPropagation();
					executeAction( el, trigger );
				} );
				break;

			case 'hover':
				el.addEventListener( 'mouseenter', function() {
					executeAction( el, trigger );
				} );
				break;

			case 'delay':
				setTimeout( function() {
					executeAction( el, trigger );
				}, parseInt( trigger.trigger_delay_ms, 10 ) || 0 );
				break;

			case 'scroll_into_view':
				if ( ! window.IntersectionObserver ) break;
				( new IntersectionObserver( function( entries, obs ) {
					entries.forEach( function( entry ) {
						if ( ! entry.isIntersecting ) return;
						executeAction( el, trigger );
						obs.unobserve( el );
					} );
				} ) ).observe( el );
				break;

			case 'time_on_page':
				setTimeout( function() {
					executeAction( el, trigger );
				}, ( parseInt( trigger.trigger_time_seconds, 10 ) || 10 ) * 1000 );
				break;

			case 'exit_intent':
				onExitIntent( function() {
					executeAction( el, trigger );
				} );
				break;

			case 'first_visit':
				if ( visitCount === 1 ) executeAction( el, trigger );
				break;

			case 'nth_visit':
				if ( visitCount === ( parseInt( trigger.trigger_visit_count, 10 ) || 2 ) ) {
					executeAction( el, trigger );
				}
				break;

			case 'ab_group_a':
				if ( getAbGroup( trigger.trigger_ab_name ) === 'a' ) executeAction( el, trigger );
				break;

			case 'ab_group_b':
				if ( getAbGroup( trigger.trigger_ab_name ) === 'b' ) executeAction( el, trigger );
				break;
		}
	}

	// ── Init ─────────────────────────────────────────────────
	function init() {
		// Apply initial hidden state before attaching any triggers
		document.querySelectorAll( '[data-elecond-hide-initially]' ).forEach( function( el ) {
			el.style.display = 'none';
		} );

		document.querySelectorAll( '[data-elecond-triggers]' ).forEach( function( el ) {
			var raw = el.getAttribute( 'data-elecond-triggers' );
			if ( ! raw ) return;

			var triggers;
			try {
				triggers = JSON.parse( raw );
			} catch ( e ) {
				return;
			}

			if ( ! Array.isArray( triggers ) ) return;

			triggers.forEach( function( trigger ) {
				attachTrigger( el, trigger );
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

} )();
