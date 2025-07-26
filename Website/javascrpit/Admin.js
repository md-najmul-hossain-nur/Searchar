 // Sales Chart
    const salesChart = new Chart(document.getElementById('salesChart').getContext('2d'), {
      type: 'line',
      data: {
        labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
        datasets: [
          {
            label: '2025',
            data: [65, 75, 70, 60, 65, 75, 85],
            borderColor: '#4339f2',
            backgroundColor: 'transparent',
            borderWidth: 3,
            pointBackgroundColor: '#4339f2',
            tension: 0.4
          },
          {
            label: '2024',
            data: [40, 60, 80, 70, 60, 70, 90],
            borderColor: '#fff',
            backgroundColor: 'transparent',
            borderWidth: 3,
            pointBackgroundColor: '#fff',
            tension: 0.4
          }
        ]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#fff' } },
          y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#fff' }, beginAtZero: true, min: 40, max: 90 }
        }
      }
    });

    // Orders Chart
    const ordersChart = new Chart(document.getElementById('ordersChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: ['A', 'B', 'C', 'D', 'E', 'F', 'G'],
        datasets: [
          {
            label: '2025',
            data: [40, 60, 80, 70, 100, 30, 10],
            backgroundColor: '#f64e60'
          },
          {
            label: '2024',
            data: [20, 50, 60, 30, 90, 25, 80],
            backgroundColor: '#4339f2'
          }
        ]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: 'rgba(0,0,0,0.05)' } },
          y: { grid: { color: 'rgba(0,0,0,0.05)' }, beginAtZero: true, min: 0, max: 110 }
        }
      }
    });
    // Sidebar click logic
    document.querySelectorAll('.sidebar ul li').forEach(function(item) {
      item.addEventListener('click', function() {
        // Remove active from all sidebar items
        document.querySelectorAll('.sidebar ul li').forEach(li => li.classList.remove('active'));
        item.classList.add('active');
        // Hide all sections
        document.querySelectorAll('.main-section').forEach(sec => sec.classList.remove('active'));
        // Show the one with same id as data-section
        const sectionId = item.getAttribute('data-section');
        if(sectionId) {
          const section = document.getElementById(sectionId);
          if(section) section.classList.add('active');
        }
      });
    });