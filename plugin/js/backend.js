jQuery(document).ready(function ($) {
	$('#select_crm').change(function () {
		var selectedCRM = $(this).val();

		// Hier kannst du je nach ausgewähltem CRM den Inhalt dynamisch anpassen
		switch (selectedCRM) {
			case 'pipedrive':
				// Zeige Inhalte für Pipedrive an, verstecke andere
				$('#pipedrive-content').show();
				$('#hubspot-content, #salesforce-content').hide();
				break;
			case 'hubspot':
				// Zeige Inhalte für HubSpot an, verstecke andere
				$('#hubspot-content').show();
				$('#pipedrive-content, #salesforce-content').hide();
				break;
			case 'salesforce':
				// Zeige Inhalte für Salesforce an, verstecke andere
				$('#salesforce-content').show();
				$('#pipedrive-content, #hubspot-content').hide();
				break;
			default:
				// Standardfall oder Fehlerbehandlung
				break;
		}
	});
});
