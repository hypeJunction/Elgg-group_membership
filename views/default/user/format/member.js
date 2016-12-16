define(function (require) {
	var elgg = require('elgg');
	var $ = require('jquery');
	var spinner = require('elgg/spinner');

	$(document).on('click', '.elgg-menu-item-groups-makeadmin > a, .elgg-menu-item-groups-removeadmin > a', function (e) {

		if ($(this).is('[data-confirm],.elgg-requires-confirmation')) {
			var confirmText = $(this).data('confirm') || elgg.echo('question:areyousure');
			if (!confirm(confirmText)) {
				return false;
			}
		}

		var $elem = $(this);

		e.preventDefault();
		elgg.action($elem.attr('href'), {
			beforeSend: spinner.start,
			complete: spinner.stop,
			success: function (response) {
				if (response.status >= 0) {
					$elem.parent().fadeOut();
				}
			}
		});
	});

	$(document).on('click', '.elgg-menu-item-groups-removeuser > a, .elgg-menu-item-groups-request-accept > a, .elgg-menu-item-groups-request-decline > a, elgg-menu-item-groups-invitation-revoke > a', function (e) {
		if ($(this).is('[data-confirm],.elgg-requires-confirmation')) {
			var confirmText = $(this).data('confirm') || elgg.echo('question:areyousure');
			if (!confirm(confirmText)) {
				return false;
			}
		}

		var $elem = $(this);
		e.preventDefault();
		elgg.action($elem.attr('href'), {
			beforeSend: spinner.start,
			complete: spinner.stop,
			success: function (response) {
				if (response.status < 0) {
					return;
				}
				if ($elem.closest('.elgg-list-members,.elgg-list-invited,.elgg-list-membership-requests').length) {
					$elem.closest('.elgg-item').fadeOut().remove();
					$elem.closest('.elgg-list-members,.elgg-list-invited,.elgg-list-membership-requests').trigger('refresh');
				} else {
					$elem.parent().fadeOut().remove();
				}
			}
		});
	});

});
