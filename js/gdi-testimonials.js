/**
 * Testimonials front-end display — fetches from the gdi_testimonial REST
 * endpoint (/wp-json/wp/v2/testimonials) and renders a simple list,
 * optionally filtered by category.
 *
 * Ported from fishbucklake.com's js/testimonials.js (same REST-driven
 * pattern as gdi-faq.js), with one deliberate behavior split by
 * data-mode, since this system launches with zero seeded testimonials:
 *   - data-mode="page" (the standalone /testimonials/ page): shows a
 *     graceful "coming soon" message when there's nothing to display yet,
 *     rather than an empty list that looks broken.
 *   - data-mode="embed" (the /work/ excerpt): the whole container is
 *     hidden outright when empty — no empty box, no placeholder text —
 *     since an excerpt with nothing in it has no reason to take up space
 *     on a page that isn't primarily about testimonials.
 */
(function () {
	'use strict';

	var REST_URL = '/wp-json/wp/v2/testimonials?per_page=100&orderby=date&order=desc';
	var fetchPromise = null;
	var decoderEl = null;

	// Category term names come back HTML-entity-encoded from the REST API
	// (see the matching comment in gdi-faq.js) — decoded once here so
	// filter buttons and matching both work with plain text.
	function decodeEntities( html ) {
		if ( ! decoderEl ) {
			decoderEl = document.createElement( 'textarea' );
		}
		decoderEl.innerHTML = html;
		return decoderEl.value;
	}

	function loadAllTestimonials() {
		if ( ! fetchPromise ) {
			fetchPromise = fetch( REST_URL ).then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'Failed to load testimonials: ' + response.status );
				}
				return response.json();
			} ).then( function ( testimonials ) {
				return testimonials.map( function ( testimonial ) {
					var rawCategories = testimonial.category_names && testimonial.category_names.length ? testimonial.category_names : [ 'General' ];
					return {
						content: testimonial.content.rendered,
						author: testimonial.author_info.name || '',
						role: testimonial.author_info.role || '',
						rating: testimonial.author_info.rating || '',
						categories: rawCategories.map( decodeEntities )
					};
				} );
			} );
		}
		return fetchPromise;
	}

	function matchesCategory( testimonial, category ) {
		if ( ! category || 'all' === category ) {
			return true;
		}
		return testimonial.categories.some( function ( name ) {
			return name.toLowerCase() === category.toLowerCase();
		} );
	}

	function renderItems( listEl, testimonials ) {
		listEl.innerHTML = '';

		testimonials.forEach( function ( testimonial ) {
			var item = document.createElement( 'div' );
			item.className = 'gdi-testimonial-item';

			var content = document.createElement( 'div' );
			content.className = 'gdi-testimonial-content';
			content.innerHTML = testimonial.content;
			item.appendChild( content );

			var author = document.createElement( 'div' );
			author.className = 'gdi-testimonial-author';

			if ( testimonial.author ) {
				var name = document.createElement( 'div' );
				name.className = 'gdi-testimonial-author-name';
				name.textContent = testimonial.author;
				author.appendChild( name );
			}

			if ( testimonial.role ) {
				var role = document.createElement( 'div' );
				role.className = 'gdi-testimonial-author-role';
				role.textContent = testimonial.role;
				author.appendChild( role );
			}

			if ( testimonial.rating ) {
				var rating = document.createElement( 'div' );
				rating.className = 'gdi-testimonial-rating';
				rating.textContent = '★'.repeat( parseInt( testimonial.rating, 10 ) );
				author.appendChild( rating );
			}

			item.appendChild( author );
			listEl.appendChild( item );
		} );
	}

	function buildFilters( container, allTestimonials, listEl, emptyEl ) {
		var filtersEl = container.querySelector( '.gdi-testimonial-filters' );
		if ( ! filtersEl ) {
			return;
		}

		var categories = new Set();
		allTestimonials.forEach( function ( testimonial ) {
			testimonial.categories.forEach( function ( name ) {
				categories.add( name );
			} );
		} );

		if ( categories.size < 2 ) {
			return;
		}

		filtersEl.hidden = false;

		Array.from( categories ).sort().forEach( function ( name ) {
			var button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'gdi-testimonial-filter-btn';
			button.setAttribute( 'data-category', name );
			button.textContent = name;

			button.addEventListener( 'click', function () {
				filtersEl.querySelectorAll( '.gdi-testimonial-filter-btn' ).forEach( function ( btn ) {
					btn.classList.remove( 'is-active' );
				} );
				button.classList.add( 'is-active' );
				applyFilter( allTestimonials, name, listEl, emptyEl, null );
			} );

			filtersEl.appendChild( button );
		} );
	}

	function applyFilter( allTestimonials, category, listEl, emptyEl, limit ) {
		var filtered = allTestimonials.filter( function ( testimonial ) {
			return matchesCategory( testimonial, category );
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
		var listEl = container.querySelector( '.gdi-testimonial-list' );
		if ( ! listEl ) {
			return;
		}

		var mode = container.getAttribute( 'data-mode' ) || 'page';
		var limit = parseInt( container.getAttribute( 'data-limit' ), 10 ) || null;
		var emptyEl = container.querySelector( '.gdi-testimonial-empty' );

		loadAllTestimonials().then( function ( allTestimonials ) {
			if ( 0 === allTestimonials.length ) {
				if ( 'embed' === mode ) {
					container.hidden = true;
				} else if ( emptyEl ) {
					var filtersEl = container.querySelector( '.gdi-testimonial-filters' );
					listEl.hidden = true;
					if ( filtersEl ) {
						filtersEl.hidden = true;
					}
					emptyEl.hidden = false;
				}
				return;
			}

			if ( 'page' === mode ) {
				buildFilters( container, allTestimonials, listEl, emptyEl );
			}
			applyFilter( allTestimonials, 'all', listEl, emptyEl, limit );
		} ).catch( function ( error ) {
			if ( 'embed' === mode ) {
				container.hidden = true;
			} else {
				listEl.innerHTML = '<p class="gdi-testimonial-error">Sorry — testimonials couldn’t be loaded right now.</p>';
			}
			console.error( 'Testimonials load error:', error );
		} );
	}

	function init() {
		document.querySelectorAll( '.gdi-testimonials' ).forEach( initInstance );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
