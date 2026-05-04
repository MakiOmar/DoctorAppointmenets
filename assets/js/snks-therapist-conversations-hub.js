/* global jQuery, snksDirectConvHub */
(function ($) {
	'use strict';

	function post(action, extra) {
		return $.ajax({
			url: snksDirectConvHub.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: $.extend(
				{
					action: action,
					nonce: snksDirectConvHub.nonce,
				},
				extra || {}
			),
		});
	}

	function scrollBottom($el) {
		var el = $el[0];
		if (el) {
			el.scrollTop = el.scrollHeight;
		}
	}

	function initHub($root) {
		var i18n = snksDirectConvHub.i18n;
		var state = {
			conversationId: 0,
			patientId: 0,
		};

		var $bar = $('<div class="snks-dc-hub-bar"></div>');
		var $bell = $(
			'<button type="button" class="snks-dc-hub-bell snks-dc-read" aria-label="Messages" title="' +
				(i18n.title || 'Messages') +
				'">&#128276;</button>'
		);
		var $dd = $('<div class="snks-dc-hub-dropdown"></div>');
		var $panel = $('<div class="snks-dc-hub-panel" style="display:none;"></div>');
		var $msgs = $('<div class="snks-dc-hub-messages"></div>');
		var $compose = $(
			'<div class="snks-dc-hub-compose"><textarea placeholder="' +
				(i18n.placeholder || '') +
				'"></textarea><div class="snks-dc-hub-compose-actions"><input type="file" class="snks-dc-file" /><button type="button" class="button snks-dc-send">' +
				(i18n.send || 'Send') +
				'</button></div></div>'
		);

		$panel.append('<h4 class="snks-dc-thread-title" style="margin:0 0 8px;"></h4>');
		$panel.append($msgs);
		$panel.append($compose);

		var $modal = $('<div class="snks-dc-hub-modal"></div>');
		var $modalInner = $('<div class="snks-dc-hub-modal-inner"></div>');
		var $modalHead = $('<div class="snks-dc-hub-modal-head"><span>' + (i18n.viewAll || '') + '</span><button type="button" class="button-link snks-dc-close">&times;</button></div>');
		var $modalList = $('<div class="snks-dc-hub-modal-list"></div>');
		$modalInner.append($modalHead).append($modalList);
		$modal.append($modalInner);

		$bar.append($bell).append($dd);
		$root.append($bar).append($panel).append($modal);

		function setBadge(unread) {
			$bell.removeClass('snks-dc-unread snks-dc-read');
			if (unread > 0) {
				$bell.addClass('snks-dc-unread');
			} else {
				$bell.addClass('snks-dc-read');
			}
		}

		function refreshBadge() {
			post('snks_direct_conv_badge').done(function (res) {
				if (res && res.success && res.data) {
					setBadge(parseInt(res.data.unread_count, 10) || 0);
				}
			});
		}

		function loadDropdown() {
			post('snks_direct_conv_recent', { limit: 10 }).done(function (res) {
				$dd.empty();
				if (!res || !res.success || !res.data || !res.data.messages || !res.data.messages.length) {
					$dd.append(
						'<div class="snks-dc-hub-item">' + (i18n.noUnread || 'No messages') + '</div>'
					);
				} else {
					res.data.messages.forEach(function (m) {
						var $it = $('<div class="snks-dc-hub-item"></div>');
						$it.text((m.sender_name || '') + ' — ' + (m.message || '').substring(0, 80));
						$it.data('id', m.id);
						$it.data('cid', m.conversation_id);
						$dd.append($it);
					});
				}
				var $va = $('<button type="button" class="snks-dc-hub-viewall"></button>').text(i18n.viewAll || 'View all');
				$dd.append($va);
			});
		}

		function loadThread(cid, title) {
			state.conversationId = cid;
			$panel.find('.snks-dc-thread-title').text(title || 'Conversation');
			$panel.show();
			post('snks_direct_conv_thread', { conversation_id: cid }).done(function (res) {
				$msgs.empty();
				if (res && res.success && res.data && res.data.messages) {
					res.data.messages.forEach(function (m) {
						var mine = parseInt(m.sender_user_id, 10) === parseInt(window.snksDcCurrentUserId || 0, 10);
						var $m = $('<div class="snks-dc-hub-msg"></div>');
						$m.addClass(mine ? 'snks-out' : 'snks-in');
						$m.text(m.message || m.body || '');
						$msgs.append($m);
					});
					scrollBottom($msgs);
				}
			});
		}

		$bell.on('click', function () {
			$dd.toggleClass('snks-open');
			if ($dd.hasClass('snks-open')) {
				loadDropdown();
			}
		});

		$dd.on('click', '.snks-dc-hub-item', function () {
			var cid = $(this).data('cid');
			var mid = $(this).data('id');
			if (mid) {
				post('snks_direct_conv_mark_read', { message_id: mid }).done(refreshBadge);
			}
			if (cid) {
				$dd.removeClass('snks-open');
				loadThread(cid, $(this).text());
			}
		});

		$dd.on('click', '.snks-dc-hub-viewall', function (e) {
			e.stopPropagation();
			$dd.removeClass('snks-open');
			post('snks_direct_conv_list').done(function (res) {
				$modalList.empty();
				if (res && res.success && res.data && res.data.conversations) {
					res.data.conversations.forEach(function (c) {
						var $row = $('<div class="snks-dc-hub-item"></div>');
						$row.text((c.patient_name || 'Patient') + ' — ' + (c.last_body || '').substring(0, 60));
						$row.data('cid', c.id);
						$modalList.append($row);
					});
				}
				$modal.addClass('snks-open');
			});
		});

		$modal.on('click', function (ev) {
			if (ev.target === $modal[0]) {
				$modal.removeClass('snks-open');
			}
		});
		$modalHead.find('.snks-dc-close').on('click', function () {
			$modal.removeClass('snks-open');
		});

		$modalList.on('click', '.snks-dc-hub-item', function () {
			var cid = $(this).data('cid');
			if (cid) {
				$modal.removeClass('snks-open');
				loadThread(cid, $(this).text());
			}
		});

		$compose.find('.snks-dc-send').on('click', function () {
			var body = $compose.find('textarea').val();
			var fd = new FormData();
			var file = $compose.find('.snks-dc-file')[0].files[0];
			function sendWithAtt(ids) {
				post('snks_direct_conv_send', {
					conversation_id: state.conversationId,
					body: body,
					attachment_ids: JSON.stringify(ids || []),
				}).done(function () {
					$compose.find('textarea').val('');
					$compose.find('.snks-dc-file').val('');
					if (state.conversationId) {
						loadThread(state.conversationId, $panel.find('.snks-dc-thread-title').text());
					}
					refreshBadge();
				});
			}
			if (file) {
				var up = new FormData();
				up.append('action', 'snks_direct_conv_upload');
				up.append('nonce', snksDirectConvHub.nonce);
				up.append('file', file);
				$.ajax({
					url: snksDirectConvHub.ajaxUrl,
					type: 'POST',
					data: up,
					processData: false,
					contentType: false,
					dataType: 'json',
				}).done(function (r) {
					if (r && r.success && r.data && r.data.id) {
						sendWithAtt([r.data.id]);
					} else {
						sendWithAtt([]);
					}
				});
			} else {
				sendWithAtt([]);
			}
		});

		$(document).on('click', function (e) {
			if (!$root.is(e.target) && $root.has(e.target).length === 0) {
				$dd.removeClass('snks-open');
			}
		});

		window.snksDcCurrentUserId = snksDirectConvHub.currentUserId || 0;

		refreshBadge();
		setInterval(refreshBadge, 60000);

		var params = new URLSearchParams(window.location.search);
		var dc = params.get('snks_dc');
		if (dc) {
			loadThread(parseInt(dc, 10), '');
		}
	}

	$(function () {
		$('#snks-therapist-conversations-hub').each(function () {
			initHub($(this));
		});
	});
})(jQuery);
