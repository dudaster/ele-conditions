( function() {
	'use strict';

	function getTarget( self, selector ) {
		if ( ! selector || selector.trim() === '' ) return self;
		return document.querySelector( selector.trim() ) || self;
	}

	function executeAction( self, trigger ) {
		var action  = trigger.action_type;
		var cls     = ( trigger.action_class || '' ).trim();
		var target  = getTarget( self, trigger.action_target );

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
					if ( el !== self ) {
						el.style.display = 'none';
					}
				} );
				break;
		}
	}

	function attachTrigger( el, trigger ) {
		var type  = trigger.trigger_type;
		var delay = parseInt( trigger.trigger_delay_ms, 10 ) || 0;

		if ( type === 'click' ) {
			el.addEventListener( 'click', function( e ) {
				e.stopPropagation();
				executeAction( el, trigger );
			} );
		} else if ( type === 'hover' ) {
			el.addEventListener( 'mouseenter', function() {
				executeAction( el, trigger );
			} );
		} else if ( type === 'delay' ) {
			setTimeout( function() {
				executeAction( el, trigger );
			}, delay );
		}
	}

	function init() {
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
