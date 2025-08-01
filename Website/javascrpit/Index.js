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
   // Set the month/year you are displaying:
  const displayYear = 2025;
  const displayMonth = 6; // July is 6 because months are zero-indexed in JS (Jan=0)

  // Get today's date:
  const today = new Date();
  const todayYear = today.getFullYear();
  const todayMonth = today.getMonth();
  const todayDate = today.getDate();

  // Only proceed if calendar is showing this month and year
  if(todayYear === displayYear && todayMonth === displayMonth) {
    const calendar = document.getElementById('calendar');
    // Get all <td> inside tbody
    const tds = calendar.querySelectorAll('tbody td');

    tds.forEach(td => {
      const day = parseInt(td.textContent);
      if (!isNaN(day)) {
        if(day === todayDate) {
          // Highlight today's date: white background, black text
          td.style.backgroundColor = 'white';
          td.style.color = 'black';
          td.style.fontWeight = 'bold';
        } 
        else if(day === todayDate + 1) {
          // Tomorrow - optional styling or leave default
          td.style.backgroundColor = ''; // default
          td.style.color = ''; 
        }
      }
    });
  }