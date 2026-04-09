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
let selectedImage = null;
let selectedVideo = null;

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
	if (mediaPreview) {
		mediaPreview.innerHTML = "";
		mediaPreview.style.display = 'none';
	}
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
		mediaPreview.style.display = 'none';
	}
	const mediaOptions = document.querySelector('.post-media-options');
	if (mediaOptions) mediaOptions.style.display = 'flex';
	resetSharedPreviewUi();
	const anonymousToggle = document.getElementById('anonymousShareToggle');
	if (anonymousToggle) anonymousToggle.checked = false;
	selectedImage = null;
	selectedVideo = null;
}

if (imageUploadInput) {
	imageUploadInput.addEventListener("change", function() {
		const file = this.files[0];
		if (!file) {
			if (mediaPreview) {
				mediaPreview.innerHTML = "";
				mediaPreview.style.display = 'none';
			}
			return;
		}
		selectedImage = file;
		selectedVideo = null;
		resetSharedPreviewUi();
		if (mediaPreview) {
			mediaPreview.innerHTML = `<img src="${URL.createObjectURL(file)}" style="width:100%; border-radius:8px;">`;
			mediaPreview.style.display = 'block';
		}
		if (videoUploadInput) videoUploadInput.value = "";
	});
}

if (videoUploadInput) {
	videoUploadInput.addEventListener("change", function() {
		const file = this.files[0];
		if (!file) {
			if (mediaPreview) {
				mediaPreview.innerHTML = "";
				mediaPreview.style.display = 'none';
			}
			return;
		}
		selectedVideo = file;
		selectedImage = null;
		resetSharedPreviewUi();
		if (mediaPreview) {
			mediaPreview.innerHTML = `<video src="${URL.createObjectURL(file)}" controls style="width:100%; border-radius:8px;"></video>`;
			mediaPreview.style.display = 'block';
		}
		if (imageUploadInput) imageUploadInput.value = "";
	});
}

function createPost() {
	if (!postTextInput) return;

	const text = postTextInput.value.trim();
	if (!text && !selectedImage && !selectedVideo) {
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

	if (selectedImage) {
		fd.append("media_images[]", selectedImage, selectedImage.name);
	}
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

