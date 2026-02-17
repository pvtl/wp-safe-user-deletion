/**
 * WP Safe User Deletion: double-check when admin chooses "Delete all content" on user delete confirmation.
 */
(function () {
	'use strict';

	var form = document.getElementById('updateusers');
	if (!form) {
		return;
	}

	var deleteAllRadio = document.getElementById('delete_option0');
	if (!deleteAllRadio) {
		return;
	}

	form.addEventListener('submit', function (e) {
		if (!deleteAllRadio.checked) {
			return;
		}
		var msg = typeof udgDeleteConfirm !== 'undefined' && udgDeleteConfirm.confirmMessage
			? udgDeleteConfirm.confirmMessage
			: 'This user has content. Assign it to another user to avoid data loss. Cancel to go back and choose a user.';
		if (!confirm(msg)) {
			e.preventDefault();
		}
	});
})();
