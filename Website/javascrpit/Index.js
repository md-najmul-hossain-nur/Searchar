// Simple JS for button animation and menu demo

// Donate button click
document.querySelector('.navbar-donate').addEventListener('click', function() {
  alert('Thank you for your interest in donating!');
});

// Search icon click
document.querySelector('.navbar-search').addEventListener('click', function() {
  alert('Search functionality coming soon!');
});

// Cart icon click
document.querySelector('.navbar-cart').addEventListener('click', function() {
  alert('Your cart is empty!');
});

// Read more buttons
document.querySelectorAll('.card .btn-dark').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    alert('More information coming soon!');
  });
});