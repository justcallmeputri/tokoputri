document.addEventListener("DOMContentLoaded", function() {
    // 1. Live Greeting & Real-time Clock
    function updateClockAndGreeting() {
        const now = new Date();
        const hours = now.getHours();
        let greeting = "Selamat Datang";

        if (hours >= 4 && hours < 11) {
            greeting = "🌅 Selamat Pagi, Administrator";
        } else if (hours >= 11 && hours < 15) {
            greeting = "☀️ Selamat Siang, Administrator";
        } else if (hours >= 15 && hours < 18) {
            greeting = "🌇 Selamat Sore, Administrator";
        } else {
            greeting = "🌙 Selamat Malam, Administrator";
        }

        const greetingEl = document.getElementById('live-greeting');
        const clockEl = document.getElementById('live-clock');

        if (greetingEl) greetingEl.textContent = greeting;
        if (clockEl) {
            const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            clockEl.textContent = "🕒 " + timeString;
        }
    }

    setInterval(updateClockAndGreeting, 1000);
    updateClockAndGreeting();

    // 2. Fade-in Animation untuk elemen hero
    const elements = document.querySelectorAll('.animate-up');
    elements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(15px)';
        el.style.transition = 'opacity 0.5s ease ' + (index * 0.1) + 's, transform 0.5s ease ' + (index * 0.1) + 's';
        
        setTimeout(() => {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, 80);
    });
});