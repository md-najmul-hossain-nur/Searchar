function delayedRedirect() {
    // Optionally, show a loading animation here
    setTimeout(function() {
      window.location.href = '../Html/loginAdmin.html';
    }, 2000); // 2000 ms = 2 seconds
  }
  document.getElementById('view-causes-btn').addEventListener('click', function(e) {
  e.preventDefault(); // Prevent default anchor behavior
  document.getElementById('our-causes').scrollIntoView({
    behavior: 'smooth'
  });
});
const slides = [
  {
    image: "../Images/makeachange.jpg",
    label: "Be a Changemaker",
    petition: "Sign the Petition Today",
    title: "Support Our Cause Donate & Make a Difference"
  },
  {
    image: "../Images/missing.jpeg",
    label: "💛 Reunite Families",
    petition: "Watch Live CCTV Feeds Assist Investigations",
    title: "Help Build a Safer, Brighter Future"
  },
  {
    image: "../Images/pexels-omaralnahi-18495.jpg",
    label: "💛 Every Clue Matters",
    petition: "Upload Crime Evidence Remain Anonymous",
    title: "Contribute to Justice Share What You Know"
  },
  {
    image: "../Images/together.jpg",
    label: "💛 Empower Your Community",
    petition: "Earn by Sharing Evidence or Live Broadcasting",
    title: "Turn Awareness Into Actionm Get Rewarded for Helping"
  }
];
let currentSlide = 0;
const heroBg = document.getElementById('hero-bg');
const heroLabel = document.getElementById('hero-label');
const heroPetition = document.getElementById('hero-petition');
const heroTitle = document.getElementById('hero-title');

// Preload images
slides.forEach(slide => {
  const img = new Image();
  img.src = slide.image;
});

function showSlide(idx) {
  heroLabel.style.opacity = 0;
  heroPetition.style.opacity = 0;
  heroTitle.style.opacity = 0;
  heroBg.style.opacity = 0.2;   // Do not set to 0, so no white flash

  setTimeout(() => {
    heroBg.src = slides[idx].image;
    heroLabel.innerText = slides[idx].label;
    heroPetition.innerText = slides[idx].petition;
    heroTitle.innerHTML = slides[idx].title;

    heroBg.style.opacity = 1;
    heroLabel.style.opacity = 1;
    heroPetition.style.opacity = 1;
    heroTitle.style.opacity = 1;
  }, 400);
}

showSlide(currentSlide);

setInterval(() => {
  currentSlide = (currentSlide + 1) % slides.length;
  showSlide(currentSlide);
}, 4000);

 document.getElementById('read-more-btn').addEventListener('click', function(e) {
    e.preventDefault(); // Prevent jump
    document.getElementById('hero-involved').scrollIntoView({
      behavior: 'smooth'
    });
  });
  
function generateCalendar(year, month) {
    const calendarHeader = document.getElementById("calendarHeader");
    const calendarBody = document.querySelector("#calendar tbody");

    // Month and year header
    const monthNames = [
      "January", "February", "March", "April", "May", "June",
      "July", "August", "September", "October", "November", "December"
    ];
    calendarHeader.textContent = `${monthNames[month]} ${year}`;

    // First day of the month
    const firstDay = new Date(year, month, 1).getDay(); // 0 = Sunday
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Convert to Monday-first format (calendar starts from Monday)
    const startingDay = firstDay === 0 ? 6 : firstDay - 1;

    // Clear existing rows
    calendarBody.innerHTML = "";

    let date = 1;
    for (let i = 0; i < 6; i++) {
      const row = document.createElement("tr");

      for (let j = 0; j < 7; j++) {
        const cell = document.createElement("td");

        if (i === 0 && j < startingDay) {
          cell.textContent = "";
        } else if (date <= daysInMonth) {
          cell.textContent = date;

          // Highlight today's date
          const today = new Date();
          if (
            date === today.getDate() &&
            year === today.getFullYear() &&
            month === today.getMonth()
          ) {
            cell.style.backgroundColor = "white";
            cell.style.color = "black";
            cell.style.fontWeight = "bold";
          }

          date++;
        } else {
          cell.textContent = "";
        }

        row.appendChild(cell);
      }

      calendarBody.appendChild(row);

      // Stop adding rows if all dates are added
      if (date > daysInMonth) break;
    }
  }

  // Auto-generate calendar for today
  const today = new Date();
  generateCalendar(today.getFullYear(), today.getMonth());