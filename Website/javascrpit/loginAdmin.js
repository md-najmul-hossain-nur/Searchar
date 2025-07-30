let container = document.getElementById('container')

toggle = () => {
	container.classList.toggle('sign-in')
	container.classList.toggle('sign-up')
}

setTimeout(() => {
	container.classList.add('sign-in')
}, 200)
  document.getElementById('logo').onclick = function() {
    window.location.href = '../Html/index.html';
  };
  function showForm() {
    const role = document.getElementById('role').value;
    const formContainer = document.getElementById('dynamicForm');
    let formHTML = '';

    if (role === 'user') {
        formHTML = `
            <h3>User Sign Up</h3>
            <input type="text" placeholder="Full Name" required>
            <input type="email" placeholder="Email" required>
            <input type="text" placeholder="Phone" required>
            <input type="password" placeholder="Password" required>
            <button>Register as User</button>
        `;
    } 
    else if (role === 'police') {
        formHTML = `
            <h3>Police Sign Up</h3>
            <input type="text" placeholder="Officer Name" required>
            <input type="text" placeholder="Badge ID" required>
            <input type="email" placeholder="Official Email" required>
            <input type="password" placeholder="Password" required>
            <button>Register as Police</button>
        `;
    }
    else if (role === 'volunteer') {
        formHTML = `
            <h3>Volunteer Sign Up</h3>
            <input type="text" placeholder="Full Name" required>
            <input type="email" placeholder="Email" required>
            <input type="text" placeholder="Phone" required>
            <input type="text" placeholder="Area of Service" required>
            <input type="password" placeholder="Password" required>
            <button>Register as Volunteer</button>
        `;
    }
    else if (role === 'contributor') {
        formHTML = `
            <h3>Camera Contributor Sign Up</h3>
            <input type="text" placeholder="Full Name" required>
            <input type="email" placeholder="Email" required>
            <input type="text" placeholder="Phone" required>
            <input type="text" placeholder="Camera Location" required>
            <input type="password" placeholder="Password" required>
            <button>Register as Contributor</button>
        `;
    }

    formContainer.innerHTML = formHTML;
}