/**
 * Admin JavaScript for WP EU Cookie Suite
 */
(function($) {
	'use strict';
	$(function() {
		const $startBtn = $('#wpeu-cs-start-scan');
		const $progress = $('#wpeu-cs-scan-progress');
		const $progressBar = $('.wpeu-cs-progress-fill');
		const $status = $('.wpeu-cs-progress-status');
		const $resultsWrapper = $('#wpeu-cs-scan-results-wrapper');
		const nonce = $('#wpeu_cs_scanner_nonce').val();

		if ($startBtn.length) {
			$startBtn.on('click', function() {
				$startBtn.prop('disabled', true);
				$startBtn.siblings('.spinner').addClass('is-active');
				$progress.show();
				$progressBar.css('width', '0%');
				$status.text('Initializing scan...');

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
							finishScan('No URLs found to scan.');
							return;
						}
						scanUrls(urls, 0);
					} else {
						finishScan(response.data.message || 'Error fetching URLs.');
					}
				},
				error: function() {
					finishScan('Network error while fetching URLs.');
				}
			});
		}

		function scanUrls(urls, index) {
			if (index >= urls.length) {
				finishScan('Scan complete!', true);
				return;
			}

			const progress = Math.round(((index + 1) / urls.length) * 100);
			$progressBar.css('width', progress + '%');
			$status.text(`Scanning (${index + 1}/${urls.length}): ${urls[index]}`);

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpeu_cs_scan_url',
					url: urls[index],
					nonce: nonce
				},
				success: function(response) {
					// We continue even if one URL fails
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
				// Reload results table
				setTimeout(() => {
					window.location.reload();
				}, 1000);
			}
		}

		// Color Picker
		$('.wpeu-cs-color-picker').wpColorPicker();

		// Banner Preview
		const $previewFrame = $('#wpeu-cs-banner-preview');
		const $refreshBtn = $('#wpeu-cs-refresh-preview');
		const previewNonce = $('#wpeu_cs_preview_nonce').val();

		function updatePreview() {
			const lang = new URLSearchParams(window.location.search).get('lang') || 'en';
			const settings = {
				banner_ui: {
					layout: $('#wpeu-cs-banner-layout').val(),
					position: $('#wpeu-cs-banner-position').val(),
					theme: $('#wpeu-cs-banner-theme').val(),
					primary_color: $('.wpeu-cs-color-picker').val(),
					custom_css: $('textarea[name="wpeu_cs_settings[banner_ui][custom_css]"]').val()
				},
				banner_texts: {},
				enabled_categories: [],
				show_reject_all: $('input[name="wpeu_cs_settings[show_reject_all]"]').is(':checked'),
				eu_mode: $('input[name="wpeu_cs_settings[eu_mode]"]').is(':checked')
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

			$refreshBtn.prop('disabled', true).text('Updating...');

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'html',
				data: {
					action: 'wpeu_cs_preview',
					nonce: previewNonce,
					settings: settings
				},
				success: function(response) {
					const doc = $previewFrame[0].contentWindow.document;
					doc.open();
					doc.write(response);
					doc.close();
				},
				complete: function() {
					$refreshBtn.prop('disabled', false).text('Refresh Preview');
				}
			});
		}

		if ($previewFrame.length && previewNonce) {
			updatePreview();
			if ($refreshBtn.length) {
				$refreshBtn.on('click', updatePreview);
			}
			$('#wpeu-cs-banner-layout, #wpeu-cs-banner-position, #wpeu-cs-banner-theme').on('change', updatePreview);
		}

		// Import scan results to inventory
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
						alert(response.data.message || 'Error importing items.');
						$btn.prop('disabled', false);
						$btn.siblings('.spinner').removeClass('is-active');
					}
				},
				error: function() {
					alert('Network error while importing items.');
					$btn.prop('disabled', false);
					$btn.siblings('.spinner').removeClass('is-active');
				}
			});
		});
	});
})(jQuery);
