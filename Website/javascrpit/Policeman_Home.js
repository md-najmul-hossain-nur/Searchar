document.getElementById('requestBroadcastBtn').addEventListener('click', function() {
  const status = document.getElementById('broadcastStatus');
  status.innerText = "Request sent to admin. Please wait for approval...";
  status.style.color = "orange";

  // Simulate admin approval after 3 seconds
  setTimeout(() => {
    const isApproved = true; // Simulate admin approval (replace with real logic)

    if (isApproved) {
      status.innerText = "Request approved! Broadcast link is now available.";
      status.style.color = "green";
      document.getElementById('broadcastLink').style.display = "block";
    } else {
      status.innerText = "Request denied by admin.";
      status.style.color = "red";
    }
  }, 3000);
});


const modal = document.getElementById("postModal");
const feed = document.getElementById("post-feed");
const mediaPreview = document.getElementById("mediaPreview");
let selectedImage = null;
let selectedVideo = null;

function openModal() {
  modal.style.display = "flex";
}

function closeModal() {
  modal.style.display = "none";
  document.getElementById("postText").value = "";
  document.getElementById("imageUpload").value = "";
  document.getElementById("videoUpload").value = "";
  mediaPreview.innerHTML = "";
  selectedImage = null;
  selectedVideo = null;
}

// Handle image upload preview
document.getElementById("imageUpload").addEventListener("change", function() {
  const file = this.files[0];
  if (file) {
    selectedImage = file;
    mediaPreview.innerHTML = `<img src="${URL.createObjectURL(file)}">`;
    selectedVideo = null;
    document.getElementById("videoUpload").value = "";
  }
});

// Handle video upload preview
document.getElementById("videoUpload").addEventListener("change", function() {
  const file = this.files[0];
  if (file) {
    selectedVideo = file;
    mediaPreview.innerHTML = `<video src="${URL.createObjectURL(file)}" controls></video>`;
    selectedImage = null;
    document.getElementById("imageUpload").value = "";
  }
});

// Create post
function createPost() {
  const text = document.getElementById("postText").value.trim();
  if (text === "" && !selectedImage && !selectedVideo) {
    alert("Please add text or media to post!");
    return;
  }

  const post = document.createElement("div");
  post.classList.add("post");
  if (text) post.innerHTML = `<p>${text}</p>`;

  if (selectedImage) {
    const img = document.createElement("img");
    img.src = URL.createObjectURL(selectedImage);
    post.appendChild(img);
  }

  if (selectedVideo) {
    const video = document.createElement("video");
    video.src = URL.createObjectURL(selectedVideo);
    video.controls = true;
    post.appendChild(video);
  }

  feed.prepend(post);
  closeModal();
}