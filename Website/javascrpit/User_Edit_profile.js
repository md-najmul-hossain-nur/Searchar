  function previewImage(event, previewId) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = () => document.getElementById(previewId).src = reader.result;
        reader.readAsDataURL(file);
      }
    }
   
// Bubble animation script (larger bubbles)
document.addEventListener('DOMContentLoaded', () => {
  const bubbleContainer = document.querySelector('.bubble-background');
  for(let i = 0; i < 18; i++) {
    const bubble = document.createElement('div');
    bubble.classList.add('bubble');
    // Increase bubble size: min 80px, max 180px
    const size = Math.random() * (180 - 80) + 80; // px
    bubble.style.width = `${size}px`;
    bubble.style.height = `${size}px`;
    bubble.style.left = `${Math.random() * 100}vw`;
    bubble.style.animationDuration = `${Math.random() * (19 - 9) + 9}s`;
    bubble.style.animationDelay = `-${Math.random() * 19}s`;
    bubbleContainer.appendChild(bubble);
  }
});