// Simple interaction for the Donate button
document.addEventListener('DOMContentLoaded', () => {
  document.querySelector('.donate-btn').addEventListener('click', () => {
    alert('Thank you for choosing to donate!');
  });

  const readMoreButtons = document.querySelectorAll('.card button');
  readMoreButtons.forEach(button => {
    button.addEventListener('click', () => {
      alert('More details coming soon...');
    });
  });
});
