/**
 * Module: MageDeveloper/Dataviewer/RecordList/RecordLinkHandler
 * Record link interaction
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser'], function($, LinkBrowser) {
	'use strict';

	/**
	 * @type {{currentLink: string}}
	 * @exports MageDeveloper/Dataviewer/RecordList/RecordLinkHandler
	 */
	var RecordLinkHandler = {
		currentLink: ''
	};

	var currentRecordId;

	/**
     * @param {Event} event
     */
    RecordLinkHandler.linkPage = function(event) {
        event.preventDefault();

        var id = $(this).data('id');
        var anchor = $(this).data('anchor');

        LinkBrowser.finalizeFunction('record:' + currentRecordId + '|' + id + (anchor ? anchor : ''));
    };

    /**
     * @param {Event} event
     */
    RecordLinkHandler.linkRecord = function(event) {
        event.preventDefault();

        var id = $(this).data('id');
        
        $('div#selected_record').html( $(this).html() );

		$("input#selectedRecord").val(id);

		$('ul.recordList li').removeClass("selected");
		$('ul.recordList li[data-recordid='+id+']').addClass("selected");
		$('div#pageTree').show();
		currentRecordId = id;
    };

	/**
	 * @param {Event} event
	 */
	RecordLinkHandler.linkPageByTextfield = function(event) {
		event.preventDefault();

		var value = $('#luid').val();
		if (!value) {
			return;
		}

		LinkBrowser.finalizeFunction('page:' + value);
	};

	/**
	 * @param {Event} event
	 */
	RecordLinkHandler.linkCurrent = function(event) {
		event.preventDefault();
		LinkBrowser.finalizeFunction('page:' + RecordLinkHandler.currentLink);
	};

	$(function() {
		RecordLinkHandler.currentLink = $('body').data('currentLink');
		var selectedRecordId = $('input#selectedRecord').val();

		if (selectedRecordId > 0) {
			currentRecordId = selectedRecordId
			$('div#pageTree').show();
		}
		
		
		$('form#dataviewer_search input#search').on("keyup", function (e) {

			$('div#pageTree').hide();
            $('div.loader').show();

			$.ajax({
				url: $('form#dataviewer_search').attr("action"),
				method: 'POST',
				data: {
					ajax: 1,
					value: $(this).val()
				}
			}).done(function (msg) {
                $('div.loader').hide();
				$('div#dataviewer_search_result').html(msg);
			});
		});

        $(document).on('click','a.t3js-pageLink', RecordLinkHandler.linkPage);
        $(document).on('click','a.t3js-recordLink', RecordLinkHandler.linkRecord);
        $('input.t3js-linkCurrent').on('click', RecordLinkHandler.linkCurrent);
        $('input.t3js-pageLink').on('click', RecordLinkHandler.linkPageByTextfield);
        
     
        
        
		
	});



	return RecordLinkHandler;
});
