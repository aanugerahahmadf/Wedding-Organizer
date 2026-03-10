<script>
document.addEventListener('DOMContentLoaded', () => {
    let startX = 0;
    let currentX = 0;
    let activeNotif = null;

    const getX = (e) => e.type.includes('mouse') ? e.clientX : e.touches[0].clientX;

    const handleStart = (e) => {
        // Prevent interfering with buttons inside notification
        if (e.target.closest('button') || e.target.closest('a')) return;

        const notif = e.target.closest('.fi-no-notification');
        if (!notif) return;

        activeNotif = notif;
        startX = getX(e);
        currentX = startX;
        activeNotif.style.transition = 'none';
    };

    const handleMove = (e) => {
        if (!activeNotif) return;
        
        currentX = getX(e);
        const diffX = currentX - startX;
        
        // Swipe right to dismiss
        if (diffX > 0) {
            activeNotif.style.transform = `translateX(${diffX}px)`;
            activeNotif.style.opacity = Math.max(0, 1 - (diffX / window.innerWidth));
        }
    };

    const handleEnd = (e) => {
        if (!activeNotif) return;

        const diffX = currentX - startX;
        activeNotif.style.transition = 'transform 0.3s ease-out, opacity 0.3s ease-out';

        const threshold = 100; // Swipe distance threshold to dismiss

        if (diffX > threshold) {
            // Animate it disappearing
            activeNotif.style.transform = `translateX(150%)`;
            activeNotif.style.opacity = '0';
            
            // Give time for animation, then close it using Alpine.js or button click
            setTimeout(() => {
                const xWrapper = activeNotif.closest('[x-data]');
                if (xWrapper && xWrapper.__x && xWrapper.__x.$data && typeof xWrapper.__x.$data.close === 'function') {
                    xWrapper.__x.$data.close();
                } else {
                    // Fallback to clicking the close button
                    const closeBtn = activeNotif.querySelector('button[wire\\\\:click="close"], button');
                    if (closeBtn) closeBtn.click();
                }
            }, 150);
        } else {
            // Reset position if not swiped far enough
            activeNotif.style.transform = 'translateX(0)';
            activeNotif.style.opacity = '1';
        }

        activeNotif = null;
    };

    // Touch events for mobile
    document.addEventListener('touchstart', handleStart, {passive: true});
    document.addEventListener('touchmove', handleMove, {passive: true});
    document.addEventListener('touchend', handleEnd, {passive: true});
    document.addEventListener('touchcancel', handleEnd, {passive: true});
    
    // Mouse events for desktop testing
    document.addEventListener('mousedown', handleStart);
    document.addEventListener('mousemove', handleMove);
    document.addEventListener('mouseup', handleEnd);
    
    // Add visual cue
    const style = document.createElement('style');
    style.innerHTML = '.fi-no-notification { cursor: grab; } .fi-no-notification:active { cursor: grabbing; }';
    document.head.appendChild(style);
});
</script>
