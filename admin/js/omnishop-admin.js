(function( $ ) {
	'use strict';

	$('.omnishop_banner-upload').click(function( event ){
	//  $( 'document' ).on( 'click', '.omnishop_banner-upload', function( event ){
		event.preventDefault(); // prevent default link click and page refresh
		const button = $(this)
		const imageId = button.next().next().val();
		
		const customUploader = wp.media({
			title: 'Sellect banner image', // modal window title
			library : {
				// uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
				type : 'image'
			},
			button: {
				text: 'Choose banner' // button label text
			},
			multiple: false
		}).on( 'select', function() { // it also has "open" and "close" events
			const attachment = customUploader.state().get( 'selection' ).first().toJSON();
			button.removeClass( 'button' ).html( '<img src="' + attachment.url + '" width="400">'); // add image instead of "Upload Image"
			button.next().show(); // show "Remove image" link
			button.next().next().val( attachment.id ); // Populate the hidden field with image ID
		})
		
		// already selected images
		customUploader.on( 'open', function() {

			if( imageId ) {
			  const selection = customUploader.state().get( 'selection' )
			  attachment = wp.media.attachment( imageId );
			  attachment.fetch();
			  selection.add( attachment ? [attachment] : [] );
			}
			
		})

		customUploader.open()
	
	});

	// on remove button click
	$( 'body' ).on( 'click', '.omnishop_banner-remove', function( event ){
		event.preventDefault();
		const button = $(this);
		button.next().val( '' ); // emptying the hidden field
		button.hide().prev().addClass( 'button' ).html( 'Upload image' ); // replace the image with text
	});

	let numberOfTags = 0;
	let newNumberOfTags = 0;

	// when there are some terms are already created
	if( ! $( '#the-list' ).children( 'tr' ).first().hasClass( 'no-items' ) ) {
		numberOfTags = $( '#the-list' ).children( 'tr' ).length;
	}

	// after a term has been added via AJAX	
	$(document).ajaxComplete( function( event, xhr, settings ){

		newNumberOfTags = $( '#the-list' ).children('tr').length;
		if( parseInt( newNumberOfTags ) > parseInt( numberOfTags ) ) {
			// refresh the actual number of tags variable
			numberOfTags = newNumberOfTags;
	
			// empty custom fields right here
			$( '.omnishop_banner-remove' ).each( function(){
				// empty hidden field
				$(this).next().val('');
				// hide remove image button
				$(this).hide().prev().addClass( 'button' ).text( 'Upload image' );
			});
		}
	});

	// settings user interface
	// add home sections
	$(function() {
		$(".drag-clone").draggable({
			connectToSortable: "#sortable",
			helper: "clone",
			revert: "invalid"
		});
	});

	$("#sortable").sortable({
		revert: "invalid",
		update: function() {
		const verified_sections_object = document.querySelectorAll('#sortable > li');
		const verified_sections_values = Object.values(verified_sections_object);

		let homepage_sections_hidden_input_selector = document.getElementById("hidden-input");
		let homepage_sections_array = [];
		for (let i = 0; i < verified_sections_values.length; i++) {
			verified_sections_values[i].classList.add("verified-values");
			homepage_sections_array.push(verified_sections_values[i].getAttribute("value"));
			homepage_sections_hidden_input_selector.setAttribute("value", homepage_sections_array);
		}
	}
	})

	$('.remove-section-field').droppable({
        accept: ".verified-values"
    }).on('drop', function(event, ui) {
		$(ui.draggable).remove();
    })

})( jQuery );
