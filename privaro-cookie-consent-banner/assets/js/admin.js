/**
 * Admin JavaScript for Privaro Cookie Consent Banner
 */
(function($) {
	'use strict';
	$(function() {
		const i18n = (window.wpeuCsAdmin && window.wpeuCsAdmin.i18n) ? window.wpeuCsAdmin.i18n : {};
		const t = function(key, fallback) {
			return i18n[key] || fallback;
		};

		const $startBtn = $('#wpeu-cs-start-scan');
		const $progress = $('#wpeu-cs-scan-progress');
		const $progressBar = $('.wpeu-cs-progress-fill');
		const $status = $('.wpeu-cs-progress-status');
		const nonce = $('#wpeu_cs_scanner_nonce').val();

		if ($startBtn.length) {
			$startBtn.on('click', function() {
				$startBtn.prop('disabled', true);
				$startBtn.siblings('.spinner').addClass('is-active');
				$progress.show();
				$progressBar.css('width', '0%');
				$status.text(t('initializingScan', 'Initializing scan...'));

				getUrls();
			});
		}

		function getUrls() {
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpeu_cs_get_scan_urls',
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						const urls = response.data.urls;
						if (urls.length === 0) {
							finishScan(t('noUrls', 'No URLs found to scan.'));
							return;
						}
						scanUrls(urls, 0);
					} else {
						finishScan(response.data.message || t('errorFetchingUrls', 'Error fetching URLs.'));
					}
				},
				error: function() {
					finishScan(t('networkErrorUrls', 'Network error while fetching URLs.'));
				}
			});
		}

		function scanUrls(urls, index) {
			if (index >= urls.length) {
				finishScan(t('scanComplete', 'Scan complete!'), true);
				return;
			}

			const progress = Math.round(((index + 1) / urls.length) * 100);
			$progressBar.css('width', progress + '%');
			const template = t('scanningProgress', 'Scanning (%1$d/%2$d): %3$s');
			$status.text(
				template
					.replace('%1$d', String(index + 1))
					.replace('%2$d', String(urls.length))
					.replace('%3$s', urls[index])
			);

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpeu_cs_scan_url',
					url: urls[index],
					nonce: nonce
				},
				success: function() {
					scanUrls(urls, index + 1);
				},
				error: function() {
					scanUrls(urls, index + 1);
				}
			});
		}

		function finishScan(message, success = false) {
			$startBtn.prop('disabled', false);
			$startBtn.siblings('.spinner').removeClass('is-active');
			$status.text(message);

			if (success) {
				setTimeout(() => {
					window.location.reload();
				}, 1000);
			}
		}

		// Color Picker + Banner Preview (independent of scanner UI)
		const $previewFrame = $('#wpeu-cs-banner-preview');
		const $refreshBtn = $('#wpeu-cs-refresh-preview');
		const previewNonce = $('#wpeu_cs_preview_nonce').val();
		const $euMode = $('input[name="wpeu_cs_settings[eu_mode]"]');
		const $rejectAll = $('input[name="wpeu_cs_settings[show_reject_all]"]');
		let previewTimer = null;
		let previewXhr = null;

		function syncRejectAllWithEuMode() {
			if (!$euMode.length || !$rejectAll.length) {
				return;
			}
			const euOn = $euMode.is(':checked');
			if (euOn) {
				$rejectAll.prop('checked', true).prop('disabled', true);
			} else {
				$rejectAll.prop('disabled', false);
			}
		}

		syncRejectAllWithEuMode();
		$euMode.on('change', syncRejectAllWithEuMode);

		function normalizeLocaleCode(code) {
			if (!code) {
				return 'en';
			}
			code = String(code).toLowerCase().trim().replace(/_/g, '-');
			const match = code.match(/^([a-z]{2,3})(?:-[a-z0-9]+)*$/);
			return match ? match[1] : 'en';
		}

		function getPreviewLang() {
			return normalizeLocaleCode(new URLSearchParams(window.location.search).get('lang') || 'en');
		}

		function getPrimaryColor() {
			const $input = $('#wpeu-cs-banner-primary-color');
			if (!$input.length) {
				return '#30363c';
			}
			const value = $input.val();
			return value && /^#([A-Fa-f0-9]{3}){1,2}$/.test(value) ? value : '#30363c';
		}

		function writePreviewHtml(html) {
			const iframe = $previewFrame[0];
			if (!iframe) {
				return;
			}

			// Preserve page scroll — rewriting iframe content otherwise jumps to Live Preview.
			const scrollX = window.scrollX;
			const scrollY = window.scrollY;
			const active = document.activeElement;

			if ('srcdoc' in iframe) {
				// Do not set src=about:blank first: that forces a load and scrolls the page.
				iframe.srcdoc = html;
			} else if (iframe.contentWindow && iframe.contentWindow.document) {
				const doc = iframe.contentWindow.document;
				doc.open();
				doc.write(html);
				doc.close();
			}

			requestAnimationFrame(function() {
				window.scrollTo(scrollX, scrollY);
				if (active && typeof active.focus === 'function' && document.contains(active)) {
					try {
						active.focus({ preventScroll: true });
					} catch (e) {
						active.focus();
					}
				}
			});
		}

		function showPreviewError(message) {
			writePreviewHtml('<p style="padding:1em;color:#b32d2e;font-family:sans-serif;">' + message + '</p>');
		}

		function updatePreview() {
			const lang = getPreviewLang();
			const settings = {
				preview_locale: lang,
				banner_ui: {
					layout: $('#wpeu-cs-banner-layout').val(),
					position: $('#wpeu-cs-banner-position').val(),
					theme: $('#wpeu-cs-banner-theme').val(),
					primary_color: getPrimaryColor()
				},
				banner_texts: {},
				enabled_categories: [],
				show_reject_all: $rejectAll.is(':checked') || $euMode.is(':checked'),
				eu_mode: $euMode.is(':checked')
			};

			$('input[name="wpeu_cs_settings[enabled_categories][]"]:checked').each(function() {
				settings.enabled_categories.push($(this).val());
			});

			settings.banner_texts[lang] = {};
			$('input[name^="wpeu_cs_settings[banner_texts][' + lang + ']"], textarea[name^="wpeu_cs_settings[banner_texts][' + lang + ']"]').each(function() {
				const name = $(this).attr('name');
				const match = name.match(/\[([^\]]+)\]$/);
				if (match) {
					settings.banner_texts[lang][match[1]] = $(this).val();
				}
			});

			if (!$previewFrame.length || !previewNonce) {
				return;
			}

			if ($refreshBtn.length) {
				$refreshBtn.prop('disabled', true).text(t('updatingPreview', 'Updating...'));
			}

			if (previewXhr && typeof previewXhr.abort === 'function') {
				previewXhr.abort();
			}

			previewXhr = $.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'html',
				data: {
					action: 'wpeu_cs_preview',
					nonce: previewNonce,
					settings: settings
				},
				success: function(response) {
					if (!response || response.indexOf('CookieConsent') === -1) {
						showPreviewError(t('previewInvalid', 'Preview response invalid.'));
						return;
					}
					writePreviewHtml(response);
				},
				error: function(xhr, status) {
					if (status === 'abort') {
						return;
					}
					showPreviewError(t('previewFailed', 'Preview failed to load.'));
				},
				complete: function() {
					previewXhr = null;
					if ($refreshBtn.length) {
						$refreshBtn.prop('disabled', false).text(t('refreshPreview', 'Refresh Preview'));
					}
				}
			});
		}

		function schedulePreviewUpdate(delay) {
			clearTimeout(previewTimer);
			previewTimer = setTimeout(updatePreview, typeof delay === 'number' ? delay : 400);
		}

		$('.wpeu-cs-color-picker').wpColorPicker({
			change: function() {
				schedulePreviewUpdate(300);
			},
			clear: function() {
				schedulePreviewUpdate(300);
			}
		});

		if ($previewFrame.length && previewNonce) {
			updatePreview();
			if ($refreshBtn.length) {
				$refreshBtn.on('click', function(e) {
					e.preventDefault();
					updatePreview();
				});
			}
			$('#wpeu-cs-banner-layout, #wpeu-cs-banner-position, #wpeu-cs-banner-theme').on('change', function() {
				schedulePreviewUpdate(200);
			});
			$('input[name="wpeu_cs_settings[eu_mode]"], input[name="wpeu_cs_settings[show_reject_all]"], input[name="wpeu_cs_settings[enabled_categories][]"]').on('change', function() {
				schedulePreviewUpdate(200);
			});
			// Do not refresh preview on every keystroke — that scrolls the page to the iframe.
			// Update when the field loses focus (change) or when Refresh is clicked.
			$('input[name^="wpeu_cs_settings[banner_texts]"], textarea[name^="wpeu_cs_settings[banner_texts]"], #wpeu-cs-banner-primary-color').on('change', function() {
				schedulePreviewUpdate(150);
			});
		}

		// Normalize language code input (ru-RU → ru) before submit.
		$('#new_lang_code').on('blur', function() {
			const $input = $(this);
			const raw = $input.val();
			if (!raw) {
				return;
			}
			$input.val(normalizeLocaleCode(raw));
		});

		// Scanner-only: import scan results
		$(document).on('click', '#wpeu-cs-import-scan', function() {
			const $btn = $(this);
			$btn.prop('disabled', true);
			$btn.siblings('.spinner').addClass('is-active');

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpeu_cs_import_scan',
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						window.location.href = window.location.href.replace('tab=scanner', 'tab=cookies');
					} else {
						alert(response.data.message || t('importError', 'Error importing items.'));
						$btn.prop('disabled', false);
						$btn.siblings('.spinner').removeClass('is-active');
					}
				},
				error: function() {
					alert(t('importNetworkError', 'Network error while importing items.'));
					$btn.prop('disabled', false);
					$btn.siblings('.spinner').removeClass('is-active');
				}
			});
		});
	});
})(jQuery);
