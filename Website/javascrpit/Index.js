function delayedRedirect() {
    // Optionally, show a loading animation here
    setTimeout(function() {
      window.location.href = '../Html/loginAdmin.html';
    }, 2000); // 2000 ms = 2 seconds
  }
const viewCausesBtn = document.getElementById('view-causes-btn');
if (viewCausesBtn) {
  viewCausesBtn.addEventListener('click', function(e) {
    e.preventDefault(); // Prevent default anchor behavior
    const causesSection = document.getElementById('our-causes');
    if (!causesSection) return;
    causesSection.scrollIntoView({
      behavior: 'smooth'
    });
  });
}
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

if (heroBg && heroLabel && heroPetition && heroTitle) {
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
}

const readMoreBtn = document.getElementById('read-more-btn');
if (readMoreBtn) {
  readMoreBtn.addEventListener('click', function(e) {
    e.preventDefault(); // Prevent jump
    const heroInvolved = document.getElementById('hero-involved');
    if (!heroInvolved) return;
    heroInvolved.scrollIntoView({
      behavior: 'smooth'
    });
  });
}
  
function generateCalendar(year, month) {
    // Support both inline calendar ids (#calendar / #calendarHeader) and shared footer ids (#footerCalendar / #footerCalendarHeader)
    const calendarHeader = document.getElementById('calendarHeader') || document.getElementById('footerCalendarHeader');
    const calendarBody = document.querySelector('#calendar tbody') || document.querySelector('#footerCalendar tbody');
    if (!calendarHeader || !calendarBody) return;

    // Month and year header
    const monthNames = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ];
    calendarHeader.textContent = `${monthNames[month]} ${year}`;

    // First day of the month
    const firstDay = new Date(year, month, 1).getDay(); // 0 = Sunday
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Convert to Monday-first format (calendar starts from Monday)
    const startingDay = firstDay === 0 ? 6 : firstDay - 1;

    // Clear existing rows
    calendarBody.innerHTML = '';

    let date = 1;
    for (let i = 0; i < 6; i++) {
      const row = document.createElement('tr');

      for (let j = 0; j < 7; j++) {
        const cell = document.createElement('td');

        if (i === 0 && j < startingDay) {
          cell.textContent = '';
        } else if (date <= daysInMonth) {
          cell.textContent = date;

          // Highlight today's date
          const today = new Date();
          if (
            date === today.getDate() &&
            year === today.getFullYear() &&
            month === today.getMonth()
          ) {
            cell.style.backgroundColor = 'white';
            cell.style.color = 'black';
            cell.style.fontWeight = 'bold';
          }

          date++;
        } else {
          cell.textContent = '';
        }

        row.appendChild(cell);
      }

      calendarBody.appendChild(row);

      // Stop adding rows if all dates are added
      if (date > daysInMonth) break;
    }
  }

  // Auto-generate calendar for today when calendar container exists.
  function tryInitCalendar() {
    const hasHeader = document.getElementById('calendarHeader') || document.getElementById('footerCalendarHeader');
    const hasBody = document.querySelector('#calendar tbody') || document.querySelector('#footerCalendar tbody');
    if (!hasHeader || !hasBody) return;
    const today = new Date();
    generateCalendar(today.getFullYear(), today.getMonth());
  }

  // Run on DOMContentLoaded and when the shared footer is injected.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', tryInitCalendar);
  } else {
    tryInitCalendar();
  }
  document.addEventListener('sharedFooter:loaded', tryInitCalendar);

async function loadHomeLiveStats() {
  const solvedEl = document.getElementById('stat-cases-solved');
  const peopleEl = document.getElementById('stat-people-impacted');
  const moneyEl = document.getElementById('stat-money-donated');
  const volunteersEl = document.getElementById('stat-total-volunteers');
  if (!solvedEl || !peopleEl || !moneyEl || !volunteersEl) return;

  try {
    const res = await fetch('../Php/public_home_stats.php', { cache: 'no-store' });
    const payload = await res.json();
    if (!res.ok || !payload || payload.success !== true || !payload.data) return;

    const solvedCases = Number(payload.data.solvedCases || 0);
    const peopleImpacted = Number(payload.data.peopleImpacted || 0);
    const totalMoney = Number(payload.data.moneyDonated || 0);
    const totalVolunteers = Number(payload.data.totalVolunteers || 0);

    solvedEl.textContent = Math.max(0, Math.floor(solvedCases)).toLocaleString();
    peopleEl.textContent = Math.max(0, Math.floor(peopleImpacted)).toLocaleString();
    moneyEl.textContent = `$${Math.round(totalMoney).toLocaleString()}`;
    volunteersEl.textContent = Math.max(0, Math.floor(totalVolunteers)).toLocaleString();
  } catch (err) {
    // Keep fallback values if API fails.
  }
}

loadHomeLiveStats();

function setupDonationAnimations() {
  const sections = Array.from(document.querySelectorAll('.donation-progress-bg'));
  if (sections.length === 0) return;

  const animateProgress = (section) => {
    const progressRing = section.querySelector('#donation-progress-ring');
    const rawTarget = section.getAttribute('data-progress');
    if (!progressRing || rawTarget == null) return;
    if (section.dataset.progressAnimated === 'true') return;

    section.dataset.progressAnimated = 'true';
    const target = Math.max(0, Math.min(100, Number(rawTarget) || 70));
    const fullCircumference = 339.292;
    const duration = 1300;
    const start = performance.now();

    const tick = (now) => {
      const p = Math.min(1, (now - start) / duration);
      const eased = 1 - Math.pow(1 - p, 3);
      const value = Math.round(target * eased);
      progressRing.style.strokeDashoffset = String(fullCircumference - (fullCircumference * value) / 100);

      if (p < 1) requestAnimationFrame(tick);
    };

    requestAnimationFrame(tick);
  };

  const revealSection = (section) => {
    section.classList.add('reveal-ready');
    section.classList.add('is-visible');
    animateProgress(section);
  };

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const section = entry.target;
        section.classList.add('is-visible');
        animateProgress(section);
        obs.unobserve(section);
      });
    }, { threshold: 0.33 });

    sections.forEach((section) => {
      section.classList.add('reveal-ready');
      observer.observe(section);
    });
  } else {
    sections.forEach(revealSection);
  }
}

setupDonationAnimations();

  function setupRescueStoriesSlider() {
    const avatarEl = document.getElementById('rescueStoriesAvatar');
    const avatarWrapEl = document.getElementById('rescueStoriesAvatarWrap');
    const quoteEl = document.getElementById('rescueStoriesQuote');
    const nameEl = document.getElementById('rescueStoriesName');
    const roleEl = document.getElementById('rescueStoriesRole');
    const dotsContainer = document.querySelector('.rescue-stories-dots');
    let dots = Array.from(document.querySelectorAll('.rescue-stories-dot'));
  
    if (!avatarEl || !quoteEl || !nameEl || !roleEl || !dotsContainer) return;

  const stories = [
    {
      image: '../Images/pexels-omaralnahi-18495.jpg',
      alt: 'Fatima Rahman',
      quote: '"I thought I\'d never see my daughter again. But thanks to this app and the amazing volunteers, she was found in just two days. I\'ll never forget what this community did for us."',
      name: '— Fatima Rahman',
      role: 'Mother of a Rescued Child'
    },
    {
      image: '../Images/demo.jpg',
      alt: 'Abdul Karim',
      quote: '"The alert reached us within minutes. Volunteers and police coordinated so fast that my younger brother was safely brought home the same night."',
      name: '— Abdul Karim',
      role: 'Brother of a Rescued Teen'
    },
    {
      image: '../Images/help.jpg',
      alt: 'Nusrat Jahan',
      quote: '"I submitted CCTV footage from my shop and the platform matched a key clue. That one upload helped investigators close a major case."',
      name: '— Nusrat Jahan',
      role: 'Local Shop Owner'
    },
    {
      image: '../Images/together.jpg',
      alt: 'Rafiq Hasan',
      quote: '"As a volunteer, I received location hints and joined a coordinated search team. Seeing a family reunite in front of us was unforgettable."',
      name: '— Rafiq Hasan',
      role: 'Community Volunteer'
    },
    {
      image: '../Images/missing.jpeg',
      alt: 'Officer Samiul',
      quote: '"This system reduced our response time and improved field coordination. Community reports plus AI signals gave us a clear operational advantage."',
      name: '— Officer Samiul',
      role: 'Field Response Unit'
    }
  ];

    let idx = Math.floor(Math.random() * stories.length);

    // Fetch dynamic stories from database
    fetch('../Php/fetch_approved_rescue_stories.php')
      .then(res => res.json())
      .then(data => {
        if (data.success && data.stories && data.stories.length > 0) {
          data.stories.forEach(s => {
            stories.push({
              image: s.profile_photo ? `../Uploads/${s.profile_photo}` : '../Images/demo_pic/profile.jpg',
              alt: s.author_name,
              quote: `"${s.story_text}"`,
              name: `— ${s.author_name}`,
              role: s.author_role || 'User'
            });
            // Add a corresponding dot
            const newDot = document.createElement('span');
            newDot.className = 'rescue-stories-dot';
            dotsContainer.appendChild(newDot);
          });
          // Update dots array reference
          dots = Array.from(document.querySelectorAll('.rescue-stories-dot'));
        }
      })
      .catch(err => console.error('Failed to load dynamic rescue stories', err));
  
    const animateSwap = (element) => {
      element.classList.remove('rescue-story-fade');
      void element.offsetWidth;
      element.classList.add('rescue-story-fade');
    };
  
    const render = () => {
      if (!stories[idx]) idx = 0; // fallback if out of bounds
      const story = stories[idx];
      avatarEl.src = story.image;
      avatarEl.alt = story.alt;
      quoteEl.textContent = story.quote;
      nameEl.textContent = story.name;
      roleEl.textContent = story.role;
  
      dots.forEach((dot, i) => dot.classList.toggle('active', i === idx));
  
      animateSwap(avatarWrapEl || avatarEl);
      animateSwap(quoteEl);
      animateSwap(nameEl);
      animateSwap(roleEl);
    };
  
    render();
  
    setInterval(() => {
      idx = (idx + 1) % stories.length;
      render();
    }, 4200);
  }

setupRescueStoriesSlider();

function setupAchievementsCarousel() {
  const track = document.getElementById('achievementsTrack');
  const prevBtn = document.getElementById('achievementsPrevBtn');
  const nextBtn = document.getElementById('achievementsNextBtn');
  if (!track || !prevBtn || !nextBtn) return;

  const achievements = [
    {
      img: '../Images/demo.jpg',
      title: 'Case #A-102 Solved',
      desc: 'AI match + volunteer tip helped locate a missing student within 18 hours.',
      donated: 72,
      amount: '$4,200 funded'
    },
    {
      img: '../Images/help.jpg',
      title: 'Rapid Response Milestone',
      desc: 'Average emergency verification time reduced by 41% through coordinated alerts.',
      donated: 64,
      amount: '41% faster response'
    },
    {
      img: '../Images/together.jpg',
      title: 'Community Patrol Success',
      desc: 'Cross-zone volunteer coverage solved 9 high-priority incidents this month.',
      donated: 58,
      amount: '9 cases this month'
    },
    {
      img: '../Images/missing.jpeg',
      title: 'Case #B-227 Reunited',
      desc: 'Anonymous CCTV evidence helped investigators close the case in 2 days.',
      donated: 83,
      amount: 'Closed in 48 hours'
    },
    {
      img: '../Images/demo.jpg',
      title: 'Volunteer Achievement Badge',
      desc: 'Top field team received achievement badges after completing 120 verified actions.',
      donated: 76,
      amount: '120 verified actions'
    },
    {
      img: '../Images/help.jpg',
      title: 'Camera Network Impact',
      desc: 'New camera contributors increased active evidence coverage in critical zones.',
      donated: 69,
      amount: 'Coverage up by 33%'
    }
  ];

  const renderCard = (item) => `
    <div class="cause-card">
      <img class="cause-card-img" src="${item.img}" alt="${item.title}">
      <div class="cause-card-title">${item.title}</div>
      <div class="cause-card-desc">${item.desc}</div>
      <div class="cause-card-progress-label">
        Progress:
        <span class="cause-card-progress-text" style="float:right;">${item.donated}%</span>
      </div>
      <div class="cause-card-progress-bar-wrapper">
        <div class="cause-card-progress-bar" style="width:${item.donated}%;"></div>
      </div>
      <div class="cause-card-bottom-row">
        <span class="cause-card-amount">${item.amount}</span>
      </div>
    </div>
  `;

  let currentIndex = 0;
  let visibleCount = 1;
  let cardWidth = 0;
  let isAnimating = false;
  let autoTimer = null;
  const originalCount = achievements.length;

  const getCardWidth = () => {
    const card = track.querySelector('.cause-card');
    if (!card) return 0;
    const gap = parseFloat(getComputedStyle(track).gap || '0');
    return card.getBoundingClientRect().width + gap;
  };

  const getVisibleCount = () => {
    if (window.innerWidth <= 900) return 1;
    if (window.innerWidth <= 1200) return 2;
    return 4;
  };

  const snapTo = (index) => {
    cardWidth = getCardWidth();
    track.style.transition = 'none';
    track.style.transform = `translate3d(${-index * cardWidth}px, 0, 0)`;
    void track.offsetWidth;
    track.style.transition = 'transform .95s cubic-bezier(.25, .8, .25, 1)';
    currentIndex = index;
  };

  const buildLoopTrack = () => {
    visibleCount = getVisibleCount();
    const prefix = achievements.slice(-visibleCount);
    const suffix = achievements.slice(0, visibleCount);
    const loopItems = [...prefix, ...achievements, ...suffix];
    track.innerHTML = loopItems.map(renderCard).join('');
    snapTo(visibleCount);
  };

  const slideTo = (index) => {
    if (isAnimating) return;
    isAnimating = true;
    cardWidth = getCardWidth();
    currentIndex = index;
    track.style.transform = `translate3d(${-currentIndex * cardWidth}px, 0, 0)`;
  };

  const restartAuto = () => {
    if (autoTimer) clearInterval(autoTimer);
    autoTimer = setInterval(() => slideTo(currentIndex + 1), 5600);
  };

  track.addEventListener('transitionend', () => {
    isAnimating = false;

    if (currentIndex >= originalCount + visibleCount) {
      snapTo(visibleCount);
      return;
    }

    if (currentIndex < visibleCount) {
      snapTo(originalCount + visibleCount - 1);
    }
  });

  prevBtn.addEventListener('click', () => {
    slideTo(currentIndex - 1);
    restartAuto();
  });

  nextBtn.addEventListener('click', () => {
    slideTo(currentIndex + 1);
    restartAuto();
  });

  track.addEventListener('mouseenter', () => {
    if (autoTimer) clearInterval(autoTimer);
  });

  track.addEventListener('mouseleave', () => {
    restartAuto();
  });

  window.addEventListener('resize', buildLoopTrack);

  buildLoopTrack();
  restartAuto();
}

setupAchievementsCarousel();

function setupHomeChatbot() {
  const CHATBOT_LOG_KEY = 'searchar_chatbot_logs_v1';
  const CHAT_SESSION_KEY = 'searchar_chat_session_token';
  const widget = document.getElementById('chatbotWidget');
  const panel = document.getElementById('chatbotPanel');
  const toggle = document.getElementById('chatbotToggle');
  const closeBtn = document.getElementById('chatbotClose');
  const form = document.getElementById('chatbotForm');
  const input = document.getElementById('chatbotInput');
  const messages = document.getElementById('chatbotMessages');
  const quickBox = document.getElementById('chatbotQuick');
  if (!panel || !toggle || !closeBtn || !form || !input || !messages) return;

  let isTogglingDebounce = false;

  const sessionToken = (() => {
    try {
      let token = localStorage.getItem(CHAT_SESSION_KEY);
      if (!token) {
        token = `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
        localStorage.setItem(CHAT_SESSION_KEY, token);
      }
      return token;
    } catch (_e) {
      return `${Date.now()}-guest`;
    }
  })();

  let lastAdminReplyId = 0;

  const addMsg = (text, role) => {
    const item = document.createElement('div');
    item.className = `chatbot-msg ${role}`;
    item.textContent = text;
    messages.appendChild(item);
    messages.scrollTop = messages.scrollHeight;
  };

  const saveLog = (question, reply) => {
    try {
      const prev = JSON.parse(localStorage.getItem(CHATBOT_LOG_KEY) || '[]');
      const list = Array.isArray(prev) ? prev : [];
      list.push({
        time: new Date().toISOString(),
        question: String(question || '').trim(),
        reply: String(reply || '').trim()
      });
      const trimmed = list.slice(-300);
      localStorage.setItem(CHATBOT_LOG_KEY, JSON.stringify(trimmed));
    } catch (_e) {
      // Ignore storage errors silently.
    }

    // Also persist to server so Admin can see logs reliably across tabs/devices.
    fetch('../Php/chatbot_log_write.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        question: String(question || '').trim(),
        reply: String(reply || '').trim(),
        time: new Date().toISOString(),
        source_page: 'index',
        session_token: sessionToken
      })
    }).catch(() => {
      // Keep chat responsive even if logging endpoint is unavailable.
    });
  };

  let adminHasReplied = localStorage.getItem('searchar_admin_replied_' + sessionToken) === 'true';

  const getReply = (q) => {
    const text = q.toLowerCase();
    if (text.includes('donat')) return 'To donate, click MAKE DONATION or Contribute Now on this page.';
    if (text.includes('volunteer') || text.includes('join')) return 'To join as volunteer, click GET INVOLVED NOW and complete registration.';
    if (text.includes('report') || text.includes('clue') || text.includes('crime')) return 'Please use the relevant logged-in dashboard to submit verified clues safely.';
    if (text.includes('login') || text.includes('log in')) return 'Use the LOG IN button on top-right to access your account.';
    if (text.includes('news')) return 'Check the LATEST NEWS section below. You can click Read More for full details.';
    
    if (adminHasReplied) {
      return 'Please wait for the admin\'s reply.';
    }
    return 'I can help with donation, volunteer joining, login, and news navigation. Ask me anything about these. Please wait for the admin\'s reply.';
  };

  const openPanel = () => {
    panel.classList.add('open');
    panel.setAttribute('aria-hidden', 'false');
    if (widget) widget.classList.add('is-open');
    input.focus();
  };

  const closePanel = () => {
    panel.classList.remove('open');
    panel.setAttribute('aria-hidden', 'true');
    if (widget) widget.classList.remove('is-open');
  };

  const askAndReply = (questionText) => {
    const userText = String(questionText || '').trim();
    if (!userText) return;

    addMsg(userText, 'user');
    const replyText = getReply(userText);
    window.setTimeout(() => {
      addMsg(replyText, 'bot');
      saveLog(userText, replyText);
    }, 280);
  };

  const pollAdminReplies = async () => {
    try {
      const res = await fetch(`../Php/chatbot_admin_reply_read.php?session_token=${encodeURIComponent(sessionToken)}&last_id=${lastAdminReplyId}`, {
        credentials: 'same-origin',
        cache: 'no-store'
      });
      const json = await res.json();
      if (!res.ok || !json || json.success !== true || !Array.isArray(json.data)) return;
      if (json.data.length === 0) return;

      json.data.forEach((row) => {
        const id = Number(row?.id || 0);
        if (id > lastAdminReplyId) lastAdminReplyId = id;
        const text = String(row?.reply_text || '').trim();
        if (text) {
          addMsg(`Admin: ${text}`, 'bot');
          if (!adminHasReplied) {
            adminHasReplied = true;
            localStorage.setItem('searchar_admin_replied_' + sessionToken, 'true');
          }
        }
      });
    } catch (_e) {
      // Silent fail to keep chat UX smooth.
    }
  };

  toggle.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isTogglingDebounce) return;
    isTogglingDebounce = true;
    setTimeout(() => { isTogglingDebounce = false; }, 300);
    
    if (panel.classList.contains('open')) {
      closePanel();
    } else {
      openPanel();
    }
  });

  closeBtn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isTogglingDebounce) return;
    isTogglingDebounce = true;
    setTimeout(() => { isTogglingDebounce = false; }, 300);
    closePanel();
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const userText = input.value.trim();
    if (!userText) return;
    input.value = '';
    askAndReply(userText);
  });

  if (quickBox) {
    quickBox.addEventListener('click', (e) => {
      const btn = e.target.closest('.chatbot-chip');
      if (!btn) return;
      const q = String(btn.getAttribute('data-q') || '').trim();
      if (!q) return;
      if (!panel.classList.contains('open')) openPanel();
      askAndReply(q);
    });
  }

  // Handle clicks outside the panel to close it
  document.addEventListener('click', (ev) => {
    if (!panel.classList.contains('open')) return;
    const isInsidePanel = ev.target.closest && ev.target.closest('.chatbot-panel');
    const isFabClick = ev.target.closest && ev.target.closest('#chatbotToggle, .chatbot-fab');
    if (!isInsidePanel && !isFabClick) {
      if (isTogglingDebounce) return;
      isTogglingDebounce = true;
      setTimeout(() => { isTogglingDebounce = false; }, 300);
      closePanel();
    }
  });

  pollAdminReplies();
  setInterval(pollAdminReplies, 1800);
}

setupHomeChatbot();