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