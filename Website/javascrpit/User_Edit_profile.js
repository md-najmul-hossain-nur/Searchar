  function previewImage(event, previewId) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = () => document.getElementById(previewId).src = reader.result;
        reader.readAsDataURL(file);
      }
    }
    // Variables for map and marker
let map, marker, selectedLatLng;

// Open the map modal and initialize the map
function selectLocationFromMap() {
  document.getElementById('mapModal').style.display = 'flex';
  setTimeout(initMap, 50);
}

// Close the map modal
function closeMapModal() {
  document.getElementById('mapModal').style.display = 'none';
}

// Initialize Leaflet map
function initMap() {
  if (!map) {
    map = L.map('map').setView([23.8103, 90.4125], 13); // Example: Dhaka
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    map.on('click', function(e) {
      setMarker(e.latlng);
    });
  }
  map.invalidateSize();
}

// Set or move marker
function setMarker(latlng) {
  selectedLatLng = latlng;
  if (marker) {
    marker.setLatLng(latlng);
  } else {
    marker = L.marker(latlng).addTo(map);
  }
}

// Get user's current location
function getCurrentLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(pos) {
      const latlng = { lat: pos.coords.latitude, lng: pos.coords.longitude };
      map.setView([latlng.lat, latlng.lng], 16);
      setMarker(latlng);
    });
  } else {
    alert('Geolocation not supported');
  }
}

// Save location and fill fields, then close modal
function saveMapLocation() {
  if (!selectedLatLng) {
    alert('Select a location on the map!');
    return;
  }
  // Set latitude and longitude
  document.getElementById('latitude').value = selectedLatLng.lat;
  document.getElementById('longitude').value = selectedLatLng.lng;

  // Reverse geocode with Nominatim
  fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${selectedLatLng.lat}&lon=${selectedLatLng.lng}`)
    .then(r => r.json())
    .then(data => {
      document.getElementById('street').value = data.address.road || data.address.neighbourhood || '';
      document.getElementById('city').value = data.address.city || data.address.town || data.address.village || '';
      document.getElementById('postal').value = data.address.postcode || '';
      document.getElementById('country').value = data.address.country || '';
      closeMapModal();
    });
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