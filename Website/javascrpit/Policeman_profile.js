const modal = document.getElementById("postModal");
const mediaPreview = document.getElementById("mediaPreview");
const postTextInput = document.getElementById("postText");
const imageUploadInput = document.getElementById("imageUpload");
const videoUploadInput = document.getElementById("videoUpload");
const sharedPreview = document.querySelector('.post-modal-preview');
const sharedPostMeta = document.getElementById('sharedPostMeta');
const sharedPostAuthorImage = document.getElementById('sharedPostAuthorImage');
const sharedPostAuthorName = document.getElementById('sharedPostAuthorName');
const sharedPostTime = document.getElementById('sharedPostTime');
const sharedPostText = document.getElementById('sharedPostText');
const sharedPostImage = document.getElementById('sharedPostImage');
const sharedPostVideo = document.getElementById('sharedPostVideo');
const MAX_IMAGE_COUNT = 5;
let selectedImages = [];
let selectedVideo = null;

function renderSelectedImagesPreview() {
	if (!mediaPreview) return;

	if (!selectedImages.length) {
		mediaPreview.innerHTML = '';
		mediaPreview.style.display = 'block';
		return;
	}

	const gridHtml = selectedImages.map((file, index) => {
		const objectUrl = URL.createObjectURL(file);
		return `
			<div class="post-media-item">
				<img src="${objectUrl}" alt="Selected image ${index + 1}">
				<button type="button" class="post-media-remove-btn" data-remove-index="${index}" aria-label="Remove image">&times;</button>
			</div>
		`;
	}).join('');

	mediaPreview.innerHTML = `<div class="post-media-grid">${gridHtml}</div>`;
	mediaPreview.style.display = 'block';

	mediaPreview.querySelectorAll('.post-media-remove-btn').forEach((btn) => {
		btn.addEventListener('click', () => {
			const removeIndex = Number(btn.getAttribute('data-remove-index'));
			if (Number.isNaN(removeIndex)) return;

			selectedImages = selectedImages.filter((_, idx) => idx !== removeIndex);
			if (imageUploadInput && !selectedImages.length) {
				imageUploadInput.value = '';
			}
			renderSelectedImagesPreview();
		});
	});
}

function resetSharedPreviewUi() {
	if (sharedPreview) sharedPreview.style.display = 'none';
	if (sharedPostMeta) sharedPostMeta.style.display = 'none';
	if (sharedPostAuthorImage) sharedPostAuthorImage.removeAttribute('src');
	if (sharedPostAuthorName) sharedPostAuthorName.innerText = '';
	if (sharedPostTime) sharedPostTime.innerText = '';
	if (sharedPostText) {
		sharedPostText.innerText = '';
		sharedPostText.style.display = 'none';
	}
	if (sharedPostImage) {
		sharedPostImage.removeAttribute('src');
		sharedPostImage.style.display = 'none';
	}
	if (sharedPostVideo) {
		sharedPostVideo.removeAttribute('src');
		sharedPostVideo.style.display = 'none';
	}
}

function openModal() {
	if (!modal) return;
	resetSharedPreviewUi();
	modal.style.display = "flex";
}

function closeModal() {
	if (!modal) return;
	modal.style.display = "none";
	if (postTextInput) postTextInput.value = "";
	if (imageUploadInput) imageUploadInput.value = "";
	if (videoUploadInput) videoUploadInput.value = "";
	if (mediaPreview) {
		mediaPreview.innerHTML = "";
		mediaPreview.style.display = 'block';
	}
	const mediaOptions = document.querySelector('.post-media-options');
	if (mediaOptions) mediaOptions.style.display = 'flex';
	resetSharedPreviewUi();
	const anonymousToggle = document.getElementById('anonymousShareToggle');
	if (anonymousToggle) anonymousToggle.checked = false;
	selectedImages = [];
	selectedVideo = null;
}

if (imageUploadInput) {
	imageUploadInput.addEventListener("change", function() {
		const files = Array.from(this.files || []);
		if (!files.length) return;

		if (files.length > MAX_IMAGE_COUNT) {
			alert(`You can select up to ${MAX_IMAGE_COUNT} photos in one post.`);
			this.value = '';
			return;
		}

		const nonImage = files.find((file) => !String(file.type || '').startsWith('image/'));
		if (nonImage) {
			alert('Only image files are allowed in photo selection.');
			this.value = '';
			return;
		}

		selectedImages = files;
		selectedVideo = null;
		renderSelectedImagesPreview();
		if (videoUploadInput) videoUploadInput.value = "";
	});
}

if (videoUploadInput) {
	videoUploadInput.addEventListener("change", function() {
		const file = this.files[0];
		if (!file) return;

		if (!String(file.type || '').startsWith('video/')) {
			alert('Please select a valid video file.');
			this.value = '';
			return;
		}

		selectedVideo = file;
		selectedImages = [];
		if (mediaPreview) mediaPreview.innerHTML = `<video src="${URL.createObjectURL(file)}" controls style="width:100%; border-radius:8px;"></video>`;
		if (imageUploadInput) imageUploadInput.value = "";
	});
}

function createPost() {
	if (!postTextInput) return;

	const text = postTextInput.value.trim();
	if (!text && !selectedImages.length && !selectedVideo) {
		alert("Please write something or add media!");
		return;
	}

	const category = document.querySelector('input[name="category"]:checked')?.value || "general";
	const fd = new FormData();
	fd.append("text", text);
	fd.append("category", category);
	fd.append("case_id", "1");
	fd.append("share_facebook", document.getElementById("facebookShareToggle")?.checked ? "1" : "0");
	fd.append("share_anonymous", document.getElementById("anonymousShareToggle")?.checked ? "1" : "0");

	selectedImages.forEach((imageFile) => {
		fd.append("media_images[]", imageFile, imageFile.name);
	});
	if (selectedVideo) {
		fd.append("media_video", selectedVideo, selectedVideo.name);
	}

	fetch("../Php/save_post.php", {
		method: "POST",
		body: fd,
		credentials: "same-origin"
	})
		.then((r) => r.json())
		.then((res) => {
			if (res && res.success) {
				alert("Post submitted successfully. It will appear after admin approval.");
				closeModal();
				window.location.reload();
			} else {
				alert("Save failed: " + (res?.error || "Unknown error"));
			}
		})
		.catch((err) => {
			console.error(err);
			alert("Network error while saving.");
		});
}

