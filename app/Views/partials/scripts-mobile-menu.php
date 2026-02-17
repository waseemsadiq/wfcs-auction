// Mobile menu
const mobileMenu = document.getElementById('mobile-menu');
const mobileOverlay = document.getElementById('mobile-overlay');
let menuOpen = false;
function toggleMenu() {
  menuOpen = !menuOpen;
  mobileMenu.classList.toggle('translate-x-full', !menuOpen);
  mobileOverlay.classList.toggle('hidden', !menuOpen);
}
