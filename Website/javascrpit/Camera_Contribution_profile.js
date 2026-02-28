    const modal = document.getElementById("postModal");
const mediaPreview = document.getElementById("mediaPreview");
let selectedImage = null;
let selectedVideo = null;

// Open Modal
function openModal() {
  modal.style.display = "flex";
}

// Close Modal
function closeModal() {
  modal.style.display = "none";
  document.getElementById("postText").value = "";
  mediaPreview.innerHTML = "";
  selectedImage = null;
  selectedVideo = null;
}

// Close if clicked outside modal
window.onclick = function(event) {
  if (event.target === modal) {
    closeModal();
  }
};

// Image Preview
document.getElementById("imageUpload").addEventListener("change", function() {
  const file = this.files[0];
  if (file) {
    selectedImage = file;
    selectedVideo = null;
    mediaPreview.innerHTML = `<img src="${URL.createObjectURL(file)}" style="width:100%; border-radius:8px;">`;
    document.getElementById("videoUpload").value = "";
  }
});

// Video Preview
document.getElementById("videoUpload").addEventListener("change", function() {
  const file = this.files[0];
  if (file) {
    selectedVideo = file;
    selectedImage = null;
    mediaPreview.innerHTML = `<video src="${URL.createObjectURL(file)}" controls style="width:100%; border-radius:8px;"></video>`;
    document.getElementById("imageUpload").value = "";
  }
});

// Create Post
function createPost() {
  const text = document.getElementById("postText").value.trim();
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
  fd.append("share_anonymous", document.getElementById("anonToggle")?.checked ? "1" : "0");

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

document.addEventListener("click", async (event) => {
  const likeBtn = event.target.closest(".like-btn");
  if (likeBtn) {
    const active = likeBtn.dataset.liked === "1";
    likeBtn.dataset.liked = active ? "0" : "1";
    likeBtn.innerHTML = active ? '<i class="fa fa-heart"></i> Like' : '<i class="fa fa-heart"></i> Liked';
    likeBtn.style.color = active ? "#444" : "#e74c3c";
    return;
  }

  const commentBtn = event.target.closest(".comment-btn");
  if (commentBtn) {
    const post = commentBtn.closest(".post");
    const box = post?.querySelector(".comment-module");
    if (box) {
      box.style.display = box.style.display === "none" || !box.style.display ? "block" : "none";
    }
    return;
  }

  const sendBtn = event.target.closest(".comment-send-btn");
  if (sendBtn) {
    const post = sendBtn.closest(".post");
    const input = post?.querySelector(".comment-editor");
    const list = post?.querySelector(".comment-module ul");
    const text = input?.innerText?.trim();
    if (!text || !list || !input) return;
    const item = document.createElement("li");
    item.textContent = text;
    list.prepend(item);
    input.innerText = "";
    return;
  }

});
document.getElementById('anonToggle').addEventListener('change', function () {
  if (this.checked) {
    console.log("Anonymous mode enabled");
    // Hide user's name or change UI if needed
  } else {
    console.log("Anonymous mode disabled");
    // Revert changes
  }
});