jQuery(document).ready(function ($) {
	$('#startBtn').click(function () {
		if ($('#checkbox_id').is(':checked')) {
			$('#progress-bar').css('width', '0%').text('0%');
			fetchContacts(0, 500, 0);
			alert("Die Synchronisation der Kontakte wird gestartet. Bitte warten Sie einen Moment.");
		}
	});

	$('#saveBtn').on('click', function () {
		const isChecked = $('#checkbox_id2').is(':checked') ? 1 : 0;

		$.ajax({
			url: edgAjax.ajax_url,
			type: 'POST',
			data: {
				action: 'save_pipedrive_cron_setting',
				nonce: edgAjax.nonce,
				is_checked: isChecked,
			},
			success: function (response) {
				if (response.success) {
					alert('Einstellung gespeichert!');
				} else {
					alert('Fehler beim Speichern der Einstellung.');
				}
			},
			error: function () {
				alert('AJAX-Fehler.');
			},
		});
	});

	function fetchContacts(start, limit, paginationCount, totalContacts, currentContacts) {
		$.ajax({
			url: edgAjax.ajax_url,
			type: 'POST',
			data: {
				action: 'fetch_pipedrive_contacts_ajax',
				nonce: edgAjax.nonce,
				start: start,
				limit: limit,
				paginationCount: paginationCount,
				totalContacts: totalContacts,
				currentContacts: currentContacts
			},
			success: function (response) {
				if (response.success) {
					$('#progress-batch').text('Current batch count: ' + response.data.currentBatchCount);
					$('#progress-current').text('Current contacts so far: ' + response.data.currentContacts);
					$('#progress-total').text('Total contacts so far: ' + response.data.totalContacts);
					$('#progress-pagination').text('Pagination count total: ' + response.data.paginationCount);

					let totalContactsLimit = totalContacts / limit;
					totalContactsLimit = Math.ceil(totalContactsLimit);

					$('#progress-pagination-total').text('Pagination count: ' + totalContactsLimit);


					let progressPercentage = (response.data.paginationCount / totalContactsLimit) * 100;
					//let progressPercentage = (response.data.paginationCount / 1) * 100;
					$('#progress-bar').css('width', progressPercentage.toFixed(2) + '%').text(progressPercentage + '%');

					//if (response.data.moreItems && response.data.paginationCount < 10) {
					if (response.data.moreItems) {
						fetchContacts(start + limit, limit, response.data.paginationCount, response.data.totalContacts, response.data.currentContacts);
					} else {
						$('#progress').append('<p>Finished fetching contacts.</p>');
						if (response.data.createdUsersCount === 0) {
							$('#progress').append('<p>Es wurden keine neuen Nutzer angelegt.</p>');
						}
						// $('#progress').append('<p>Fertig! Es wurden ' + response.data.createdUsersCount + ' neue Nutzer angelegt.</p>');
						// if (response.data.createdUsers && response.data.createdUsers.length > 0) {
						// 	$('#progress').append('<p>Neue Nutzer:<br>' + response.data.createdUsers.join('<br>') + '</p>');
						// }
					}
				} else {
					$('#progress').append('<p>Error: ' + response.data.error + '</p>');
				}
			},
			error: function (xhr, status, error) {
				$('#progress').append('<p>AJAX error: ' + error + '</p>');
			}
		});
	}
});
