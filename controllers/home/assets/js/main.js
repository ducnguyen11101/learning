document.addEventListener('DOMContentLoaded', function() {
    // Xử lý responsive menu
    const menuToggle = document.querySelector('.menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            document.querySelector('nav').classList.toggle('active');
        });
    }
    
    // Xử lý form
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input[required]');
            let valid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('error');
                    valid = false;
                }
            });
            
            if (!valid) e.preventDefault();
        });
    });


    const form = document.querySelector('.auth-container form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let valid = true;
            
            // Validate password match
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password.value !== confirmPassword.value) {
                alert('Mật khẩu không khớp!');
                valid = false;
            }
            
            if (password.value.length < 8) {
                alert('Mật khẩu phải có ít nhất 8 ký tự!');
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
            }
        });
    }
});

