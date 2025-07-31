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

