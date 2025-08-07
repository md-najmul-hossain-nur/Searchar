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
function openMissingForm() {
  document.getElementById("missingFormModal").style.display = "flex";
}

function closeMissingForm() {
  document.getElementById("missingFormModal").style.display = "none";
}

// Close when clicking outside the form
window.onclick = function(event) {
  const modal = document.getElementById("missingFormModal");
  if (event.target === modal) {
    modal.style.display = "none";
  }
};
// Comment Show/Hide Toggle
document.querySelectorAll('.comment-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const post = this.closest('.post');
    const commentSection = post.querySelector('.comment-module');
    if (commentSection.style.display === "none" || commentSection.style.display === "") {
      commentSection.style.display = "block"; // Show comments
    } else {
      commentSection.style.display = "none"; // Hide comments
    }
  });
});

// Likes & Dislikes Count
let likesUpParent = document.getElementsByClassName("comment-likes-up");
let likesDownParent = document.getElementsByClassName("comment-likes-down");

let likesEl = [];
for (let i = 0; i < likesUpParent.length; i++) {
  let likesUp = likesUpParent[i].getElementsByTagName("img")[0];
  let likesDown = likesDownParent[i].getElementsByTagName("img")[0];
  likesEl.push(likesUp, likesDown);
}

likesEl.forEach(el => {
  el.addEventListener("click", function () {
    let likesCountEl = this.parentElement.querySelector("span");
    let likesCount = likesCountEl ? parseInt(likesCountEl.innerText) || 0 : 0;
    likesCountEl.innerText = likesCount + 1;
  });
});
document.querySelectorAll('.comment-reply a').forEach(replyBtn => {
  replyBtn.addEventListener('click', function (e) {
    e.preventDefault();

    // পুরানো reply box রিমুভ
    document.querySelectorAll('.reply-input-area').forEach(box => box.remove());

    // নতুন reply box
    let replyBox = document.createElement('div');
    replyBox.classList.add('reply-input-area');
    replyBox.innerHTML = `
      <div class="comment-editor" contenteditable="true" data-placeholder="Write a reply..."></div>
<button class="comment-send-btn">
  <img src="../Images/send.png" alt="Send">
</button>    `;

    // `.comment` এর নিচে বসানো
    let commentLi = this.closest('li');
    commentLi.appendChild(replyBox); // এখন এটা নিচে দেখাবে

    // Auto resize
    const editor = replyBox.querySelector('.comment-editor');
    editor.addEventListener('input', function () {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 150) + 'px';
    });

    // Reply send
    replyBox.querySelector('.comment-send-btn').addEventListener('click', function () {
      let replyText = editor.innerText.trim();
      if (replyText) {
        alert("Reply sent: " + replyText); // এখানে AJAX দিয়ে সার্ভারে পাঠানো যাবে
        replyBox.remove();
      }
    });
  });
});
// Open Modal and Set Preview
document.querySelectorAll('.share-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const post = this.closest('.post');
    const text = post.querySelector('p')?.innerText || '';
    const img = post.querySelector('.post-img')?.getAttribute('src') || '';

    // Fill preview
    document.getElementById('sharedPostText').innerText = text;
    document.getElementById('sharedPostImage').src = img;

    // Show modal in center
    document.getElementById('postModal').style.display = 'flex';
  });
});