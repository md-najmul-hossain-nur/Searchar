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

  const post = document.createElement("div");
  post.classList.add("card", "post");

  if (text) post.innerHTML = `<p>${text}</p>`;
  if (selectedImage) post.innerHTML += `<img src="${URL.createObjectURL(selectedImage)}" style="width:100%; border-radius:8px;">`;
  if (selectedVideo) post.innerHTML += `<video src="${URL.createObjectURL(selectedVideo)}" controls style="width:100%; border-radius:8px;"></video>`;

  document.getElementById("post-feed").prepend(post);
  closeModal();
}