// เพิ่มการสลับระหว่าง Light Mode และ Dark Mode

$(document).ready(function() {
  // ตรวจสอบว่าผู้ใช้เคยเลือก Dark Mode ไว้หรือไม่
  const currentTheme = localStorage.getItem('theme');
  if (currentTheme === 'dark') {
    document.body.classList.add('dark-mode');
    $('#theme-switch').prop('checked', true);
  }

  // สร้างปุ่มสลับโหมด
  const themeSwitcher = `
    <div class="theme-switch-wrapper">
      <label class="theme-switch" for="theme-switch">
        <input type="checkbox" id="theme-switch" ${currentTheme === 'dark' ? 'checked' : ''}>
        <div class="slider round"></div>
      </label>
      <span class="theme-label">Dark Mode</span>
    </div>
  `;

  // เพิ่มปุ่มสลับโหมดไว้ที่ navbar
  $('.navbar-nav.ml-auto').prepend(`<li class="nav-item">${themeSwitcher}</li>`);

  // เมื่อกดปุ่มสลับโหมด
  $('#theme-switch').on('change', function() {
    if ($(this).is(':checked')) {
      document.body.classList.add('dark-mode');
      localStorage.setItem('theme', 'dark');
    } else {
      document.body.classList.remove('dark-mode');
      localStorage.setItem('theme', 'light');
    }
  });
}); 