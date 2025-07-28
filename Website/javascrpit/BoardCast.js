// Bangladesh: Division -> District -> Area
const divisionDistrictAreaData = {

  "Dhaka": {
    "Dhaka District": ["Dhamrai", "Dohar", "Keraniganj", "Nawabganj", "Savar"],
    "Faridpur": ["Alfadanga", "Bhanga", "Boalmari", "Char Bhadrasan", "Faridpur Sadar"],
    "Gazipur": ["Gazipur Sadar", "Kaliakoir", "Kaliganj", "Kapasia", "Sreepur"],
    "Gopalganj": ["Gopalganj Sadar", "Kashiani", "Kotalipara", "Maksudpur", "Tungipara"],
    "Kishoreganj": ["Austagram", "Bajitpur", "Bhairab", "Hossainpur", "Itna"],
    "Madaripur": ["Kalkini", "Madaripur Sadar", "Rajoir", "Shibchar"],
    "Manikganj": ["Daulatpur", "Ghior", "Harirampur", "Manikganj Sadar"],
    "Munshiganj": ["Gazaria", "Lauhajang", "Munshiganj Sadar", "Serajdikhan"],
    "Narayanganj": ["Araihazar", "Bandar", "Narayanganj Sadar", "Rupganj"],
    "Narsingdi": ["Belabo", "Monohardi", "Narsingdi Sadar", "Palash"],
    "Rajbari": ["Baliakandi", "Goalanda", "Pangsha", "Rajbari Sadar"],
    "Shariatpur": ["Bhedarganj", "Damudya", "Gosairhat", "Naria"],
    "Tangail": ["Basail", "Bhuapur", "Delduar", "Dhanbari"],
    "Jamalpur": ["Baksiganj", "Dewanganj", "Islampur", "Jamalpur Sadar"],
    "Sherpur": ["Jhenaigati", "Nakla", "Nalitabari", "Sherpur Sadar"],
    "Netrokona": ["Atpara", "Barhatta", "Durgapur", "Khaliajuri"],
    "Mymensingh": ["Bhaluka", "Dhobaura", "Fulbaria", "Gaffargaon"]
  },
  "Chattogram": {
    "Chattogram District": ["Anowara", "Banshkhali", "Boalkhali", "Chandnaish"],
    "Cox’s Bazar": ["Cox’s Bazar Sadar", "Chakaria", "Kutubdia", "Maheshkhali"],
    "Feni": ["Feni Sadar", "Daganbhuiyan", "Chhagalnaiya", "Fulgazi"],
    "Cumilla (Comilla)": ["Cumilla Sadar North", "Cumilla Sadar South", "Barura", "Brahmanpara"],
    "Bandarban": ["Bandarban Sadar", "Thanchi", "Ruma", "Lama"],
    "Brahmanbaria": ["Brahmanbaria Sadar", "Ashuganj", "Bancharampur", "Bijoynagar"],
    "Chandpur": ["Chandpur Sadar", "Faridganj", "Haimchar", "Haziganj"],
    "Khagrachhari": ["Dighinala", "Khagrachhari Sadar", "Lakshmichhari", "Mahalchhari"],
    "Lakshmipur": ["Lakshmipur Sadar", "Kamalnagar", "Raipur", "Ramganj"],
    "Noakhali": ["Noakhali Sadar", "Begumganj", "Chatkhil", "Companiganj"],
    "Rangamati": ["Rangamati Sadar", "Baghaichhari", "Barkal", "Kawkhali"]
  },
  "Barishal": {
    "Barishal District": ["Agailjhara", "Babuganj", "Bakerganj", "Banaripara"],
    "Barguna": ["Amtali", "Bamna", "Barguna Sadar", "Betagi"],
    "Bhola": ["Bhola Sadar", "Borhanuddin", "Char Fasson", "Daulatkhan"],
    "Jhalokathi": ["Jhalokathi Sadar", "Kathalia", "Nalchity", "Rajapur"],
    "Patuakhali": ["Patuakhali Sadar", "Bauphal", "Dashmina", "Dumki"],
    "Pirojpur": ["Pirojpur Sadar", "Bhandaria", "Kawkhali", "Mathbaria"]
  },
  "Khulna": {
    "Jashore (Jessore)": ["Abhaynagar", "Bagherpara", "Chaugachha", "Jashore Sadar"],
    "Kushtia": ["Kushtia Sadar", "Kumarkhali", "Khoksa", "Mirpur"],
    "Satkhira": ["Satkhira Sadar", "Assasuni", "Debhata", "Kalaroa"],
    "Meherpur": ["Meherpur Sadar", "Mujibnagar", "Gangni"],
    "Bagerhat": ["Bagerhat Sadar", "Chitalmari", "Fakirhat", "Kachua"],
    "Chuadanga": ["Chuadanga Sadar", "Alamdanga", "Damurhuda", "Jibannagar"],
    "Jhenaidah": ["Jhenaidah Sadar", "Harinakundu", "Kaliganj", "Kotchandpur"],
    "Magura": ["Magura Sadar", "Mohammadpur", "Shalikha", "Sreepur"],
    "Narail": ["Narail Sadar", "Kalia", "Lohagara"]
  },
  "Rajshahi": {
    "Joypurhat": ["Akkelpur", "Joypurhat Sadar", "Kalai", "Khetlal"],
    "Bogra (Bogura)": ["Adamdighi", "Bogura Sadar", "Dhunat", "Dupchanchia"],
    "Naogaon": ["Atrai", "Badalgachhi", "Dhamoirhat", "Mohadevpur"],
    "Natore": ["Bagatipara", "Baraigram", "Gurudaspur", "Lalpur"],
    "Sirajganj": ["Belkuchi", "Chauhali", "Kamarkhanda", "Kazipur"],
    "Pabna": ["Atgharia", "Bera", "Bhangura", "Chatmohar"],
    "Rajshahi": ["Bagha", "Bagmara", "Charghat", "Durgapur"]
  },
  "Rangpur": {
    "Dinajpur": ["Birampur", "Birganj", "Biral", "Bochaganj"],
    "Gaibandha": ["Fulchhari", "Gaibandha Sadar", "Gobindaganj", "Palashbari"],
    "Kurigram": ["Bhurungamari", "Char Rajibpur", "Chilmari", "Kurigram Sadar"],
    "Lalmonirhat": ["Aditmari", "Hatibandha", "Kaliganj", "Lalmonirhat Sadar"],
    "Nilphamari": ["Dimla", "Domar", "Jaldhaka", "Kishoreganj"],
    "Panchagarh": ["Atwari", "Boda", "Debiganj", "Panchagarh Sadar"],
    "Rangpur": ["Badarganj", "Gangachara", "Kaunia", "Mithapukur"],
    "Thakurgaon": ["Baliadangi", "Haripur", "Pirganj", "Ranisankail"]
  },
  "Mymensingh": {
    "Mymensingh District": ["Mymensingh Sadar", "Trishal", "Bhaluka", "Dhobaura"],
    "Sherpur": ["Sherpur Sadar", "Nalitabari", "Jhinaigati", "Nakla"],
    "Jamalpur": ["Baksiganj", "Dewanganj", "Islampur", "Jamalpur Sadar"],
    "Netrokona": ["Atpara", "Barhatta", "Durgapur", "Khaliajuri"]
  },
  "Sylhet": {
    "Sylhet District": ["Sylhet Sadar", "Beanibazar", "Balaganj", "Bishwanath"],
    "Moulvibazar": ["Moulvibazar Sadar", "Barlekha", "Kamalganj", "Kulaura"],
    "Habiganj": ["Habiganj Sadar", "Baniachong", "Chunarughat", "Madhabpur"],
    "Sunamganj": ["Sunamganj Sadar", "South Sunamganj", "Derai", "Dharmapasha"]
  }

};

// DOM elements
const divisionSelect = document.getElementById('divisionSelect');
const districtSelect = document.getElementById('districtSelect');
const areaSelect = document.getElementById('areaSelect');
const cameraGrid = document.getElementById('cameraGrid');
const cameraInfo = document.getElementById('cameraInfo');
const cameraCount = document.getElementById('cameraCount');
const areaName = document.getElementById('areaName');

// Populate Division dropdown
function populateDivisions() {
  divisionSelect.innerHTML = '<option value="">-- Choose Division --</option>';
  for (const division in divisionDistrictAreaData) {
    const option = document.createElement('option');
    option.value = division;
    option.textContent = division;
    divisionSelect.appendChild(option);
  }
}

// Populate Districts based on Division
function populateDistricts(division) {
  districtSelect.innerHTML = '<option value="">-- Choose District --</option>';
  areaSelect.innerHTML = '<option value="">-- Choose Area --</option>';
  if (division && divisionDistrictAreaData[division]) {
    for (const district in divisionDistrictAreaData[division]) {
      const option = document.createElement('option');
      option.value = district;
      option.textContent = district;
      districtSelect.appendChild(option);
    }
  }
}

// Populate Areas based on District
function populateAreas(division, district) {
  areaSelect.innerHTML = '<option value="">-- Choose Area --</option>';
  if (
    division &&
    district &&
    divisionDistrictAreaData[division] &&
    divisionDistrictAreaData[division][district]
  ) {
    divisionDistrictAreaData[division][district].forEach(area => {
      const option = document.createElement('option');
      option.value = area;
      option.textContent = area;
      areaSelect.appendChild(option);
    });
  }
}

// Event Listeners
divisionSelect.addEventListener('change', function () {
  populateDistricts(this.value);
  cameraInfo.classList.add("hidden");
  cameraGrid.innerHTML = "";
});
districtSelect.addEventListener('change', function () {
  populateAreas(divisionSelect.value, this.value);
  cameraInfo.classList.add("hidden");
  cameraGrid.innerHTML = "";
});

areaSelect.addEventListener('change', function () {
  const area = areaSelect.value;
  if (area) {
    cameraInfo.classList.remove("hidden");
    areaName.textContent = area;
    const randomCount = Math.floor(Math.random() * 5) + 1;
    cameraCount.textContent = randomCount;
    cameraGrid.innerHTML = "";
    for (let i = 0; i < randomCount; i++) {
      const video = document.createElement("div");
      video.className = "bg-black rounded shadow p-2";
      video.innerHTML = `
        <div class="aspect-video bg-gray-700 rounded mb-2"></div>
        <p class="text-white text-sm">Camera ${i + 1} - ${area}</p>
      `;
      cameraGrid.appendChild(video);
    }
  } else {
    cameraInfo.classList.add("hidden");
    cameraGrid.innerHTML = "";
  }
});

// Initial call
populateDivisions();