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

		const states = Array.from(document.querySelectorAll('.webcam-preview-state'));
		const restartBtns = Array.from(document.querySelectorAll('.start-webcam-btn'));
		
		let currentStream = null;

		const setState = (text) => {
			states.forEach((state) => {
				if (!text) {
					state.style.display = 'none';
				} else {
					state.style.display = 'block';
					state.textContent = text;
				}
			});
		};

		const startCamera = async () => {
			if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
				setState('Webcam preview is not supported in this browser.');
				return;
			}

			try {
				restartBtns.forEach(btn => btn.disabled = true);
				setState('Requesting webcam permission...');

				if (currentStream) {
					currentStream.getTracks().forEach(t => t.stop());
				}

				currentStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
				webcamVideos.forEach((videoEl) => {
					videoEl.srcObject = currentStream;
					videoEl.play().catch(e => console.error('Play failed:', e));
				});
				setState('');
				restartBtns.forEach(btn => btn.textContent = 'Restart Webcam');
			} catch (error) {
				console.error('Webcam error:', error);
				setState('Webcam permission denied. Allow camera access to show preview.');
			} finally {
				restartBtns.forEach(btn => btn.disabled = false);
			}
		};

		restartBtns.forEach(btn => {
			btn.addEventListener('click', (e) => {
				e.preventDefault();
				startCamera();
			});
		});

		startCamera();
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
