document.addEventListener('DOMContentLoaded', () => {
	const liveVideos = Array.from(document.querySelectorAll('.live-video'));
	const webcamVideos = Array.from(document.querySelectorAll('.webcam-video'));

	const initLiveVideo = (videoEl) => {
		const sourceUrl = (videoEl.getAttribute('data-live-src') || '').trim();
		if (!sourceUrl) return;

		const isHls = /\.m3u8($|\?)/i.test(sourceUrl);
		if (!isHls) {
			videoEl.src = sourceUrl;
			return;
		}

		if (videoEl.canPlayType('application/vnd.apple.mpegurl')) {
			videoEl.src = sourceUrl;
			return;
		}

		if (window.Hls && window.Hls.isSupported()) {
			const hls = new window.Hls({
				enableWorker: true,
				lowLatencyMode: true,
			});
			hls.loadSource(sourceUrl);
			hls.attachMedia(videoEl);
			videoEl.dataset.hlsAttached = '1';
			return;
		}

		const fallbackLink = document.createElement('a');
		fallbackLink.href = sourceUrl;
		fallbackLink.target = '_blank';
		fallbackLink.rel = 'noopener';
		fallbackLink.className = 'live-link';
		fallbackLink.textContent = 'Open live stream in new tab';
		videoEl.insertAdjacentElement('afterend', fallbackLink);
	};

	if (liveVideos.length) {
		liveVideos.forEach(initLiveVideo);
	}

	const initWebcamVideos = async () => {
		if (!webcamVideos.length) return;

		const startCameraForWrap = async (wrap, deviceId = null) => {
			const videoEl = wrap.querySelector('.webcam-video');
			const stateEl = wrap.querySelector('.webcam-preview-state');
			const btn = wrap.querySelector('.start-webcam-btn');
			if (!videoEl || !stateEl) return;

			if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
				stateEl.style.display = 'block';
				stateEl.textContent = 'Webcam not supported.';
				return;
			}

			try {
				if (btn) btn.disabled = true;
				stateEl.style.display = 'block';
				stateEl.textContent = 'Requesting camera...';

				if (videoEl.srcObject) {
					videoEl.srcObject.getTracks().forEach(t => t.stop());
				}

				const constraints = { video: true, audio: false };
				if (deviceId) {
					constraints.video = { deviceId: { exact: deviceId } };
				}

				const stream = await navigator.mediaDevices.getUserMedia(constraints);
				videoEl.srcObject = stream;
				await videoEl.play();
				stateEl.style.display = 'none';
				if (btn) btn.textContent = 'Restart Webcam';
				
				// Save selection
				const feedId = wrap.getAttribute('data-feed-id');
				if (feedId && deviceId) {
					localStorage.setItem(`searchar_cam_feed_${feedId}`, deviceId);
				}
			} catch (error) {
				console.error('Webcam error:', error);
				stateEl.style.display = 'block';
				stateEl.textContent = 'Permission denied or camera unavailable.';
			} finally {
				if (btn) btn.disabled = false;
			}
		};

		const populateCameraDropdowns = async () => {
			try {
				const devices = await navigator.mediaDevices.enumerateDevices();
				const videoDevices = devices.filter(d => d.kind === 'videoinput');
				
				document.querySelectorAll('.webcam-video-wrap').forEach(wrap => {
					const controls = wrap.querySelector('.webcam-controls');
					if (!controls) return;
					
					let select = controls.querySelector('.camera-select');
					if (!select) {
						select = document.createElement('select');
						select.className = 'camera-select';
						select.style.width = '100%';
						select.style.padding = '10px';
						select.style.borderRadius = '8px';
						select.style.background = '#f8f9fa';
						select.style.color = '#333';
						select.style.border = '1px solid #ccc';
						select.style.fontFamily = 'inherit';
						select.style.fontSize = '14px';
						select.style.cursor = 'pointer';
						select.style.textAlign = 'center';
						select.style.textAlignLast = 'center';
						
						select.addEventListener('change', (e) => {
							startCameraForWrap(wrap, e.target.value);
						});
						controls.appendChild(select);
					}
					
					select.innerHTML = '';
					videoDevices.forEach((device, index) => {
						const labelLower = (device.label || '').toLowerCase();
						if (labelLower.includes('virtual') || labelLower.includes('obs')) {
							return;
						}
						const option = document.createElement('option');
						option.value = device.deviceId;
						option.text = device.label || `Camera ${index + 1}`;
						select.appendChild(option);
					});

					// If no real cameras found, show a message
					if (select.options.length === 0) {
						const option = document.createElement('option');
						option.text = "No actual cameras found";
						option.disabled = true;
						select.appendChild(option);
					}

					const feedId = wrap.getAttribute('data-feed-id');
					const savedId = localStorage.getItem(`searchar_cam_feed_${feedId}`);
					if (savedId && Array.from(select.options).some(o => o.value === savedId)) {
						select.value = savedId;
					}
					
					startCameraForWrap(wrap, select.value);
				});
			} catch (err) {
				console.error('Failed to enumerate devices', err);
			}
		};

		document.querySelectorAll('.webcam-video-wrap').forEach(wrap => {
			const btn = wrap.querySelector('.start-webcam-btn');
			if (btn) {
				btn.addEventListener('click', (e) => {
					e.preventDefault();
					const select = wrap.querySelector('.camera-select');
					startCameraForWrap(wrap, select ? select.value : null);
				});
			}
		});

		try {
			const initialStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
			initialStream.getTracks().forEach(t => t.stop());
		} catch (e) {
			console.error('Initial permission denied', e);
		}

		populateCameraDropdowns();

        // Capture frames every 3 seconds for AI detection
        setInterval(() => {
            webcamVideos.forEach((videoEl) => {
                if (videoEl.paused || videoEl.ended) return;
                
                const wrap = videoEl.closest('.webcam-video-wrap');
                if (!wrap) return;
                const feedId = wrap.getAttribute('data-feed-id');
                if (!feedId) return;

                // Create a temporary canvas
                const canvas = document.createElement('canvas');
                canvas.width = videoEl.videoWidth || 640;
                canvas.height = videoEl.videoHeight || 480;
                if (canvas.width === 0 || canvas.height === 0) return;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);

                canvas.toBlob((blob) => {
                    if (!blob) return;
                    const formData = new FormData();
                    formData.append('frame', blob, 'frame.jpg');
                    formData.append('feed_id', feedId);

                    fetch('../Php/upload_webcam_frame.php', {
                        method: 'POST',
                        body: formData
                    }).catch(err => console.error('Failed to upload frame:', err));
                }, 'image/jpeg', 0.8);
            });
        }, 3000);
	};

	initWebcamVideos();

	const payoutEl = document.getElementById('payoutCountdown');
	if (payoutEl) {
		let remaining = Number(payoutEl.getAttribute('data-next-payout') || '');
		if (!Number.isFinite(remaining) || remaining < 0) {
			payoutEl.textContent = 'Paused';
		} else {
			const render = () => {
				const mins = Math.floor(remaining / 60);
				const secs = remaining % 60;
				payoutEl.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
			};
			render();
			setInterval(() => {
				if (remaining > 0) {
					remaining -= 1;
					render();
				}
			}, 1000);
		}
	}
});
