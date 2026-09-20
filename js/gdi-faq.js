/**
 * FAQ front-end display — fetches from the gdi_faq REST endpoint
 * (/wp-json/wp/v2/faqs) and renders an accordion, optionally filtered.
 *
 * Ported from fishbucklake.com's js/faq-system.js (same REST-driven
 * accordion pattern), generalized to run as either:
 *   - a full listing with a live category dropdown (data-mode="page",
 *     used on /faq/), or
 *   - a fixed-category excerpt with no dropdown and a "see more" link
 *     already in the container's own markup (data-mode="embed", used on
 *     /services/, /event-photography/, /contact/).
 *
 * Multiple independent instances per page are supported (each
 * `.gdi-faq` container gets its own fetch + render), unlike the FBL
 * original's single hardcoded #faqContainer/#categorySelect — not
 * currently needed on any one godindev.com page, but a page-mode and
 * embed-mode instance are never assumed to be mutually exclusive.
 */
(function () {
	'use strict';

	var REST_URL = '/wp-json/wp/v2/faqs?per_page=100';
	var fetchPromise = null;
	var decoderEl = null;

	// WP's REST API returns title.rendered and taxonomy term names as
	// HTML-entity-encoded text (e.g. "Recipes &amp; Tools Licensing",
	// "What&#8217;s included…") — decoded once here so every
	// downstream use (dropdown options, category matching, question
	// display) works with plain text instead of literal entity codes.
	function decodeEntities( html ) {
		if ( ! decoderEl ) {
			decoderEl = document.createElement( 'textarea' );
		}
		decoderEl.innerHTML = html;
		return decoderEl.value;
	}

	function loadAllFaqs() {
		if ( ! fetchPromise ) {
			fetchPromise = fetch( REST_URL ).then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'Failed to load FAQs: ' + response.status );
				}
				return response.json();
			} ).then( function ( faqs ) {
				return faqs.map( function ( faq ) {
					var rawCategories = faq.category_names && faq.category_names.length ? faq.category_names : [ 'Uncategorized' ];
					return {
						question: decodeEntities( faq.title.rendered ),
						answer: faq.answer || faq.content.rendered,
						categories: rawCategories.map( decodeEntities )
					};
				} );
			} );
		}
		return fetchPromise;
	}

	function matchesCategory( faq, category ) {
		if ( ! category || 'all' === category ) {
			return true;
		}
		return faq.categories.some( function ( name ) {
			return name.toLowerCase() === category.toLowerCase();
		} );
	}

	function renderItems( listEl, faqs ) {
		listEl.innerHTML = '';

		faqs.forEach( function ( faq ) {
			var item = document.createElement( 'div' );
			item.className = 'gdi-faq-item';

			var question = document.createElement( 'button' );
			question.type = 'button';
			question.className = 'gdi-faq-question';
			question.setAttribute( 'aria-expanded', 'false' );
			question.textContent = faq.question;

			var answer = document.createElement( 'div' );
			answer.className = 'gdi-faq-answer';
			var answerInner = document.createElement( 'div' );
			answerInner.className = 'gdi-faq-answer-content';
			answerInner.innerHTML = faq.answer;
			answer.appendChild( answerInner );

			question.addEventListener( 'click', function () {
				var isOpen = item.classList.contains( 'is-open' );
				listEl.querySelectorAll( '.gdi-faq-item.is-open' ).forEach( function ( openItem ) {
					openItem.classList.remove( 'is-open' );
					openItem.querySelector( '.gdi-faq-question' ).setAttribute( 'aria-expanded', 'false' );
				} );
				if ( ! isOpen ) {
					item.classList.add( 'is-open' );
					question.setAttribute( 'aria-expanded', 'true' );
				}
			} );

			item.appendChild( question );
			item.appendChild( answer );
			listEl.appendChild( item );
		} );
	}

	function buildDropdown( container, allFaqs, listEl, emptyEl ) {
		var select = container.querySelector( '.gdi-faq-category-select' );
		if ( ! select ) {
			return;
		}

		var categories = new Set();
		allFaqs.forEach( function ( faq ) {
			faq.categories.forEach( function ( name ) {
				categories.add( name );
			} );
		} );

		Array.from( categories ).sort().forEach( function ( name ) {
			var option = document.createElement( 'option' );
			option.value = name;
			option.textContent = name;
			select.appendChild( option );
		} );

		select.addEventListener( 'change', function () {
			applyFilter( allFaqs, select.value, listEl, emptyEl, null );
		} );
	}

	function applyFilter( allFaqs, category, listEl, emptyEl, limit ) {
		var filtered = allFaqs.filter( function ( faq ) {
			return matchesCategory( faq, category );
		} );

		if ( limit ) {
			filtered = filtered.slice( 0, limit );
		}

		renderItems( listEl, filtered );

		if ( emptyEl ) {
			emptyEl.hidden = filtered.length > 0;
		}
	}

	function initInstance( container ) {
		var listEl = container.querySelector( '.gdi-faq-list' );
		var emptyEl = container.querySelector( '.gdi-faq-empty' );
		if ( ! listEl ) {
			return;
		}

		var mode = container.getAttribute( 'data-mode' ) || 'page';
		var category = container.getAttribute( 'data-category' ) || 'all';
		var limit = parseInt( container.getAttribute( 'data-limit' ), 10 ) || null;

		loadAllFaqs().then( function ( allFaqs ) {
			if ( 'page' === mode ) {
				buildDropdown( container, allFaqs, listEl, emptyEl );
			}
			applyFilter( allFaqs, category, listEl, emptyEl, limit );
		} ).catch( function ( error ) {
			listEl.innerHTML = '<p class="gdi-faq-error">Sorry — the FAQs couldn’t be loaded right now.</p>';
			console.error( 'FAQ load error:', error );
		} );
	}

	function init() {
		document.querySelectorAll( '.gdi-faq' ).forEach( initInstance );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
