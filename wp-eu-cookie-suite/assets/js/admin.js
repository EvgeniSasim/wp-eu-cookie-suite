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

		if (!$startBtn.length) return;

		$startBtn.on('click', function() {
			$startBtn.prop('disabled', true);
			$startBtn.siblings('.spinner').addClass('is-active');
			$progress.show();
			$progressBar.css('width', '0%');
			$status.text('Initializing scan...');

			getUrls();
		});

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
	});
})(jQuery);
