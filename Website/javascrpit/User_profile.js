const modal = document.getElementById("postModal");
const mediaPreview = document.getElementById("mediaPreview");
let selectedImages = [];
let selectedVideo = null;

function imageFileKey(file) {
  return `${file.name}_${file.size}_${file.lastModified}`;
}

function renderSelectedImagesPreview() {
  if (!selectedImages.length) {
    mediaPreview.innerHTML = "";
    return;
  }

  const grid = document.createElement("div");
  grid.className = "post-media-grid";

  selectedImages.forEach((file) => {
    const item = document.createElement("div");
    item.className = "post-media-item";

    const image = document.createElement("img");
    image.src = URL.createObjectURL(file);
    image.alt = file.name || "Selected image";

    const removeButton = document.createElement("button");
    removeButton.type = "button";
    removeButton.className = "post-media-remove-btn";
    removeButton.innerHTML = "&times;";
    removeButton.addEventListener("click", () => {
      const key = imageFileKey(file);
      selectedImages = selectedImages.filter((entry) => imageFileKey(entry) !== key);
      renderSelectedImagesPreview();
    });

    item.appendChild(image);
    item.appendChild(removeButton);
    grid.appendChild(item);
  });

  mediaPreview.innerHTML = "";
  mediaPreview.appendChild(grid);
}

// Open Modal
function openModal() {
  modal.style.display = "flex";
}

// Close Modal
function closeModal() {
  modal.style.display = "none";
  document.getElementById("postText").value = "";
  document.getElementById("imageUpload").value = "";
  document.getElementById("videoUpload").value = "";
  mediaPreview.innerHTML = "";
  selectedImages = [];
  selectedVideo = null;
  const facebookToggle = document.getElementById("facebookShareToggle");
  const anonymousToggle = document.getElementById("anonymousShareToggle");
  if (facebookToggle) facebookToggle.checked = false;
  if (anonymousToggle) anonymousToggle.checked = false;
}

// Close if clicked outside modal
window.onclick = function(event) {
  if (event.target === modal) {
    closeModal();
  }
};

// Image Preview
document.getElementById("imageUpload").addEventListener("change", function() {
  const files = Array.from(this.files || []);
  if (!files.length) {
    this.value = "";
    return;
  }

  const invalid = files.find((file) => !file.type || !file.type.startsWith("image/"));
  if (invalid) {
    alert("Only image files are allowed in Photo upload.");
    this.value = "";
    return;
  }

  const dedupe = new Map(selectedImages.map((file) => [imageFileKey(file), file]));
  files.forEach((file) => dedupe.set(imageFileKey(file), file));
  const merged = Array.from(dedupe.values());

  if (merged.length > 5) {
    alert("You can upload maximum 5 images in one post.");
    selectedImages = merged.slice(0, 5);
  } else {
    selectedImages = merged;
  }

  selectedVideo = null;
  document.getElementById("videoUpload").value = "";
  renderSelectedImagesPreview();
  this.value = "";
});

// Video Preview
document.getElementById("videoUpload").addEventListener("change", function() {
  const file = this.files[0];
  if (file) {
    selectedVideo = file;
    selectedImages = [];
    mediaPreview.innerHTML = `<video src="${URL.createObjectURL(file)}" controls style="width:100%; border-radius:8px;"></video>`;
    document.getElementById("imageUpload").value = "";
  }
});

// Create Post
function createPost() {
  const text = document.getElementById("postText").value.trim();
  if (!text && selectedImages.length === 0 && !selectedVideo) {
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

  if (selectedImages.length > 0) {
    selectedImages.forEach((imageFile) => {
      fd.append("media_images[]", imageFile, imageFile.name);
    });
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

