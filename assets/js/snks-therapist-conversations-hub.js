/* global jQuery, snksDirectConvHub */
(function ($) {
	'use strict';

	var SNKS_DC_DEBUG = false;
	var THREAD_MS_VISIBLE = 12000;
	var THREAD_MS_HIDDEN = 45000;
	var BADGE_MS_VISIBLE = 60000;
	var BADGE_MS_HIDDEN = 120000;
	var THREAD_FIRST_POLL_MS = 2500;

	function post(action, extra) {
		if (SNKS_DC_DEBUG) {
			console.log('[SNKS-DC] AJAX request', action, extra || {});
		}
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

	function isNearBottom($el, threshold) {
		var el = $el[0];
		if (!el) {
			return true;
		}
		threshold = threshold || 120;
		return el.scrollHeight - el.scrollTop - el.clientHeight < threshold;
	}

	function initHub($root) {
		if (!$root || !$root.length || $root.data('snksDcHubInited')) {
			if (SNKS_DC_DEBUG && $root && $root.length) {
				console.log('[SNKS-DC] Skip init (already initialized)');
			}
			return;
		}
		$root.data('snksDcHubInited', 1);
		if (SNKS_DC_DEBUG) {
			console.log('[SNKS-DC] Init hub', $root.get(0));
		}
		var i18n = snksDirectConvHub.i18n || {};
		var state = {
			conversationId: 0,
			patientId: 0,
			lastMessageId: 0,
			threadPollTimer: null,
			activeTab: 'booked',
		};

		var $bar = $('<div class="snks-dc-hub-bar"></div>');
		var $bell = $(
			'<button type="button" class="snks-dc-hub-bell snks-dc-read" aria-label="' +
				(i18n.title || 'الرسائل') +
				'" title="' +
				(i18n.title || 'الرسائل') +
				'">&#128276;</button>'
		);
		var $dd = $('<div class="snks-dc-hub-dropdown"></div>');
		var $tabs = $('<div class="snks-dc-tabs"></div>');
		var $tabBooked = $('<button type="button" class="snks-dc-tab is-active" data-tab="booked"></button>').text(i18n.bookedPatientsTab || 'قائمة المرضى');
		var $tabUnread = $('<button type="button" class="snks-dc-tab" data-tab="unread"></button>').text(i18n.unreadTab || 'غير المقروءة');
		var $tabContent = $('<div class="snks-dc-tab-content"></div>');
		var $tabBookedList = $('<div class="snks-dc-tab-panel is-active" data-panel="booked"></div>');
		var $tabUnreadList = $('<div class="snks-dc-tab-panel" data-panel="unread"></div>');
		var $viewAllBtn = $('<button type="button" class="snks-dc-hub-viewall"></button>').text(i18n.viewAll || 'عرض كل المحادثات');
		$tabs.append($tabBooked, $tabUnread);
		$tabContent.append($tabBookedList, $tabUnreadList);
		$dd.append($tabs, $tabContent, $viewAllBtn);
		var $panel = $('<div class="snks-dc-hub-panel" style="display:none;"></div>');
		var $msgs = $('<div class="snks-dc-hub-messages"></div>');
		var $compose = $(
			'<div class="snks-dc-hub-compose">' +
				'<label class="snks-dc-compose-label">' + (i18n.messageLabel || 'الرسالة:') + '</label>' +
				'<textarea class="snks-dc-inline-message" placeholder="' + (i18n.placeholder || 'اكتب رسالتك هنا...') + '"></textarea>' +
				'<label class="snks-dc-compose-label">' + (i18n.attachLabel || 'المرفقات (اختياري):') + '</label>' +
				'<div class="snks-dc-inline-dropzone">' +
					'<span class="snks-dc-dropzone-main">' + (i18n.dropzoneMain || 'اضغط أو اسحب الملفات هنا') + '</span>' +
					'<span class="snks-dc-dropzone-sub">' + (i18n.dropzoneSub || 'صور، فيديوهات، أو مستندات (حتى 10 ملفات)') + '</span>' +
					'<input type="file" class="snks-dc-inline-file" multiple accept="image/*,video/*,.pdf,.doc,.docx,.txt" style="display:none;" />' +
				'</div>' +
				'<div class="snks-dc-inline-preview" style="display:none;"></div>' +
				'<button type="button" class="snks-dc-open-compose">' + (i18n.send || 'إرسال') + '</button>' +
			'</div>'
		);
		var selectedFiles = [];
		var $panelHead = $('<div class="snks-dc-hub-panel-head"></div>');
		var $panelTitle = $('<h4 class="snks-dc-thread-title" style="margin:0;"></h4>');
		var $panelClose = $('<button type="button" class="snks-dc-panel-close">×</button>');
		$panelHead.append($panelTitle).append($panelClose);
		$panel.append($panelHead);
		$panel.append($msgs);
		$panel.append($compose);

		var $modal = $('<div class="snks-dc-hub-modal"></div>');
		var $modalInner = $('<div class="snks-dc-hub-modal-inner"></div>');
		var $modalHead = $('<div class="snks-dc-hub-modal-head"><span>' + (i18n.viewAll || 'عرض كل المحادثات') + '</span><button type="button" class="button-link snks-dc-close">&times;</button></div>');
		var $modalList = $('<div class="snks-dc-hub-modal-list"></div>');
		$modalInner.append($modalHead).append($modalList);
		$modal.append($modalInner);
		var $lightbox = $(
			'<div class="snks-dc-lightbox" aria-hidden="true">' +
				'<button type="button" class="snks-dc-lightbox-close" aria-label="' + (i18n.close || 'إغلاق') + '">×</button>' +
				'<img class="snks-dc-lightbox-image" alt="' + (i18n.attachmentImage || 'Attachment image') + '" />' +
			'</div>'
		);

		$bar.append($bell).append($dd);
		$root.append($bar).append($panel).append($modal).append($lightbox);

		function escapeHtml(value) {
			return $('<div/>').text(value || '').html();
		}

		function isImageAttachment(att) {
			return !!(att && att.type && att.type.indexOf('image/') === 0);
		}

		function renderMessageMarkup(messageText, attachments) {
			var safeMessage = escapeHtml(messageText || '');
			var attachmentsHtml = '';
			if (attachments && attachments.length) {
				attachmentsHtml = '<div class="snks-dc-msg-attachments">' +
					attachments.map(function (att) {
						var safeUrl = escapeHtml(att.url || '');
						var safeName = escapeHtml(att.name || 'attachment');
						if (isImageAttachment(att)) {
							return '<button type="button" class="snks-dc-msg-attachment snks-dc-msg-attachment-image" data-lightbox-src="' + safeUrl + '" aria-label="' + safeName + '">' +
								'<img src="' + safeUrl + '" alt="' + safeName + '" loading="lazy" />' +
							'</button>';
						}
						return '<a class="snks-dc-msg-attachment snks-dc-msg-attachment-file" href="' + safeUrl + '" target="_blank" rel="noopener noreferrer">' +
							'<span class="snks-dc-msg-file-icon">📄</span>' +
							'<span class="snks-dc-msg-file-name">' + safeName + '</span>' +
						'</a>';
					}).join('') +
				'</div>';
			}
			return '<div class="snks-dc-msg-body">' + safeMessage + '</div>' + attachmentsHtml;
		}

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
					if (SNKS_DC_DEBUG) {
						console.log('[SNKS-DC] Badge updated', res.data.unread_count);
					}
				}
			}).fail(function (err) {
				console.error('[SNKS-DC] Badge request failed', err);
			});
		}

		var badgeTimer = null;
		function scheduleBadgeRefresh() {
			if (badgeTimer) {
				clearTimeout(badgeTimer);
				badgeTimer = null;
			}
			var delay = document.hidden ? BADGE_MS_HIDDEN : BADGE_MS_VISIBLE;
			badgeTimer = setTimeout(function () {
				badgeTimer = null;
				refreshBadge();
				scheduleBadgeRefresh();
			}, delay);
		}

		function setActiveTab(tab) {
			state.activeTab = tab === 'unread' ? 'unread' : 'booked';
			$tabs.find('.snks-dc-tab').removeClass('is-active');
			$tabs.find('.snks-dc-tab[data-tab="' + state.activeTab + '"]').addClass('is-active');
			$tabContent.find('.snks-dc-tab-panel').removeClass('is-active');
			$tabContent.find('.snks-dc-tab-panel[data-panel="' + state.activeTab + '"]').addClass('is-active');
		}

		function renderUnreadList(messages) {
			$tabUnreadList.empty();
			if (!messages.length) {
				$tabUnreadList.append('<div class="snks-dc-hub-item snks-empty">' + (i18n.noUnread || 'لا توجد رسائل غير مقروءة') + '</div>');
				return;
			}
			messages.forEach(function (m) {
				var $it = $('<div class="snks-dc-hub-item"></div>');
				$it.text((m.sender_name || (i18n.patientFallback || 'مريض')) + ' - ' + (m.message || '').substring(0, 80));
				$it.data('cid', m.conversation_id);
				$it.data('mid', m.id);
				$it.data('title', m.sender_name || (i18n.patientFallback || 'مريض'));
				$it.attr('data-item-type', 'unread');
				$tabUnreadList.append($it);
			});
		}

		function renderBookedPatients(rows) {
			$tabBookedList.empty();
			if (!rows.length) {
				$tabBookedList.append('<div class="snks-dc-hub-item snks-empty">' + (i18n.noBookedPatients || 'لا يوجد مرضى لديهم حجز حديث') + '</div>');
				return;
			}
			rows.forEach(function (p) {
				var name = p.patient_name || (i18n.patientFallback || 'مريض');
				var $it = $('<div class="snks-dc-hub-item"></div>');
				$it.text(name);
				$it.data('pid', p.patient_user_id);
				$it.data('cid', p.conversation_id || 0);
				$it.data('title', name + ' - ' + (i18n.newConversation || 'محادثة جديدة'));
				$it.attr('data-item-type', 'booked');
				$tabBookedList.append($it);
			});
		}

		function startThreadPoller() {
			stopThreadPoller();
			state.threadPollTimer = setTimeout(threadPollTick, THREAD_FIRST_POLL_MS);
		}

		function loadDropdown() {
			if (SNKS_DC_DEBUG) {
				console.log('[SNKS-DC] Loading dropdown tabs');
			}
			$.when(
				post('snks_direct_conv_booked_patients', { limit: 20 }),
				post('snks_direct_conv_recent', { limit: 10 })
			).done(function (bookedRes, unreadRes) {
				var booked = bookedRes && bookedRes[0] && bookedRes[0].success && bookedRes[0].data && bookedRes[0].data.patients ? bookedRes[0].data.patients : [];
				var unread = unreadRes && unreadRes[0] && unreadRes[0].success && unreadRes[0].data && unreadRes[0].data.messages ? unreadRes[0].data.messages : [];
				renderBookedPatients(booked);
				renderUnreadList(unread);
				setActiveTab(state.activeTab);
				if (SNKS_DC_DEBUG) {
					console.log('[SNKS-DC] Dropdown loaded', {
						bookedCount: booked.length,
						unreadCount: unread.length,
						activeTab: state.activeTab,
					});
				}
			}).fail(function (err) {
				console.error('[SNKS-DC] Dropdown load failed', err);
			});
		}

		function stopThreadPoller() {
			if (state.threadPollTimer) {
				clearTimeout(state.threadPollTimer);
				state.threadPollTimer = null;
			}
		}

		function appendThreadRows(rows) {
			if (!rows || !rows.length) {
				return;
			}
			$msgs.find('.snks-dc-empty-history').remove();
			var stickToBottom = isNearBottom($msgs);
			rows.forEach(function (m) {
				var mid = parseInt(m.id, 10) || 0;
				if (mid && $msgs.find('.snks-dc-hub-msg[data-msg-id="' + mid + '"]').length) {
					return;
				}
				var mine = parseInt(m.sender_user_id, 10) === parseInt(window.snksDcCurrentUserId || 0, 10);
				var $m = $('<div class="snks-dc-hub-msg"></div>');
				$m.attr('data-msg-id', mid || '');
				$m.addClass(mine ? 'snks-out' : 'snks-in');
				$m.html(renderMessageMarkup(m.message || m.body || '', m.attachments || []));
				$msgs.append($m);
				if (mid > state.lastMessageId) {
					state.lastMessageId = mid;
				}
			});
			if (stickToBottom) {
				scrollBottom($msgs);
			}
		}

		function threadPollTick() {
			state.threadPollTimer = null;
			if (!state.conversationId || !$panel.is(':visible')) {
				return;
			}
			post('snks_direct_conv_thread_since', {
				conversation_id: state.conversationId,
				since_id: state.lastMessageId,
			})
				.done(function (res) {
					if (res && res.success && res.data && res.data.messages) {
						appendThreadRows(res.data.messages);
					}
				})
				.always(function () {
					if (!state.conversationId || !$panel.is(':visible')) {
						return;
					}
					var nextDelay = document.hidden ? THREAD_MS_HIDDEN : THREAD_MS_VISIBLE;
					state.threadPollTimer = setTimeout(threadPollTick, nextDelay);
				});
		}

		function loadThread(cid, title, pid) {
			if (SNKS_DC_DEBUG) {
				console.log('[SNKS-DC] Load thread', { cid: cid, pid: pid, title: title });
			}
			stopThreadPoller();
			state.conversationId = parseInt(cid, 10) || 0;
			state.patientId = parseInt(pid, 10) || 0;
			state.lastMessageId = 0;
			$panel.find('.snks-dc-thread-title').text(title || (i18n.newConversation || 'محادثة جديدة'));
			$panel.show();
			$msgs.empty();
			selectedFiles = [];
			$compose.find('.snks-dc-inline-file').val('');
			$compose.find('.snks-dc-inline-preview').hide().empty();
			$compose.find('.snks-dc-inline-message').val('');
			if (!state.conversationId) {
				$msgs.html('<div class="snks-dc-empty-history">ابدأ المحادثة برسالتك الأولى.</div>');
				return;
			}
			post('snks_direct_conv_thread', { conversation_id: state.conversationId }).done(function (res) {
				if (res && res.success && res.data && res.data.messages) {
					appendThreadRows(res.data.messages);
					if (!res.data.messages.length) {
						$msgs.html('<div class="snks-dc-empty-history">لا توجد رسائل سابقة. اكتب أول رسالة الآن.</div>');
					} else {
						scrollBottom($msgs);
					}
					if (SNKS_DC_DEBUG) {
						console.log('[SNKS-DC] Thread loaded', {
							conversationId: state.conversationId,
							messages: res.data.messages.length,
						});
					}
				}
				startThreadPoller();
			}).fail(function (err) {
				console.error('[SNKS-DC] Thread load failed', err);
			});
		}

		$panelClose.on('click', function () {
			stopThreadPoller();
			$panel.hide();
		});

		$tabs.on('click', '.snks-dc-tab', function () {
			if (SNKS_DC_DEBUG) {
				console.log('[SNKS-DC] Tab click', $(this).data('tab'));
			}
			setActiveTab($(this).data('tab'));
		});

		$bell.on('click', function () {
			$dd.toggleClass('snks-open');
			if (SNKS_DC_DEBUG) {
				console.log('[SNKS-DC] Bell click, dropdown open?', $dd.hasClass('snks-open'));
			}
			if ($dd.hasClass('snks-open')) {
				loadDropdown();
			}
		});

		$dd.on('click', '.snks-dc-hub-item[data-item-type="unread"]', function () {
			if (SNKS_DC_DEBUG) {
				console.log('[SNKS-DC] Click unread row', {
					cid: $(this).data('cid'),
					mid: $(this).data('mid'),
				});
			}
			var cid = parseInt($(this).data('cid'), 10) || 0;
			var mid = parseInt($(this).data('mid'), 10) || 0;
			if (mid) {
				post('snks_direct_conv_mark_read', { message_id: mid }).done(refreshBadge);
			}
			if (cid) {
				$dd.removeClass('snks-open');
				loadThread(cid, $(this).data('title') || '');
			}
		});

		$dd.on('click', '.snks-dc-hub-item[data-item-type="booked"]', function () {
			if (SNKS_DC_DEBUG) {
				console.log('[SNKS-DC] Click booked patient row', {
					cid: $(this).data('cid'),
					pid: $(this).data('pid'),
				});
			}
			var cid = parseInt($(this).data('cid'), 10) || 0;
			var pid = parseInt($(this).data('pid'), 10) || 0;
			$dd.removeClass('snks-open');
			loadThread(cid, $(this).data('title') || '', pid);
		});

		$dd.on('click', '.snks-dc-hub-viewall', function (e) {
			e.stopPropagation();
			$dd.removeClass('snks-open');
			post('snks_direct_conv_list').done(function (res) {
				$modalList.empty();
				if (res && res.success && res.data && res.data.conversations) {
					res.data.conversations.forEach(function (c) {
						var $row = $('<div class="snks-dc-hub-item"></div>');
						$row.text((c.patient_name || (i18n.patientFallback || 'مريض')) + ' - ' + (c.last_body || '').substring(0, 60));
						$row.data('cid', c.id);
						$row.data('title', c.patient_name || (i18n.patientFallback || 'مريض'));
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
			var cid = parseInt($(this).data('cid'), 10) || 0;
			if (cid) {
				$modal.removeClass('snks-open');
				loadThread(cid, $(this).data('title') || '');
			}
		});

		$panel.on('click', '.snks-dc-msg-attachment-image', function () {
			var src = $(this).attr('data-lightbox-src') || '';
			if (!src) {
				return;
			}
			$lightbox.find('.snks-dc-lightbox-image').attr('src', src);
			$lightbox.addClass('snks-open').attr('aria-hidden', 'false');
		});
		$lightbox.on('click', function (ev) {
			if (ev.target === $lightbox[0] || $(ev.target).closest('.snks-dc-lightbox-close').length) {
				$lightbox.removeClass('snks-open').attr('aria-hidden', 'true');
				$lightbox.find('.snks-dc-lightbox-image').attr('src', '');
			}
		});

		function renderInlineFiles() {
			var $preview = $compose.find('.snks-dc-inline-preview');
			if (!selectedFiles.length) {
				$preview.hide().empty();
				$compose.find('.snks-dc-inline-dropzone').removeClass('has-files');
				return;
			}
			$compose.find('.snks-dc-inline-dropzone').addClass('has-files');
			$preview.show();
			$preview.html(
				'<div class="snks-dc-inline-files-grid">' +
				selectedFiles.map(function (file, idx) {
					var isImage = file.type && file.type.indexOf('image/') === 0;
					var fileUrl = isImage ? URL.createObjectURL(file) : '';
					var fileName = file.name.length > 13 ? file.name.substring(0, 10) + '...' : file.name;
					return '<div class="snks-dc-inline-file-card">' +
						(isImage ? '<img src="' + fileUrl + '" class="snks-dc-inline-file-thumb" alt="' + escapeHtml(fileName) + '">' : '<div class="snks-dc-inline-file-placeholder">📄</div>') +
						'<div class="snks-dc-inline-file-name">' + escapeHtml(fileName) + '</div>' +
						'<button type="button" class="snks-dc-inline-remove" data-remove-inline="' + idx + '">×</button>' +
					'</div>';
				}).join('') +
				'</div>'
			);
		}

		$compose.on('click', '.snks-dc-inline-file', function (e) {
			e.stopPropagation();
		});
		$compose.on('click', '.snks-dc-inline-dropzone', function (e) {
			if ($(e.target).closest('.snks-dc-inline-file,[data-remove-inline]').length) {
				return;
			}
			var inputEl = $(this).find('.snks-dc-inline-file').get(0);
			if (inputEl) {
				inputEl.click();
			}
		});
		$compose.on('change', '.snks-dc-inline-file', function (e) {
			selectedFiles = Array.from(e.target.files || []);
			renderInlineFiles();
		});
		$compose.on('click', '[data-remove-inline]', function (e) {
			e.stopPropagation();
			var idx = parseInt($(this).attr('data-remove-inline'), 10);
			if (!Number.isNaN(idx)) {
				selectedFiles.splice(idx, 1);
				renderInlineFiles();
			}
		});

		$compose.find('.snks-dc-open-compose').on('click', function () {
			if (SNKS_DC_DEBUG) {
				console.log('[SNKS-DC] Send click', {
					conversationId: state.conversationId,
					patientId: state.patientId,
				});
			}
			var body = $.trim($compose.find('.snks-dc-inline-message').val());
			if (!body && !selectedFiles.length) {
				if (window.Swal) {
					window.Swal.fire({
						title: 'تنبيه',
						text: 'يرجى إدخال رسالة أو إرفاق ملف',
						icon: 'warning',
						confirmButtonText: 'حسناً',
					});
				}
				return;
			}

			var $sendBtn = $compose.find('.snks-dc-open-compose');
			$sendBtn.prop('disabled', true).addClass('is-sending').html('<span class="snks-dc-btn-spinner"></span><span>جاري الإرسال...</span>');

			function finishSendSuccess() {
				$compose.find('.snks-dc-inline-message').val('');
				$compose.find('.snks-dc-inline-file').val('');
				selectedFiles = [];
				renderInlineFiles();
				$sendBtn.prop('disabled', false).removeClass('is-sending').text(i18n.send || 'إرسال');
				refreshBadge();
				if (window.Swal) {
					window.Swal.fire({
						title: 'تم بنجاح!',
						text: 'تم إرسال الرسالة للمريض',
						icon: 'success',
						confirmButtonText: 'حسناً',
					});
				}
			}

			function sendMessageWithAttachments(attachmentIds) {
				post('snks_direct_conv_send', {
					conversation_id: state.conversationId,
					patient_user_id: state.patientId,
					body: body,
					attachment_ids: JSON.stringify(attachmentIds || []),
				}).done(function (res) {
					if (res && res.success && !state.conversationId && state.patientId) {
						post('snks_direct_conv_list').done(function (listRes) {
							if (listRes && listRes.success && listRes.data && listRes.data.conversations) {
								listRes.data.conversations.forEach(function (c) {
									if (parseInt(c.patient_user_id, 10) === parseInt(state.patientId, 10)) {
										state.conversationId = parseInt(c.id, 10) || 0;
									}
								});
							}
							if (state.conversationId) {
								loadThread(state.conversationId, $panel.find('.snks-dc-thread-title').text(), state.patientId);
							}
							finishSendSuccess();
						});
					} else {
						if (state.conversationId) {
							post('snks_direct_conv_thread_since', {
								conversation_id: state.conversationId,
								since_id: state.lastMessageId,
							}).done(function (r) {
								if (r && r.success && r.data && r.data.messages) {
									appendThreadRows(r.data.messages);
								}
							});
						}
						finishSendSuccess();
					}
				}).fail(function () {
					$sendBtn.prop('disabled', false).removeClass('is-sending').text(i18n.send || 'إرسال');
					if (window.Swal) {
						window.Swal.fire({
							title: 'خطأ!',
							text: 'حدث خطأ أثناء إرسال الرسالة',
							icon: 'error',
							confirmButtonText: 'حسناً',
						});
					}
				});
			}

			if (!selectedFiles.length) {
				sendMessageWithAttachments([]);
				return;
			}

			var attachmentIds = [];
			function uploadNext(index) {
				if (index >= selectedFiles.length) {
					sendMessageWithAttachments(attachmentIds);
					return;
				}
				var up = new FormData();
				up.append('action', 'snks_direct_conv_upload');
				up.append('nonce', snksDirectConvHub.nonce);
				up.append('file', selectedFiles[index]);
				$.ajax({
					url: snksDirectConvHub.ajaxUrl,
					type: 'POST',
					data: up,
					processData: false,
					contentType: false,
					dataType: 'json',
				}).done(function (r) {
					if (r && r.success && r.data && r.data.id) {
						attachmentIds.push(r.data.id);
					}
					uploadNext(index + 1);
				}).fail(function () {
					$sendBtn.prop('disabled', false).removeClass('is-sending').text(i18n.send || 'إرسال');
					if (window.Swal) {
						window.Swal.fire({
							title: 'خطأ!',
							text: 'فشل رفع أحد الملفات',
							icon: 'error',
							confirmButtonText: 'حسناً',
						});
					}
				});
			}
			uploadNext(0);
		});

		$(document).on('click', function (e) {
			if (!$root.is(e.target) && $root.has(e.target).length === 0) {
				$dd.removeClass('snks-open');
			}
		});

		document.addEventListener('visibilitychange', function () {
			scheduleBadgeRefresh();
		});

		window.snksDcCurrentUserId = snksDirectConvHub.currentUserId || 0;

		refreshBadge();
		scheduleBadgeRefresh();

		var params = new URLSearchParams(window.location.search);
		var dc = params.get('snks_dc');
		if (dc) {
			loadThread(parseInt(dc, 10), '');
		}
	}

	function resolveHubContext(context) {
		if (!context) {
			return $(document);
		}
		if (context.jquery) {
			return context;
		}
		if (context.nodeType) {
			return $(context);
		}
		// JetPopup event payload variants.
		if (context.self) {
			if (context.self.$popup && context.self.$popup.length) {
				return context.self.$popup;
			}
			if (context.self.$element && context.self.$element.length) {
				return context.self.$element;
			}
		}
		if (context.data && context.data.popup_id) {
			var byId = $('#jet-popup-' + context.data.popup_id);
			if (byId.length) {
				return byId;
			}
		}
		return $(document);
	}

	function initAllHubs(context) {
		var $ctx = resolveHubContext(context);
		if (SNKS_DC_DEBUG) {
			console.log('[SNKS-DC] initAllHubs context', context || document, 'resolved:', $ctx.get(0));
		}
		var $hubs = $ctx.find('.snks-dc-hub').addBack('.snks-dc-hub');
		if (SNKS_DC_DEBUG) {
			console.log('[SNKS-DC] hubs found:', $hubs.length);
		}
		$hubs.each(function () {
			initHub($(this));
		});
	}

	$(function () {
		initAllHubs(document);
	});

	// JetPopup loads content after initial page load; re-init hub on popup render/show.
	$(window).on('jet-popup/show-event/after-show', function (_event, popup) {
		if (SNKS_DC_DEBUG) {
			console.log('[SNKS-DC] Jet popup show event', popup);
		}
		initAllHubs(popup ? popup : document);
		// Some popup builders inject inner HTML asynchronously after show.
		setTimeout(function () {
			initAllHubs(popup ? popup : document);
		}, 250);
		setTimeout(function () {
			initAllHubs(popup ? popup : document);
		}, 900);
	});
	$(window).on('jet-popup/render-content/render-custom-content', function (_event, popup) {
		if (SNKS_DC_DEBUG) {
			console.log('[SNKS-DC] Jet popup render-content event', popup);
		}
		initAllHubs(popup ? popup : document);
	});

	// Elementor popup fallback.
	$(window).on('elementor/popup/show', function () {
		if (SNKS_DC_DEBUG) {
			console.log('[SNKS-DC] Elementor popup show event');
		}
		initAllHubs(document);
	});
})(jQuery);
