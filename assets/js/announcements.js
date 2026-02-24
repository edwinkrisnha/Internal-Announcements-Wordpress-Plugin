/**
 * Internal Announcements — mark-as-read behaviour.
 *
 * Uses event delegation on the feed container so no per-button listeners
 * are needed even when the DOM updates dynamically.
 *
 * Globals injected by wp_localize_script():
 *   iaData.ajaxUrl  string  WordPress admin-ajax.php URL.
 *   iaData.nonce    string  Nonce for the ia_mark_read action.
 */

( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var container = document.getElementById( 'ia-announcements' );
		if ( ! container ) {
			return;
		}

		container.addEventListener( 'click', function ( event ) {
			var btn = event.target.closest( '.ia-mark-read-btn' );
			if ( ! btn || btn.disabled ) {
				return;
			}

			var postId = btn.dataset.postId;
			if ( ! postId ) {
				return;
			}

			markAsRead( btn, postId, container );
		} );
	} );

	// -------------------------------------------------------------------------

	function markAsRead( btn, postId, container ) {
		btn.disabled = true;

		var body = new URLSearchParams( {
			action:  'ia_mark_read',
			nonce:   iaData.nonce,
			post_id: postId,
		} );

		fetch( iaData.ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
			body:        body.toString(),
		} )
		.then( function ( response ) {
			return response.json();
		} )
		.then( function ( data ) {
			if ( ! data.success ) {
				// Server rejected the request — re-enable so the user can retry.
				btn.disabled = false;
				return;
			}

			updateCardToRead( btn );
			updateUnreadSummary( container );
		} )
		.catch( function () {
			// Network error — re-enable.
			btn.disabled = false;
		} );
	}

	// -------------------------------------------------------------------------

	/**
	 * Visually transition a card from unread to read state.
	 */
	function updateCardToRead( btn ) {
		var card = btn.closest( '.ia-announcement' );
		if ( card ) {
			card.classList.remove( 'ia-unread' );
			card.classList.add( 'ia-read' );

			var dot = card.querySelector( '.ia-unread-dot' );
			if ( dot ) {
				dot.remove();
			}
		}

		var readLabel = document.createElement( 'span' );
		readLabel.className   = 'ia-read-label';
		readLabel.textContent = iaData.readLabel || 'Read';

		btn.replaceWith( readLabel );
	}

	/**
	 * Recount unread cards and update (or remove) the summary banner.
	 */
	function updateUnreadSummary( container ) {
		var summary     = container.querySelector( '.ia-unread-summary' );
		var unreadCount = container.querySelectorAll( '.ia-announcement.ia-unread' ).length;

		if ( unreadCount === 0 ) {
			if ( summary ) {
				summary.remove();
			}
			return;
		}

		if ( summary ) {
			summary.textContent = unreadCount === 1
				? 'You have 1 unread announcement.'
				: 'You have ' + unreadCount + ' unread announcements.';
		}
	}

} )();
