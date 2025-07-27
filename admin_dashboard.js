function hexToRgb(hex) { /* For focus styles */
    let r = 0, g = 0, b = 0;
    if (hex.length == 4) {
        r = "0x" + hex[1] + hex[1]; g = "0x" + hex[2] + hex[2]; b = "0x" + hex[3] + hex[3];
    } else if (hex.length == 7) {
        r = "0x" + hex[1] + hex[2]; g = "0x" + hex[3] + hex[4]; b = "0x" + hex[5] + hex[6];
    }
    return "" + +r + "," + +g + "," + +b;
}

const primaryColorCSSVar = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim();
if (primaryColorCSSVar) {
    document.documentElement.style.setProperty('--primary-color-rgb', hexToRgb(primaryColorCSSVar));
}

/* --- START: UPDATED JAVASCRIPT FOR SIDEBAR TOGGLE AND SUBMENUS --- */
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const adminSidebar = document.getElementById('adminSidebar');

    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            adminSidebar.classList.toggle('open');
        });

        document.addEventListener('click', function(event) {
            if (adminSidebar.classList.contains('open') && !adminSidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                adminSidebar.classList.remove('open');
            }
        });
    }

    // Submenu functionality
    const submenuToggles = document.querySelectorAll('.admin-nav .has-submenu > a');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parentLi = this.parentElement;
            parentLi.classList.toggle('open');

            // Optional: Close other submenus
            document.querySelectorAll('.admin-nav .has-submenu.open').forEach(openSubmenu => {
                if(openSubmenu !== parentLi) {
                    openSubmenu.classList.remove('open');
                }
            });
        });
    });
});
/* --- END: UPDATED JAVASCRIPT --- */
