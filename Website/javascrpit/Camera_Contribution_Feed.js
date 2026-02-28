document.addEventListener('DOMContentLoaded', () => {
	const liveVideos = Array.from(document.querySelectorAll('.live-video'));
	if (!liveVideos.length) return;

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

	liveVideos.forEach(initLiveVideo);
});
