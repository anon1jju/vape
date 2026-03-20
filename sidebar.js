document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const closeMenu = document.getElementById('close-menu');
    const overlay = document.getElementById('overlay');

    const openSidebar = () => {
      sidebar.classList.remove('-translate-x-full');
      overlay.classList.remove('hidden');
    };

    const closeSidebar = () => {
      sidebar.classList.add('-translate-x-full');
      overlay.classList.add('hidden');
    };

    menuToggle.addEventListener('click', openSidebar);
    closeMenu.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);
  });
